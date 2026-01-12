# 403エラーログ分析レポート

## 調査日時
作成日: 2025-01-XX

## 調査目的
ログファイルで403エラーの原因を調査し、トークンスコープ検査の結果を確認する。

## コード分析結果

### 1. トークンスコープ検査の実装（public/index.php 1068-1124行目）

#### 検査フロー
1. アクセストークンを取得（期限切れが近ければ自動refresh）
2. `google_tokeninfo()` 関数でトークン情報を取得
3. HTTP 200 かつ `scope` が存在する場合：
   - 実際のスコープを取得
   - `https://www.googleapis.com/auth/gmail.send` が含まれているか確認
   - 含まれていない場合：`Gmail 403 Prevention` ログを出力し、再認証へリダイレクト
   - 含まれている場合：`Token scope check OK` ログを出力
4. tokeninfo API呼び出しが失敗した場合：
   - `Tokeninfo check failed` ログを出力
   - HTTP 401の場合は再認証へリダイレクト
   - その他の場合は警告のみで続行

#### 出力されるログメッセージ

**スコープ不足の場合（1086-1091行目）:**
```
Gmail 403 Prevention: Token missing gmail.send scope. token_preview=%s | actual_scopes=%s | user_id=%d
```

**スコープ確認成功の場合（1097-1101行目）:**
```
Token scope check OK: gmail.send scope found. token_preview=%s | user_id=%d
```

**tokeninfo API呼び出し失敗の場合（1108-1115行目）:**
```
Tokeninfo check failed: HTTP %d | token_preview=%s | user_id=%d | error_message=%s | body_preview=%s
```

**tokeninfo APIが401を返した場合（1120行目）:**
```
Tokeninfo returned 401, redirecting to reauth. token_preview=%s
```

### 2. Gmail API呼び出し時の403エラーハンドリング（public/index.php 1165-1204行目）

#### エラーハンドリングフロー
1. Gmail API呼び出し後、HTTPステータスコードを確認
2. HTTP 403の場合：
   - エラーレスポンスを解析
   - `insufficientPermissions` が検出された場合、特別なログを出力
   - トークンスコープ情報を含む詳細ログを出力
   - エラーログをデータベースに保存
   - ユーザーにエラーメッセージを表示

#### 出力されるログメッセージ

**insufficientPermissions検出時（1180-1185行目）:**
```
Gmail 403: insufficientPermissions detected. token_preview=%s | user_id=%d | message=%s | This indicates the token does not have the required gmail.send scope.
```

**403エラーの詳細情報（1196-1204行目）:**
```
Gmail 403 Error Details: reason=%s | message=%s | token_preview=%s | user_id=%d | token_scope=%s | body_preview=%s
```

### 3. Gmail API呼び出し時のエラーログ（app/services/gmail_send.php 98-105行目）

#### エラーログ出力
- HTTPステータスコードが400以上の場合、エラーログを出力
- エラー理由（reason）とメッセージを抽出
- トークンプレビューとレスポンスボディのプレビューを含む

#### 出力されるログメッセージ

**4xx/5xxエラー全般（98-105行目）:**
```
Gmail API Error: HTTP %d | reason=%s | message=%s | token_preview=%s | body_preview=%s
```

## ログファイルの場所

### サーバー側
- メインログ: `/home/besttrust/kimito-link.com/_git/mailloop/storage/app_error.log`
- デバッグログ: `/home/besttrust/kimito-link.com/_git/mailloop/storage/app_debug.log`
- フォールバックログ: `/tmp/mailloop_debug.log` または `$HOME/mailloop_debug.log`

### ログ設定（app/bootstrap.php 16-22行目）
```php
$logDir = __DIR__ . '/../storage';
if (!is_dir($logDir)) {
  @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/app_error.log');
```

## 確認すべきログパターン

