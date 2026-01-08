#!/bin/bash
# サーバー側で実行: スクリプトを探して実行

REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
cd "$REPO_DIR" || exit 1

echo "=========================================="
echo "  スクリプト検索と実行"
echo "=========================================="
echo ""

# 1. 現在のディレクトリを確認
echo "=== 現在のディレクトリ ==="
pwd
echo ""

# 2. スクリプトファイルを検索
echo "=== スクリプトファイルを検索 ==="
find . -name "FIX_PATH_AND_RESTORE.sh" -type f 2>/dev/null
find . -name "*.sh" -type f | head -10
echo ""

# 3. Gitで追跡されているファイルを確認
echo "=== Gitで追跡されている.shファイル ==="
git ls-files | grep "\.sh$" | head -10
echo ""

# 4. 直接実行可能な方法: インラインスクリプト
echo "=== インライン復元スクリプトを実行 ==="

BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

if [ -f "public/index.php" ]; then
    BACKUP_FILE="$BACKUP_DIR/index.php.backup.$TIMESTAMP"
    cp public/index.php "$BACKUP_FILE"
    echo "✓ バックアップ作成: $BACKUP_FILE"
else
    echo "⚠ バックアップ対象のファイルが存在しません"
fi
echo ""

# Gitからの復元を試行
echo "=== Gitからの復元を試行 ==="
git fetch origin
echo ""

if git checkout origin/main -- public/index.php 2>/dev/null; then
    echo "✓ git checkoutで復元成功"
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
