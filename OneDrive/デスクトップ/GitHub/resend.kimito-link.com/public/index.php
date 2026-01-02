<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../views/helpers/emails.php';

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

// Routes
route('GET', '/', function() {
  render_view('home', ['page' => 'home']);
});

route('GET', '/auth/login', function() use ($config) {
  header('Location: ' . google_auth_url($config)); exit;
});

route('GET', '/auth/callback', function() use ($config, $storage) {
  $code = $_GET['code'] ?? '';
  if ($code === '') { echo 'Missing code'; return; }
  [$resp, $data] = google_exchange_code($config, $code);
  if (($resp['code'] ?? 0) !== 200 || empty($data['access_token'])) {
    echo "OAuth failed: " . htmlspecialchars($resp['body']); return;
  }
  $access = $data['access_token'];
  $refresh = $data['refresh_token'] ?? '';
  [$uResp, $uData] = google_userinfo($access);
  if (($uResp['code'] ?? 0) !== 200 || empty($uData['email'])) {
    echo "Userinfo failed: " . htmlspecialchars($uResp['body']); return;
  }
  $userId = abs(crc32($uData['id'] ?? $uData['email']));
  $user = [
    'id' => $userId,
    'email' => $uData['email'],
    'name' => $uData['name'] ?? $uData['email'],
    'avatar' => $uData['picture'] ?? '',
  ];
  $storage->upsertUser($user);

  $token = [
    'access_token_enc' => encrypt_str($access, $config['APP_KEY']),
    'refresh_token_enc' => $refresh ? encrypt_str($refresh, $config['APP_KEY']) : null,
    'expires_at' => date('Y-m-d H:i:s', time() + (int)($data['expires_in'] ?? 3500)),
    'scopes' => $config['GMAIL_SCOPE'],
  ];
  $storage->saveToken($userId, $token);

  header('Location: /templates'); exit;
});

route('POST', '/auth/logout', function() {
  session_destroy();
  header('Location: /auth/login'); exit;
});

// Templates
// TODO: OAuth実装後は require_login に戻す
route('GET', '/templates', function() use ($storage) {
  // 一時的にダミーユーザーで動作確認（OAuth実装前）
  $u = $storage->getUser();
  if (!$u) {
    $u = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];
    $storage->upsertUser($u);
  }
  $q = $_GET['q'] ?? '';
  $templates = $storage->listTemplates($u['id'], $q);
  render_view('templates/index', ['user'=>$u, 'templates'=>$templates, 'q'=>$q, 'page'=>'templates']);
});
route('GET', '/templates/new', function() use ($storage) {
  // 一時的にダミーユーザーで動作確認（OAuth実装前）
  $u = $storage->getUser();
  if (!$u) {
    $u = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];
    $storage->upsertUser($u);
  }
  render_view('templates/edit', ['user'=>$u, 't'=>null, 'page'=>'templates']);
});
route('GET', '/templates/edit', function() use ($storage) {
  // 一時的にダミーユーザーで動作確認（OAuth実装前）
  $u = $storage->getUser();
  if (!$u) {
    $u = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];
    $storage->upsertUser($u);
  }
  $id = (int)($_GET['id'] ?? 0);
  $t = $storage->getTemplate($u['id'], $id);
  if (!$t) { header('Location: /templates'); exit; }
  render_view('templates/edit', ['user'=>$u, 't'=>$t, 'page'=>'templates']);
});
route('POST', '/templates/save', function() use ($storage) {
  // 一時的にダミーユーザーで動作確認（OAuth実装前）
  $u = $storage->getUser();
  if (!$u) {
    $u = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];
    $storage->upsertUser($u);
  }
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
  render_view('groups/edit', ['user'=>$u, 'g'=>null, 'page'=>'groups']);
});
route('GET', '/groups/edit', function() use ($storage) {
  $u = require_login($storage);
  $id=(int)($_GET['id']??0);
  $g=$storage->getGroup($u['id'],$id);
  if(!$g){ header('Location: /groups'); exit; }
  render_view('groups/edit', ['user'=>$u, 'g'=>$g, 'page'=>'groups']);
});
route('POST', '/groups/save', function() use ($storage) {
  $u = require_login($storage);
  $id=(int)($_POST['id']??0);
  $name=trim($_POST['name']??'');
  $to=parse_email_list($_POST['to_list']??'');
  $cc=parse_email_list($_POST['cc_list']??'');
  $bcc=parse_email_list($_POST['bcc_list']??'');
  [$to,$cc,$bcc,$warn] = dedupe_priority($to,$cc,$bcc);
  $grp=['name'=>$name?:'Untitled','to'=>$to,'cc'=>$cc,'bcc'=>$bcc,'warn'=>$warn];
  if($id>0) $storage->updateGroup($u['id'],$id,$grp);
  else $storage->createGroup($u['id'],$grp);
  header('Location: /groups'); exit;
});
route('POST', '/groups/delete', function() use ($storage) {
  $u=require_login($storage);
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

  $tokRow=$storage->getToken($u['id']);
  if(!$tokRow){ echo "No token. Please login again."; return; }
  $access=decrypt_str($tokRow['access_token_enc'], $config['APP_KEY']);
  $refresh=$tokRow['refresh_token_enc'] ? decrypt_str($tokRow['refresh_token_enc'], $config['APP_KEY']) : '';

  if (!empty($tokRow['expires_at']) && strtotime($tokRow['expires_at']) < time()+30 && $refresh) {
    [$rResp,$rData]=google_refresh_token($config,$refresh);
    if(($rResp['code']??0)===200 && !empty($rData['access_token'])) {
      $access=$rData['access_token'];
      $tokRow['access_token_enc']=encrypt_str($access, $config['APP_KEY']);
      $tokRow['expires_at']=date('Y-m-d H:i:s', time() + (int)($rData['expires_in'] ?? 3500));
      $storage->saveToken($u['id'], $tokRow);
    }
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

  [$sResp,$sData]=gmail_send_message($access, $mail);
  $status = (($sResp['code']??0)===200) ? 'success' : 'failed';
  $error = $status==='failed' ? ['code'=>$sResp['code'],'curl_error'=>$sResp['error'],'body'=>$sResp['body']] : null;

  $logId = $storage->createLog($u['id'], [
    'template_id'=>$template_id,
    'group_id'=>$group_id,
    'subject_snapshot'=>$subject,
    'body_snapshot'=>$body,
    'attachments_snapshot'=>[],
    'counts'=>['to'=>count($to),'cc'=>count($cc),'bcc'=>count($bcc)],
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
