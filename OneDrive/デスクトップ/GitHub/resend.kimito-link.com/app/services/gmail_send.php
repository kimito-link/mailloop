<?php
declare(strict_types=1);

// base64url_encode()はgoogle_oauth.phpで定義されているため、ここでは定義しない
function build_rfc822(array $mail): string {
  $headers = [];
  $headers[] = 'From: ' . $mail['from'];
  if (!empty($mail['to']))  $headers[] = 'To: ' . implode(', ', $mail['to']);
  if (!empty($mail['cc']))  $headers[] = 'Cc: ' . implode(', ', $mail['cc']);
  if (!empty($mail['bcc'])) $headers[] = 'Bcc: ' . implode(', ', $mail['bcc']);
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
  return [$resp, $data];
}
