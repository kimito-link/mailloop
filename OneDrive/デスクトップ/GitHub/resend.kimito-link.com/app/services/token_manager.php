<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

require_once __DIR__ . '/google_oauth.php'; // google_refresh_token()
require_once __DIR__ . '/../lib/crypto.php'; // encrypt_str / decrypt_str

/**
 * 期限切れが近ければ refresh して保存し、常に「使える access_token(平文)」を返す。
 *
 * @param Storage $storage
 * @param array $config
 * @param int $userId
 * @param int $skewSeconds 有効期限の何秒前にrefreshするか（デフォルト: 300秒 = 5分）
 * @return string アクセストークン（平文）
 * @throws RuntimeException 再認証が必要/トークン取得不能など
 */
function get_google_access_token_or_refresh($storage, array $config, int $userId, int $skewSeconds = 300): string
{
  $tokRow = $storage->getToken($userId);
  if (!$tokRow) {
    throw new RuntimeException('No token. Please login again.');
  }

  // 復号
  $accessEnc = $tokRow['access_token_enc'] ?? '';
  if ($accessEnc === '') {
    throw new RuntimeException('Token is broken (missing access token). Please login again.');
  }
  $access = decrypt_str($accessEnc, $config['APP_KEY']);

  $refresh = null;
  if (!empty($tokRow['refresh_token_enc'])) {
    $refresh = decrypt_str($tokRow['refresh_token_enc'], $config['APP_KEY']);
  }

  // expires_at 判定（DATETIME文字列 → epoch）
  $expiresAtStr = $tokRow['expires_at'] ?? '';
  $expiresAt = $expiresAtStr !== '' ? strtotime($expiresAtStr) : 0;

  // expires_at が無い/壊れてる場合は「保守的にrefresh」する
  $now = time();
  $needsRefresh = ($expiresAt <= 0) || ($expiresAt - $now <= $skewSeconds);

  if (!$needsRefresh) {
    return $access;
  }

  // refresh競合対策：updated_at が30秒以内なら、もう一度getTokenして返す（他プロセスがrefresh済みの可能性）
  // さらに expires_at で二段判定（再取得したtokenのexpires_atが十分未来なら採用）
  $updatedAtStr = $tokRow['updated_at'] ?? '';
  if ($updatedAtStr !== '') {
    $updatedAt = strtotime($updatedAtStr);
    if ($updatedAt > 0 && ($now - $updatedAt) < 30) {
      // 30秒以内に更新されているので、再取得して返す
      $tokRow2 = $storage->getToken($userId);
      if ($tokRow2 && !empty($tokRow2['access_token_enc'])) {
        // expires_at で二段判定：再取得したtokenのexpires_atが十分未来なら採用
        $expiresAtStr2 = $tokRow2['expires_at'] ?? '';
        $expiresAt2 = $expiresAtStr2 !== '' ? strtotime($expiresAtStr2) : 0;
        if ($expiresAt2 > 0 && ($expiresAt2 - $now) > $skewSeconds) {
          // 十分未来のexpires_atなので、このtokenを採用
          $access2 = decrypt_str($tokRow2['access_token_enc'], $config['APP_KEY']);
          if ($access2 !== '') {
            return $access2;
          }
        }
      }
    }
  }

  // refresh_token が無い場合は再認証が必要
  if (!$refresh) {
    throw new RuntimeException('Refresh token is missing. Please login again.');
  }

  // refresh 実行
  [$resp, $data] = google_refresh_token($config, $refresh);
  $code = (int)($resp['code'] ?? 0);

  if ($code !== 200 || empty($data['access_token'])) {
    error_log('Refresh failed: HTTP ' . $code . ' body=' . ($resp['body'] ?? ''));
    // refresh失敗は再認証で復旧するのが安全
    throw new RuntimeException('Token refresh failed. Please login again.');
  }

  $newAccess = $data['access_token'];
  $expiresIn = (int)($data['expires_in'] ?? 3500);

  // Googleは refresh で refresh_token を返さないことが多いので「既存refreshを保持」
  $newRefresh = $refresh;

  // 保存（暗号化）
  $tokenRow = [
    'access_token_enc' => encrypt_str($newAccess, $config['APP_KEY']),
    'refresh_token_enc' => $newRefresh ? encrypt_str($newRefresh, $config['APP_KEY']) : null,
    'expires_at' => date('Y-m-d H:i:s', $now + $expiresIn),
    'scopes' => $tokRow['scopes'] ?? ($config['GMAIL_SCOPE'] ?? ''),
  ];
  $storage->saveToken($userId, $tokenRow);

  return $newAccess;
}

/**
 * 401などが出たときの「一度だけrefreshして再取得」用
 *
 * @param Storage $storage
 * @param array $config
 * @param int $userId
 * @return string アクセストークン（平文）
 * @throws RuntimeException 再認証が必要/トークン取得不能など
 */
function force_refresh_google_access_token($storage, array $config, int $userId): string
{
  // skewSeconds を大きくして強制refresh
  return get_google_access_token_or_refresh($storage, $config, $userId, 365 * 24 * 3600);
}
