#!/bin/bash
# サーバー側で実行: Base64からindex.phpを復元

cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ
if [ -f "public/index.php" ]; then
    cp public/index.php public/index.php.bak_$(date +%Y%m%d_%H%M%S)
fi

# Base64デコードして復元
cat > /tmp/index_php_base64.txt << 'ENDBASE64'
PD9waHAKLy8gZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7IC8vIFhzZXJ2ZXLjga5QSFDjg5Djg7zjgrjjg6fjg7PjgYzlj6TjgYTjgZ/jgoHjgrPjg6Hjg7Pjg4jjgqLjgqbjg4gKCi8vIOKYheacgOWEquWFiOg...
ENDBASE64

# 実際のBase64文字列をここに貼り付け
BASE64_CONTENT=$(cat index_php_base64.txt 2>/dev/null || echo "")

if [ -z "$BASE64_CONTENT" ]; then
    echo "エラー: Base64ファイルが見つかりません"
    exit 1
fi

echo "$BASE64_CONTENT" | base64 -d > public/index.php

# 確認
ls -lh public/index.php
echo "復元完了"
