# ローカルでのファイル確認手順

## 問題

`head -10 app/lib/db.php`を実行した際に「No such file or directory」エラーが発生しています。これは、現在のディレクトリがリポジトリのルートではないためです。

## 解決方法

### 1. リポジトリのディレクトリに移動

```bash
cd "/c/Users/info/OneDrive/デスクトップ/GitHub/resend.kimito-link.com"
```

または、相対パスで：

```bash
cd ~/OneDrive/デスクトップ/GitHub/resend.kimito-link.com
```

### 2. ファイルの存在を確認

```bash
# 現在のディレクトリを確認
pwd

# ファイルが存在するか確認
ls -la app/lib/db.php
ls -la app/services/storage.php
ls -la public/index.php
```

### 3. ファイルの内容を確認

```bash
# db.phpの最初の数行を確認
head -10 app/lib/db.php

# エラーログ出力部分を確認
grep -A 3 "error_log('db_pdo connect failed" app/lib/db.php

# storage.phpのrequirePdo部分を確認
grep -A 10 "private function requirePdo" app/services/storage.php

# index.phpのrequire_login部分を確認
grep -A 10 "function require_login" public/index.php
```

## サーバー側での確認（重要）

ローカルで確認できても、**サーバー側のファイルが更新されているか確認する必要があります**。

サーバーにSSH接続して、以下を実行してください：

```bash
# サーバー側のリポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/mailloop

# ファイルの存在を確認
ls -la app/lib/db.php
ls -la app/services/storage.php
ls -la public/index.php

# ファイルの内容を確認
head -10 app/lib/db.php
```

## トラブルシューティング

### パスに日本語が含まれている場合

MINGW64では日本語パスが問題になることがあります。以下の方法を試してください：

```bash
# 方法1: クォートで囲む
cd "/c/Users/info/OneDrive/デスクトップ/GitHub/resend.kimito-link.com"

# 方法2: 短いパス名を使用（Windowsの短いパス名）
cd /c/Users/info/ONEDRI~1/デスクトップ/GitHub/resend.kimito-link.com

# 方法3: 相対パスを使用
cd ~/OneDrive/デスクトップ/GitHub/resend.kimito-link.com
```

### ファイルが見つからない場合

```bash
# リポジトリのルートを探す
find /c/Users/info -name "db.php" -path "*/app/lib/db.php" 2>/dev/null

# または、.gitディレクトリを探す
find /c/Users/info -name ".git" -type d 2>/dev/null | grep resend
```
