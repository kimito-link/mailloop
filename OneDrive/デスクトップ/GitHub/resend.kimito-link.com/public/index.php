<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../views/helpers/emails.php';

// エラーハンドリング: 最上流で例外をキャッチ
try {

function render_view(string $viewName, array $vars = []) {
  extract($vars);
  $view = $viewName;
  require __DIR__ . '/../views/layout.php';
}
function require_login($storage) {
  $u = $storage->getUser();
  if (!$u) { header('Location: /auth/login'); exit; }
  return $u;
}

function validateTemplate(array $t): array {
  $errors = [];
  if (empty(trim($t['title'] ?? ''))) {
    $errors[] = 'タイトルは必須です';
  } elseif (mb_strlen($t['title']) > 100) {
    $errors[] = 'タイトルは100文字以内です';
  }
  if (empty(trim($t['subject'] ?? ''))) {
    $errors[] = '件名は必須です';
  } elseif (mb_strlen($t['subject']) > 150) {
    $errors[] = '件名は150文字以内です';
  }
  if (empty(trim($t['body_text'] ?? ''))) {
    $errors[] = '本文は必須です';
  } elseif (mb_strlen($t['body_text']) > 20000) {
    $errors[] = '本文は20,000文字以内です';
  }
  return $errors;
}

function validateGroup(array $g): array {
  $errors = [];
  $name = trim($g['name'] ?? '');
  if (empty($name)) {
    $errors[] = 'グループ名は必須です';
  } elseif (mb_strlen($name) > 100) {
    $errors[] = 'グループ名は100文字以内です';
  }
  // dedupe後の件数で判定（重複除去後の実質件数）
  $to = $g['to'] ?? [];
  $cc = $g['cc'] ?? [];
  $bcc = $g['bcc'] ?? [];
  $total = count($to) + count($cc) + count($bcc);
  if ($total === 0) {
    $errors[] = 'To/CC/BCCのいずれかに最低1件のメールアドレスが必要です';
  }
  if ($total > 100) {
    $errors[] = 'To/CC/BCCの合計は100件以内です（現在: ' . $total . '件）';
  }
  return $errors;
}

function render_error(string $message, ?string $detail = null): void {
  http_response_code(500);
  render_view('error', ['message' => $message, 'detail' => $detail]);
  exit;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
  $token = $_POST['csrf_token'] ?? '';
  $sessionToken = $_SESSION['csrf_token'] ?? '';
  if (empty($sessionToken) || empty($token)) {
    error_log('MailLoop CSRF: Missing token');
    http_response_code(403);
    render_error('不正なリクエストです。CSRFトークンが無効です。');
    return false;
  }
  if (!hash_equals($sessionToken, $token)) {
    error_log('MailLoop CSRF: Token mismatch');
    http_response_code(403);
    render_error('不正なリクエストです。CSRFトークンが一致しません。');
    return false;
  }
  return true;
}

// Routes
route('GET', '/', function() use ($storage) {
  // ログイン状態を判定
  $u = $storage->getUser();
  $loggedIn = !empty($u);

  if (!$loggedIn) {
    header('Location: /auth/login');
    exit;
  }

  // ログイン済みなら /send へ（/send ルートは既に存在）
  header('Location: /send');
  exit;
});

route('GET', '/auth/login', function() use ($config) {
  // デバッグモード（/auth/login?debug=1）
  if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $state = oauth_build_state($config, $_SERVER);
    if ($state === false) {
      header('Content-Type: text/html; charset=UTF-8');
      echo "<h1>Error: APP_KEY not configured</h1>";
      echo "<p>Please set APP_KEY in config/secrets.php</p>";
      exit;
    }
    
    $url = google_auth_url($config, $state);
    $hasState = strpos($url, 'state=') !== false;
    
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>OAuth Debug</title></head><body>";
    echo "<h1>OAuth認可URLデバッグ</h1>";
    echo "<h2>生成されたState（先頭32文字）:</h2>";
    echo "<code>" . htmlspecialchars(substr($state, 0, 32)) . "...</code>";
    echo "<h2>認可URL:</h2>";
    echo "<p><a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($url) . "</a></p>";
    echo "<h2>State検証:</h2>";
    if ($hasState) {
      echo "<p style='color:green;'>✅ stateパラメータが含まれています</p>";
    } else {
      echo "<p style='color:red;'>❌ stateパラメータが含まれていません！</p>";
    }
    echo "<h2>ファイルパス:</h2>";
    echo "<code>" . htmlspecialchars(__FILE__) . "</code>";
    echo "</body></html>";
    exit;
  }
  
  // 通常のリダイレクト版（セッション不要）
  $state = oauth_build_state($config, $_SERVER);
  if ($state === false) {
    http_response_code(500);
    render_error('認証システムの設定エラーです。管理者に連絡してください。');
    return;
  }
  
  $url = google_auth_url($config, $state);
  if (function_exists('app_log')) {
    app_log('LOGIN: redirect to Google with state (first 16 chars: ' . substr($state, 0, 16) . ')');
  }
  
  header('Location: ' . $url);
  exit;
});

