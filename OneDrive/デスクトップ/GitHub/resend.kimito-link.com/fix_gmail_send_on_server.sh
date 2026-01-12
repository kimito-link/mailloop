#!/bin/bash
# サーバー側で実行する gmail_send.php 修正スクリプト

REPO_DIR="/home/besttrust/kimito-link.com/_git/mailloop"
FILE_PATH="$REPO_DIR/app/services/gmail_send.php"
BACKUP_PATH="$FILE_PATH.backup.$(date +%Y%m%d_%H%M%S)"

echo "=== gmail_send.php を修正します ==="

# バックアップを作成
if [ -f "$FILE_PATH" ]; then
    cp "$FILE_PATH" "$BACKUP_PATH"
    echo "✓ バックアップ作成: $BACKUP_PATH"
fi

# 正しい内容でファイルを上書き
cat > "$FILE_PATH" << 'EOFPHP'
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
  return [$resp, $data];
}
EOFPHP

echo "✓ ファイルを更新しました"
echo ""
echo "=== 確認 ==="
echo "ファイルの行数:"
wc -l "$FILE_PATH"
echo ""
echo "extract_emails関数の存在確認:"
if grep -q "function extract_emails" "$FILE_PATH"; then
    echo "✓ extract_emails関数が見つかりました"
    echo ""
    echo "関数の内容（最初の10行）:"
    grep -A 10 "function extract_emails" "$FILE_PATH"
else
    echo "✗ extract_emails関数が見つかりませんでした"
fi
echo ""
echo "=== 完了 ==="
