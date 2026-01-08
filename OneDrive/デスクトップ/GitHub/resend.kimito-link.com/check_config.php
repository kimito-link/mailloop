<?php
// サーバー側で実行する設定確認スクリプト
$baseDir = __DIR__;
echo "Base directory: $baseDir\n";

$secretsFile = $baseDir . '/config/secrets.php';
echo "Secrets file path: $secretsFile\n";
echo "Secrets file exists: " . (file_exists($secretsFile) ? 'YES' : 'NO') . "\n";

$secrets = [];
if (file_exists($secretsFile)) {
  $secrets = require $secretsFile;
  if (!is_array($secrets)) $secrets = [];
}

echo "\n=== secrets.php の内容 ===\n";
$clientId = isset($secrets['GOOGLE_CLIENT_ID']) ? $secrets['GOOGLE_CLIENT_ID'] : 'NOT SET';
echo "GOOGLE_CLIENT_ID: $clientId\n";
$hasSecret = isset($secrets['GOOGLE_CLIENT_SECRET']);
echo "GOOGLE_CLIENT_SECRET: " . ($hasSecret ? 'SET (hidden)' : 'NOT SET') . "\n";

echo "\n=== config.php の読み込み ===\n";
$config = require $baseDir . '/config/config.php';
$configClientId = isset($config['GOOGLE_CLIENT_ID']) ? $config['GOOGLE_CLIENT_ID'] : 'NOT SET';
echo "GOOGLE_CLIENT_ID: $configClientId\n";
$configHasSecret = isset($config['GOOGLE_CLIENT_SECRET']);
echo "GOOGLE_CLIENT_SECRET: " . ($configHasSecret ? 'SET (hidden)' : 'NOT SET') . "\n";
