#!/bin/bash
# サーバー側でgit pullを実行してgmail_send.phpを更新するスクリプト

echo "=== gmail_send.php をサーバーに反映 ==="
echo ""

# SSH接続してgit pullを実行
ssh xserver-besttrust << 'EOF'
cd /home/besttrust/kimito-link.com/_git/mailloop
echo "現在のディレクトリ: $(pwd)"
echo ""
echo "git pullを実行中..."
git pull origin main
echo ""
echo "更新されたファイルを確認:"
git log -1 --name-only
echo ""
echo "gmail_send.phpの内容を確認（最初の20行）:"
head -20 app/services/gmail_send.php
echo ""
echo "=== デプロイ完了 ==="
EOF

echo ""
echo "サーバーへの反映が完了しました。"
