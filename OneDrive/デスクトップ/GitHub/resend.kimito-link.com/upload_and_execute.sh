#!/bin/bash
# ローカルからサーバーにスクリプトをアップロードして実行するスクリプト
# Git Bashで実行してください

set -e

echo "=== サーバーへのスクリプトアップロードと実行 ==="
echo ""

# SSH接続情報
SSH_HOST="besttrust@sv16.sixcore.ne.jp"
SSH_PORT="10022"
SSH_KEY="$HOME/.ssh/id_rsa"
REMOTE_DIR="~/kimito-link.com/_git/mailloop"

# ファイルの存在確認
if [ ! -f "fix_page_not_found.sh" ]; then
    echo "❌ エラー: fix_page_not_found.shが見つかりません"
    exit 1
fi

if [ ! -f "server_setup_commands.sh" ]; then
    echo "❌ エラー: server_setup_commands.shが見つかりません"
    exit 1
fi

echo "✓ 必要なファイルを確認しました"
echo ""

# Phase 1: サーバーに接続テスト
echo "=== Phase 1: SSH接続テスト ==="
echo "サーバーへの接続をテスト中..."
if ssh -i "$SSH_KEY" -p "$SSH_PORT" -o ConnectTimeout=10 "$SSH_HOST" "echo 'SSH接続成功'" 2>/dev/null; then
    echo "✓ SSH接続成功"
else
    echo "❌ SSH接続に失敗しました"
    echo "手動で接続してください: ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST"
    exit 1
fi
echo ""

# Phase 2: サーバー側でディレクトリを作成
echo "=== Phase 2: サーバー側ディレクトリの準備 ==="
ssh -i "$SSH_KEY" -p "$SSH_PORT" "$SSH_HOST" << 'REMOTE_EOF'
mkdir -p ~/kimito-link.com/_git/mailloop
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "✓ ディレクトリを準備しました"
REMOTE_EOF
echo ""

# Phase 3: スクリプトをアップロード
echo "=== Phase 3: スクリプトのアップロード ==="
echo "fix_page_not_found.shをアップロード中..."
scp -i "$SSH_KEY" -P "$SSH_PORT" "fix_page_not_found.sh" "$SSH_HOST:$REMOTE_DIR/"
echo "✓ fix_page_not_found.shをアップロードしました"

echo "server_setup_commands.shをアップロード中..."
scp -i "$SSH_KEY" -P "$SSH_PORT" "server_setup_commands.sh" "$SSH_HOST:~/"
echo "✓ server_setup_commands.shをアップロードしました"
echo ""

# Phase 4: サーバー側でスクリプトを実行
echo "=== Phase 4: サーバー側でスクリプトを実行 ==="
echo "統合セットアップスクリプトを実行中..."
ssh -i "$SSH_KEY" -p "$SSH_PORT" "$SSH_HOST" << 'REMOTE_EOF'
chmod +x ~/server_setup_commands.sh
bash ~/server_setup_commands.sh
REMOTE_EOF

echo ""
echo "=== アップロードと実行完了 ==="
echo ""
echo "次のステップ:"
echo "1. ブラウザで https://resend.kimito-link.com/_test.txt にアクセス"
echo "2. デバッグモード: https://resend.kimito-link.com/?dbg=raw"
echo "3. 通常アクセス: https://resend.kimito-link.com/"
