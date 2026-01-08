<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

require_once __DIR__ . '/token_manager.php';
require_once __DIR__ . '/gmail_send.php';

/**
 * 送信処理の中核（token取得→送信→retry→log upsert）
 * 
 * @param Storage $storage
 * @param array $config
 * @param array $user
 * @param array $mail ['from'=>string, 'to'=>array, 'cc'=>array, 'bcc'=>array, 'subject'=>string, 'body_text'=>string]
 * @param array $meta ['template_id'=>int, 'group_id'=>int, 'send_attempt_id'=>string]
 * @return array ['status'=>'success|failed', 'http_code'=>int, 'log_id'=>int, 'send_attempt_id'=>string, 'error'=>array|null]
 */
function send_mail_with_logging($storage, array $config, array $user, array $mail, array $meta): array {
  $sendAttemptId = $meta['send_attempt_id'] ?? '';
  if ($sendAttemptId === '') {
    throw new RuntimeException('send_attempt_id is required');
  }

  // トークン取得（期限切れが近ければ自動refresh）
  try {
    $access = get_google_access_token_or_refresh($storage, $config, (int)$user['id']);
  } catch (RuntimeException $e) {
    error_log('Token refresh failed in send_mail_with_logging: ' . $e->getMessage());
    throw $e;
  }

  // 実行時間制限を延長（BCC100件など大量送信時）
  set_time_limit(60);
  
  // Gmail送信（401の場合は一度だけrefreshして再試行、429/5xxは最大2回リトライ）
  [$sResp,$sData]=gmail_send_message($access, $mail);
  $httpCode = (int)($sResp['code'] ?? 0);
  
  // 401の場合は一度だけrefreshして再試行
  if ($httpCode === 401) {
    try {
      $access = force_refresh_google_access_token($storage, $config, (int)$user['id']);
      [$sResp,$sData]=gmail_send_message($access, $mail);
      $httpCode = (int)($sResp['code'] ?? 0);
    } catch (RuntimeException $e) {
      error_log('Force refresh failed in send_mail_with_logging: ' . $e->getMessage());
      throw $e;
    }
  }
  
  // 403（権限不足）の場合は再試行不要、エラーログ記録
  if ($httpCode === 403) {
    $status = 'failed';
    $error = [
      'http_code' => 403,
      'google_error' => [
        'message' => '権限が不足しています',
        'status' => 'PERMISSION_DENIED',
      ],
      'when' => date('c'),
    ];
    
    $toCount = count($mail['to'] ?? []);
    $ccCount = count($mail['cc'] ?? []);
    $bccCount = count($mail['bcc'] ?? []);
    $totalCount = $toCount + $ccCount + $bccCount;
    
    $logId = $storage->upsertLogByAttempt((int)$user['id'], [
      'send_attempt_id'=>$sendAttemptId,
      'template_id'=>(int)($meta['template_id'] ?? 0),
      'group_id'=>(int)($meta['group_id'] ?? 0),
      'subject_snapshot'=>$mail['subject'] ?? '',
      'body_snapshot'=>$mail['body_text'] ?? '',
      'attachments_snapshot'=>[],
      'counts'=>['to'=>$toCount,'cc'=>$ccCount,'bcc'=>$bccCount,'total'=>$totalCount],
      'status'=>$status,
      'error'=>$error,
      'gmail_message_id'=>null,
    ]);
    
    return [
      'status' => $status,
      'http_code' => $httpCode,
      'log_id' => $logId,
      'send_attempt_id' => $sendAttemptId,
      'error' => $error,
    ];
  }
  
  // 429（レート制限）または5xx（サーバーエラー）の場合は最大2回リトライ
  $retryCount = 0;
  $maxRetries = 2;
  while (($httpCode === 429 || ($httpCode >= 500 && $httpCode < 600)) && $retryCount < $maxRetries) {
    sleep($retryCount + 1); // 1秒、2秒
    [$sResp,$sData]=gmail_send_message($access, $mail);
    $httpCode = (int)($sResp['code'] ?? 0);
    $retryCount++;
  }
  
  $status = ($httpCode === 200) ? 'success' : 'failed';
  
  // error_json を構造化・短縮（PIIを避ける）
  $error = null;
  if ($status === 'failed') {
    $errorBody = $sResp['body'] ?? '';
    $errorBodyShort = mb_substr($errorBody, 0, 2000); // 最大2000文字
    $errorData = json_decode($errorBodyShort, true);
    
    $error = [
      'http_code' => $sResp['code'] ?? 0,
      'curl_error' => $sResp['error'] ?? null,
    ];
    
    // Google APIのエラー情報を抽出
    if (is_array($errorData) && isset($errorData['error'])) {
      $error['google_error'] = [
        'message' => $errorData['error']['message'] ?? null,
        'status' => $errorData['error']['status'] ?? null,
        'reason' => $errorData['error']['errors'][0]['reason'] ?? null,
      ];
    } else {
      $error['body_preview'] = $errorBodyShort;
    }
    
    $error['when'] = date('c'); // ISO 8601形式
  }
  
  // 送信先の件数を計算（totalも含める）
  $toCount = count($mail['to'] ?? []);
  $ccCount = count($mail['cc'] ?? []);
  $bccCount = count($mail['bcc'] ?? []);
  $totalCount = $toCount + $ccCount + $bccCount;
  
  // send_attempt_id を使ってログを upsert（同一送信試行は1行にまとめる）
  $logId = $storage->upsertLogByAttempt((int)$user['id'], [
    'send_attempt_id'=>$sendAttemptId,
    'template_id'=>(int)($meta['template_id'] ?? 0),
    'group_id'=>(int)($meta['group_id'] ?? 0),
    'subject_snapshot'=>$mail['subject'] ?? '',
    'body_snapshot'=>$mail['body_text'] ?? '',
    'attachments_snapshot'=>[],
    'counts'=>['to'=>$toCount,'cc'=>$ccCount,'bcc'=>$bccCount,'total'=>$totalCount],
    'status'=>$status,
    'error'=>$error,
    'gmail_message_id'=>$sData['id'] ?? null,
  ]);

  return [
    'status' => $status,
    'http_code' => $httpCode,
    'log_id' => $logId,
    'send_attempt_id' => $sendAttemptId,
    'error' => $error,
  ];
}
