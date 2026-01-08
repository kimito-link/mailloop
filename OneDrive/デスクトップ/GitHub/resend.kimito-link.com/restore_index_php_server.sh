#!/bin/bash
# サーバー側で実行する index.php 復旧スクリプト（実行環境統一方針対応版）

set -e  # エラー時に停止

echo "=== index.php 復旧スクリプト ==="
echo ""

# 1. 現在のディレクトリを確認
REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
cd "$REPO_DIR" || exit 1
echo "✓ リポジトリディレクトリに移動: $REPO_DIR"
echo ""

# 2. 実行中のindex.phpの実体パスを特定
echo "=== Phase 1: 実行中のindex.phpの特定 ==="
echo ""

# 2-1. Gitリポジトリ内のファイル確認
echo "# Gitで追跡されているindex.php:"
git ls-files | grep -E '(^|/)index\.php$' || echo "  (見つかりませんでした)"
echo ""

# 2-2. 実体ファイルの確認
TARGET_FILE="$REPO_DIR/public/index.php"
echo "# 実体ファイル: $TARGET_FILE"
if [ -f "$TARGET_FILE" ]; then
    echo "  ✓ ファイルが存在します"
    ls -lh "$TARGET_FILE"
else
    echo "  ✗ ファイルが存在しません"
fi
echo ""

# 2-3. シンボリックリンクの確認
SYMLINK_PATH="/home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com"
echo "# シンボリックリンクの可能性があるパス: $SYMLINK_PATH"
if [ -d "$SYMLINK_PATH" ]; then
    if [ -L "$SYMLINK_PATH/index.php" ]; then
        REAL_FILE=$(readlink -f "$SYMLINK_PATH/index.php" 2>/dev/null || realpath "$SYMLINK_PATH/index.php" 2>/dev/null)
        echo "  ✓ シンボリックリンクが見つかりました"
        echo "  実体: $REAL_FILE"
    elif [ -f "$SYMLINK_PATH/index.php" ]; then
        echo "  ✓ 通常ファイルが見つかりました"
        ls -lh "$SYMLINK_PATH/index.php"
    else
        echo "  - index.phpが見つかりませんでした"
    fi
else
    echo "  - ディレクトリが存在しません"
fi
echo ""

# 3. バックアップ作成
echo "=== Phase 2: バックアップ作成 ==="
echo ""

BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

if [ -f "$TARGET_FILE" ]; then
    BACKUP_FILE="$BACKUP_DIR/index.php.backup.$TIMESTAMP"
    cp "$TARGET_FILE" "$BACKUP_FILE"
    echo "✓ バックアップ作成: $BACKUP_FILE"
    ls -lh "$BACKUP_FILE"
else
    echo "⚠ バックアップ対象のファイルが存在しません"
fi
echo ""

# 4. Gitから最新版を取得
echo "=== Phase 3: Gitから最新版を取得 ==="
echo ""

# 4-1. 現在の状態を確認
echo "# 現在のGit状態:"
git status --short public/index.php || echo "  (git statusでエラー)"
echo ""

# 4-2. リモートから最新版を取得
echo "# リモートから最新版を取得:"
git fetch origin
echo ""

# 4-3. ファイルを復元
echo "# public/index.phpを復元:"
if git checkout origin/main -- public/index.php 2>/dev/null; then
    echo "  ✓ git checkoutで復元成功"
elif git checkout HEAD -- public/index.php 2>/dev/null; then
    echo "  ✓ git checkout HEADで復元成功"
else
    echo "  ✗ Gitからの復元に失敗"
    echo ""
    echo "  → 手動でファイルをアップロードする必要があります"
    echo "  → upload_index_php.sh を使用するか、SCPで直接アップロードしてください"
    exit 1
fi
echo ""

# 5. 復元後の確認
echo "=== Phase 4: 復元後の確認 ==="
echo ""

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
        grep -A 5 "route('GET', '/auth/logout'" "$TARGET_FILE" | head -6
    else
        echo "  ✗ GETログアウトルートが見つかりませんでした"
    fi
    echo ""
    
    # PHP構文チェック（bin/phpが利用可能な場合）
    echo "# PHP構文チェック:"
    if [ -f "bin/php" ]; then
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

echo "=== 復旧完了 ==="
echo ""
echo "次のステップ:"
echo "1. ブラウザで https://resend.kimito-link.com にアクセスして動作確認"
echo "2. https://resend.kimito-link.com/auth/logout にアクセスしてログアウト機能を確認"
echo "3. ログアウト→再ログインでGmail送信権限を取得"
echo "4. メール送信機能をテスト"
