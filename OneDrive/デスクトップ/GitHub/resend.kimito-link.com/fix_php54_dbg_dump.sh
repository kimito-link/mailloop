#!/bin/bash
# サーバー側で実行する dbg_dump関数の修正スクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== dbg_dump関数の修正 ==="

# バックアップを作成
cp public/index.php public/index.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✓ バックアップ作成完了"

# 24-27行目を正しいコードで置き換え
cat > /tmp/fix_dbg_dump.txt << 'EOF'
    $isProd = ((isset($config['APP_ENV']) ? $config['APP_ENV'] : 'dev') === 'prod');
    $dbg = isset($_GET['dbg']) ? $_GET['dbg'] : '';
    $dbgKey = isset($_GET['dbg_key']) ? $_GET['dbg_key'] : '';
    $needKey = isset($config['DEBUG_KEY']) ? $config['DEBUG_KEY'] : '';
EOF

# 24-27行目を削除して、正しいコードを挿入
sed -i '24,27d' public/index.php
sed -i '23r /tmp/fix_dbg_dump.txt' public/index.php

echo "✓ 24-27行目を修正しました"

# 構文チェック
echo ""
echo "=== 構文チェック ==="
php -l public/index.php

# 修正後の24行目周辺を確認
echo ""
echo "=== 修正後の24行目周辺 ==="
sed -n '20,30p' public/index.php

echo ""
echo "=== 完了 ==="
