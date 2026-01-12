#!/bin/bash
# サーバー側で実行: Base64エンコードされたindex.phpを復元するスクリプト
# 
# 使用方法:
#   1. このスクリプトをサーバーにアップロード
#   2. index_php_base64.txt の内容をこのスクリプトに埋め込む
#   3. サーバー側で実行: bash restore_index_php_from_base64.sh

set -e

REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
TARGET_FILE="$REPO_DIR/public/index.php"
BACKUP_DIR="/home/besttrust/kimito-link.com/_backup"

echo "=== index.php Base64復元スクリプト ==="
echo ""

# ディレクトリに移動
cd "$REPO_DIR" || exit 1
echo "✓ リポジトリディレクトリに移動: $REPO_DIR"
echo ""

# バックアップ作成
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

# Base64デコード
echo "=== Base64デコード ==="
echo "Base64エンコードされた内容を貼り付けてください（EOFと入力して終了）:"
echo ""

# 一時ファイルにBase64データを保存
TEMP_BASE64="/tmp/index_php_base64_$$.txt"
cat > "$TEMP_BASE64" << 'EOFMARKER'
# ここにBase64エンコードされた内容を貼り付け
EOFMARKER

echo "Base64データを貼り付けてください（EOFと入力して終了）:"
cat >> "$TEMP_BASE64"

# Base64デコード
echo ""
echo "=== ファイル復元 ==="
if base64 -d "$TEMP_BASE64" > "$TARGET_FILE.new" 2>/dev/null; then
    echo "✓ Base64デコード成功"
    
    # ファイルサイズ確認
    FILE_SIZE=$(wc -c < "$TARGET_FILE.new")
    echo "  ファイルサイズ: $FILE_SIZE bytes"
    
    # ファイルを置き換え
    mv "$TARGET_FILE.new" "$TARGET_FILE"
    chmod 644 "$TARGET_FILE"
    echo "✓ ファイル復元完了"
else
    echo "✗ Base64デコード失敗"
    rm -f "$TARGET_FILE.new"
    rm -f "$TEMP_BASE64"
    exit 1
fi

# 一時ファイル削除
rm -f "$TEMP_BASE64"

# 確認
echo ""
echo "=== 復元後の確認 ==="
ls -lh "$TARGET_FILE"
echo ""
echo "# ファイルの先頭10行:"
head -10 "$TARGET_FILE"
echo ""
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
fi

echo ""
echo "=== 復元完了 ==="
