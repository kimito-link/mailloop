#!/bin/bash
# サーバー側で実行: index.phpを直接復元する緊急スクリプト

set -e

REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
TARGET_FILE="$REPO_DIR/public/index.php"
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"

echo "=========================================="
echo "  index.php 緊急復元スクリプト"
echo "=========================================="
echo ""

# 1. ディレクトリに移動
cd "$REPO_DIR" || exit 1
echo "✓ リポジトリディレクトリに移動: $REPO_DIR"
echo ""

# 2. Gitで追跡されているファイルを確認
echo "=== Gitで追跡されているindex.phpを確認 ==="
git ls-files | grep -E '(^|/)index\.php$' || echo "  (見つかりませんでした)"
echo ""

# 3. バックアップ作成
echo "=== バックアップ作成 ==="
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

if [ -f "$TARGET_FILE" ]; then
    BACKUP_FILE="$BACKUP_DIR/index.php.backup.$TIMESTAMP"
    cp "$TARGET_FILE" "$BACKUP_FILE"
    echo "✓ バックアップ作成: $BACKUP_FILE"
    ls -lh "$BACKUP_FILE"
else
    echo "⚠ バックアップ対象のファイルが存在しません（新規作成）"
fi
echo ""

# 4. 現在のファイルの状態を確認
echo "=== 現在のファイル状態 ==="
if [ -f "$TARGET_FILE" ]; then
    echo "ファイルサイズ: $(wc -c < "$TARGET_FILE") bytes"
    echo "行数: $(wc -l < "$TARGET_FILE")"
    echo ""
    echo "GETログアウトルートの確認:"
    if grep -q "route('GET', '/auth/logout'" "$TARGET_FILE"; then
        echo "  ✓ GETログアウトルートが見つかりました"
    else
        echo "  ✗ GETログアウトルートが見つかりませんでした"
    fi
else
    echo "✗ ファイルが存在しません"
fi
echo ""

# 5. Gitからの復元を試行
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
    echo "→ 以下のいずれかの方法で復元してください:"
    echo ""
    echo "【方法A】SCPで直接アップロード（推奨）"
    echo "  ローカル（PowerShell）で実行:"
    echo "  scp -i C:\\Users\\info\\.ssh\\id_rsa -P 10022 \\"
    echo "    \"C:\\Users\\info\\OneDrive\\デスクトップ\\GitHub\\resend.kimito-link.com\\public\\index.php\" \\"
    echo "    besttrust@sv16.sixcore.ne.jp:/home/besttrust/kimito-link.com/_git/mailloop/public/index.php"
    echo ""
    echo "【方法B】Base64エンコード方式"
    echo "  1. ローカルでBase64エンコード:"
    echo "     PowerShell: [Convert]::ToBase64String([System.IO.File]::ReadAllBytes('public\\index.php')) | Out-File -Encoding utf8 index_php_base64.txt"
    echo "  2. サーバー側で以下を実行（Base64内容を貼り付け）:"
    echo "     base64 -d > public/index.php.new << 'EOFBASE64'"
    echo "     （ここにBase64エンコードされた内容を貼り付け）"
    echo "     EOFBASE64"
    echo "     mv public/index.php.new public/index.php"
    echo "     chmod 644 public/index.php"
    echo ""
    exit 1
fi
echo ""

# 6. 復元後の確認
echo "=== 復元後の確認 ==="
if [ -f "$TARGET_FILE" ]; then
    echo "# ファイル情報:"
    ls -lh "$TARGET_FILE"
    echo ""
    echo "# GETログアウトルートの確認:"
    if grep -q "route('GET', '/auth/logout'" "$TARGET_FILE"; then
        echo "  ✓ GETログアウトルートが見つかりました"
        echo ""
        echo "  該当箇所:"
        grep -A 5 "route('GET', '/auth/logout'" "$TARGET_FILE" | head -6
    else
        echo "  ✗ GETログアウトルートが見つかりませんでした"
    fi
    echo ""
else
    echo "✗ ファイルが存在しません"
    exit 1
fi

# 7. OPcacheクリア
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
