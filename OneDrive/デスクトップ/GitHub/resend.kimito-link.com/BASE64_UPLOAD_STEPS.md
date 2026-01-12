# Base64エンコード方式でのindex.phpアップロード手順

## 状況

Gitからの復元が失敗したため、Base64エンコード方式で直接アップロードが必要です。

## 手順

### ステップ1: ローカルでBase64内容を確認

ローカルの `index_php_base64.txt` ファイルを開き、内容をすべてコピーしてください。

**注意**: Base64エンコードされた内容は非常に長い1行の文字列です。改行を含めずにすべてコピーしてください。

### ステップ2: サーバー側で復元

サーバーにSSH接続後、以下を実行:

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成（既に作成済みの場合はスキップ可）
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
cp public/index.php "$BACKUP_DIR/index.php.backup.$(date +%Y%m%d_%H%M%S)"

# Base64デコード（index_php_base64.txtの内容を貼り付け）
base64 -d > public/index.php.new << 'EOFBASE64'
# ここに index_php_base64.txt の内容を貼り付け（改行なし、1行で）
EOFBASE64

# ファイルを置き換え
mv public/index.php.new public/index.php
chmod 644 public/index.php

# 確認
ls -lh public/index.php
head -10 public/index.php
grep -A 5 "route('GET', '/auth/logout'" public/index.php

# OPcacheクリア
if [ -f "bin/php" ]; then
    chmod +x bin/php
    bin/php public/clear_cache.php
else
    php public/clear_cache.php
fi
```

## 重要な注意事項

1. **Base64内容の貼り付け**: `EOFBASE64` と `EOFBASE64` の間に、Base64エンコードされた内容を**1行で**貼り付けてください（改行は含めない）。

2. **ファイルサイズ確認**: 復元後、`ls -lh public/index.php` でファイルサイズを確認してください。正常な場合は約40KB以上になるはずです。

3. **GETログアウトルートの確認**: `grep` コマンドで `route('GET', '/auth/logout'` が見つかることを確認してください。

## トラブルシューティング

### Base64デコードが失敗する場合

- Base64内容に改行が含まれていないか確認
- `base64 -d` コマンドが利用可能か確認: `which base64`
- エラーメッセージを確認

### ファイルサイズが異常に小さい場合

- Base64エンコードが正しく行われたか確認
- ローカルで再度Base64エンコードを実行
