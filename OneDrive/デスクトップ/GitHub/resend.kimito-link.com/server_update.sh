#!/bin/bash
# サーバー側で実行する更新スクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== Git Pull 実行 ==="
git pull origin main

echo ""
echo "=== 更新されたファイルを確認 ==="
git log -1 --name-only

echo ""
echo "=== OPcacheクリア ==="
if [ -f "bin/php" ]; then
    bin/php public/clear_cache.php 2>&1 || echo "clear_cache.phpが実行できませんでした"
else
    # フォールバック: bin/phpが存在しない場合は従来の方法
    php public/clear_cache.php 2>&1 || echo "clear_cache.phpが実行できませんでした"
fi

echo ""
echo "=== 更新完了 ==="
