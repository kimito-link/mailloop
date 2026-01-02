# MailLoop (kimito-link) - Xserver Business upload-ready (PHP)

このZIPは **Xserver Business にアップしてすぐ動かし始められる最小構成**のPHPアプリです。
- スマホ優先UI
- テンプレ / 宛先グループ（To/CC/Bcc）
- Gmail API（OAuth2）で本人のGmailから送信
- 送信ログ
- **MySQL推奨**／超簡易なら **ファイル保存(JSON)** でも動作

## 必要なもの
- Xserver Business（HTTPS）
- Google Cloud（OAuthクライアントID/Secret）
- MySQL（推奨）またはファイル保存モード

## 1) アップロード
`public/`配下を `public_html/` に置く（またはドメインの公開先を `/public` にする）

## 2) 設定
`config/config.sample.php` → `config/config.php` にコピーして編集
- APP_URL
- APP_KEY
- Google OAuth（ID/Secret/Redirect）
- STORAGE_DRIVER=mysql もしくは file
- mysqlの場合 DB設定

### 2-1) 機密情報の設定（推奨: secrets.php）
パスワードなどの機密情報は `config/secrets.php` で管理することを推奨します。

**手順:**
1. `config/secrets.php` を開く（既に存在します）
2. 以下のように設定を記述：
```php
<?php
return [
  'DB_HOST' => 'localhost',
  'DB_NAME' => 'besttrust_mail',
  'DB_USER' => 'besttrust_mail',
  'DB_PASS' => 'your_actual_password',
];
```

**読み込み順序:**
`config/config.php` は以下の順で設定を読み込みます：
1. `config/secrets.php`（最優先）
2. 環境変数（`getenv()`）
3. デフォルト値

**重要:**
- `config/secrets.php` は Git 管理外にしてください（`.gitignore` に追加推奨）
- `STORAGE_DRIVER=mysql` の場合、`DB_PASS` が未設定だとエラーで停止します

## 3) DB作成（STORAGE_DRIVER=mysql の場合）

XserverのMySQLデータベースを作成し、以下の手順でテーブルを作成します。

### 3-1) データベース作成
1. Xserverのサーバーパネルにログイン
2. 「データベース」→「MySQL追加」でデータベースを作成
3. データベース名、ユーザー名、パスワードをメモ

### 3-2) テーブル作成
1. phpMyAdmin にアクセス（Xserverのサーバーパネルから）
2. 作成したデータベースを選択
3. 「SQL」タブを開く
4. `database/schema.sql` の内容をコピー＆ペーストして実行

または、コマンドラインから：
```bash
mysql -u [DB_USER] -p [DB_NAME] < database/schema.sql
```

### 3-3) 作成されるテーブル
- `oauth_tokens` - OAuthトークン保存
- `message_templates` - メールテンプレート
- `recipient_groups` - 宛先グループ（To/CC/BCC）
- `send_logs` - 送信ログ

### 3-4) DB接続設定
`config/secrets.php` に以下を設定してください（推奨）：
```php
<?php
return [
  'DB_HOST' => 'localhost', // Xserverの場合は mysql[数字].xserver.jp の可能性あり
  'DB_NAME' => '作成したデータベース名',
  'DB_USER' => '作成したデータベースユーザー名',
  'DB_PASS' => '作成したデータベースパスワード', // 必須
];
```

または、環境変数で設定することも可能です。

**重要**: 
- `STORAGE_DRIVER=mysql` の場合、`DB_PASS` が未設定だとエラーで停止します
- `config/secrets.php` は Git 管理外にしてください

## 4) Google側設定
Authorized redirect URI:
- https://あなたのドメイン/auth/callback

## 注意（重要）
- Gmailの送信履歴から **Bcc全員を復元** は基本できません（仕様的に保持されないことが多い）
  → なので本アプリは「宛先グループ」を保存して再利用します。
- 添付はMVPでは **リンク** で対応（Drive URL等を本文に含める想定。Drive APIスコープ不要）

