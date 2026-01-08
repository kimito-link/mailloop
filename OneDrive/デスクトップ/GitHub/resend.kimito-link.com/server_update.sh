#!/bin/bash
# サーバー側で実行する更新スクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== Git Pull 実行 ==="
git pull origin main

echo ""
echo "=== 更新されたファイルを確認 ==="
git log -1 --name-only

echo ""
echo "=== 更新完了 ==="
