# PowerShell用SSH接続設定スクリプト
# Windows PowerShellまたはPowerShell Coreで実行してください

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "SSH接続設定スクリプト (PowerShell)" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$sshConfigPath = "$env:USERPROFILE\.ssh\config"
$sshDir = "$env:USERPROFILE\.ssh"
$idRsaPath = "$env:USERPROFILE\.ssh\id_rsa"

# 1. SSH設定ファイルの確認
Write-Host "1. SSH設定ファイルの確認..." -ForegroundColor Yellow
if (Test-Path $sshConfigPath) {
    Write-Host "   ✅ SSH設定ファイルが見つかりました: $sshConfigPath" -ForegroundColor Green
    
    $configContent = Get-Content $sshConfigPath -Raw
    if ($configContent -match "Host xserver-besttrust") {
        Write-Host "   ✅ 'xserver-besttrust'の設定が見つかりました" -ForegroundColor Green
        Write-Host ""
        Write-Host "   設定内容:" -ForegroundColor Cyan
        Get-Content $sshConfigPath | Select-String -Pattern "xserver-besttrust" -Context 0,6 | ForEach-Object {
            Write-Host "      $_" -ForegroundColor Gray
        }
    } else {
        Write-Host "   ❌ 'xserver-besttrust'の設定が見つかりません" -ForegroundColor Red
    }
} else {
    Write-Host "   ❌ SSH設定ファイルが見つかりません: $sshConfigPath" -ForegroundColor Red
    Write-Host "   💡 SSH設定ファイルを作成する必要があります" -ForegroundColor Yellow
}
Write-Host ""

# 2. SSH鍵ファイルの確認
Write-Host "2. SSH鍵ファイルの確認..." -ForegroundColor Yellow
if (Test-Path $idRsaPath) {
    Write-Host "   ✅ 秘密鍵が見つかりました: $idRsaPath" -ForegroundColor Green
    $keyInfo = Get-Item $idRsaPath
    Write-Host "      サイズ: $($keyInfo.Length) bytes" -ForegroundColor Gray
    Write-Host "      更新日時: $($keyInfo.LastWriteTime)" -ForegroundColor Gray
} else {
    Write-Host "   ❌ 秘密鍵が見つかりません: $idRsaPath" -ForegroundColor Red
    Write-Host "   💡 SSH鍵を生成する必要があります" -ForegroundColor Yellow
}
Write-Host ""

# 3. 接続情報の表示
Write-Host "3. 接続情報の確認..." -ForegroundColor Yellow
Write-Host "   ホスト名: sv16.sixcore.ne.jp" -ForegroundColor Cyan
Write-Host "   IPアドレス: 202.226.36.17" -ForegroundColor Cyan
Write-Host "   ユーザー名: besttrust" -ForegroundColor Cyan
Write-Host "   ポート: 10022" -ForegroundColor Cyan
Write-Host "   鍵ファイル: $idRsaPath" -ForegroundColor Cyan
Write-Host ""

# 4. 接続テスト（オプション）
Write-Host "4. 接続テスト..." -ForegroundColor Yellow
Write-Host "   接続をテストしますか？ (Y/N): " -NoNewline -ForegroundColor Yellow
$response = Read-Host

if ($response -eq "Y" -or $response -eq "y") {
    Write-Host ""
    Write-Host "   SSH接続を試行中..." -ForegroundColor Cyan
    Write-Host "   (接続をキャンセルする場合は Ctrl+C を押してください)" -ForegroundColor Gray
    Write-Host ""
    
    # SSH接続コマンドを実行
    $sshCommand = "ssh -i `"$idRsaPath`" -p 10022 besttrust@sv16.sixcore.ne.jp"
    Write-Host "   実行コマンド: $sshCommand" -ForegroundColor Gray
    Write-Host ""
    
    try {
        Invoke-Expression $sshCommand
    } catch {
        Write-Host "   ❌ 接続に失敗しました: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "   トラブルシューティング:" -ForegroundColor Yellow
        Write-Host "   1. 鍵ファイルのパスフレーズを確認してください" -ForegroundColor Gray
        Write-Host "   2. ネットワーク接続を確認してください" -ForegroundColor Gray
        Write-Host "   3. ファイアウォール設定を確認してください" -ForegroundColor Gray
    }
} else {
    Write-Host "   接続テストをスキップしました" -ForegroundColor Gray
}
Write-Host ""

# 5. 接続コマンドの表示
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "接続コマンド" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Git Bashで接続する場合:" -ForegroundColor Yellow
Write-Host "  ssh xserver-besttrust" -ForegroundColor White
Write-Host ""
Write-Host "PowerShellで直接接続する場合:" -ForegroundColor Yellow
Write-Host "  ssh -i `"$idRsaPath`" -p 10022 besttrust@sv16.sixcore.ne.jp" -ForegroundColor White
Write-Host ""
Write-Host "または、PowerShellのSSHエイリアスを使用:" -ForegroundColor Yellow
Write-Host "  Set-Alias ssh-xserver `"ssh -i `"$idRsaPath`" -p 10022 besttrust@sv16.sixcore.ne.jp`"" -ForegroundColor White
Write-Host "  ssh-xserver" -ForegroundColor White
Write-Host ""
