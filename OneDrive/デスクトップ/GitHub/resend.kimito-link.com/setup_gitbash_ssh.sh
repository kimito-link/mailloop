#!/bin/bash
# Git BashでSSH接続を設定するスクリプト
# このスクリプトはGit Bashで実行してください

# エラー時に即座に終了しないようにする（一部のコマンドは失敗しても続行）
set +e

echo "=========================================="
echo "Git Bash SSH接続設定スクリプト"
echo "=========================================="
echo ""

# WindowsのSSH設定ファイルのパス
WINDOWS_SSH_DIR="/c/Users/info/.ssh"
GITBASH_SSH_DIR="$HOME/.ssh"

echo "1. Git Bashのホームディレクトリを確認..."
echo "   HOME: $HOME"
echo "   PWD: $(pwd)"
echo ""

echo "2. WindowsのSSH設定ファイルを確認..."
if [ -f "$WINDOWS_SSH_DIR/config" ]; then
    echo "   ✅ WindowsのSSH設定ファイルが見つかりました: $WINDOWS_SSH_DIR/config"
else
    echo "   ❌ WindowsのSSH設定ファイルが見つかりません: $WINDOWS_SSH_DIR/config"
    exit 1
fi
echo ""

echo "3. Git Bashの.sshディレクトリを作成..."
mkdir -p "$GITBASH_SSH_DIR"
chmod 700 "$GITBASH_SSH_DIR"
echo "   ✅ ディレクトリを作成しました: $GITBASH_SSH_DIR"
echo ""

echo "4. SSH設定ファイルをシンボリックリンクで設定..."
if [ -L "$GITBASH_SSH_DIR/config" ]; then
    echo "   ℹ️  既存のシンボリックリンクを削除します..."
    rm "$GITBASH_SSH_DIR/config"
fi

if [ -f "$GITBASH_SSH_DIR/config" ]; then
    echo "   ⚠️  既存の設定ファイルをバックアップします..."
    cp "$GITBASH_SSH_DIR/config" "$GITBASH_SSH_DIR/config.backup.$(date +%Y%m%d_%H%M%S)"
fi

ln -s "$WINDOWS_SSH_DIR/config" "$GITBASH_SSH_DIR/config"
echo "   ✅ シンボリックリンクを作成しました"
echo ""

echo "5. SSH鍵ファイルをシンボリックリンクで設定..."
for key_file in id_rsa id_rsa.pub; do
    if [ -f "$WINDOWS_SSH_DIR/$key_file" ]; then
        if [ -L "$GITBASH_SSH_DIR/$key_file" ] || [ -f "$GITBASH_SSH_DIR/$key_file" ]; then
            echo "   ℹ️  既存の$key_fileを削除します..."
            rm -f "$GITBASH_SSH_DIR/$key_file"
        fi
        ln -s "$WINDOWS_SSH_DIR/$key_file" "$GITBASH_SSH_DIR/$key_file"
        echo "   ✅ $key_file のシンボリックリンクを作成しました"
        
        # 権限設定（Windowsではchmodが効かない場合があるため、エラーを無視）
        if [ "$key_file" = "id_rsa" ]; then
            chmod 600 "$WINDOWS_SSH_DIR/$key_file" 2>/dev/null || true
            chmod 600 "$GITBASH_SSH_DIR/$key_file" 2>/dev/null || true
        else
            chmod 644 "$WINDOWS_SSH_DIR/$key_file" 2>/dev/null || true
            chmod 644 "$GITBASH_SSH_DIR/$key_file" 2>/dev/null || true
        fi
    else
        echo "   ⚠️  $key_file が見つかりません: $WINDOWS_SSH_DIR/$key_file"
    fi
done
echo ""

echo "6. 設定の確認..."
if [ -f "$GITBASH_SSH_DIR/config" ]; then
    echo "   ✅ SSH設定ファイルが存在します"
    if grep -q "Host xserver-besttrust" "$GITBASH_SSH_DIR/config"; then
        echo "   ✅ 'xserver-besttrust'の設定が見つかりました"
        echo ""
        echo "   設定内容:"
        grep -A 6 "Host xserver-besttrust" "$GITBASH_SSH_DIR/config" | sed 's/^/      /'
    else
        echo "   ❌ 'xserver-besttrust'の設定が見つかりません"
    fi
else
    echo "   ❌ SSH設定ファイルが見つかりません"
fi
echo ""

echo "7. 最終確認..."
if [ -L "$GITBASH_SSH_DIR/config" ] || [ -f "$GITBASH_SSH_DIR/config" ]; then
    if [ -L "$GITBASH_SSH_DIR/id_rsa" ] || [ -f "$GITBASH_SSH_DIR/id_rsa" ]; then
        echo "   ✅ すべての設定が完了しました"
    else
        echo "   ⚠️  鍵ファイルの設定に問題がある可能性があります"
    fi
else
    echo "   ❌ 設定ファイルの作成に失敗しました"
    exit 1
fi
echo ""

echo "=========================================="
echo "設定完了！"
echo "=========================================="
echo ""
echo "次のコマンドでSSH接続をテストできます:"
echo "  ssh xserver-besttrust"
echo ""
echo "または、直接IPアドレスで接続:"
echo "  ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp"
echo ""
echo "接続できない場合は、以下を確認してください:"
echo "  - 鍵ファイルのパスフレーズが必要な場合があります"
echo "  - ファイアウォールやネットワーク設定を確認してください"
echo ""
