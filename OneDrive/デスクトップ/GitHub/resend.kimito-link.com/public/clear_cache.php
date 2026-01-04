<?php
require_once __DIR__ . '/../app/bootstrap.php';
$env = $config['APP_ENV'] ?? 'dev';
if ($env === 'prod') { http_response_code(403); exit; }

header('Content-Type: text/plain; charset=UTF-8');
echo "OK CLEAR CACHE\n";
echo "timestamp: " . date('c') . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "realpath(__FILE__): " . realpath(__FILE__) . "\n";

if (function_exists('opcache_reset')) {
  $r = opcache_reset();
  echo "opcache_reset: " . ($r ? "OK" : "FAILED") . "\n";
} else {
  echo "opcache_reset: not available\n";
}

if (function_exists('apcu_clear_cache')) {
  apcu_clear_cache();
  echo "apcu_clear_cache: OK\n";
}

echo "Reload this page a few times if multiple PHP workers exist.\n";
<?php
// OPcacheクリア用スクリプト
header('Content-Type: text/plain; charset=UTF-8');

if (function_exists('opcache_reset')) {
  if (opcache_reset()) {
    echo "OPcache reset: OK\n";
  } else {
    echo "OPcache reset: FAILED\n";
  }
} else {
  echo "OPcache not available.\n";
}

clearstatcache();
echo "Done.\n";
<?php
// OPcacheクリア用スクリプト（デバッグ用）
// 本番環境では削除またはアクセス制限を推奨

header('Content-Type: text/plain; charset=UTF-8');

echo "=== OPcache Clear Script ===\n\n";

// OPcacheが有効かチェック
if (function_exists('opcache_reset')) {
    echo "OPcache is enabled.\n";
    
    // OPcacheをリセット
    if (opcache_reset()) {
        echo "✓ OPcache cleared successfully!\n";
    } else {
        echo "✗ Failed to clear OPcache.\n";
    }
} else {
    echo "OPcache is not enabled or not available.\n";
}

// ファイルの最終更新時刻を表示
echo "\n=== File Modification Times ===\n";
$files = [
    __DIR__ . '/../app/services/google_oauth.php',
    __DIR__ . '/../app/services/gmail_send.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        $size = filesize($file);
        echo basename($file) . ": " . date('Y-m-d H:i:s', $mtime) . " (size: {$size} bytes)\n";
    } else {
        echo basename($file) . ": FILE NOT FOUND\n";
    }
}

// 関数の存在確認
echo "\n=== Function Check ===\n";
if (function_exists('base64url_encode')) {
    echo "✓ base64url_encode() is defined\n";
    $reflection = new ReflectionFunction('base64url_encode');
    echo "  Defined in: " . $reflection->getFileName() . ":" . $reflection->getStartLine() . "\n";
} else {
    echo "✗ base64url_encode() is NOT defined\n";
}

echo "\n=== Done ===\n";
