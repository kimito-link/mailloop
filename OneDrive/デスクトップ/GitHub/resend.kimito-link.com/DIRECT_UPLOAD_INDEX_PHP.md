# public/index.php 直接アップロード手順

## 状況

サーバー側で `git checkout origin/main -- public/index.php` が失敗しています。
これは `public/index.php` がGitで追跡されていないためです。

## 解決方法: Base64エンコード方式

### ステップ1: ローカルでBase64エンコード

**PowerShellで実行:**

```powershell
cd "C:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"
[Convert]::ToBase64String([System.IO.File]::ReadAllBytes('public\index.php')) | Out-File -Encoding utf8 index_php_base64.txt
```

**Git Bashで実行:**

```bash
cd /c/Users/info/OneDrive/デスクトップ/GitHub/resend.kimito-link.com
base64 public/index.php > index_php_base64.txt
```

### ステップ2: Base64内容をコピー

`index_php_base64.txt` ファイルを開き、内容をすべてコピーしてください。

### ステップ3: サーバー側で復元

サーバーにSSH接続後、以下を実行:

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
cp public/index.php "$BACKUP_DIR/index.php.backup.$(date +%Y%m%d_%H%M%S)"

# Base64デコード（コピーした内容を貼り付け）
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

# OPcacheクリア
if [ -f "bin/php" ]; then
    chmod +x bin/php
    bin/php public/clear_cache.php
else
    php public/clear_cache.php
fi
```

## 注意事項

1. **Base64内容の貼り付け**: `EOFBASE64` と `EOFBASE64` の間に、Base64エンコードされた内容を**1行で**貼り付けてください（改行は含めない）。

2. **ファイルサイズ確認**: 復元後、`ls -lh public/index.php` でファイルサイズを確認してください。正常な場合は約40KB以上になるはずです。

3. **GETログアウトルートの確認**: `grep` コマンドで `route('GET', '/auth/logout'` が見つかることを確認してください。

## トラブルシューティング

### Base64デコードが失敗する場合

- Base64内容に改行が含まれていないか確認
- `base64 -d` コマンドが利用可能か確認: `which base64`
- エラーメッセージを確認: `base64 -d < /dev/null 2>&1`

### ファイルサイズが異常に小さい場合

- Base64エンコードが正しく行われたか確認
- ローカルで再度Base64エンコードを実行
