#!/bin/bash
# サーバー側で実行するgit pullスクリプト

echo "=== サーバー側でのgit pull実行 ==="

# リポジトリのパス（SSH_INFO.mdに記載されているパス）
REPO_PATH="/home/besttrust/kimito-link.com/_git/mailloop"

echo "リポジトリパス: $REPO_PATH"

# パスの存在確認
if [ ! -d "$REPO_PATH" ]; then
  echo "❌ エラー: リポジトリが見つかりません: $REPO_PATH"
  echo ""
  echo "リポジトリを探します..."
  find ~ -name ".git" -type d 2>/dev/null | grep mailloop | head -5
  exit 1
fi

# リポジトリに移動
cd "$REPO_PATH"

echo ""
echo "=== 現在のディレクトリ ==="
pwd

echo ""
echo "=== 現在のブランチと状態 ==="
git branch
git status

echo ""
echo "=== git pull実行 ==="
git pull origin main

echo ""
echo "=== 更新されたファイルを確認 ==="
git log -1 --name-only

echo ""
echo "=== ファイルの内容を確認（db.php） ==="
head -10 app/lib/db.php

echo ""
echo "=== 更新完了 ==="
