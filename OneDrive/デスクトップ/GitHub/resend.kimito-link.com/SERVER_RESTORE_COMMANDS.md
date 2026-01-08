# サーバー側で実行する index.php 復旧コマンド

## 概要

ローカルの正しい `public/index.php` をサーバーに反映するためのコマンド集です。

## 方法1: Git経由（推奨・最も安全）

サーバー側で以下を実行:

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
cp public/index.php "$BACKUP_DIR/index.php.backup.$(date +%Y%m%d_%H%M%S)"

# Gitから最新版を取得
git fetch origin
git checkout origin/main -- public/index.php

# 確認
ls -lh public/index.php
head -10 public/index.php
grep -A 5 "route('GET', '/auth/logout'" public/index.php || echo "GETログアウトルートが見つかりません"

# PHP構文チェック（bin/phpが利用可能な場合）
if [ -f "bin/php" ]; then
    bin/php -l public/index.php
else
    echo "bin/phpが存在しないため、構文チェックをスキップ"
fi
```

## 方法2: Base64エンコード方式（Gitが使えない場合）

### ステップ1: ローカルでBase64エンコード

**PowerShellの場合:**
```powershell
cd "C:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"
[Convert]::ToBase64String([System.IO.File]::ReadAllBytes('public\index.php')) | Out-File -Encoding utf8 index_php_base64.txt
```

**Git Bashの場合:**
```bash
cd /c/Users/info/OneDrive/デスクトップ/GitHub/resend.kimito-link.com
base64 public/index.php > index_php_base64.txt
```

### ステップ2: サーバー側で復元

1. `index_php_base64.txt` の内容をコピー
2. サーバー側で以下を実行:

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
cp public/index.php "$BACKUP_DIR/index.php.backup.$(date +%Y%m%d_%H%M%S)"

# Base64デコード（index_php_base64.txtの内容を貼り付け）
base64 -d > public/index.php.new << 'EOFBASE64'
# ここにBase64エンコードされた内容を貼り付け
EOFBASE64

# ファイルを置き換え
mv public/index.php.new public/index.php
chmod 644 public/index.php

# 確認
ls -lh public/index.php
head -10 public/index.php
grep -A 5 "route('GET', '/auth/logout'" public/index.php || echo "GETログアウトルートが見つかりません"

# PHP構文チェック（bin/phpが利用可能な場合）
if [ -f "bin/php" ]; then
    bin/php -l public/index.php
else
    echo "bin/phpが存在しないため、構文チェックをスキップ"
fi
```

## 復旧後の確認

### 1. ファイルの存在確認

```bash
ls -lh /home/besttrust/kimito-link.com/_git/mailloop/public/index.php
```

### 2. GETログアウトルートの確認

```bash
grep -A 5 "route('GET', '/auth/logout'" /home/besttrust/kimito-link.com/_git/mailloop/public/index.php
```

### 3. Web経由での動作確認

ブラウザで以下にアクセス:

- https://resend.kimito-link.com （正常に表示されるか）
- https://resend.kimito-link.com/auth/logout （正常にログアウトできるか）

### 4. OPcacheクリア（推奨）

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
if [ -f "bin/php" ]; then
    bin/php public/clear_cache.php
else
    php public/clear_cache.php
fi
```

## トラブルシューティング

### Gitからの復元が失敗する場合

- `git fetch origin` を実行してリモート情報を更新
- `git status` で現在の状態を確認
- `git ls-files | grep index.php` でファイルが追跡されているか確認

### Base64デコードが失敗する場合

- Base64エンコードされた内容が正しいか確認
- 改行コードの問題（CRLF vs LF）を確認
- `base64 -d` コマンドが利用可能か確認

### PHP構文チェックが失敗する場合

- `bin/php` が存在し、実行権限があるか確認: `ls -la bin/php`
- `bin/php` のPHPバージョンを確認: `bin/php -v`
- WebサーバーのPHPバージョンと一致しているか確認
