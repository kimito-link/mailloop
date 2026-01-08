#!/bin/bash
# サーバー側で実行: パスを確認してindex.phpを復元

set -e

REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
cd "$REPO_DIR" || exit 1

echo "=========================================="
echo "  index.php 復元スクリプト（パス確認版）"
echo "=========================================="
echo ""

# 1. 現在のディレクトリとファイルを確認
echo "=== 現在のディレクトリ ==="
pwd
echo ""

echo "=== 利用可能なスクリプトを確認 ==="
ls -la *.sh 2>/dev/null || echo "現在のディレクトリに.shファイルが見つかりません"
ls -la QUICK_RESTORE_INDEX_PHP.sh restore_and_verify.sh 2>/dev/null || echo "指定されたスクリプトが見つかりません"
echo ""

# 2. Gitで追跡されているファイルを確認
echo "=== Gitで追跡されているindex.phpを確認 ==="
git ls-files | grep -E '(^|/)index\.php$' || echo "  (見つかりませんでした)"
echo ""

# 3. バックアップ作成
echo "=== バックアップ作成 ==="
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

if [ -f "public/index.php" ]; then
    BACKUP_FILE="$BACKUP_DIR/index.php.backup.$TIMESTAMP"
    cp public/index.php "$BACKUP_FILE"
    echo "✓ バックアップ作成: $BACKUP_FILE"
    ls -lh "$BACKUP_FILE"
else
    echo "⚠ バックアップ対象のファイルが存在しません（新規作成）"
fi
echo ""

# 4. Gitからの復元を試行
echo "=== Gitからの復元を試行 ==="
git fetch origin
echo ""

# 方法1: git checkoutを試行
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

# 5. 復元後の確認
echo "=== 復元後の確認 ==="
if [ -f "public/index.php" ]; then
    echo "# ファイル情報:"
    ls -lh public/index.php
    echo ""
    echo "# ファイルの先頭10行:"
    head -10 public/index.php
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

# 6. OPcacheクリア
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
echo ""
echo "次のステップ:"
echo "1. ブラウザで https://resend.kimito-link.com にアクセス"
echo "2. https://resend.kimito-link.com/auth/logout でログアウト確認"
echo "3. ログアウト→再ログインでGmail送信権限を取得"
echo ""
