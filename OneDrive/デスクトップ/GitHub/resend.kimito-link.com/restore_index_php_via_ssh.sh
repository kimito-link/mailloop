#!/bin/bash
# SSH経由でBase64からindex.phpを復元するスクリプト

set -e

# 設定
SSH_HOST="xserver-besttrust"
REMOTE_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
BASE64_FILE="index_php_base64.txt"

# カラー出力
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Base64からindex.phpを復元 ===${NC}"

# 1. ローカルのBase64ファイルを確認
if [ ! -f "$BASE64_FILE" ]; then
    echo -e "${RED}エラー: $BASE64_FILE が見つかりません${NC}"
    exit 1
fi

echo -e "${GREEN}✓ $BASE64_FILE を読み込み中...${NC}"

# 2. Base64ファイルのサイズ確認
BASE64_SIZE=$(wc -c < "$BASE64_FILE" | tr -d ' ')
if [ "$BASE64_SIZE" -eq 0 ]; then
    echo -e "${RED}エラー: Base64内容が空です${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Base64内容を確認しました (${BASE64_SIZE}バイト)${NC}"

# 3. SSH経由でサーバー側に送信して復元
echo -e "${YELLOW}サーバー側で復元を実行中...${NC}"

# 一時ファイル名を生成（ローカルとリモート）
# Windows Git Bash対応: プロジェクトディレクトリ内に一時ファイルを作成
TEMP_BASE64_LOCAL="./index_php_base64_temp_$$.txt"
TEMP_BASE64_REMOTE="/tmp/index_php_base64_$$.txt"

# Base64文字列を改行なしで一時ファイルに保存
cat "$BASE64_FILE" | tr -d '\n\r' > "$TEMP_BASE64_LOCAL"

# 一時ファイルをサーバーに転送
echo -e "${GREEN}✓ Base64ファイルをサーバーに転送中...${NC}"
scp "$TEMP_BASE64_LOCAL" "$SSH_HOST:$TEMP_BASE64_REMOTE"

# サーバー側で復元処理を実行
ssh "$SSH_HOST" bash << EOF
set -e
REMOTE_DIR="$REMOTE_DIR"
TEMP_BASE64="$TEMP_BASE64_REMOTE"
cd "\$REMOTE_DIR"

# バックアップ
if [ -f "public/index.php" ]; then
    BACKUP_FILE="public/index.php.bak_\$(date +%Y%m%d_%H%M%S)"
    cp public/index.php "\$BACKUP_FILE"
    echo "✓ バックアップ作成: \$BACKUP_FILE"
else
    echo "⚠ public/index.php が見つかりません（新規作成）"
fi

# Base64デコードして復元
if [ -f "\$TEMP_BASE64" ]; then
    base64 -d < "\$TEMP_BASE64" > public/index.php
    rm -f "\$TEMP_BASE64"
else
    echo "✗ エラー: 一時ファイルが見つかりません"
    exit 1
fi

# 確認
if [ -f "public/index.php" ]; then
    echo "✓ 復元完了"
    echo "ファイルサイズ: \$(ls -lh public/index.php | awk '{print \$5}')"
    echo "先頭5行:"
    head -5 public/index.php
else
    echo "✗ 復元失敗: public/index.php が作成されませんでした"
    exit 1
fi

# OPcacheクリア
if [ -f "public/clear_cache.php" ]; then
    # PHP実行コマンドを決定（bin/phpラッパー優先、なければPHP 8.3のフルパスを探す）
    PHP_CMD=""
    if [ -f "\$REMOTE_DIR/bin/php" ] && [ -x "\$REMOTE_DIR/bin/php" ]; then
        PHP_CMD="\$REMOTE_DIR/bin/php"
    else
        # PHP 8.3のフルパスを探す
        for candidate in "/usr/local/bin/php8.3" "/usr/bin/php8.3" "/opt/php83/bin/php" "/usr/local/bin/php83" "/usr/bin/php83"; do
            if [ -x "\$candidate" ]; then
                VERSION_OUTPUT=\$("\$candidate" -v 2>&1 | head -1)
                if echo "\$VERSION_OUTPUT" | grep -qE "PHP 8\.[0-9]"; then
                    PHP_CMD="\$candidate"
                    break
                fi
            fi
        done
    fi
    
    if [ -n "\$PHP_CMD" ]; then
        "\$PHP_CMD" public/clear_cache.php 2>&1 || echo "⚠ OPcacheクリアに失敗（無視）"
    else
        echo "⚠ PHP 8.3が見つからないため、OPcacheクリアをスキップします"
    fi
else
    echo "⚠ public/clear_cache.php が見つかりません"
fi

echo "復元処理完了"
EOF

# ローカルの一時ファイルを削除
rm -f "$TEMP_BASE64_LOCAL"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ 復元が正常に完了しました${NC}"
else
    echo -e "${RED}✗ 復元中にエラーが発生しました${NC}"
    exit 1
fi
