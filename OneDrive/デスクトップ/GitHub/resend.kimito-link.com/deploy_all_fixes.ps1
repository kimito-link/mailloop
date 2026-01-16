# PowerShell用: すべての修正を自動実行する統合スクリプト
# Windows PowerShellで実行してください

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Page Not Found エラー修正 - 統合スクリプト" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# 設定
$SSH_HOST = "besttrust@sv16.sixcore.ne.jp"
$SSH_PORT = "10022"
$SSH_KEY = "$env:USERPROFILE\.ssh\id_rsa"
$SCRIPT_NAME = "fix_page_not_found.sh"
$REMOTE_SCRIPT_PATH = "~/kimito-link.com/_git/mailloop/$SCRIPT_NAME"

# 色付きメッセージ用の関数
function Print-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Print-Error {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

function Print-Info {
    param([string]$Message)
    Write-Host "ℹ️  $Message" -ForegroundColor Yellow
}

function Print-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host $Message -ForegroundColor Cyan
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host ""
}

# Phase 1: SSH接続の確認
Print-Step "Phase 1: SSH接続の確認"

if (-not (Test-Path $SSH_KEY)) {
    Print-Error "SSH鍵ファイルが見つかりません: $SSH_KEY"
    exit 1
}

Print-Success "SSH鍵ファイルを確認: $SSH_KEY"

# SSH接続テスト
Print-Info "SSH接続をテスト中..."
$sshTest = & ssh -i $SSH_KEY -p $SSH_PORT -o ConnectTimeout=10 -o BatchMode=yes $SSH_HOST "echo 'SSH接続成功'" 2>&1

if ($LASTEXITCODE -eq 0) {
    Print-Success "SSH接続に成功しました"
} else {
    Print-Error "SSH接続に失敗しました。手動で接続を確認してください。"
    Write-Host "接続コマンド: ssh -i `"$SSH_KEY`" -p $SSH_PORT $SSH_HOST" -ForegroundColor Gray
    exit 1
}

# Phase 2: サーバー側のSSH設定ファイルにgithub.com-kimitolinkの設定を追加
Print-Step "Phase 2: サーバー側のGit設定を修正"

Print-Info "サーバー側のSSH設定ファイルを確認中..."

$sshConfigResult = & ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST @"
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
"@

if ($sshConfigResult -eq "EXISTS") {
    Print-Success "github.com-kimitolinkの設定は既に存在します"
} elseif ($sshConfigResult -eq "ADDED") {
    Print-Success "github.com-kimitolinkの設定を追加しました"
} else {
    Print-Error "SSH設定ファイルの更新に失敗しました"
    exit 1
}

# Phase 3: fix_page_not_found.shをサーバーにアップロード
Print-Step "Phase 3: 修正スクリプトをサーバーにアップロード"

if (-not (Test-Path $SCRIPT_NAME)) {
    Print-Error "ローカルのスクリプトファイルが見つかりません: $SCRIPT_NAME"
    exit 1
}

Print-Info "スクリプトをアップロード中: $SCRIPT_NAME -> $SSH_HOST:$REMOTE_SCRIPT_PATH"

# リモートディレクトリを作成
& ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST "mkdir -p ~/kimito-link.com/_git/mailloop" | Out-Null

# スクリプトをアップロード
$scpResult = & scp -i $SSH_KEY -P $SSH_PORT $SCRIPT_NAME "${SSH_HOST}:${REMOTE_SCRIPT_PATH}" 2>&1

if ($LASTEXITCODE -eq 0) {
    Print-Success "スクリプトのアップロードに成功しました"
    
    # 実行権限を付与
    & ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST "chmod +x $REMOTE_SCRIPT_PATH" | Out-Null
    Print-Success "実行権限を付与しました"
} else {
    Print-Error "スクリプトのアップロードに失敗しました"
    Write-Host $scpResult -ForegroundColor Red
    exit 1
}

# Phase 4: サーバー側でfix_page_not_found.shを実行
Print-Step "Phase 4: サーバー側で修正スクリプトを実行"

Print-Info "修正スクリプトを実行中..."
Print-Info "（この処理には数秒かかる場合があります）"

$scriptResult = & ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST "cd ~/kimito-link.com/_git/mailloop && bash $SCRIPT_NAME" 2>&1

if ($LASTEXITCODE -eq 0) {
    Print-Success "修正スクリプトの実行が完了しました"
    Write-Host $scriptResult -ForegroundColor Gray
} else {
    Print-Error "修正スクリプトの実行中にエラーが発生しました"
    Write-Host $scriptResult -ForegroundColor Red
    Write-Host ""
    Print-Info "サーバーに接続して手動で実行してください:"
    Write-Host "  ssh -i `"$SSH_KEY`" -p $SSH_PORT $SSH_HOST" -ForegroundColor Gray
    Write-Host "  cd ~/kimito-link.com/_git/mailloop" -ForegroundColor Gray
    Write-Host "  bash $SCRIPT_NAME" -ForegroundColor Gray
    exit 1
}

# Phase 5: 動作確認
Print-Step "Phase 5: 動作確認"

Print-Info "テストファイルの確認中..."

$testResult = & ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST @"
TEST_FILE1=\"~/kimito-link.com/public_html/resend.kimito-link.com/_test.txt\"
TEST_FILE2=\"~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_test.txt\"

if [ -f `$TEST_FILE1 ]; then
    cat `$TEST_FILE1
    echo '|FILE1'
elif [ -f `$TEST_FILE2 ]; then
    cat `$TEST_FILE2
    echo '|FILE2'
else
    echo 'NOT_FOUND'
fi
"@

if ($testResult -ne "NOT_FOUND" -and $testResult) {
    $testContent = ($testResult -split '\|')[0]
    $testLocation = ($testResult -split '\|')[1]
    Print-Success "テストファイルが見つかりました ($testLocation)"
    Print-Info "テストファイルの内容: $testContent"
} else {
    Print-Info "テストファイルが見つかりませんでした（スクリプトが正常に実行されなかった可能性があります）"
}

# 最終確認
Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "✅ すべての処理が完了しました！" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "次のステップ:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. ブラウザで以下にアクセスして確認してください:" -ForegroundColor White
Write-Host "   https://resend.kimito-link.com/_test.txt" -ForegroundColor Cyan
Write-Host "   （テストファイルが表示されれば正しいドキュメントルートです）" -ForegroundColor Gray
Write-Host ""
Write-Host "2. デバッグモードで確認:" -ForegroundColor White
Write-Host "   https://resend.kimito-link.com/?dbg=raw" -ForegroundColor Cyan
Write-Host "   （'INDEX_PHP_HIT' が表示されれば index.php は正しく動作しています）" -ForegroundColor Gray
Write-Host ""
Write-Host "3. 通常アクセスで確認:" -ForegroundColor White
Write-Host "   https://resend.kimito-link.com/" -ForegroundColor Cyan
Write-Host "   （正常に表示されれば問題解決です）" -ForegroundColor Gray
Write-Host ""
