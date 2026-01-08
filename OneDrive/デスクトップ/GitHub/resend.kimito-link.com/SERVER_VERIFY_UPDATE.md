# サーバー側での更新確認手順

## 1. ファイルが更新されているか確認

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# 最新のコミットを確認
git log -1 --oneline

# 更新されたファイルを確認
git log -1 --name-only
```

以下のファイルが表示されるはずです：
- `app/lib/db.php`
- `app/services/storage.php`
- `public/index.php`

## 2. ファイルの内容を確認

### db.phpの確認

```bash
# 最初の数行を確認（declare(strict_types=1)がコメントアウトされているか）
head -10 app/lib/db.php
```

以下のように表示されるはずです：
```php
<?php
// declare(strict_types=1); // 古いPHPで詰む可能性があるので外す

function db_pdo($config) {
```

### storage.phpの確認

```bash
# requirePdo関数を確認
grep -A 10 "private function requirePdo" app/services/storage.php
```

エラーログ出力が追加されているか確認してください。

### index.phpの確認

```bash
# require_login関数を確認
grep -A 10 "function require_login" public/index.php
```

`id`の存在チェックが追加されているか確認してください。

## 3. Webサーバーが読み込んでいるファイルのパスを確認

```bash
# シンボリックリンクを確認
ls -la /home/besttrust/kimito-link.com/

# publicディレクトリの実体を確認
readlink -f /home/besttrust/kimito-link.com/public
```

## 4. OPcacheをクリア

PHPのOPcacheが古いコードをキャッシュしている可能性があります。

```bash
# 方法1: clear_cache.phpを実行
php public/clear_cache.php

# 方法2: Webサーバーを再起動
sudo systemctl restart apache2
# または
sudo service apache2 restart
```

## 5. 実際のファイルの内容を直接確認

```bash
# db.phpの内容を確認
cat app/lib/db.php | head -20

# エラーログ出力部分を確認
grep -A 3 "error_log('db_pdo connect failed" app/lib/db.php
```

## トラブルシューティング

### ファイルが更新されていない場合

```bash
# リモートの最新情報を強制的に取得
git fetch origin
git reset --hard origin/main
```

### シンボリックリンクが別の場所を指している場合

```bash
# 実際のWebサーバーのドキュメントルートを確認
# Apacheの場合
grep -r "DocumentRoot" /etc/apache2/sites-enabled/ 2>/dev/null

# または、.htaccessで確認
cat /home/besttrust/kimito-link.com/public/.htaccess | grep -i "rewritebase\|documentroot"
```

### OPcacheが有効になっている場合

```bash
# PHPの設定を確認
php -i | grep -i opcache

# OPcacheを無効にする（一時的）
# php.iniまたは.htaccessで設定
```