### パターン1: トークンスコープ検査で検出された場合
```
Gmail 403 Prevention: Token missing gmail.send scope. token_preview=... | actual_scopes=... | user_id=...
```
**意味**: トークンスコープ検査が機能し、スコープ不足を検出。再認証へリダイレクトされる。

### パターン2: tokeninfo API呼び出しが失敗した場合
```
Tokeninfo check failed: HTTP 401 | token_preview=... | user_id=... | error_message=... | body_preview=...
```
**意味**: tokeninfo APIの呼び出しが失敗。401の場合は認証エラー、再認証が必要。

### パターン3: 実際のGmail API呼び出し時に403が発生した場合
```
Gmail API Error: HTTP 403 | reason=insufficientPermissions | message=... | token_preview=... | body_preview=...
```
または
```
Gmail 403: insufficientPermissions detected. token_preview=... | user_id=... | message=...
```
**意味**: 実際のGmail API呼び出し時に403が発生。スコープが不足している可能性が高い。

### パターン4: トークンスコープ検査は成功したが、実際のAPI呼び出し時に403が発生した場合
```
Token scope check OK: gmail.send scope found. token_preview=... | user_id=...
...
Gmail API Error: HTTP 403 | reason=... | message=... | token_preview=... | body_preview=...
```
**意味**: トークンスコープ検査では成功しているが、実際のAPI呼び出し時に403が発生。トークンの有効期限切れや、スコープの不一致の可能性。

## 調査手順

### ステップ1: サーバーにSSH接続
```bash
ssh xserver-besttrust
```

### ステップ2: ログファイルの存在確認
```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
ls -lh storage/app_error.log
```

### ステップ3: 403エラー関連のログを確認
```bash
# 1. Gmail 403 Prevention ログ
grep -i "Gmail 403 Prevention" storage/app_error.log | tail -20

# 2. Tokeninfo check failed ログ
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20

# 3. Gmail API Error: HTTP 403 ログ
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20

# 4. Gmail 403: insufficientPermissions ログ
grep -i "Gmail 403: insufficientPermissions" storage/app_error.log | tail -20

# 5. Gmail 403 Error Details ログ
grep -i "Gmail 403 Error Details" storage/app_error.log | tail -20

# 6. 最近のログ（全般）
tail -50 storage/app_error.log
```

### ステップ4: ログの分析
1. どのパターンのログが出力されているか確認
2. トークンスコープ検査が機能しているか確認
3. 実際のスコープ値を確認（`actual_scopes` または `token_scope` フィールド）
4. エラーメッセージと理由を確認

## 想定される原因と対処法

### 原因1: トークンスコープが不足している
**症状**: `Gmail 403 Prevention` または `insufficientPermissions` ログが出力される
**対処法**: 
- 再認証が必要（`/auth/login?reauth=1` へのリダイレクトが実行される）
- `config/config.php` の `GOOGLE_SCOPES` と `GMAIL_SCOPE` が正しく設定されているか確認
- OAuth認証時に `prompt=consent` が設定されているか確認（`app/services/google_oauth.php` 115行目）

### 原因2: tokeninfo APIの呼び出しが失敗している
**症状**: `Tokeninfo check failed` ログが出力される
**対処法**:
- HTTP 401の場合は認証エラー、再認証が必要
- その他のHTTPステータスコードの場合は、API呼び出しの問題を調査

### 原因3: トークンスコープ検査は成功したが、実際のAPI呼び出し時に403が発生
**症状**: `Token scope check OK` ログの後に `Gmail API Error: HTTP 403` ログが出力される
**対処法**:
- トークンの有効期限切れの可能性（自動refreshが機能していない可能性）
- スコープの不一致の可能性（tokeninfo APIと実際のAPIでスコープが異なる可能性）

## 次のステップ

1. サーバー側のログファイルを確認
2. 上記のパターンに基づいて問題を特定
3. 特定された問題に応じて対処法を実施
4. 対処後、再度メール送信を試行し、ログを確認
