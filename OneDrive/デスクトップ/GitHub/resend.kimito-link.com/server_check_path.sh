#!/bin/bash
# サーバー側で実行するパス確認スクリプト

echo "=== 現在のディレクトリ ==="
pwd

echo ""
echo "=== ホームディレクトリの内容を確認 ==="
ls -la ~ | head -20

echo ""
echo "=== リポジトリを探す ==="
find ~ -name ".git" -type d 2>/dev/null | head -10

echo ""
echo "=== mailloopディレクトリを探す ==="
find ~ -type d -name "mailloop" 2>/dev/null | head -10

echo ""
echo "=== シンボリックリンクを確認 ==="
ls -la /home/besttrust/kimito-link.com/ 2>/dev/null || echo "パスが見つかりません"

echo ""
echo "=== 実際のWebサーバーのドキュメントルートを確認 ==="
# Apacheの場合
if [ -f /etc/apache2/sites-enabled/*.conf ]; then
  grep -r "DocumentRoot" /etc/apache2/sites-enabled/ 2>/dev/null | head -5
fi
