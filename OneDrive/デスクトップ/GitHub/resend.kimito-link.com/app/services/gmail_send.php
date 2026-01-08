<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

// base64url_encode()はgoogle_oauth.phpで定義されているため、ここでは定義しない
function extract_emails($list): array {
  if (empty($list)) return [];
  // 単一の文字列やnullが渡された場合
  if (!is_array($list)) return [(string)$list];
  
  // 単一の連想配列（['email' => '...']）が渡された場合
  if (isset($list['email']) && !is_array($list['email'])) {
    return [(string)$list['email']];
  }
  
  $emails = [];
  foreach ($list as $item) {
    if (is_array($item)) {
      if (isset($item['email']) && !is_array($item['email'])) {
        $emails[] = (string)$item['email'];
      }
    } elseif (!is_null($item) && (string)$item !== '') {
      $emails[] = (string)$item;
    }
  }
  return array_values(array_unique($emails));
}

function build_rfc822(array $mail): string {
  $headers = [];
  $headers[] = 'From: ' . $mail['from'];
  if (!empty($mail['to']))  $headers[] = 'To: ' . implode(', ', extract_emails($mail['to']));
  if (!empty($mail['cc']))  $headers[] = 'Cc: ' . implode(', ', extract_emails($mail['cc']));
  if (!empty($mail['bcc'])) $headers[] = 'Bcc: ' . implode(', ', extract_emails($mail['bcc']));
  $headers[] = 'Subject: ' . mb_encode_mimeheader($mail['subject'], 'UTF-8');
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-Type: text/plain; charset="UTF-8"';
  $headers[] = 'Content-Transfer-Encoding: 8bit';
  return implode("\r\n", $headers) . "\r\n\r\n" . $mail['body_text'];
}
function gmail_send_message(string $accessToken, array $mail): array {
  $raw = build_rfc822($mail);
  $payload = ['raw' => base64url_encode($raw)];
  $resp = http_post_json('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', $payload, [
    'Authorization: Bearer ' . $accessToken
  ]);
  $data = json_decode($resp['body'], true) ?: [];
  
  // エラー時（4xx/5xx）のログ出力
  $httpCode = (int)($resp['code'] ?? 0);
  if ($httpCode >= 400) {
    $tokenPreview = substr($accessToken, 0, 10) . '...';
    $errorBody = mb_substr($resp['body'] ?? '', 0, 2000);
    $errorReason = '';
    $errorMessage = '';
    if (is_array($data) && isset($data['error'])) {
      $errorReason = $data['error']['errors'][0]['reason'] ?? '';
      $errorMessage = $data['error']['message'] ?? '';
    }
    error_log(sprintf(
      'Gmail API Error: HTTP %d | reason=%s | message=%s | token_preview=%s | body_preview=%s',
      $httpCode,
      $errorReason,
      $errorMessage,
      $tokenPreview,
      $errorBody
    ));
  }
  
  return [$resp, $data];
}
