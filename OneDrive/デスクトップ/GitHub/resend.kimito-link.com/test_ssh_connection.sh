#!/bin/bash
# SSH接続をテストするスクリプト

echo "=========================================="
echo "SSH接続テスト"
echo "=========================================="
echo ""

# 設定ファイルの確認
echo "1. SSH設定ファイルの確認..."
if [ -f ~/.ssh/config ]; then
    echo "   ✅ SSH設定ファイルが見つかりました: ~/.ssh/config"
    if grep -q "Host xserver-besttrust" ~/.ssh/config; then
        echo "   ✅ 'xserver-besttrust'の設定が見つかりました"
    else
        echo "   ❌ 'xserver-besttrust'の設定が見つかりません"
    fi
else
    echo "   ❌ SSH設定ファイルが見つかりません"
    echo "   💡 ヒント: setup_gitbash_ssh.sh を実行してください"
fi
echo ""

# 鍵ファイルの確認
echo "2. SSH鍵ファイルの確認..."
if [ -f ~/.ssh/id_rsa ]; then
    echo "   ✅ 秘密鍵が見つかりました: ~/.ssh/id_rsa"
    ls -lh ~/.ssh/id_rsa
else
    echo "   ❌ 秘密鍵が見つかりません: ~/.ssh/id_rsa"
fi
echo ""

# 接続テスト（実際には接続しない）
echo "3. 接続情報の確認..."
echo "   ホスト名: sv16.sixcore.ne.jp"
echo "   ユーザー名: besttrust"
echo "   ポート: 10022"
echo "   鍵ファイル: ~/.ssh/id_rsa"
echo ""

echo "4. 接続コマンドの例:"
echo "   ssh xserver-besttrust"
echo "   または"
echo "   ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp"
echo ""

echo "=========================================="
echo "実際に接続する場合は、上記のコマンドを実行してください"
echo "=========================================="