route('GET', '/auth/callback', function() use ($config, $storage) {
  // 0) stateが来てない場合の表示（切り分け高速化）
  $state = isset($_GET['state']) ? $_GET['state'] : '';
  if (empty($state)) {
    $isProd = isset($config['APP_ENV']) && $config['APP_ENV'] === 'prod';
    if (!$isProd) {
      // 開発環境: 詳細なデバッグ情報を表示
      header('Content-Type: text/html; charset=UTF-8');
      echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>STATE_MISSING_FROM_GOOGLE_CALLBACK</title></head><body>";
      echo "<h1>❌ STATE_MISSING_FROM_GOOGLE_CALLBACK</h1>";
      echo "<p>Googleからのコールバックにstateパラメータが含まれていません。</p>";
      echo "<h2>REQUEST_URI:</h2>";
      echo "<code>" . htmlspecialchars(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NONE') . "</code>";
      echo "<h2>GET パラメータ:</h2>";
      echo "<ul>";
      echo "<li>code: " . (isset($_GET['code']) ? 'あり（' . substr($_GET['code'], 0, 20) . '...）' : 'なし') . "</li>";
      echo "<li>scope: " . (isset($_GET['scope']) ? 'あり' : 'なし') . "</li>";
      echo "<li>state: なし</li>";
      echo "</ul>";
      echo "<h2>デバッグ:</h2>";
      echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
      echo "</body></html>";
      exit;
    } else {
      // 本番環境: 一般エラー
      http_response_code(400);
      render_error('不正な認証リクエストです（stateパラメータがありません）');
      return;
    }
  }
  
  // 1) ユーザー拒否
  if (!empty($_GET['error'])) {
    $errorDesc = isset($_GET['error_description']) ? $_GET['error_description'] : (isset($_GET['error']) ? $_GET['error'] : '');
    if (function_exists('app_log')) {
      app_log('OAuth error: ' . $errorDesc);
    }
    render_error('認証がキャンセルされました。もう一度お試しください。');
    return;
  }

  // 2) state検証（署名ベース、セッション不要）
  if (function_exists('app_log')) {
    app_log('CALLBACK: URI=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NONE'));
    app_log('CALLBACK: GET state=' . substr($state, 0, 32) . '...');
  }
  
  $errMsg = '';
  $stateValid = oauth_verify_state($config, $state, $_SERVER, $errMsg);
  if (!$stateValid) {
    if (function_exists('app_log')) {
      app_log('CALLBACK: STATE VERIFICATION FAILED: ' . $errMsg);
    }
    
    $isProd = isset($config['APP_ENV']) && $config['APP_ENV'] === 'prod';
    if (!$isProd) {
      // 開発環境: 詳細なエラー情報を表示
      header('Content-Type: text/html; charset=UTF-8');
      echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>State Verification Failed</title></head><body>";
      echo "<h1>❌ State検証失敗</h1>";
      echo "<p>エラー: " . htmlspecialchars($errMsg) . "</p>";
      echo "<h2>REQUEST_URI:</h2>";
      echo "<code>" . htmlspecialchars(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NONE') . "</code>";
      echo "<h2>デバッグ:</h2>";
      echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
      echo "</body></html>";
      exit;
    } else {
      // 本番環境: 一般エラー
      http_response_code(400);
      render_error('不正な認証リクエストです（state検証失敗）');
      return;
    }
  }
  
  if (function_exists('app_log')) {
    app_log('CALLBACK: STATE VERIFICATION OK');
  }

  // 3) code
  $code = $_GET['code'] ?? '';
  if ($code === '') {
    http_response_code(400);
    render_error('認証コードが取得できませんでした。');
    return;
  }

  // 4) code -> token
  [$resp, $data] = google_exchange_code($config, $code);
  // 古いPHP対応：??演算子の代わりにisset()を使用
  $respCode = isset($resp['code']) ? (int)$resp['code'] : 0;
  $accessToken = isset($data['access_token']) ? $data['access_token'] : '';
  if ($respCode !== 200 || empty($accessToken)) {
    $errorBody = isset($resp['body']) ? $resp['body'] : '';
    error_log('Token exchange failed: ' . $errorBody);
    render_error('認証に失敗しました（token取得失敗）');
    return;
  }

  $access = $accessToken;
  $refresh = isset($data['refresh_token']) ? $data['refresh_token'] : null;
  $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 3500;

  // 5) userinfo
  [$uResp, $uData] = google_userinfo($access);
  // 古いPHP対応：??演算子の代わりにisset()を使用
  $sub = isset($uData['sub']) ? $uData['sub'] : (isset($uData['id']) ? $uData['id'] : '');
  $respCode = isset($uResp['code']) ? (int)$uResp['code'] : 0;
  if ($respCode !== 200 || $sub === '') {
    $errorBody = isset($uResp['body']) ? $uResp['body'] : '';
    error_log('Userinfo failed: ' . $errorBody);
    render_error('認証に失敗しました（ユーザー情報取得失敗）');
    return;
  }

  // 6) users upsert
  $user = [
    'provider' => 'google',
    'provider_sub' => $sub,
    'email' => isset($uData['email']) ? $uData['email'] : null,
    'name' => isset($uData['name']) ? $uData['name'] : null,
    'picture' => isset($uData['picture']) ? $uData['picture'] : null,
  ];
  $user = $storage->upsertUser($user);
  // idが存在することを確認
  if (!isset($user['id'])) {
    error_log('upsertUser failed: id not returned');
    render_error('認証に失敗しました（ユーザー登録失敗）');
    return;
  }
  $userId = (int)$user['id'];

  // 7) token保存（storage側が encrypt する形でもOKだが、今の流儀に合わせてここでencrypt）
  $tokenRow = [
    'access_token_enc' => encrypt_str($access, $config['APP_KEY']),
    'refresh_token_enc' => $refresh ? encrypt_str($refresh, $config['APP_KEY']) : null,
    'expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
    'scopes' => $config['GMAIL_SCOPE'],
  ];
  $storage->saveToken($userId, $tokenRow);

  // 8) セッション確立
  session_regenerate_id(true);
  $_SESSION['user_id'] = $userId;
  $_SESSION['user'] = $user;

  // 9) CSRF再生成
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

  // 10) リダイレクト
  header('Location: /templates');
  exit;
});

route('POST', '/auth/logout', function() {
  session_destroy();
  header('Location: /auth/login'); exit;
});

// Templates
route('GET', '/templates', function() use ($storage) {
  $u = require_login($storage);
  $q = $_GET['q'] ?? '';
  $templates = $storage->listTemplates($u['id'], $q);
  render_view('templates/index', ['user'=>$u, 'templates'=>$templates, 'q'=>$q, 'page'=>'templates']);
});
route('GET', '/templates/new', function() use ($storage) {
  $u = require_login($storage);
  render_view('templates/edit', ['user'=>$u, 't'=>null, 'page'=>'templates']);
});
route('GET', '/templates/edit', function() use ($storage) {
  $u = require_login($storage);
  $id = (int)($_GET['id'] ?? 0);
  $t = $storage->getTemplate($u['id'], $id);
  if (!$t) { header('Location: /templates'); exit; }
  render_view('templates/edit', ['user'=>$u, 't'=>$t, 'page'=>'templates']);
});
route('POST', '/templates/save', function() use ($storage) {
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
  $u = require_login($storage);
  $id = (int)($_POST['id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $body = trim($_POST['body_text'] ?? '');
  $att = trim($_POST['attachment_url'] ?? '');
  
  // バリデーション
  $tpl = ['title'=>$title, 'subject'=>$subject, 'body_text'=>$body];
  $errors = validateTemplate($tpl);
  
  if (!empty($errors)) {
    // エラー時は編集画面を再表示
    $t = $id > 0 ? $storage->getTemplate($u['id'], $id) : null;
    if ($t) {
      $t['title'] = $title;
      $t['subject'] = $subject;
      $t['body_text'] = $body;
    } else {
      $t = ['title'=>$title, 'subject'=>$subject, 'body_text'=>$body];
    }
    render_view('templates/edit', ['user'=>$u, 't'=>$t, 'errors'=>$errors, 'page'=>'templates']);
    return;
  }
  
  // バリデーション通過後、保存
  $tpl['attachments'] = $att ? [['type'=>'link','url'=>$att]] : [];
  if ($id>0) $storage->updateTemplate($u['id'], $id, $tpl);
  else $storage->createTemplate($u['id'], $tpl);
  header('Location: /templates'); exit;
});
route('POST', '/templates/delete', function() use ($storage) {
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
  $u = require_login($storage);
  $id = (int)($_POST['id'] ?? 0);
  if ($id>0) $storage->deleteTemplate($u['id'], $id);
  header('Location: /templates'); exit;
});

// Groups
route('GET', '/groups', function() use ($storage) {
  $u = require_login($storage);
  $q = $_GET['q'] ?? '';
  $groups = $storage->listGroups($u['id'], $q);
  render_view('groups/index', ['user'=>$u, 'groups'=>$groups, 'q'=>$q, 'page'=>'groups']);
});
route('GET', '/groups/new', function() use ($storage) {
  $u = require_login($storage);
  render_view('groups/edit', ['user'=>$u, 'g'=>null, 'warn'=>[], 'page'=>'groups']);
});
route('GET', '/groups/edit', function() use ($storage) {
  $u = require_login($storage);
  $id=(int)($_GET['id']??0);
  $g=$storage->getGroup($u['id'],$id);
  if(!$g){ header('Location: /groups'); exit; }
  render_view('groups/edit', ['user'=>$u, 'g'=>$g, 'warn'=>[], 'page'=>'groups']);
});
route('POST', '/groups/save', function() use ($storage) {
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
  $u = require_login($storage);
  $id=(int)($_POST['id']??0);
  $name=trim($_POST['name']??'');
  $to=parse_email_list($_POST['to_list']??'');
  $cc=parse_email_list($_POST['cc_list']??'');
  $bcc=parse_email_list($_POST['bcc_list']??'');
  // 重複除去（dedupe）を先に実行
  [$to,$cc,$bcc,$warn] = dedupe_priority($to,$cc,$bcc);
  
  // バリデーション（dedupe後の件数で判定）
  $grp = ['name'=>$name, 'to'=>$to, 'cc'=>$cc, 'bcc'=>$bcc];
  $errors = validateGroup($grp);
  
  if (!empty($errors)) {
    // エラー時は編集画面を再表示
    $g = $id > 0 ? $storage->getGroup($u['id'], $id) : null;
    if ($g) {
      $g['name'] = $name;
      $g['to'] = $to;
      $g['cc'] = $cc;
      $g['bcc'] = $bcc;
    } else {
      $g = ['name'=>$name, 'to'=>$to, 'cc'=>$cc, 'bcc'=>$bcc];
    }
    render_view('groups/edit', ['user'=>$u, 'g'=>$g, 'errors'=>$errors, 'warn'=>$warn, 'page'=>'groups']);
    return;
  }
  
  // JSON形式に変換（[{"email":"...","name":"..."}]形式）
  $toJson = array_map(fn($e) => ['email' => $e, 'name' => ''], $to);
  $ccJson = array_map(fn($e) => ['email' => $e, 'name' => ''], $cc);
  $bccJson = array_map(fn($e) => ['email' => $e, 'name' => ''], $bcc);
  
  // バリデーション通過後、保存（グループ名もトリム済み）
  $grp = ['name'=>$name, 'to'=>$toJson, 'cc'=>$ccJson, 'bcc'=>$bccJson];
  if($id>0) $storage->updateGroup($u['id'],$id,$grp);
  else $storage->createGroup($u['id'],$grp);
  header('Location: /groups'); exit;
});
route('POST', '/groups/delete', function() use ($storage) {
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
  $u = require_login($storage);
  $id=(int)($_POST['id']??0);
  if($id>0) $storage->deleteGroup($u['id'],$id);
  header('Location: /groups'); exit;
});

// Send flow
route('GET', '/send', function() use ($storage) {
  $u=require_login($storage);
  $template_id=(int)($_GET['template_id']??0);
  $t=$template_id ? $storage->getTemplate($u['id'],$template_id) : null;
  $groups=$storage->listGroups($u['id'],'');
  render_view('send/prepare', ['user'=>$u,'t'=>$t,'groups'=>$groups,'page'=>'send']);
});
route('POST', '/send/confirm', function() use ($storage, $config) {
  $u=require_login($storage);
  $template_id=(int)($_POST['template_id']??0);
  $group_id=(int)($_POST['group_id']??0);
  $t=$storage->getTemplate($u['id'],$template_id);
  $g=$storage->getGroup($u['id'],$group_id);
  if (!$t || !$g) { header('Location: /send'); exit; }

  $subject=trim($_POST['subject'] ?? ($t['subject'] ?? ''));
  $body=trim($_POST['body_text'] ?? ($t['body_text'] ?? ''));

  $to = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
  $cc = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
  $bcc = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);

  $counts=['to'=>count($to),'cc'=>count($cc),'bcc'=>count($bcc),'total'=>count($to)+count($cc)+count($bcc)];
  $warnings=[];
  if($counts['bcc'] >= ($config['WARN_BCC_2']??100)) $warnings[]='BCCが多いです（強い注意）';
  else if($counts['bcc'] >= ($config['WARN_BCC_1']??50)) $warnings[]='BCCが多いです（注意）';

  render_view('send/confirm', ['user'=>$u,'t'=>$t,'g'=>$g,'subject'=>$subject,'body'=>$body,'counts'=>$counts,'warnings'=>$warnings,'page'=>'send']);
});
route('POST', '/send/execute', function() use ($storage, $config) {
  $u=require_login($storage);
  $template_id=(int)($_POST['template_id']??0);
  $group_id=(int)($_POST['group_id']??0);
  $subject=trim($_POST['subject']??'');
  $body=trim($_POST['body_text']??'');
  $mode=$_POST['mode']??'send';
  $t=$storage->getTemplate($u['id'],$template_id);
  $g=$storage->getGroup($u['id'],$group_id);
  if(!$t || !$g){ header('Location: /send'); exit; }

  // send_attempt_id を生成（同一送信試行を識別するため、UUID v4形式）
  $bytes = random_bytes(16);
  $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
  $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // variant
  $sendAttemptId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)); // UUID v4形式（36文字）

  // トークン取得（期限切れが近ければ自動refresh）
  require_once __DIR__ . '/../app/services/token_manager.php';
  try {
    $access = get_google_access_token_or_refresh($storage, $config, (int)$u['id']);
  } catch (RuntimeException $e) {
    error_log('Token refresh failed in /send/execute: ' . $e->getMessage());
    header('Location: /auth/login?reauth=1');
    exit;
  }

  $to = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
  $cc = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
  $bcc = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);

  if($mode==='test'){ $to=[$u['email']]; $cc=[]; $bcc=[]; }

  $mail = [
    'from'=>$u['email'],
    'to'=>$to,
    'cc'=>$cc,
    'bcc'=>$bcc,
    'subject'=>$subject,
    'body_text'=>$body,
  ];

  // 実行時間制限を延長（BCC100件など大量送信時）
  set_time_limit(60);
  
  // Gmail送信（401の場合は一度だけrefreshして再試行、429/5xxは最大2回リトライ）
  [$sResp,$sData]=gmail_send_message($access, $mail);
  $httpCode = (int)($sResp['code'] ?? 0);
  
  // 401の場合は一度だけrefreshして再試行
  if ($httpCode === 401) {
    try {
      $access = force_refresh_google_access_token($storage, $config, (int)$u['id']);
      [$sResp,$sData]=gmail_send_message($access, $mail);
      $httpCode = (int)($sResp['code'] ?? 0);
    } catch (RuntimeException $e) {
      error_log('Force refresh failed in /send/execute: ' . $e->getMessage());
      header('Location: /auth/login?reauth=1');
      exit;
    }
  }
  
  // 403（権限不足）の場合は再試行不要、再認証へ誘導
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
    // 送信先の件数を計算
    $toCount = count($to);
    $ccCount = count($cc);
    $bccCount = count($bcc);
    $totalCount = $toCount + $ccCount + $bccCount;
    
    $logId = $storage->upsertLogByAttempt($u['id'], [
      'send_attempt_id'=>$sendAttemptId,
      'template_id'=>$template_id,
      'group_id'=>$group_id,
      'subject_snapshot'=>$subject,
      'body_snapshot'=>$body,
      'attachments_snapshot'=>[],
      'counts'=>['to'=>$toCount,'cc'=>$ccCount,'bcc'=>$bccCount,'total'=>$totalCount],
      'status'=>$status,
      'error'=>$error,
      'gmail_message_id'=>null,
    ]);
    render_error('権限が不足しています。再ログインしてください。');
    return;
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
  $toCount = count($to);
  $ccCount = count($cc);
  $bccCount = count($bcc);
  $totalCount = $toCount + $ccCount + $bccCount;
  
  // send_attempt_id を使ってログを upsert（同一送信試行は1行にまとめる）
  $logId = $storage->upsertLogByAttempt($u['id'], [
    'send_attempt_id'=>$sendAttemptId,
    'template_id'=>$template_id,
    'group_id'=>$group_id,
    'subject_snapshot'=>$subject,
    'body_snapshot'=>$body,
    'attachments_snapshot'=>[],
    'counts'=>['to'=>$toCount,'cc'=>$ccCount,'bcc'=>$bccCount,'total'=>$totalCount],
    'status'=>$status,
    'error'=>$error,
    'gmail_message_id'=>$sData['id'] ?? null,
  ]);

  header('Location: /logs/view?id=' . $logId);
  exit;
});

// Logs
route('GET','/logs', function() use ($storage){
  $u=require_login($storage);
  $logs=$storage->listLogs($u['id']);
  render_view('logs/index',['user'=>$u,'logs'=>$logs,'page'=>'logs']);
});
route('GET','/logs/view', function() use ($storage){
  $u=require_login($storage);
  $id=(int)($_GET['id']??0);
  $log=$storage->getLog($u['id'],$id);
  if(!$log){ header('Location:/logs'); exit; }
  render_view('logs/view',['user'=>$u,'log'=>$log,'page'=>'logs']);
});

  dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], []);
} catch (RuntimeException $e) {
  error_log('MailLoop Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  $detail = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null;
  render_error('システムエラーが発生しました。管理者にお問い合わせください。', $detail);
} catch (Exception $e) {
  error_log('MailLoop Unexpected Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  $detail = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null;
  render_error('予期しないエラーが発生しました。', $detail);
} catch (Throwable $e) {
  error_log('MailLoop Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  $detail = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null;
  render_error('予期しないエラーが発生しました。', $detail);
}
