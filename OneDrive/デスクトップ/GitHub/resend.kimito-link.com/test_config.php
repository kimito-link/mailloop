<?php
/**
 * MailLoop Configuration & Database Test Script
 * このスクリプトは設定とデータベース接続をテストします
 */

echo "=== MailLoop Configuration Test ===\n\n";

// 1. Bootstrap読み込み
try {
    require_once __DIR__ . '/app/bootstrap.php';
    echo "✓ Bootstrap loaded successfully\n\n";
} catch (Exception $e) {
    echo "✗ Bootstrap failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Configuration check
echo "2. Configuration:\n";
echo "   APP_URL: " . $config['APP_URL'] . "\n";
echo "   APP_KEY: " . (strlen($config['APP_KEY']) > 20 ? "Set (" . strlen($config['APP_KEY']) . " chars)" : "NOT SET") . "\n";
echo "   APP_ENV: " . ($config['APP_ENV'] ?? 'dev') . "\n";
echo "   STORAGE_DRIVER: " . $config['STORAGE_DRIVER'] . "\n";
echo "   DB_HOST: " . $config['DB_HOST'] . "\n";
echo "   DB_NAME: " . $config['DB_NAME'] . "\n";
echo "   DB_USER: " . $config['DB_USER'] . "\n";
echo "   DB_PASS: " . (empty($config['DB_PASS']) ? "NOT SET" : "Set (hidden)") . "\n";
echo "   GOOGLE_CLIENT_ID: " . (strlen($config['GOOGLE_CLIENT_ID']) > 20 ? "Set" : "NOT SET") . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (strlen($config['GOOGLE_CLIENT_SECRET']) > 10 ? "Set" : "NOT SET") . "\n\n";

// 3. Database connection test
echo "3. Database Connection:\n";
try {
    $pdo = db_connect($config);
    echo "   ✓ Connection successful\n";
    
    // Check tables
    $tables = ['users', 'oauth_tokens', 'message_templates', 'recipient_groups', 'send_logs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            // Count rows
            $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            echo "   ✓ Table '$table' exists ($count rows)\n";
        } else {
            echo "   ✗ Table '$table' MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . "\n";
}

// 4. Storage test
echo "\n4. Storage Layer:\n";
try {
    echo "   Storage class: " . get_class($storage) . "\n";
    
    // Test getUser (should return null if not logged in)
    $user = $storage->getUser();
    if ($user === null) {
        echo "   ✓ getUser() returns null (not logged in)\n";
    } else {
        echo "   ✓ getUser() returns user: " . ($user['email'] ?? 'no email') . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Storage test failed: " . $e->getMessage() . "\n";
}

// 5. Session test
echo "\n5. Session:\n";
echo "   Session name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session save path: " . session_save_path() . "\n";
echo "   Session keys: " . implode(', ', array_keys($_SESSION)) . "\n";

// 6. File permissions
echo "\n6. File Permissions:\n";
$dirs = [
    __DIR__ . '/storage',
    __DIR__ . '/config',
    __DIR__ . '/public',
];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? '✓' : '✗';
        echo "   $writable $dir ($perms)\n";
    } else {
        echo "   ✗ $dir (not found)\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nNext steps:\n";
echo "1. If all tests pass, try accessing https://resend.kimito-link.com/\n";
echo "2. Check error logs at: storage/app_error.log\n";
echo "3. Check server logs for any PHP errors\n";
