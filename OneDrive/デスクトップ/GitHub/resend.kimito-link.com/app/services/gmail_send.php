<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

// base64url_encode()はgoogle_oauth.phpで定義されているため、ここでは定義しない

/**
 * 宛先リストからメールアドレスを抽出する関数
 * 
 * 以下の形式に対応：
 * - 文字列: 'email@example.com'
 * - 文字列の配列: ['email1@example.com', 'email2@example.com']
 * - 連想配列の配列: [{'email': 'email1@example.com', 'name': ''}, {'email': 'email2@example.com', 'name': ''}]
 * - 単一の連想配列: {'email': 'email@example.com', 'name': ''}
 * - ネストされた配列（再帰的処理）
 * 
 * @param mixed $list 宛先リスト（文字列、配列、連想配列など）
 * @return array 抽出されたメールアドレスの配列（重複除去済み）
 */
function extract_emails($list): array {
  if (empty($list)) return [];
  
  // 単一の文字列やnullが渡された場合
  if (!is_array($list)) {
    $email = trim((string)$list);
    return $email !== '' ? [$email] : [];
  }
  
  // 単一の連想配列（['email' => '...']）が渡された場合
  // 'email'キーが存在し、その値が配列でない場合（連想配列として扱う）
  // 数値キーの配列（[0, 1, 2...]）の場合は、'email'キーは存在しないので、この条件はfalseになる
  if (isset($list['email']) && !is_array($list['email'])) {
    $email = trim((string)$list['email']);
    return $email !== '' ? [$email] : [];
  }
  
  // 配列の場合（[{'email': '...'}, ...] または ['email1@...', 'email2@...']）
  $emails = [];
  foreach ($list as $item) {
    if (is_array($item)) {
      // 連想配列の場合、'email'キーからメールアドレスを抽出
      // 例: {'email': 'user@example.com', 'name': 'User Name'}
      if (isset($item['email']) && !is_array($item['email'])) {
        $email = trim((string)$item['email']);
        if ($email !== '') {
          $emails[] = $email;
        }
      }
      // 'email'キーがない場合、配列の値から直接メールアドレスを探す（再帰的処理）
      // ネストされた配列構造に対応
      else {
        $nestedEmails = extract_emails($item);
        $emails = array_merge($emails, $nestedEmails);
      }
    } elseif (!is_null($item)) {
      // 文字列の配列の場合（例: ['email1@example.com', 'email2@example.com']）
      $email = trim((string)$item);
      if ($email !== '') {
        $emails[] = $email;
      }
    }
  }
  // 空の文字列を除外し、重複を削除して返す
  return array_values(array_unique(array_filter($emails, function($email) {
    return $email !== '';
  })));
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
