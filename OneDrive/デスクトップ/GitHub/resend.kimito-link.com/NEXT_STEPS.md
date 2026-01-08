# 次のステップ: index.php復旧

## 現在の状況

✅ `git pull origin main` が成功し、復旧スクリプトが取得されました
❌ `public/index.php` にGETログアウトルートがまだ含まれていません

## 復旧方法の選択

### 方法1: 復旧スクリプトを実行（推奨）

サーバー側で以下を実行:

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# スクリプトに実行権限を付与
chmod +x restore_and_verify.sh
chmod +x QUICK_RESTORE_INDEX_PHP.sh

# 復旧スクリプトを実行
./QUICK_RESTORE_INDEX_PHP.sh
```

このスクリプトは以下を実行します:
1. バックアップ作成
2. Gitからの復元を試行
3. 復元後の確認

**注意**: `public/index.php`がGitで追跡されていない場合、このスクリプトは失敗します。その場合は方法2を使用してください。

### 方法2: Base64エンコード方式（確実）

`public/index.php`がGitで追跡されていない場合、直接アップロードが必要です。

#### ステップ1: ローカルでBase64エンコード

ローカルの `index_php_base64.txt` ファイルを開き、内容をすべてコピーしてください。

#### ステップ2: サーバー側で復元

サーバー側で以下を実行（Base64内容を貼り付け）:

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成
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

## 復旧後の確認

### 1. ファイルの確認

```bash
# ファイルサイズ確認（正常な場合は約40KB以上）
ls -lh public/index.php

# GETログアウトルートの確認
grep -A 5 "route('GET', '/auth/logout'" public/index.php
```

### 2. Web経由での動作確認

ブラウザで以下にアクセス:

- https://resend.kimito-link.com （トップページが正常に表示されるか）
- https://resend.kimito-link.com/auth/logout （ログアウトが正常に動作するか）

### 3. ログアウト→再ログイン

1. ログアウト: https://resend.kimito-link.com/auth/logout
2. 再ログイン: https://resend.kimito-link.com/auth/login
3. Gmail送信権限が正しく取得されているか確認

## トラブルシューティング

### Base64デコードが失敗する場合

- Base64内容に改行が含まれていないか確認
- `base64 -d` コマンドが利用可能か確認: `which base64`
- エラーメッセージを確認

### ファイルサイズが異常に小さい場合

- Base64エンコードが正しく行われたか確認
- ローカルで再度Base64エンコードを実行
