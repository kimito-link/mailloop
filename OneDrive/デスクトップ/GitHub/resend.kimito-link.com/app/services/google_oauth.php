<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

// base64urlエンコード（PHP古めでも動く）
if (!function_exists('base64url_encode')) {
  function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }
}

// base64urlデコード（PHP古めでも動く）
if (!function_exists('base64url_decode')) {
  function base64url_decode($data) {
    $data = strtr($data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad) {
      $data .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($data);
  }
}

// 署名付きstate生成（セッション不要）
function oauth_build_state($config, $server) {
  $appKey = isset($config['APP_KEY']) ? $config['APP_KEY'] : '';
  if (empty($appKey) || $appKey === 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_AT_DEPLOYMENT') {
    return false; // APP_KEY未設定
  }
  
  $nonce = bin2hex(random_bytes(16));
  $ua = isset($server['HTTP_USER_AGENT']) ? substr($server['HTTP_USER_AGENT'], 0, 80) : '';
  $ts = time();
  
  $payload = json_encode([
    'ts' => $ts,
    'nonce' => $nonce,
    'ua' => $ua,
  ]);
  
  if ($payload === false) {
    return false;
  }
  
  $payloadB64 = base64url_encode($payload);
  $mac = hash_hmac('sha256', $payloadB64, $appKey, true);
  $macB64 = base64url_encode($mac);
  
  return $payloadB64 . '.' . $macB64;
}

// 署名付きstate検証（セッション不要）
function oauth_verify_state($config, $state, $server, &$errMsg = null) {
  $errMsg = null;
  $ttl = 600; // 10分
  
  $appKey = isset($config['APP_KEY']) ? $config['APP_KEY'] : '';
  if (empty($appKey) || $appKey === 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_AT_DEPLOYMENT') {
    $errMsg = 'APP_KEY not configured';
    return false;
  }
  
  $parts = explode('.', $state);
  if (count($parts) !== 2) {
    $errMsg = 'Invalid state format (expected payload.mac)';
    return false;
  }
  
  $payloadB64 = $parts[0];
  $macB64 = $parts[1];
  
  // 署名検証
  $mac = base64url_decode($macB64);
  if ($mac === false) {
    $errMsg = 'Failed to decode MAC';
    return false;
  }
  
  $expectMac = hash_hmac('sha256', $payloadB64, $appKey, true);
  if (!hash_equals($expectMac, $mac)) {
    $errMsg = 'Signature mismatch';
    return false;
  }
  
  // ペイロード解析
  $payloadJson = base64url_decode($payloadB64);
  if ($payloadJson === false) {
    $errMsg = 'Failed to decode payload';
    return false;
  }
  
  $payload = json_decode($payloadJson, true);
  if (!is_array($payload) || !isset($payload['ts'])) {
    $errMsg = 'Invalid payload format';
    return false;
  }
  
  // 有効期限チェック
  $ts = (int)$payload['ts'];
  if ($ts <= 0) {
    $errMsg = 'Invalid timestamp';
    return false;
  }
  if ((time() - $ts) > $ttl) {
    $errMsg = 'State expired (TTL: ' . $ttl . ' seconds)';
    return false;
  }
  
  return true;
}

function google_auth_url($config, $state) {
  $scopes = trim($config['GOOGLE_SCOPES'] . ' ' . $config['GMAIL_SCOPE']);

  $params = array(
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'redirect_uri' => $config['GOOGLE_REDIRECT_URI'],
    'response_type' => 'code',
    'scope' => $scopes,
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => $state,
  );

  // 互換性重視のクエリ組み立て（PHP古め対策）
  $pairs = array();
  foreach ($params as $k => $v) {
    if ($v === null) continue;
    $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
  }
  $q = implode('&', $pairs);

  $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . $q;

  // ★ 最終保険：stateが見つからなければ強制付与
  if (strpos($url, 'state=') === false) {
    $url .= '&state=' . rawurlencode($state);
    if (function_exists('app_log')) {
      app_log('WARNING: google_auth_url() state was missing, forced to add');
    } else {
      error_log('MailLoop WARNING: google_auth_url() state was missing, forced to add');
    }
  }
  
  return $url;
}
function google_exchange_code($config, $code) {
  $resp = http_post_form('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'client_secret' => $config['GOOGLE_CLIENT_SECRET'],
    'redirect_uri' => $config['GOOGLE_REDIRECT_URI'],
    'grant_type' => 'authorization_code',
  ]);
  $data = json_decode($resp['body'], true) ?: [];
  return [$resp, $data];
}
function google_refresh_token($config, $refreshToken) {
  $resp = http_post_form('https://oauth2.googleapis.com/token', [
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'client_secret' => $config['GOOGLE_CLIENT_SECRET'],
    'refresh_token' => $refreshToken,
    'grant_type' => 'refresh_token',
  ]);
  $data = json_decode($resp['body'], true) ?: [];
  return [$resp, $data];
}
function google_userinfo($accessToken) {
  $resp = http_get('https://www.googleapis.com/oauth2/v2/userinfo', [
    'Authorization: Bearer ' . $accessToken
  ]);
  $data = json_decode($resp['body'], true) ?: [];
  return [$resp, $data];
}
