#!/bin/bash
# 構文エラーと403エラーの修正をサーバー側で確認・適用するスクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop || exit 1

echo "=== 1. 現在のコードの確認 ==="
echo ""

# 1218行目付近を確認（より広い範囲）
echo "--- 1218行目付近のコード（1200-1240行目） ---"
sed -n '1200,1240p' public/index.php | cat -n | sed 's/^[[:space:]]*/    /'
echo ""

# 括弧のバランスを確認（1166-1243行目）
echo "--- 括弧のバランスチェック（1166-1243行目） ---"
sed -n '1166,1243p' public/index.php > /tmp/check_section.php
open_paren=$(grep -o '(' /tmp/check_section.php | wc -l)
close_paren=$(grep -o ')' /tmp/check_section.php | wc -l)
open_bracket=$(grep -o '\[' /tmp/check_section.php | wc -l)
close_bracket=$(grep -o ']' /tmp/check_section.php | wc -l)
open_brace=$(grep -o '{' /tmp/check_section.php | wc -l)
close_brace=$(grep -o '}' /tmp/check_section.php | wc -l)

echo "  開き括弧 '(': $open_paren"
echo "  閉じ括弧 ')': $close_paren"
echo "  差分: $((open_paren - close_paren))"
echo ""
echo "  開き角括弧 '[': $open_bracket"
echo "  閉じ角括弧 ']': $close_bracket"
echo "  差分: $((open_bracket - close_bracket))"
echo ""
echo "  開き波括弧 '{': $open_brace"
echo "  閉じ波括弧 '}': $close_brace"
echo "  差分: $((open_brace - close_brace))"
echo ""

if [ $open_paren -ne $close_paren ] || [ $open_bracket -ne $close_bracket ] || [ $open_brace -ne $close_brace ]; then
  echo "  ✗ ERROR: 括弧のバランスが崩れています！"
  echo ""
  echo "  問題のある行を特定中..."
  # 1218行目付近で特にチェック
  echo "  --- 1218行目付近の詳細（1210-1225行目） ---"
  sed -n '1210,1225p' public/index.php | cat -n | sed 's/^[[:space:]]*/      /'
else
  echo "  ✓ 括弧のバランスは正しいです。"
fi
rm -f /tmp/check_section.php
echo ""

# トークンスコープ検査部分を確認
echo "--- トークンスコープ検査部分（1068-1120行目） ---"
sed -n '1068,1120p' public/index.php
echo ""

# 構文チェック
echo "=== 2. 構文チェック ==="
echo ""

# PHP 8.3で構文チェック（利用可能なパスを試行）
SYNTAX_ERROR=0
if [ -f bin/php ]; then
  echo "Using bin/php for syntax check..."
  if ! bin/php -l public/index.php 2>&1; then
    SYNTAX_ERROR=1
  fi
  if ! bin/php -l app/services/google_oauth.php 2>&1; then
    SYNTAX_ERROR=1
  fi
elif command -v php8.3 &> /dev/null; then
  echo "Using php8.3 for syntax check..."
  if ! php8.3 -l public/index.php 2>&1; then
    SYNTAX_ERROR=1
  fi
  if ! php8.3 -l app/services/google_oauth.php 2>&1; then
    SYNTAX_ERROR=1
  fi
elif command -v php &> /dev/null; then
  echo "Using php for syntax check (may be older version)..."
  if ! php -l public/index.php 2>&1; then
    SYNTAX_ERROR=1
  fi
  if ! php -l app/services/google_oauth.php 2>&1; then
    SYNTAX_ERROR=1
  fi
else
  echo "ERROR: PHP executable not found"
  exit 1
fi

echo ""
if [ $SYNTAX_ERROR -eq 1 ]; then
  echo "=== 3. 構文エラーが検出されました ==="
  echo ""
  echo "構文エラーの詳細を確認してください。"
  echo ""
  echo "修正方法:"
  echo "1. ローカルの public/index.php を確認（構文エラーがないことを確認）"
  echo "2. サーバー側のファイルと比較"
  echo "3. ローカルからサーバーに正しいファイルをアップロード"
  echo ""
  echo "または、git pullで最新版を取得:"
  echo "  git pull origin main"
else
  echo "=== 3. 構文チェック完了 ==="
  echo ""
  echo "✓ 構文エラーは検出されませんでした。"
  echo ""
  echo "もしWeb上でエラーが発生している場合は、以下を確認してください："
  echo "  - OPcacheをクリア: php public/clear_cache.php"
  echo "  - エラーログを確認: tail -f log/resend.kimito-link.com_error_log"
fi
echo ""
