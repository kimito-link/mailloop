# Google OAuth設定ガイド

## 📋 実装手順

### 1. Google Cloud ConsoleでOAuthクライアントを作成

1. [Google Cloud Console](https://console.cloud.google.com/) にアクセス
2. プロジェクトを選択（または新規作成）
3. **API とサービス** → **OAuth 同意画面**
   - User Type: **External**（個人利用の場合）
   - アプリ名、サポートメール、デベロッパー連絡先メールを入力
   - スコープ（最小構成）:
     - `openid`
     - `email`
     - `profile`
     - `https://www.googleapis.com/auth/gmail.send`
   - External で「テスト」状態なら **テストユーザーに自分のGoogleアカウントを追加**（重要）
4. **API とサービス** → **認証情報** → **認証情報を作成** → **OAuth クライアント ID**
   - アプリケーションの種類: **ウェブ アプリケーション**
   - 名前: 任意（例：MailLoop Resend）
   - **承認済みのリダイレクト URI**: `https://resend.kimito-link.com/auth/callback`（完全一致で追加）
5. 作成後に表示される **クライアントID** と **クライアントシークレット** を控える

---

### 2. APP_KEYの生成

APP_KEYは暗号化キーとして使用されます。既にDBに暗号化済みトークンがある場合は変更しないでください。

**新規作成の場合**:
```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

または、PHPファイルで生成:
```php
<?php
echo bin2hex(random_bytes(32));
```

**出力例**: `a1b2c3d4e5f6...`（64文字のランダム文字列）

---

### 3. サーバー側の `config/secrets.php` を更新

SSHでサーバーに接続して編集:

```bash
ssh xserver-besttrust
nano ~/kimito-link.com/_git/mailloop/config/secrets.php
```

以下の内容に更新（実際のCLIENT_ID/SECRET/APP_KEYを入力）:

```php
<?php
// secrets.php - Git管理外の機密情報ファイル
// このファイルは .gitignore に追加してください

return [
  'DB_HOST' => 'sv16.sixcore.ne.jp',
  'DB_NAME' => 'besttrust_mail',
  'DB_USER' => 'besttrust_mail',
  'DB_PASS' => 'pass369code',
  
  'GOOGLE_CLIENT_ID' => 'xxxx.apps.googleusercontent.com',
  'GOOGLE_CLIENT_SECRET' => 'yyyyyyyyyyyyyyyy',
  'APP_KEY' => '本番用の長いランダム文字列(最低32文字以上推奨)',
];
```

**重要**:
- `GOOGLE_CLIENT_ID`: Google Cloud Consoleで取得したクライアントID
- `GOOGLE_CLIENT_SECRET`: Google Cloud Consoleで取得したクライアントシークレット
- `APP_KEY`: 上記で生成したランダム文字列（既存のDBに暗号化済みトークンがある場合は変更しない）

---

### 4. ファイル権限の設定（セキュリティ）

```bash
ssh xserver-besttrust
chmod 600 ~/kimito-link.com/_git/mailloop/config/secrets.php
```

これで所有者のみ読み書き可能になります。

---

### 5. `.gitignore` の確認

`.gitignore` に `config/secrets.php` が含まれているか確認:

```bash
git check-ignore config/secrets.php
```

含まれていない場合は追加:

```bash
echo "config/secrets.php" >> .gitignore
```

---

### 6. 動作確認

1. `https://resend.kimito-link.com/auth/login` にアクセス
2. Googleの同意画面が表示される → 許可
3. `/auth/callback` にリダイレクトされる
4. phpMyAdminで確認:
   - `users` テーブルに1行追加されている
   - `oauth_tokens` テーブルに1行追加されている（`access_token_enc`, `refresh_token_enc`, `expires_at` が入る）
5. `/send` でテスト送信 → `/logs` に success が残る

---

## 🔍 トラブルシューティング

### まだ `Error 401: invalid_client` が出る場合

以下の順で確認してください:

1. **client_id がコピペミス**（空白・改行混入、別プロジェクトのID等）
2. **OAuthクライアント種別がWebアプリになってない**（Android等で作ったIDを使ってる）
3. **redirect_uri がConsole登録と完全一致してない**
   - http/https違い
   - パス違い `/auth/callback`
   - サブドメイン違い
4. **External テスト中なのにテストユーザーに自分を追加してない**
5. **複数環境で混ざってる**（ローカル用のIDで本番叩いてる等）

### デバッグ方法

実際に生成された認可URLを確認:

```bash
# サーバー側でログを確認
ssh xserver-besttrust
tail -f ~/log/resend.kimito-link.com_error_log
```

または、`/auth/login` にアクセスした際のリダイレクト先URLをブラウザの開発者ツール（F12）で確認。

---

## 📝 注意事項

- `config/secrets.php` は **Git管理外** にしてください
- ファイル権限は **600（所有者のみ読み書き）** に設定してください
- `APP_KEY` を変更すると既存の暗号化済みトークンが復号できなくなります
- ローカル環境で動作確認する場合は、別のOAuthクライアントを作成するか、redirect_uriを追加してください
