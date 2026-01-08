# 実装完了：oauth_tokens競合ガードとsend_logs重複防止の最適化

## ✅ 実装した変更

### 1. oauth_tokens の最適化

#### 変更内容
- `REPLACE INTO` → `INSERT ... ON DUPLICATE KEY UPDATE` に変更
- `refresh_token_enc` は `COALESCE(VALUES(refresh_token_enc), refresh_token_enc)` で既存を保持
- `created_at` は初回のみ、`updated_at` は必ず `NOW()` で更新

#### 変更ファイル
- `app/services/storage.php` の `saveToken()` メソッド

#### SQL（確定版）
```php
INSERT INTO oauth_tokens (user_id, provider, access_token_enc, refresh_token_enc, expires_at, scopes, created_at, updated_at)
VALUES (:uid, 'google', :at, :rt, :exp, :sc, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  access_token_enc   = VALUES(access_token_enc),
  refresh_token_enc  = COALESCE(VALUES(refresh_token_enc), refresh_token_enc),
  expires_at         = VALUES(expires_at),
  scopes             = VALUES(scopes),
  updated_at         = NOW()
```

---

### 2. send_logs の重複防止（方式A：send_attempt_id + upsert）

#### 変更内容
- `send_attempt_id` カラムを追加（CHAR(36), UNIQUE）
- `updated_at` カラムを追加
- `upsertLogByAttempt()` メソッドを新規追加
- `/send/execute` ルートで `send_attempt_id` を生成して使用

#### 変更ファイル
- `database/schema.sql`: send_logs テーブル定義を更新
- `database/migration_add_send_attempt_id.sql`: マイグレーションSQL（新規作成）
- `app/services/storage.php`: `upsertLogByAttempt()` メソッドを追加
- `public/index.php`: `/send/execute` ルートを修正

#### DDL変更（マイグレーション）
```sql
ALTER TABLE send_logs
  ADD COLUMN send_attempt_id CHAR(36) NULL,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE send_logs
  ADD UNIQUE KEY uq_send_attempt (send_attempt_id);
```

#### SQL（確定版）
```php
INSERT INTO send_logs
  (send_attempt_id, user_id, template_id, group_id, subject_snapshot, body_snapshot,
   attachments_snapshot_json, recipient_counts_json, status, error_json, gmail_message_id, created_at, updated_at)
VALUES
  (:aid, :uid, :tid, :gid, :sub, :body, :att, :cnt, :st, :err, :mid, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  status = VALUES(status),
  error_json = VALUES(error_json),
  gmail_message_id = COALESCE(VALUES(gmail_message_id), gmail_message_id),
  recipient_counts_json = VALUES(recipient_counts_json),
  updated_at = NOW()
```

---

### 3. 競合ガードの改善（expires_at で二段判定）

#### 変更内容
- `updated_at` ガードに加えて、`expires_at` で二段判定を追加
- 再取得したtokenの `expires_at` が十分未来なら採用

#### 変更ファイル
- `app/services/token_manager.php` の `get_google_access_token_or_refresh()` 関数

#### 実装
```php
// refresh競合対策：updated_at が30秒以内なら、もう一度getTokenして返す
// さらに expires_at で二段判定（再取得したtokenのexpires_atが十分未来なら採用）
$updatedAtStr = $tokRow['updated_at'] ?? '';
if ($updatedAtStr !== '') {
  $updatedAt = strtotime($updatedAtStr);
  if ($updatedAt > 0 && ($now - $updatedAt) < 30) {
    // 30秒以内に更新されているので、再取得して返す
    $tokRow2 = $storage->getToken($userId);
    if ($tokRow2 && !empty($tokRow2['access_token_enc'])) {
      // expires_at で二段判定：再取得したtokenのexpires_atが十分未来なら採用
      $expiresAtStr2 = $tokRow2['expires_at'] ?? '';
      $expiresAt2 = $expiresAtStr2 !== '' ? strtotime($expiresAtStr2) : 0;
      if ($expiresAt2 > 0 && ($expiresAt2 - $now) > $skewSeconds) {
        // 十分未来のexpires_atなので、このtokenを採用
        $access2 = decrypt_str($tokRow2['access_token_enc'], $config['APP_KEY']);
        if ($access2 !== '') {
          return $access2;
        }
      }
    }
  }
}
```

---

## 📋 実装手順

### 1. データベースマイグレーション

phpMyAdminで以下のSQLを実行：

```sql
-- database/migration_add_send_attempt_id.sql の内容を実行
ALTER TABLE send_logs
  ADD COLUMN send_attempt_id CHAR(36) NULL,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE send_logs
  ADD UNIQUE KEY uq_send_attempt (send_attempt_id);
```

### 2. 動作確認

1. **oauth_tokens の動作確認**
   - OAuthログイン → トークンが正しく保存される
   - refresh動作確認（DBでexpires_atを過去に設定）
   - 同時リクエスト時の競合が正しく処理される

2. **send_logs の動作確認**
   - 送信実行 → `send_attempt_id` が生成される
   - リトライ時も同じ `send_attempt_id` で更新される
   - 重複レコードが作成されない

---

## 🎯 改善効果

### oauth_tokens
- ✅ `REPLACE INTO` の副作用（created_at上書き、FK/トリガ問題）を解消
- ✅ `refresh_token_enc` が正しく保持される
- ✅ 競合ガードがより確実に動作

### send_logs
- ✅ 同一送信試行が1行にまとまる（重複防止）
- ✅ リトライ時のログ更新が可能
- ✅ 運用で追跡しやすい（send_attempt_idで識別）

### 競合ガード
- ✅ `expires_at` で二段判定により、誤判定を防止
- ✅ より確実な競合対策

---

## 📝 注意事項

1. **マイグレーション実行が必要**
   - `database/migration_add_send_attempt_id.sql` を実行してください
   - 既存レコードの `send_attempt_id` は NULL のまま（過去ログはそのまま）

2. **send_attempt_id の生成**
   - UUID v4形式（36文字）で生成
   - 同一送信試行では同じIDを使用

3. **後方互換性**
   - `createLog()` メソッドは残しています（既存コードとの互換性のため）
   - 新規実装では `upsertLogByAttempt()` を使用してください

---

以上、実装完了です。
