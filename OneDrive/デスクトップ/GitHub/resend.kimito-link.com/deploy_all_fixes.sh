#!/bin/bash
# すべての修正を自動実行する統合スクリプト
# Git Bashで実行してください

set -e

echo "=========================================="
echo "Page Not Found エラー修正 - 統合スクリプト"
echo "=========================================="
echo ""

# 設定
SSH_HOST="besttrust@sv16.sixcore.ne.jp"
SSH_PORT="10022"
SSH_KEY="/c/Users/info/.ssh/id_rsa"
SSH_OPTIONS="-i $SSH_KEY -p $SSH_PORT"
SCRIPT_NAME="fix_page_not_found.sh"
REMOTE_SCRIPT_PATH="~/kimito-link.com/_git/mailloop/$SCRIPT_NAME"

# 色付きメッセージ用の関数
print_success() {
    echo "✅ $1"
}

print_error() {
    echo "❌ $1"
}

print_info() {
    echo "ℹ️  $1"
}

print_step() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
    echo ""
}

# Phase 1: SSH接続の確認
print_step "Phase 1: SSH接続の確認"

if [ ! -f "$SSH_KEY" ]; then
    print_error "SSH鍵ファイルが見つかりません: $SSH_KEY"
    exit 1
fi

print_success "SSH鍵ファイルを確認: $SSH_KEY"

# SSH接続テスト
print_info "SSH接続をテスト中..."
if ssh $SSH_OPTIONS -o ConnectTimeout=10 -o BatchMode=yes $SSH_HOST "echo 'SSH接続成功'" 2>/dev/null; then
    print_success "SSH接続に成功しました"
else
    print_error "SSH接続に失敗しました。手動で接続を確認してください。"
    print_info "接続コマンド: ssh $SSH_OPTIONS $SSH_HOST"
    exit 1
fi

# Phase 2: サーバー側のSSH設定ファイルにgithub.com-kimitolinkの設定を追加
print_step "Phase 2: サーバー側のGit設定を修正"

print_info "サーバー側のSSH設定ファイルを確認中..."

SSH_CONFIG_ADDED=$(ssh $SSH_OPTIONS $SSH_HOST "
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    
    if [ ! -f ~/.ssh/config ]; then
        touch ~/.ssh/config
        chmod 600 ~/.ssh/config
    fi
    
    if grep -q 'Host github.com-kimitolink' ~/.ssh/config; then
        echo 'EXISTS'
    else
        cat >> ~/.ssh/config << 'EOF'

# GitHub接続用設定（プロジェクト用）
Host github.com-kimitolink
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_kimitolink
  IdentitiesOnly yes
EOF
        chmod 600 ~/.ssh/config
        echo 'ADDED'
    fi
")

if [ "$SSH_CONFIG_ADDED" = "EXISTS" ]; then
    print_success "github.com-kimitolinkの設定は既に存在します"
elif [ "$SSH_CONFIG_ADDED" = "ADDED" ]; then
    print_success "github.com-kimitolinkの設定を追加しました"
else
    print_error "SSH設定ファイルの更新に失敗しました"
    exit 1
fi

# Phase 3: fix_page_not_found.shをサーバーにアップロード
print_step "Phase 3: 修正スクリプトをサーバーにアップロード"

if [ ! -f "$SCRIPT_NAME" ]; then
    print_error "ローカルのスクリプトファイルが見つかりません: $SCRIPT_NAME"
    exit 1
fi

print_info "スクリプトをアップロード中: $SCRIPT_NAME -> $SSH_HOST:$REMOTE_SCRIPT_PATH"

# リモートディレクトリを作成
ssh $SSH_OPTIONS $SSH_HOST "mkdir -p ~/kimito-link.com/_git/mailloop"

# スクリプトをアップロード
if scp $SSH_OPTIONS "$SCRIPT_NAME" "$SSH_HOST:$REMOTE_SCRIPT_PATH"; then
    print_success "スクリプトのアップロードに成功しました"
    
    # 実行権限を付与
    ssh $SSH_OPTIONS $SSH_HOST "chmod +x $REMOTE_SCRIPT_PATH"
    print_success "実行権限を付与しました"
else
    print_error "スクリプトのアップロードに失敗しました"
    exit 1
fi

# Phase 4: サーバー側でfix_page_not_found.shを実行
print_step "Phase 4: サーバー側で修正スクリプトを実行"

print_info "修正スクリプトを実行中..."
print_info "（この処理には数秒かかる場合があります）"

# スクリプトを実行（エラーが発生しても続行）
if ssh $SSH_OPTIONS $SSH_HOST "cd ~/kimito-link.com/_git/mailloop && bash $SCRIPT_NAME"; then
    print_success "修正スクリプトの実行が完了しました"
else
    print_error "修正スクリプトの実行中にエラーが発生しました"
    print_info "サーバーに接続して手動で実行してください:"
    print_info "  ssh $SSH_OPTIONS $SSH_HOST"
    print_info "  cd ~/kimito-link.com/_git/mailloop"
    print_info "  bash $SCRIPT_NAME"
    exit 1
fi

# Phase 5: 動作確認
print_step "Phase 5: 動作確認"

print_info "テストファイルの確認中..."

TEST_RESULT=$(ssh $SSH_OPTIONS $SSH_HOST "
    TEST_FILE1=\"~/kimito-link.com/public_html/resend.kimito-link.com/_test.txt\"
    TEST_FILE2=\"~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_test.txt\"
    
    if [ -f \$TEST_FILE1 ]; then
        cat \$TEST_FILE1
        echo '|FILE1'
    elif [ -f \$TEST_FILE2 ]; then
        cat \$TEST_FILE2
        echo '|FILE2'
    else
        echo 'NOT_FOUND'
    fi
")

if [ "$TEST_RESULT" != "NOT_FOUND" ] && [ -n "$TEST_RESULT" ]; then
    TEST_CONTENT=$(echo "$TEST_RESULT" | cut -d'|' -f1)
    TEST_LOCATION=$(echo "$TEST_RESULT" | cut -d'|' -f2)
    print_success "テストファイルが見つかりました ($TEST_LOCATION)"
    print_info "テストファイルの内容: $TEST_CONTENT"
else
    print_info "テストファイルが見つかりませんでした（スクリプトが正常に実行されなかった可能性があります）"
fi

# 最終確認
echo ""
echo "=========================================="
echo "✅ すべての処理が完了しました！"
echo "=========================================="
echo ""
echo "次のステップ:"
echo ""
echo "1. ブラウザで以下にアクセスして確認してください:"
echo "   https://resend.kimito-link.com/_test.txt"
echo "   （テストファイルが表示されれば正しいドキュメントルートです）"
echo ""
echo "2. デバッグモードで確認:"
echo "   https://resend.kimito-link.com/?dbg=raw"
echo "   （'INDEX_PHP_HIT' が表示されれば index.php は正しく動作しています）"
echo ""
echo "3. 通常アクセスで確認:"
echo "   https://resend.kimito-link.com/"
echo "   （正常に表示されれば問題解決です）"
echo ""
echo "4. 確認後、テストファイルを削除してください:"
if [ "$TEST_LOCATION" = "FILE1" ]; then
    echo "   ssh $SSH_OPTIONS $SSH_HOST 'rm ~/kimito-link.com/public_html/resend.kimito-link.com/_test.txt'"
elif [ "$TEST_LOCATION" = "FILE2" ]; then
    echo "   ssh $SSH_OPTIONS $SSH_HOST 'rm ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_test.txt'"
fi
echo ""
