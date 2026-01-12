#!/bin/bash
# サーバー側で直接実行するログ確認スクリプト

echo "=== ログファイルの場所を確認 ==="
LOG_DIR="/home/besttrust/kimito-link.com/_git/mailloop/storage"
echo "ログディレクトリ: $LOG_DIR"
ls -lh "$LOG_DIR" 2>/dev/null || echo "ディレクトリが見つかりません"

echo ""
echo "=== 403エラー関連のログを確認 ==="

# ログファイルのパス
ERROR_LOG="$LOG_DIR/app_error.log"
DEBUG_LOG="$LOG_DIR/app_debug.log"

# 1. Gmail 403 Prevention ログ
echo "--- 1. Gmail 403 Prevention: Token missing gmail.send scope ---"
if [ -f "$ERROR_LOG" ]; then
  grep -i "Gmail 403 Prevention" "$ERROR_LOG" | tail -20
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
fi

echo ""
# 2. Tokeninfo check failed ログ
echo "--- 2. Tokeninfo check failed ---"
if [ -f "$ERROR_LOG" ]; then
  grep -i "Tokeninfo check failed" "$ERROR_LOG" | tail -20
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
fi

echo ""
# 3. Gmail API Error: HTTP 403 ログ
echo "--- 3. Gmail API Error: HTTP 403 ---"
if [ -f "$ERROR_LOG" ]; then
  grep -i "Gmail API Error: HTTP 403" "$ERROR_LOG" | tail -20
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
fi

echo ""
# 4. Gmail 403: insufficientPermissions ログ
echo "--- 4. Gmail 403: insufficientPermissions detected ---"
if [ -f "$ERROR_LOG" ]; then
  grep -i "Gmail 403: insufficientPermissions" "$ERROR_LOG" | tail -20
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
fi

echo ""
# 5. Gmail 403 Error Details ログ
echo "--- 5. Gmail 403 Error Details ---"
if [ -f "$ERROR_LOG" ]; then
  grep -i "Gmail 403 Error Details" "$ERROR_LOG" | tail -20
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
fi

echo ""
# 6. 認証ループ関連のログ
echo "--- 6. 認証ループ関連のログ（require_login） ---"
if [ -f "$ERROR_LOG" ]; then
  grep -i "require_login" "$ERROR_LOG" | tail -30
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
fi

echo ""
# 7. 最近のログ（全般）
echo "--- 7. 最近のログ（最後の50行） ---"
if [ -f "$ERROR_LOG" ]; then
  tail -50 "$ERROR_LOG"
else
  echo "ログファイルが見つかりません: $ERROR_LOG"
  echo ""
  echo "代替ログファイルを確認中..."
  if [ -f "$DEBUG_LOG" ]; then
    echo "デバッグログ: $DEBUG_LOG"
    tail -50 "$DEBUG_LOG"
  else
    echo "デバッグログも見つかりません"
  fi
fi

echo ""
echo "=== ログファイルの統計情報 ==="
if [ -f "$ERROR_LOG" ]; then
  echo "ファイルサイズ: $(ls -lh "$ERROR_LOG" | awk '{print $5}')"
  echo "最終更新: $(ls -l "$ERROR_LOG" | awk '{print $6, $7, $8}')"
  echo "総行数: $(wc -l < "$ERROR_LOG")"
fi
