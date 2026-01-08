#!/bin/bash
# サーバー側で実行するクイックチェックスクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== 現在のコミット ==="
git log -1 --oneline

echo ""
echo "=== リモートとの差分を確認 ==="
git fetch origin
git log HEAD..origin/main --oneline

if [ -z "$(git log HEAD..origin/main --oneline)" ]; then
  echo "✅ リモートと同期されています"
else
  echo "⚠️ リモートに新しいコミットがあります"
  echo ""
  echo "=== git pullを実行しますか？ ==="
  read -p "実行しますか？ (y/n): " answer
  if [ "$answer" = "y" ]; then
    git pull origin main
    echo ""
    echo "=== 更新後の確認 ==="
    git log -1 --oneline
  fi
fi

echo ""
echo "=== ファイルの内容を確認（db.php） ==="
head -10 app/lib/db.php | grep -E "declare|function db_pdo"

echo ""
echo "=== OPcacheクリア ==="
php public/clear_cache.php 2>&1 || echo "clear_cache.phpが実行できませんでした"
