<?php
require_once __DIR__ . '/../app/bootstrap.php';
$env = $config['APP_ENV'] ?? 'dev';
if ($env === 'prod') { http_response_code(403); exit; }

header('Content-Type: text/plain; charset=UTF-8');
echo "OK DB CHECK\n";
echo "timestamp: " . date('c') . "\n";

// show config (mask password)
$mask = function($v){ return str_repeat('*', max(1, strlen((string)$v))); };
echo "STORAGE_DRIVER: " . ($config['STORAGE_DRIVER'] ?? '') . "\n";
echo "DB_HOST: " . ($config['DB_HOST'] ?? '') . "\n";
echo "DB_NAME: " . ($config['DB_NAME'] ?? '') . "\n";
echo "DB_USER: " . ($config['DB_USER'] ?? '') . "\n";
echo "DB_CHARSET: " . ($config['DB_CHARSET'] ?? '') . "\n";
echo "DB_PASS: " . $mask($config['DB_PASS'] ?? '') . "\n";

$pdo = db_pdo($config);
if (!$pdo) {
  echo "DB connect: FAILED (pdo is null)\n";
  exit;
}
echo "DB connect: OK\n";

$q = function($sql) use ($pdo) {
  $stmt = $pdo->query($sql);
  return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : null;
};

echo "\n-- SELECT DATABASE(), @@hostname, VERSION(), CURRENT_USER()\n";
print_r($q("SELECT DATABASE() AS db, @@hostname AS host, VERSION() AS ver, CURRENT_USER() AS current_user"));

echo "\n-- SHOW TABLES LIKE 'users'\n";
print_r($q("SHOW TABLES LIKE 'users'"));

echo "\n-- DESCRIBE users (if exists)\n";
$desc = $q("DESCRIBE users");
if ($desc === null) {
  echo "users table not found or DESCRIBE failed\n";
} else {
  print_r($desc);
}
