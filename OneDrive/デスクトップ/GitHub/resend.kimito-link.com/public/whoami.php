<?php
// dev-only helper: show runtime environment (for docroot/realpath)
// Protect in prod

require_once __DIR__ . '/../app/bootstrap.php';

$env = $config['APP_ENV'] ?? 'dev';
if ($env === 'prod') {
  http_response_code(403);
  exit;
}

header('Content-Type: text/plain; charset=UTF-8');
echo "OK WHOAMI\n";
echo "PHP version: " . phpversion() . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "realpath(__FILE__): " . realpath(__FILE__) . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'undefined') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'undefined') . "\n";
echo "getcwd(): " . getcwd() . "\n";
echo "cwd=" . getcwd() . "\n";
echo "docroot=" . ($_SERVER['DOCUMENT_ROOT'] ?? 'NONE') . "\n";
echo "script=" . ($_SERVER['SCRIPT_FILENAME'] ?? 'NONE') . "\n";
echo "opcache.enable: " . ini_get('opcache.enable') . "\n";
echo "opcache.enable_cli: " . ini_get('opcache.enable_cli') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "dirname realpath: " . realpath(__DIR__) . "\n";
