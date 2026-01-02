<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set('Asia/Tokyo');
session_name('mailloop_session');
// セッション設定（セキュリティ）
ini_set('session.cookie_httponly', '1');
// HTTPS判定（HTTPS時のみsecureを有効化）
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.use_strict_mode', '1');
session_start();

// セッションタイムアウト（30分無操作でログアウト）
$ttl = 1800; // 30分
$now = time();
if (!isset($_SESSION['last_seen'])) {
  $_SESSION['last_seen'] = $now;
} else {
  if (($now - (int)$_SESSION['last_seen']) > $ttl) {
    session_unset();
    session_destroy();
    // ログインページへリダイレクト
    header('Location: /auth/login?timeout=1');
    exit;
  }
  $_SESSION['last_seen'] = $now;
}

require_once __DIR__ . '/lib/crypto.php';
require_once __DIR__ . '/lib/http.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/router.php';

require_once __DIR__ . '/services/storage.php';
require_once __DIR__ . '/services/google_oauth.php';
require_once __DIR__ . '/services/gmail_send.php';

$storage = create_storage($config);
