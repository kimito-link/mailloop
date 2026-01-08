# SSHで実行する手順

## 前提条件

- SSH接続が可能であること
- サーバーへのアクセス権限があること

## 実行方法

### 方法1: コマンドを1つずつ実行

SSHでサーバーに接続後、以下のコマンドを順番に実行してください：

```bash
# 1. DocumentRoot直下のindex.phpの実体を確認
readlink -f /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php

# 2. .htaccessのRewriteRuleを確認
cat /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/.htaccess

# 3. /authディレクトリの内容を確認
ls -la /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/auth/

# 4. /auth/callbackファイルが存在するか確認
ls -la /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/auth/callback

# 5. DocumentRoot直下のindex.phpの内容（最初の20行）を確認
head -20 /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php

# 6. _git/mailloop/public/index.phpが存在するか確認
ls -la /home/besttrust/kimito-link.com/_git/mailloop/public/index.php
```

### 方法2: スクリプトを実行

1. `ssh_commands.sh`をサーバーにアップロード
2. 実行権限を付与:
   ```bash
   chmod +x ssh_commands.sh
   ```
3. 実行:
   ```bash
   ./ssh_commands.sh
   ```

## 各コマンドの目的

### コマンド1: `readlink -f`
- **目的**: DocumentRoot直下の`index.php`がシンボリックリンクか実ファイルかを確認
- **確認ポイント**: 
  - シンボリックリンクの場合、リンク先が`_git/mailloop/public/index.php`を指しているか
  - 実ファイルの場合、内容を確認する必要がある

### コマンド2: `cat .htaccess`
- **目的**: `.htaccess`の`RewriteRule`を確認
- **確認ポイント**:
  - `RewriteRule ^ index.php [QSA,L]`が正しく設定されているか
  - `/auth/callback`だけ例外で別へ飛ばすルールがないか

### コマンド3: `ls -la /auth/`
- **目的**: `/auth`ディレクトリの内容を確認
- **確認ポイント**:
  - `/auth/.htaccess`が存在しないか
  - 別のファイルやディレクトリが存在しないか

### コマンド4: `ls -la /auth/callback`
- **目的**: `/auth/callback`ファイルが存在するか確認
- **確認ポイント**:
  - ファイルが存在する場合、実ファイルテストの結果を確認

### コマンド5: `head -20 index.php`
- **目的**: DocumentRoot直下の`index.php`の内容を確認
- **確認ポイント**:
  - 編集したコードを読み込んでいるか
  - 別のコードが書かれていないか

### コマンド6: `ls -la _git/mailloop/public/index.php`
- **目的**: 編集した`index.php`が存在するか確認
- **確認ポイント**:
  - ファイルが存在するか
  - パスが正しいか

## 結果の解釈

### パターン1: シンボリックリンクが正しく設定されている

```
/home/besttrust/kimito-link.com/_git/mailloop/public/index.php
```

→ シンボリックリンクは正しい。次は`.htaccess`を確認。

### パターン2: シンボリックリンクが別の場所を指している

```
/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/old_index.php
```

→ シンボリックリンクを修正する必要がある。

### パターン3: 実ファイルが存在する

```
-rw-r--r-- 1 user user 1234 ... index.php
```

→ 実ファイルの内容を確認し、必要に応じて修正。

### パターン4: .htaccessのRewriteRuleが正しく設定されている

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

→ `.htaccess`は正しい。次は`index.php`の内容を確認。

### パターン5: /auth/.htaccessが存在する

```
-rw-r--r-- 1 user user 123 ... .htaccess
```

→ `/auth/.htaccess`の内容を確認する必要がある。

## 修正が必要な場合

### 修正1: DocumentRoot直下のindex.phpをラッパーにする

```bash
# バックアップを取る
cp /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php.bak

# ラッパーファイルを作成
cat > /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php << 'EOF'
<?php
require __DIR__ . '/../_git/mailloop/public/index.php';
EOF
```

### 修正2: シンボリックリンクを作成/修正

```bash
# 既存のファイルをバックアップ
mv /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php.bak

# シンボリックリンクを作成
ln -s /home/besttrust/kimito-link.com/_git/mailloop/public/index.php /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php
```

## 次のステップ

1. コマンドを実行して結果を確認
2. 結果を共有してください
3. 問題が特定できたら、修正方法を提案します
