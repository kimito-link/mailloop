#!/bin/bash
# サーバー側で実行する更新確認スクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== 現在のコミットを確認 ==="
git log -1 --oneline

echo ""
echo "=== 更新されたファイルを確認 ==="
git log -1 --name-only

echo ""
echo "=== ファイルの内容を確認（db.phpの最初の数行） ==="
head -5 app/lib/db.php

echo ""
echo "=== ファイルの内容を確認（storage.phpのrequirePdo部分） ==="
grep -A 5 "private function requirePdo" app/services/storage.php

echo ""
echo "=== ファイルの内容を確認（index.phpのrequire_login部分） ==="
grep -A 5 "function require_login" public/index.php

echo ""
echo "=== Webサーバーが読み込んでいるファイルのパスを確認 ==="
# シンボリックリンクを確認
ls -la /home/besttrust/kimito-link.com/ | grep -E "public|app"

echo ""
echo "=== OPcacheクリア（PHPファイルを実行） ==="
php public/clear_cache.php 2>&1 || echo "clear_cache.phpが存在しないか、実行できませんでした"
