<?php
// secrets.php → getenv() → デフォルト の順で読み込む
$secrets = [];
$secretsFile = __DIR__ . '/secrets.php';
if (file_exists($secretsFile)) {
  $secrets = require $secretsFile;
  if (!is_array($secrets)) $secrets = [];
}

$config = [
  'APP_URL' => 'https://resend.kimito-link.com',
  'APP_KEY' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_AT_DEPLOYMENT',
  'STORAGE_DRIVER' => 'mysql', // mysql|file

  // Xserver MySQL設定（secrets.php → getenv() → デフォルト の順）
  'DB_HOST' => $secrets['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
  'DB_NAME' => $secrets['DB_NAME'] ?? getenv('DB_NAME') ?: 'besttrust_mail',
  'DB_USER' => $secrets['DB_USER'] ?? getenv('DB_USER') ?: 'besttrust_mail',
  'DB_PASS' => $secrets['DB_PASS'] ?? getenv('DB_PASS') ?: '',
  'DB_CHARSET' => 'utf8mb4',

  'GOOGLE_CLIENT_ID' => 'DUMMY_CLIENT_ID',
  'GOOGLE_CLIENT_SECRET' => 'DUMMY_CLIENT_SECRET',
  'GOOGLE_REDIRECT_URI' => 'https://resend.kimito-link.com/auth/callback',

  'GOOGLE_SCOPES' => 'openid email profile',
  'GMAIL_SCOPE' => 'https://www.googleapis.com/auth/gmail.send',

  'WARN_BCC_1' => 50,
  'WARN_BCC_2' => 100,
  'DUPLICATE_SEND_HOURS' => 6,
];

// mysql利用時にDB_PASS未設定ならエラーで停止
if ($config['STORAGE_DRIVER'] === 'mysql' && empty($config['DB_PASS'])) {
  throw new RuntimeException('DB_PASS is not set. Please configure it in config/secrets.php or environment variable.');
}

// デバッグモード（開発時のみ true、本番は false）
if (!defined('APP_DEBUG')) {
  define('APP_DEBUG', false);
}

return $config;
