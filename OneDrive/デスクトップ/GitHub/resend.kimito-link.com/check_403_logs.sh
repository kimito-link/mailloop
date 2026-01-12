#!/bin/bash
# サーバー側で403エラーのログを確認するスクリプト

echo "=== 403エラー関連ログの確認 ==="
echo ""

# サーバー側のログファイルパス
LOG_FILE="/home/besttrust/kimito-link.com/_git/mailloop/storage/app_error.log"

# ログファイルが存在するか確認
if [ ! -f "$LOG_FILE" ]; then
    echo "警告: ログファイルが見つかりません: $LOG_FILE"
    echo ""
    echo "代替ログファイルを確認中..."
    
    # 代替パスを確認
    ALTERNATIVE_LOGS=(
        "/home/besttrust/kimito-link.com/_git/mailloop/storage/app_debug.log"
        "/tmp/mailloop_debug.log"
        "$HOME/mailloop_debug.log"
    )
    
    for alt_log in "${ALTERNATIVE_LOGS[@]}"; do
        if [ -f "$alt_log" ]; then
            echo "見つかったログファイル: $alt_log"
            LOG_FILE="$alt_log"
            break
        fi
    done
    
    if [ ! -f "$LOG_FILE" ]; then
        echo "エラー: ログファイルが見つかりませんでした。"
        exit 1
    fi
fi

echo "ログファイル: $LOG_FILE"
echo "ファイルサイズ: $(ls -lh "$LOG_FILE" | awk '{print $5}')"
echo ""

# 1. Gmail 403 Prevention ログ（トークンスコープ不足）
echo "=== 1. Gmail 403 Prevention: Token missing gmail.send scope ==="
grep -i "Gmail 403 Prevention: Token missing gmail.send scope" "$LOG_FILE" | tail -20
echo ""

# 2. Tokeninfo check failed ログ
echo "=== 2. Tokeninfo check failed ==="
grep -i "Tokeninfo check failed" "$LOG_FILE" | tail -20
echo ""

# 3. Gmail API Error: HTTP 403 ログ
echo "=== 3. Gmail API Error: HTTP 403 ==="
grep -i "Gmail API Error: HTTP 403" "$LOG_FILE" | tail -20
echo ""

# 4. Gmail 403: insufficientPermissions ログ
echo "=== 4. Gmail 403: insufficientPermissions detected ==="
grep -i "Gmail 403: insufficientPermissions detected" "$LOG_FILE" | tail -20
echo ""

# 5. Gmail 403 Error Details ログ
echo "=== 5. Gmail 403 Error Details ==="
grep -i "Gmail 403 Error Details" "$LOG_FILE" | tail -20
echo ""

# 6. 最近の403関連ログ（全般）
echo "=== 6. 最近の403関連ログ（全般） ==="
grep -i "403" "$LOG_FILE" | tail -30
echo ""

# 7. ログファイルの最後の50行（最新のログ）
echo "=== 7. ログファイルの最後の50行（最新のログ） ==="
tail -50 "$LOG_FILE"
echo ""

echo "=== 確認完了 ==="
