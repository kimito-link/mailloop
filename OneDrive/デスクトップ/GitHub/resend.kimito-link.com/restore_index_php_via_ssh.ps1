# PowerShell版: SSH経由でBase64からindex.phpを復元するスクリプト

# 設定
$SSH_HOST = "xserver-besttrust"
$REMOTE_DIR = "/home/besttrust/kimito-link.com/_git/mailloop"
$BASE64_FILE = "index_php_base64.txt"

Write-Host "=== Base64からindex.phpを復元 ===" -ForegroundColor Yellow

# 1. ローカルのBase64ファイルを確認
if (-not (Test-Path $BASE64_FILE)) {
    Write-Host "エラー: $BASE64_FILE が見つかりません" -ForegroundColor Red
    exit 1
}

Write-Host "✓ $BASE64_FILE を読み込み中..." -ForegroundColor Green

# 2. Base64ファイルの内容を読み込み
$base64Content = Get-Content -Path $BASE64_FILE -Raw
$base64Content = $base64Content -replace '\r?\n',''

if ([string]::IsNullOrWhiteSpace($base64Content)) {
    Write-Host "エラー: Base64内容が空です" -ForegroundColor Red
    exit 1
}

$base64Size = $base64Content.Length
Write-Host "✓ Base64内容を確認しました ($base64Size 文字)" -ForegroundColor Green

# 3. SSH経由でサーバー側に送信して復元
Write-Host "サーバー側で復元を実行中..." -ForegroundColor Yellow

# SSHコマンドを構築
$remoteScript = @"
cd $REMOTE_DIR
if [ -f public/index.php ]; then
    cp public/index.php public/index.php.bak_`$(date +%Y%m%d_%H%M%S)
    echo "✓ バックアップ作成完了"
else
    echo "⚠ public/index.php が見つかりません（新規作成）"
fi
echo '$base64Content' | base64 -d > public/index.php
if [ -f public/index.php ]; then
    echo "✓ 復元完了"
    ls -lh public/index.php
    echo "先頭5行:"
    head -5 public/index.php
else
    echo "✗ 復元失敗: public/index.php が作成されませんでした"
    exit 1
fi
if [ -f public/clear_cache.php ]; then
    php public/clear_cache.php 2>&1 || echo "⚠ OPcacheクリアに失敗（無視）"
else
    echo "⚠ public/clear_cache.php が見つかりません"
fi
echo "復元処理完了"
"@

# SSH経由で実行
$remoteScript | ssh $SSH_HOST bash

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ 復元が正常に完了しました" -ForegroundColor Green
} else {
    Write-Host "✗ 復元中にエラーが発生しました" -ForegroundColor Red
    exit 1
}
