# OAuth実装完了（最終版）

## ✅ 実装完了項目

### 1. usersテーブルの追加
- `database/schema.sql` に users テーブル定義を追加
- `provider` + `provider_sub` でユーザーを一意に識別
- `email` は補助情報として保存（NULL可、UNIQUE制約あり）
- `created_at` / `updated_at` は `NOW()` で統一（DDL依存を減らす）

### 2. google_auth_url() 関数の修正
- `state` パラメータを受け取るように修正
- `GOOGLE_SCOPES` 設定に対応

### 3. MysqlStorage->upsertUser() の実装
- usersテーブルへの保存機能を実装
- email UNIQUE制約違反時のエラーハンドリング（ログインは通す）
- セッションにも保存（既存動作を維持）

### 4. /auth/login ルートの改善
- state生成を追加
- `google_auth_url()` にstateを渡すように修正

### 5. /auth/callback ルートの改善
- state検証を追加
- エラーハンドリングを統一（`render_error()` を使用）
- usersテーブルへの保存
- セッション固定化対策（`session_regenerate_id(true)`）
- CSRFトークン再生成
- `sub` が無い場合は `id` をフォールバック

### 6. require_login() の復活
- `/templates` と `/groups` の全ルートからダミーユーザーのバイパスを削除
- `require_login($storage)` を使用するように統一

---

## 📋 次のステップ

### 1. phpMyAdminでusersテーブルを作成

`database/schema.sql` の users テーブル定義を実行：

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(20) NOT NULL,
  provider_sub VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  name VARCHAR(255) NULL,
  picture TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_sub (provider, provider_sub),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**注意**: DDLには `DEFAULT CURRENT_TIMESTAMP` が付いていますが、コード側は `NOW()` で統一しています。

### 2. Google Cloud Console の設定確認

- OAuthクライアントの設定
- 承認済みリダイレクトURI: `https://resend.kimito-link.com/auth/callback`
- スコープ: `openid email profile https://www.googleapis.com/auth/gmail.send`
- 同意画面の設定（テストユーザーの追加）

### 3. config/secrets.php の設定

```php
<?php
return [
  'GOOGLE_CLIENT_ID' => '実際のCLIENT_ID',
  'GOOGLE_CLIENT_SECRET' => '実際のCLIENT_SECRET',
  'APP_KEY' => '長いランダム文字列（暗号化キー）',
];
```

**APP_KEYの生成方法**:
```php
bin2hex(random_bytes(32)); // 64文字のランダム文字列
```

### 4. 動作確認

1. `/auth/login` にアクセス
2. Google認証画面で同意
3. `/auth/callback` で認証完了
4. `/templates` にリダイレクトされ、一覧が表示される
5. phpMyAdminで確認：
   - `users` テーブルに1行追加されている
   - `oauth_tokens` テーブルに1行追加されている
6. ブラウザのCookieで `mailloop_session` が変更されていることを確認

---

## 🔍 実装のポイント

### state検証
- CSRF対策として必須
- セッションに保存し、callbackで検証
- 検証後は即座に削除（使い捨て）

### email UNIQUE制約違反時の処理
- ログインは通す（sub基準でユーザーを確定）
- email更新は諦めて、name/pictureのみ更新
- エラーはログに記録（SQLSTATE 23000）

### セッション管理
- ログイン成功時に `session_regenerate_id(true)` でセッション固定化対策
- CSRFトークンを再生成
- `$_SESSION['user_id']` と `$_SESSION['user']` の両方を設定

### sub のフォールバック
- `sub` が無い場合は `id` を試す（念のため）
- ログに記録（通常は発生しない）

---

## 📝 実装済みコード

### google_auth_url()（修正後）

```php
function google_auth_url(array $config, string $state): string {
  $scopes = trim(($config['GOOGLE_SCOPES'] ?? '') . ' ' . ($config['GMAIL_SCOPE'] ?? ''));
  $params = [
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'redirect_uri' => $config['GOOGLE_REDIRECT_URI'],
    'response_type' => 'code',
    'scope' => $scopes,
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => $state,
  ];
  return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
```

### config.php（GOOGLE_SCOPES追加）

```php
'GOOGLE_SCOPES' => 'openid email profile',
'GMAIL_SCOPE' => 'https://www.googleapis.com/auth/gmail.send',
```

### /auth/callback（subフォールバック追加）

```php
// 5) userinfo
[$uResp, $uData] = google_userinfo($access);
$sub = $uData['sub'] ?? ($uData['id'] ?? '');
if (($uResp['code'] ?? 0) !== 200 || $sub === '') {
  error_log('Userinfo failed: ' . ($uResp['body'] ?? '') );
  render_error('認証に失敗しました（ユーザー情報取得失敗）');
  return;
}
```

---

**実装日**: 2026年1月2日
**状態**: OAuth実装完了 ✅
**次のタスク**: phpMyAdminでusersテーブル作成 → Google Cloud Console設定 → 動作確認
