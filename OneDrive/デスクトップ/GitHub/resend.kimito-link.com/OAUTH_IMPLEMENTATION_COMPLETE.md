# OAuth実装完了サマリー

## ✅ 実装完了項目

### 1. usersテーブルの追加
- `database/schema.sql` に users テーブル定義を追加
- `provider` + `provider_sub` でユーザーを一意に識別
- `email` は補助情報として保存（NULL可、UNIQUE制約あり）

### 2. google_auth_url() 関数の修正
- `state` パラメータを受け取るように修正
- `GOOGLE_SCOPES` 設定に対応（フォールバックあり）

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

### 6. require_login() の復活
- `/templates` と `/groups` の全ルートからダミーユーザーのバイパスを削除
- `require_login($storage)` を使用するように統一

---

## 📋 次のステップ

### 1. phpMyAdminでusersテーブルを作成

`database/schema.sql` を実行して users テーブルを作成：

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
- エラーはログに記録

### セッション管理
- ログイン成功時に `session_regenerate_id(true)` でセッション固定化対策
- CSRFトークンを再生成
- `$_SESSION['user_id']` と `$_SESSION['user']` の両方を設定

---

**実装日**: 2026年1月2日
**次のタスク**: phpMyAdminでusersテーブル作成 → Google Cloud Console設定 → 動作確認
