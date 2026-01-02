<?php
declare(strict_types=1);

function google_auth_url(array $config, string $state): string {
  $scopes = trim(($config['GOOGLE_SCOPES'] ?? '') . ' ' . ($config['GMAIL_SCOPE'] ?? ''));
  $params = [
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'redirect_uri' => $config['GOOGLE_REDIRECT_URI'],
    'response_type' => 'code',
    'scope' => $scopes,
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => $state,
  ];
  return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
function google_exchange_code(array $config, string $code): array {
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
function google_refresh_token(array $config, string $refreshToken): array {
  $resp = http_post_form('https://oauth2.googleapis.com/token', [
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'client_secret' => $config['GOOGLE_CLIENT_SECRET'],
    'refresh_token' => $refreshToken,
    'grant_type' => 'refresh_token',
  ]);
  $data = json_decode($resp['body'], true) ?: [];
  return [$resp, $data];
}
function google_userinfo(string $accessToken): array {
  $resp = http_get('https://www.googleapis.com/oauth2/v2/userinfo', [
    'Authorization: Bearer ' . $accessToken
  ]);
  $data = json_decode($resp['body'], true) ?: [];
  return [$resp, $data];
}
