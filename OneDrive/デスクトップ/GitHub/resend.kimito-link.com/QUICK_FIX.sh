#!/bin/bash
# クイック修正スクリプト
# DocumentRoot直下のindex.phpをラッパーに修正します

DOCROOT="/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com"
GIT_INDEX="/home/besttrust/kimito-link.com/_git/mailloop/public/index.php"

echo "=== DocumentRoot直下のindex.phpをラッパーに修正 ==="

# バックアップを取る
if [ -f "$DOCROOT/index.php" ]; then
    echo "既存のindex.phpをバックアップします..."
    cp "$DOCROOT/index.php" "$DOCROOT/index.php.bak.$(date +%Y%m%d_%H%M%S)"
    echo "バックアップ完了: $DOCROOT/index.php.bak.$(date +%Y%m%d_%H%M%S)"
fi

# ラッパーファイルを作成
echo "ラッパーファイルを作成します..."
cat > "$DOCROOT/index.php" << 'EOF'
<?php
require __DIR__ . '/../_git/mailloop/public/index.php';
EOF

# パーミッションを設定
chmod 644 "$DOCROOT/index.php"

echo "修正完了！"
echo ""
echo "確認:"
echo "  - ファイル: $DOCROOT/index.php"
echo "  - 内容:"
cat "$DOCROOT/index.php"
echo ""
echo "次に、ブラウザで以下にアクセスして確認してください:"
echo "  https://resend.kimito-link.com/auth/callback?dbg=raw"
