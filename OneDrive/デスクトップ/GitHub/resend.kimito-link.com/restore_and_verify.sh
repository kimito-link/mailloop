#!/bin/bash
# サーバー側で実行: index.php復旧と動作確認の完全スクリプト

set -e

REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
TARGET_FILE="$REPO_DIR/public/index.php"
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"

echo "=========================================="
echo "  index.php 復旧と動作確認スクリプト"
echo "=========================================="
echo ""

# 1. ディレクトリに移動
cd "$REPO_DIR" || exit 1
echo "✓ リポジトリディレクトリに移動: $REPO_DIR"
echo ""

# 2. バックアップ作成
echo "=== Phase 1: バックアップ作成 ==="
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

# 3. Gitから最新版を取得
echo "=== Phase 2: Gitから最新版を取得 ==="
git fetch origin
echo ""

# 4. ファイルを復元
echo "=== Phase 3: ファイル復元 ==="
if git checkout origin/main -- public/index.php 2>/dev/null; then
    echo "✓ git checkoutで復元成功"
elif git checkout HEAD -- public/index.php 2>/dev/null; then
    echo "✓ git checkout HEADで復元成功"
else
    echo "✗ Gitからの復元に失敗"
    echo ""
    echo "→ 手動でファイルをアップロードする必要があります"
    echo "→ SERVER_RESTORE_COMMANDS.md の「方法2: Base64エンコード方式」を参照してください"
    exit 1
fi
echo ""

# 5. 復元後の確認
echo "=== Phase 4: 復元後の確認 ==="
if [ -f "$TARGET_FILE" ]; then
    echo "# ファイル情報:"
    ls -lh "$TARGET_FILE"
    echo ""
    echo "# ファイルの先頭10行:"
    head -10 "$TARGET_FILE"
    echo ""
    echo "# ファイルの行数:"
    wc -l "$TARGET_FILE"
    echo ""
    
    # GETログアウトルートの確認
    echo "# GETログアウトルートの確認:"
    if grep -q "route('GET', '/auth/logout'" "$TARGET_FILE"; then
        echo "  ✓ GETログアウトルートが見つかりました"
        echo ""
        echo "  該当箇所:"
        grep -A 5 "route('GET', '/auth/logout'" "$TARGET_FILE" | head -6
    else
        echo "  ✗ GETログアウトルートが見つかりませんでした"
        echo "  → ファイルが正しく復元されていない可能性があります"
    fi
    echo ""
    
    # PHP構文チェック（bin/phpが利用可能な場合）
    echo "# PHP構文チェック:"
    if [ -f "bin/php" ]; then
        chmod +x bin/php 2>/dev/null || true
        if bin/php -l "$TARGET_FILE" 2>&1; then
            echo "  ✓ 構文チェック成功（bin/php使用）"
        else
            echo "  ✗ 構文エラーが検出されました"
        fi
    else
        echo "  ⚠ bin/phpが存在しないため、構文チェックをスキップ"
        echo "  （CLIのPHP 5.4では ?? 演算子がエラーになるため）"
    fi
    echo ""
else
    echo "✗ ファイルが存在しません"
    exit 1
fi

# 6. OPcacheクリア
echo "=== Phase 5: OPcacheクリア ==="
if [ -f "bin/php" ]; then
    chmod +x bin/php 2>/dev/null || true
    if bin/php public/clear_cache.php 2>&1; then
        echo "✓ OPcacheクリア成功（bin/php使用）"
    else
        echo "⚠ OPcacheクリアに失敗（bin/php使用）"
    fi
elif [ -f "public/clear_cache.php" ]; then
    if php public/clear_cache.php 2>&1; then
        echo "✓ OPcacheクリア成功（php使用）"
    else
        echo "⚠ OPcacheクリアに失敗（php使用）"
    fi
else
    echo "⚠ clear_cache.phpが見つかりません"
fi
echo ""

# 7. 次のステップの案内
echo "=========================================="
echo "  復旧完了"
echo "=========================================="
echo ""
echo "次のステップ:"
echo ""
echo "1. ブラウザで以下にアクセスして動作確認:"
echo "   - https://resend.kimito-link.com"
echo "   - https://resend.kimito-link.com/auth/logout"
echo ""
echo "2. ログアウト→再ログインでGmail送信権限を取得"
echo ""
echo "3. メール送信機能をテスト（連想配列形式の宛先）"
echo ""
echo "=========================================="
