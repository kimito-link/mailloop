#!/bin/bash
# ローカルの正しい public/index.php をサーバーにアップロードするスクリプト
# 
# 使用方法:
#   1. このスクリプトをローカルで実行（PowerShellまたはGit Bash）
#   2. または、サーバー側で実行する場合は、Base64エンコードされた内容を使用

set -e

# 設定
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCAL_FILE="$REPO_DIR/public/index.php"
REMOTE_USER="besttrust"
REMOTE_HOST="sv16.sixcore.ne.jp"
REMOTE_PORT="10022"
REMOTE_PATH="/home/besttrust/kimito-link.com/_git/mailloop/public/index.php"
SSH_KEY="$HOME/.ssh/id_rsa"

echo "=== public/index.php アップロードスクリプト ==="
echo ""

# ローカルファイルの確認
if [ ! -f "$LOCAL_FILE" ]; then
    echo "エラー: ローカルファイルが見つかりません: $LOCAL_FILE" >&2
    exit 1
fi

echo "✓ ローカルファイルを確認: $LOCAL_FILE"
echo "  ファイルサイズ: $(wc -c < "$LOCAL_FILE") bytes"
echo "  行数: $(wc -l < "$LOCAL_FILE")"
echo ""

# GETログアウトルートの確認
if grep -q "route('GET', '/auth/logout'" "$LOCAL_FILE"; then
    echo "✓ GETログアウトルートが含まれています"
else
    echo "⚠ 警告: GETログアウトルートが見つかりませんでした"
fi
echo ""

# アップロード方法の選択
echo "アップロード方法を選択してください:"
echo "1) SCP経由（推奨・最速）"
echo "2) Base64エンコードしてサーバー側で復元"
echo ""
read -p "選択 (1 or 2): " method

case "$method" in
    1)
        echo ""
        echo "=== SCP経由でアップロード ==="
        
        # SSH鍵の確認
        if [ ! -f "$SSH_KEY" ]; then
            echo "⚠ 警告: SSH鍵が見つかりません: $SSH_KEY"
            echo "別の鍵パスを指定するか、パスワード認証を使用してください"
            read -p "SSH鍵のパス (Enterでスキップ): " custom_key
            if [ -n "$custom_key" ]; then
                SSH_KEY="$custom_key"
            fi
        fi
        
        # SCPコマンドの構築
        if [ -f "$SSH_KEY" ]; then
            SCP_CMD="scp -i \"$SSH_KEY\" -P $REMOTE_PORT \"$LOCAL_FILE\" $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
        else
            SCP_CMD="scp -P $REMOTE_PORT \"$LOCAL_FILE\" $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
        fi
        
        echo "実行コマンド:"
        echo "  $SCP_CMD"
        echo ""
        echo "⚠ 注意: Windows環境では、このコマンドを手動で実行する必要があります"
        echo ""
        echo "PowerShellの場合:"
        echo "  scp -i C:\\Users\\info\\.ssh\\id_rsa -P 10022 \"$LOCAL_FILE\" $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
        echo ""
        echo "または、Git Bashの場合:"
        echo "  $SCP_CMD"
        ;;
    2)
        echo ""
        echo "=== Base64エンコード方式 ==="
        echo ""
        echo "以下のコマンドをサーバー側で実行してください:"
        echo ""
        echo "--- サーバー側で実行するコマンド（開始） ---"
        echo ""
        echo "# バックアップ作成"
        echo "cd /home/besttrust/kimito-link.com/_git/mailloop"
        echo "BACKUP_DIR=\"/home/besttrust/kimito-link.com/_backup\""
        echo "mkdir -p \"\$BACKUP_DIR\""
        echo "cp public/index.php \"\$BACKUP_DIR/index.php.backup.\$(date +%Y%m%d_%H%M%S)\""
        echo ""
        echo "# Base64デコードしてファイルに書き込み"
        echo "cat > public/index.php.new << 'EOFBASE64'"
        
        # Base64エンコード
        base64 "$LOCAL_FILE"
        
        echo "EOFBASE64"
        echo ""
        echo "# ファイルを置き換え"
        echo "mv public/index.php.new public/index.php"
        echo "chmod 644 public/index.php"
        echo ""
        echo "# 確認"
        echo "ls -lh public/index.php"
        echo "head -10 public/index.php"
        echo "grep -A 5 \"route('GET', '/auth/logout'\" public/index.php || echo 'GETログアウトルートが見つかりません'"
        echo ""
        echo "--- サーバー側で実行するコマンド（終了） ---"
        ;;
    *)
        echo "無効な選択です"
        exit 1
        ;;
esac

echo ""
echo "=== 完了 ==="
