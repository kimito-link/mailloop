#!/bin/bash
# ローカル（Windows MINGW64）で実行する確認スクリプト

# リポジトリのディレクトリに移動
cd "/c/Users/info/OneDrive/デスクトップ/GitHub/resend.kimito-link.com"

echo "=== 現在のディレクトリ ==="
pwd

echo ""
echo "=== 最新のコミットを確認 ==="
git log -1 --oneline

echo ""
echo "=== 更新されたファイルを確認 ==="
git log -1 --name-only

echo ""
echo "=== db.phpの最初の数行を確認 ==="
head -10 app/lib/db.php

echo ""
echo "=== storage.phpのrequirePdo部分を確認 ==="
grep -A 5 "private function requirePdo" app/services/storage.php

echo ""
echo "=== index.phpのrequire_login部分を確認 ==="
grep -A 5 "function require_login" public/index.php
