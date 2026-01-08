<?php
// 開発用デバッグ: /auth/callback の挙動を単独で確認する（APP_ENV != 'prod' のみ）
require_once __DIR__ . '/../app/bootstrap.php';

$env = isset($config['APP_ENV']) ? $config['APP_ENV'] : 'dev';
if ($env === 'prod') {
  http_response_code(403);
  exit;
}

header('Content-Type: text/plain; charset=UTF-8');

echo "OK CALLBACK_DBG\n";
echo "time=" . date('c') . "\n";
echo "__FILE__=" . __FILE__ . "\n";
echo "cwd=" . getcwd() . "\n";
echo "DOCUMENT_ROOT=" . ($_SERVER['DOCUMENT_ROOT'] ?? 'NONE') . "\n";
echo "SCRIPT_FILENAME=" . ($_SERVER['SCRIPT_FILENAME'] ?? 'NONE') . "\n";

echo "storage_class=" . (is_object($storage) ? get_class($storage) : 'NOT_OBJECT') . "\n";
echo "storage_file=" . (function_exists('storage_debug_file') ? storage_debug_file() : 'NOFUNC') . "\n";
echo "session_name=" . session_name() . "\n";
echo "session_id=" . session_id() . "\n";
echo "cookie=" . ($_SERVER['HTTP_COOKIE'] ?? 'NONE') . "\n";

echo "\n--- GET params ---\n";
var_export($_GET);
echo "\n";

$code = isset($_GET['code']) ? $_GET['code'] : '';
if ($code === '') {
  echo "code is empty; nothing to do.\n";
  exit;
}

echo "\n--- TOKEN EXCHANGE ---\n";
[$resp, $data] = google_exchange_code($config, $code);
$respCode = isset($resp['code']) ? (int)$resp['code'] : 0;
if ($respCode !== 200 || !isset($data['access_token'])) {
  echo "token exchange failed\n";
  echo "respCode=" . $respCode . "\n";
  echo "resp_body=" . (isset($resp['body']) ? $resp['body'] : 'NONE') . "\n";
  exit;
}
$accessToken = $data['access_token'];
$refreshToken = isset($data['refresh_token']) ? $data['refresh_token'] : null;
$expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 3500;
echo "token ok; access_token length=" . strlen($accessToken) . " expires_in=" . $expiresIn . "\n";

echo "\n--- USERINFO ---\n";
[$uResp, $uData] = google_userinfo($accessToken);
$uRespCode = isset($uResp['code']) ? (int)$uResp['code'] : 0;
if ($uRespCode !== 200 || empty($uData)) {
  echo "userinfo failed respCode=" . $uRespCode . "\n";
  echo "resp_body=" . (isset($uResp['body']) ? $uResp['body'] : 'NONE') . "\n";
  exit;
}
echo "userinfo ok; keys=" . implode(',', array_keys($uData)) . "\n";

$sub = isset($uData['sub']) ? $uData['sub'] : (isset($uData['id']) ? $uData['id'] : '');
if ($sub === '') {
  echo "sub is empty; cannot upsert\n";
  exit;
}

$user = [
  'provider' => 'google',
  'provider_sub' => $sub,
  'email' => isset($uData['email']) ? $uData['email'] : null,
  'name' => isset($uData['name']) ? $uData['name'] : null,
  'picture' => isset($uData['picture']) ? $uData['picture'] : null,
];

echo "\n--- UPSERT INPUT ---\n";
var_export($user);
echo "\n";

try {
  $user = $storage->upsertUser($user);
  echo "\n--- UPSERT RESULT ---\n";
  var_export($user);
  echo "\n";
  echo "has_id=" . (isset($user['id']) ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
  echo "\n--- UPSERT EXCEPTION ---\n";
  echo get_class($e) . ": " . $e->getMessage() . "\n";
  echo "file=" . $e->getFile() . " line=" . $e->getLine() . "\n";
  echo $e->getTraceAsString() . "\n";
  exit;
}

// セッションへ格納（テスト用）
$_SESSION['user'] = $user;
$_SESSION['user_id'] = isset($user['id']) ? (int)$user['id'] : null;
session_write_close();

echo "\nDONE\n";
