# サーバー側でのクイックチェック手順

## 現在の状態を確認

サーバーにSSH接続して、以下を実行してください：

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# 現在のコミットを確認
git log -1 --oneline

# リモートとの差分を確認
git fetch origin
git log HEAD..origin/main --oneline
```

## リモートに新しいコミットがある場合

```bash
# git pullを実行
git pull origin main

# 更新後の確認
git log -1 --oneline
```

## 既に最新の場合

リモートと同期されている場合、OPcacheをクリアしてください：

```bash
# OPcacheをクリア
php public/clear_cache.php

# または、Webサーバーを再起動
sudo systemctl restart apache2
```

## ファイルの内容を確認

```bash
# db.phpの内容を確認（declareがコメントアウトされているか）
head -10 app/lib/db.php | grep -E "declare|function db_pdo"

# エラーログ出力部分を確認
grep -A 3 "error_log('db_pdo connect failed" app/lib/db.php
```

## トラブルシューティング

### git pullが「Already up to date」と表示される場合

これは正常です。ファイルは既に最新の状態です。OPcacheをクリアしてください。

### ファイルが更新されていないように見える場合

```bash
# ファイルの更新日時を確認
ls -la app/lib/db.php app/services/storage.php public/index.php

# ファイルの内容を直接確認
head -10 app/lib/db.php
```

### OPcacheが有効になっている場合

```bash
# PHPの設定を確認
php -i | grep -i opcache

# OPcacheを無効にする（一時的）
# php.iniまたは.htaccessで設定
```
