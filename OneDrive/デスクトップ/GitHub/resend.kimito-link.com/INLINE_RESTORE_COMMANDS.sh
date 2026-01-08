#!/bin/bash
# サーバー側で直接実行: index.php復元コマンド（インライン版）

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=========================================="
echo "  index.php 復元（インライン実行版）"
echo "=========================================="
echo ""

# バックアップ作成
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
if [ -f "public/index.php" ]; then
    cp public/index.php "$BACKUP_DIR/index.php.backup.$TIMESTAMP"
    echo "✓ バックアップ作成: $BACKUP_DIR/index.php.backup.$TIMESTAMP"
else
    echo "⚠ バックアップ対象のファイルが存在しません"
fi
echo ""

# Gitからの復元を試行
echo "=== Gitからの復元を試行 ==="
git fetch origin
if git checkout origin/main -- public/index.php 2>/dev/null; then
    echo "✓ Gitからの復元成功"
elif git checkout HEAD -- public/index.php 2>/dev/null; then
    echo "✓ git checkout HEADで復元成功"
else
    echo "✗ Gitからの復元に失敗"
    echo ""
    echo "→ public/index.phpがGitで追跡されていないため、Base64エンコード方式で直接アップロードが必要です"
    echo ""
    echo "【次のステップ】"
    echo "1. ローカルの index_php_base64.txt ファイルを開く"
    echo "2. 内容をすべてコピー"
    echo "3. サーバー側で以下を実行（Base64内容を貼り付け）:"
    echo ""
    echo "   base64 -d > public/index.php.new << 'EOFBASE64'"
    echo "   （ここにBase64エンコードされた内容を貼り付け）"
    echo "   EOFBASE64"
    echo "   mv public/index.php.new public/index.php"
    echo "   chmod 644 public/index.php"
    echo ""
    exit 1
fi
echo ""

# 復元後の確認
echo "=== 復元後の確認 ==="
if [ -f "public/index.php" ]; then
    echo "# ファイル情報:"
    ls -lh public/index.php
    echo ""
    echo "# GETログアウトルートの確認:"
    if grep -q "route('GET', '/auth/logout'" public/index.php; then
        echo "  ✓ GETログアウトルートが見つかりました"
        echo ""
        echo "  該当箇所:"
        grep -A 5 "route('GET', '/auth/logout'" public/index.php | head -6
    else
        echo "  ✗ GETログアウトルートが見つかりませんでした"
    fi
    echo ""
else
    echo "✗ ファイルが存在しません"
    exit 1
fi

# OPcacheクリア
echo "=== OPcacheクリア ==="
if [ -f "bin/php" ]; then
    chmod +x bin/php 2>/dev/null || true
    bin/php public/clear_cache.php 2>&1 || echo "⚠ OPcacheクリアに失敗"
elif [ -f "public/clear_cache.php" ]; then
    php public/clear_cache.php 2>&1 || echo "⚠ OPcacheクリアに失敗"
fi
echo ""

echo "=========================================="
echo "  復旧完了"
echo "=========================================="
