<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

// Fatal errorをログに記録（ファイル書き込み失敗でもerror_logだけは残す）
register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
    error_log('MailLoop FATAL: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
  }
});

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set('Asia/Tokyo');

// ログ出力先をアプリ専用ファイルに設定
ini_set('log_errors', '1');
$logDir = __DIR__ . '/../storage';
if (!is_dir($logDir)) {
  @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/app_error.log');

// アプリ専用デバッグログ関数（確実に書ける版）
function app_log($msg) {
  $line = '[' . date('c') . '] ' . $msg . "\n";
  // 1) まず /tmp に書く（ほぼ確実に書ける）
  $tmpDir = sys_get_temp_dir();
  $tmp = $tmpDir . '/mailloop_debug.log';
  // ディレクトリが存在し、書き込み可能な場合のみ
  if (is_dir($tmpDir) && is_writable($tmpDir)) {
    @file_put_contents($tmp, $line, FILE_APPEND);
  }
  
  // 2) ホームディレクトリにも書く（フォールバック）
  $homeLog = getenv('HOME') . '/mailloop_debug.log';
  if ($homeLog && is_writable(dirname($homeLog))) {
    @file_put_contents($homeLog, $line, FILE_APPEND);
  }

  // 3) 可能なら storage にも書く（書ければ嬉しい）
  $logDir = __DIR__ . '/../storage';
  $logFile = $logDir . '/app_debug.log';
  if (is_dir($logDir) && is_writable($logDir)) {
    @file_put_contents($logFile, $line, FILE_APPEND);
  }
  
  // 4) 最後の手段: error_log（必ず出力される）
  @error_log('MailLoop: ' . $msg);
}

session_name('mailloop_session');

// セッション保存先を storage/sessions に設定（アプリケーション管理下）
$sessionDir = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionDir)) {
  $mkdirResult = @mkdir($sessionDir, 0700, true);
  if (!$mkdirResult) {
    error_log('MailLoop Session: Failed to create session directory: ' . $sessionDir);
    // フォールバック: /tmp を使用
    $sessionDir = sys_get_temp_dir() . '/mailloop_sessions';
    if (!is_dir($sessionDir)) {
      @mkdir($sessionDir, 0700, true);
    }
    error_log('MailLoop Session: Using fallback /tmp session directory: ' . $sessionDir);
  } else {
    error_log('MailLoop Session: Created session directory: ' . $sessionDir);
  }
}

if (is_dir($sessionDir) && is_writable($sessionDir)) {
  session_save_path($sessionDir);
  error_log('MailLoop Session: Using session directory: ' . $sessionDir);
} else {
  error_log('MailLoop Session: WARNING - Session directory not writable: ' . $sessionDir . ' | exists=' . (is_dir($sessionDir) ? 'yes' : 'no') . ' | writable=' . (is_writable($sessionDir) ? 'yes' : 'no'));
}

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => 'resend.kimito-link.com', // 空より明示の方が事故りにくい
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Lax',
]);

ini_set('session.use_strict_mode', '0'); // ★まずOFFにして切り分け
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.gc_maxlifetime', '3600');

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
