#!/bin/bash
# サーバー側で実行する修正コマンド

echo "=== Fixing gmail_send.php on server ==="

cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成
cp app/services/gmail_send.php app/services/gmail_send.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✓ Backup created"

# ファイルを正しい内容で上書き
cat > app/services/gmail_send.php << 'ENDOFFILE'
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
ENDOFFILE

echo "✓ File updated"
echo ""
echo "=== Verifying ==="
head -10 app/services/gmail_send.php
echo ""
echo "=== Checking google_oauth.php ==="
head -10 app/services/google_oauth.php
echo ""
echo "=== Done ==="
