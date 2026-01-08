# public/index.php サーバー反映手順

## 概要

ローカルの正しい `public/index.php` をサーバーに反映する手順です。

## 前提条件

- ローカルの `public/index.php` が正しく、GETログアウトルートが含まれていること
- サーバーへのSSH接続が可能であること

## 方法1: SCP経由（推奨・最速）

### Windows PowerShellの場合

```powershell
cd "C:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"
scp -i C:\Users\info\.ssh\id_rsa -P 10022 `
  "public\index.php" `
  besttrust@sv16.sixcore.ne.jp:/home/besttrust/kimito-link.com/_git/mailloop/public/index.php
```

### Git Bashの場合

```bash
cd /c/Users/info/OneDrive/デスクトップ/GitHub/resend.kimito-link.com
scp -i ~/.ssh/id_rsa -P 10022 \
  public/index.php \
  besttrust@sv16.sixcore.ne.jp:/home/besttrust/kimito-link.com/_git/mailloop/public/index.php
```

## 方法2: Base64エンコード方式（SCPが使えない場合）

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

サーバーにSSH接続後、以下を実行:

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
```

## 方法3: Git経由（Gitが正常に動作する場合）

サーバー側で実行:

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
```

## 復元後の確認

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

### 4. PHP構文チェック（bin/phpが利用可能な場合）

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
if [ -f "bin/php" ]; then
    bin/php -l public/index.php
else
    echo "bin/phpが存在しないため、構文チェックをスキップ"
fi
```

## トラブルシューティング

### SCPが失敗する場合

- SSH鍵のパスを確認
- ポート番号（10022）を確認
- サーバーのホスト名を確認

### Base64デコードが失敗する場合

- Base64エンコードされた内容が正しいか確認
- 改行コードの問題（CRLF vs LF）を確認
- `base64 -d` コマンドが利用可能か確認

### Gitからの復元が失敗する場合

- `git fetch origin` を実行してリモート情報を更新
- `git status` で現在の状態を確認
- `git ls-files | grep index.php` でファイルが追跡されているか確認

## 注意事項

1. **必ずバックアップを作成**してから上書きしてください
2. ファイルの権限（644）を確認してください
3. WebサーバーのOPcacheをクリアすることを推奨します（`bin/php public/clear_cache.php`）
