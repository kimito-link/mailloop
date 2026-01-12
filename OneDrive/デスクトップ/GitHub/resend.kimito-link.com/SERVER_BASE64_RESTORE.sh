#!/bin/bash
# サーバー側で実行: Base64エンコードされたindex.phpを復元

cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ
if [ -f "public/index.php" ]; then
    cp public/index.php public/index.php.bak_$(date +%Y%m%d_%H%M%S)
    echo "✓ バックアップ作成"
fi

# Base64デコードして復元
echo "PD9waHAKLy8gZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7IC8vIFhzZXJ2ZXLjga5QSFDjg5Djg7zjgrjjg6fjg7PjgYzlj6TjgYTjgZ/jgoHjgrPjg6Hjg7Pjg4jjgqLjgqbjg4gKCi8vIOKYheacgOWEquWFiDog44Gp44Gu44OV44Kh44Kk44Or44GM5a6f6KGM44GV44KM44Gm44GE44KL44GL56K65a6a77yI44Or44O844OG44Kj44Oz44Kw44KI44KK5YmN77yJCmlmIChpc3NldCgkX0dFVFsnZGJnJ10pICYmICRfR0VUWydkYmcnXSA9PT0gJ3JhdycpIHsKICBoZWFkZXIoJ0NvbnRlbnQtVHlwZTogdGV4dC9wbGFpbjsgY2hhcnNldD1VVEYtOCcpOwogIGVjaG8gIklOREVYX1BIUF9ISVRcbiI7CiAgZWNobyAiX19GSUxFX189IiAuIF9fRklMRV9fIC4gIlxuIjsKICBlY2hvICJET0NST09UPSIgLiAoJF9TRVJWRVJbJ0RPQ1VNRU5UX1JPT1QnXSA/PyAnTk9ORScpIC4gIlxuIjsKICBlY2hvICJTQ1JJUFQ9IiAuICgkX1NFUlZFUlsnU0NSSVBUX0ZJTEVOQU1FJ10gPz8gJ05PTkUnKSAuICJcbiI7CiAgZWNobyAiUkVRVUVTVF9VUkk9IiAuICgkX1NFUlZFUlsnUkVRVUVTVF9VUkknXSA/PyAnTk9ORScpIC4gIlxuIjsKICBlY2hvICJQQVRIX0lORk89IiAuICgkX1NFUlZFUlsnUEFUSF9JTkZPJ10gPz8gJ05PTkUnKSAuICJcbiI7CiAgZWNobyAiR0VUPVxuIjsgdmFyX2V4cG9ydCgkX0dFVCk7IGVjaG8gIlxuIjsKICBleGl0Owp9CgpyZXF1aXJlX29uY2UgX19ESVJfXyAuICcvLi4vYXBwL2Jvb3RzdHJhcC5waHAnOwpyZXF1aXJlX29uY2UgX19ESVJfXyAuICcvLi4vdmlld3MvaGVscGVycy9lbWFpbHMucGhwJzsKCnJvdXRlKCdHRVQnLCAnL2F1dGgvbG9nb3V0JywgZnVuY3Rpb24oKSB7CiAgc2Vzc2lvbl9kZXN0cm95KCk7CiAgaGVhZGVyKCdMb2NhdGlvbjogL2F1dGgvbG9naW4nKTsgZXhpdDsKfSk7" | base64 -d > public/index.php

# 確認
ls -lh public/index.php
echo ""
echo "# GETログアウトルートの確認:"
grep -q "route('GET', '/auth/logout'" public/index.php && echo "✓ GETログアウトルートが見つかりました" || echo "✗ GETログアウトルートが見つかりませんでした"

# OPcacheクリア
if [ -f "bin/php" ]; then
    chmod +x bin/php 2>/dev/null || true
    bin/php public/clear_cache.php 2>&1 || php public/clear_cache.php 2>&1
elif [ -f "public/clear_cache.php" ]; then
    php public/clear_cache.php 2>&1
fi

echo ""
echo "復旧完了"
