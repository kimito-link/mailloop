#!/bin/bash
# サーバー側で実行する構文エラー修正スクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== 構文エラーを修正します ==="

# バックアップを作成
cp public/index.php public/index.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✓ バックアップ作成完了"

# 845行目周辺を確認
echo ""
echo "=== 現在の845行目周辺のコード ==="
sed -n '835,850p' public/index.php

# 構文チェック
echo ""
echo "=== 構文チェック ==="
php -l public/index.php 2>&1 | head -5

# 839-846行を正しいコードで置き換え
echo ""
echo "=== 修正を適用 ==="
cat > /tmp/fix_845.php << 'EOFPHP'
<?php
// 正しいPOSTルートのコード
$correct_code = "route('POST', '/auth/logout', function() {
  // CSRF検証（ログアウトも保護）
  if (!csrf_verify()) {
    return;
  }
  session_destroy();
  header('Location: /auth/login'); exit;
});";

// ファイルを読み込む
$file = 'public/index.php';
$lines = file($file);

// 839-846行を置き換え
$new_lines = array_slice($lines, 0, 838); // 1-838行を保持
$new_lines[] = $correct_code . "\n"; // 新しいコードを追加
$new_lines = array_merge($new_lines, array_slice($lines, 846)); // 847行目以降を保持

// ファイルに書き込む
file_put_contents($file, implode('', $new_lines));
echo "✓ 修正完了\n";
EOFPHP

php /tmp/fix_845.php

# 再度構文チェック
echo ""
echo "=== 修正後の構文チェック ==="
php -l public/index.php

echo ""
echo "=== 修正後の845行目周辺のコード ==="
sed -n '835,850p' public/index.php

echo ""
echo "=== 完了 ==="
