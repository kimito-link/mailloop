#!/bin/bash
# サーバー側で実行: Base64エンコードされたindex.phpを復元

set -e

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=========================================="
echo "  index.php Base64復元スクリプト"
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
if base64 -d "$TEMP_BASE64" > public/index.php.new 2>/dev/null; then
    echo "✓ Base64デコード成功"
    
    # ファイルサイズ確認
    FILE_SIZE=$(wc -c < public/index.php.new)
    echo "  ファイルサイズ: $FILE_SIZE bytes"
    
    if [ "$FILE_SIZE" -lt 10000 ]; then
        echo "⚠ 警告: ファイルサイズが異常に小さいです（正常な場合は約40KB以上）"
        echo "  Base64エンコードが正しく行われていない可能性があります"
        read -p "続行しますか？ (y/n): " answer
        if [ "$answer" != "y" ]; then
            rm -f public/index.php.new
            rm -f "$TEMP_BASE64"
            exit 1
        fi
    fi
    
    # ファイルを置き換え
    mv public/index.php.new public/index.php
    chmod 644 public/index.php
    echo "✓ ファイル復元完了"
else
    echo "✗ Base64デコード失敗"
    rm -f public/index.php.new
    rm -f "$TEMP_BASE64"
    exit 1
fi

# 一時ファイル削除
rm -f "$TEMP_BASE64"

# 確認
echo ""
echo "=== 復元後の確認 ==="
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

# PHP構文チェック（bin/phpが利用可能な場合）
echo "# PHP構文チェック:"
if [ -f "bin/php" ]; then
    chmod +x bin/php 2>/dev/null || true
    if bin/php -l public/index.php 2>&1; then
        echo "  ✓ 構文チェック成功（bin/php使用）"
    else
        echo "  ✗ 構文エラーが検出されました"
    fi
else
    echo "  ⚠ bin/phpが存在しないため、構文チェックをスキップ"
fi
echo ""

# OPcacheクリア
echo "=== OPcacheクリア ==="
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

echo "=========================================="
echo "  復旧完了"
echo "=========================================="
echo ""
echo "次のステップ:"
echo "1. ブラウザで https://resend.kimito-link.com にアクセスして動作確認"
echo "2. https://resend.kimito-link.com/auth/logout でログアウト確認"
echo "3. ログアウト→再ログインでGmail送信権限を取得"
echo ""
