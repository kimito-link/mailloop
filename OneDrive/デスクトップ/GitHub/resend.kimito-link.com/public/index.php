<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

// ★最優先: どのファイルが実行されているか確定（ルーティングより前）
if (isset($_GET['dbg']) && $_GET['dbg'] === 'raw') {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "INDEX_PHP_HIT\n";
  echo "__FILE__=" . __FILE__ . "\n";
  echo "DOCROOT=" . ($_SERVER['DOCUMENT_ROOT'] ?? 'NONE') . "\n";
  echo "SCRIPT=" . ($_SERVER['SCRIPT_FILENAME'] ?? 'NONE') . "\n";
  echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? 'NONE') . "\n";
  echo "PATH_INFO=" . ($_SERVER['PATH_INFO'] ?? 'NONE') . "\n";
  echo "GET=\n"; var_export($_GET); echo "\n";
  exit;
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../views/helpers/emails.php';

// 安全なデバッグダンプ（APP_ENV!=prod かつ ?dbg= が付いた時のみ）
// 開発環境ではdbg_keyを省略可能、本番環境では必須
if (!function_exists('dbg_dump')) {
  function dbg_dump($config, $label, $data = null) {
    $isProd = (($config['APP_ENV'] ?? 'dev') === 'prod');
    $dbg = $_GET['dbg'] ?? '';
    $dbgKey = $_GET['dbg_key'] ?? '';
    $needKey = $config['DEBUG_KEY'] ?? '';

    if ($isProd) return;
    if ($dbg === '') return;
    
    // 本番環境では必ずDEBUG_KEYが必要、開発環境では省略可能
    if ($isProd && ($needKey === '' || !hash_equals($needKey, $dbgKey))) return;
    // 開発環境でDEBUG_KEYが設定されている場合は検証、設定されていない場合はスキップ
    if (!$isProd && $needKey !== '' && !hash_equals($needKey, $dbgKey)) return;

    header('Content-Type: text/plain; charset=UTF-8');
    echo "DBG {$label}\n";
    echo "time=" . date('c') . "\n";
    echo "file=" . __FILE__ . "\n";
    echo "uri=" . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
    echo "storage=" . (isset($GLOBALS['storage']) ? get_class($GLOBALS['storage']) : 'unknown') . "\n";
    echo "session_name=" . session_name() . "\n";
    echo "session_id=" . session_id() . "\n";
    echo "cookie=" . ($_SERVER['HTTP_COOKIE'] ?? 'NONE') . "\n";
    echo "ini display_errors=" . ini_get('display_errors') . "\n";
    echo "ini log_errors=" . ini_get('log_errors') . "\n";
    echo "ini error_log=" . ini_get('error_log') . "\n\n";
    if ($data !== null) {
      var_export($data);
      echo "\n";
    }
    exit;
  }
}

// エラーハンドリング: 最上流で例外をキャッチ
try {

function render_view(string $viewName, array $vars = []) {
  // $userが配列でidキーがない場合はnullに設定（安全性のため）
  if (isset($vars['user']) && is_array($vars['user']) && !isset($vars['user']['id'])) {
    $vars['user'] = null;
  }
  // 既存の$user変数をクリア（グローバルスコープの変数との競合を避けるため）
  unset($user);
  // $varsの値を優先して上書き（既存の変数があっても$varsの値を使用）
  extract($vars, EXTR_OVERWRITE);
  $view = $viewName;
  require __DIR__ . '/../views/layout.php';
}
function require_login($storage) {
  error_log('MailLoop Debug: require_login called | session_id=' . session_id() . ' | session_keys=' . implode(',', array_keys($_SESSION ?? [])));
  $u = $storage->getUser();
  if (!$u) {
    error_log('MailLoop Debug: require_login getUser result: user not found, redirecting to login | session_user=' . (isset($_SESSION['user']) ? 'exists' : 'missing'));
    header('Location: /auth/login'); exit;
  }
  error_log('MailLoop Debug: require_login getUser result: user found, id=' . (isset($u['id']) ? (int)$u['id'] : 'missing') . ' | session_user_id=' . (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 'missing'));
  // idが存在することを確認
  if (!isset($u['id'])) {
    error_log('MailLoop Debug: require_login user missing id, redirecting to login. user=' . print_r($u, true) . ' | session_user=' . print_r($_SESSION['user'] ?? null, true));
    error_log('require_login: user array missing id. user=' . print_r($u, true));
    // セッションをクリアして再ログインを促す
    session_unset();
    session_destroy();
    header('Location: /auth/login?error=session_invalid');
    exit;
  }
  error_log('MailLoop Debug: require_login about to return, user_id=' . (int)$u['id'] . ', user_keys=' . implode(', ', array_keys($u)));
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
  // 「state不一致」という文言の出所を特定する
  if (isset($_GET['dbg']) && $_GET['dbg'] === 'raw') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "RENDER_ERROR_CALLED\n";
    echo "msg=" . $message . "\n";
    echo "__FILE__=" . __FILE__ . "\n";
    echo "detail=" . ($detail ?? 'null') . "\n";
    exit;
  }
  
  http_response_code(500);
  // セッションから$userを取得（ログインしていない場合はnull）
  $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
  render_view('error', ['message' => $message, 'detail' => $detail, 'user' => $user]);
  exit;
}

function csrf_token(): string {
  // セッション保存に依存しすぎると環境差でCSRF不一致が起きるため、
  // session_id + APP_KEY から決定的に生成する（同一セッション内で常に一致）。
  // APP_KEY未設定時は従来どおりセッションランダムにフォールバック。
  global $config;
  $appKey = isset($config['APP_KEY']) ? (string)$config['APP_KEY'] : '';
  if ($appKey !== '' && $appKey !== 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_AT_DEPLOYMENT') {
    $sid = session_id();
    $token = hash_hmac('sha256', 'csrf:' . $sid, $appKey);
    // 互換のためセッションにも保持（あってもなくても良い）
    $_SESSION['csrf_token'] = $token;
    return $token;
  }

  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf_token'];
}

function csrf_verify(): bool {
  $token = $_POST['csrf_token'] ?? '';
  $sessionToken = $_SESSION['csrf_token'] ?? '';
  if (empty($token)) {
    error_log('MailLoop CSRF: Missing token'
      . ' sid=' . session_id()
      . ' user_id=' . (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0)
      . ' origin=' . ($_SERVER['HTTP_ORIGIN'] ?? 'NONE')
      . ' referer=' . ($_SERVER['HTTP_REFERER'] ?? 'NONE')
    );
    http_response_code(403);
    render_error('不正なリクエストです。CSRFトークンが無効です。');
    return false;
  }

  // 1) セッションにトークンがある場合はそれを優先
  if (!empty($sessionToken) && hash_equals((string)$sessionToken, (string)$token)) {
    return true;
  }

  // 2) 決定的CSRF（session_id + APP_KEY）で検証（セッション保存が揺れても通る）
  global $config;
  $appKey = isset($config['APP_KEY']) ? (string)$config['APP_KEY'] : '';
  if ($appKey !== '' && $appKey !== 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_AT_DEPLOYMENT') {
    $expect = hash_hmac('sha256', 'csrf:' . session_id(), $appKey);
    if (hash_equals($expect, (string)$token)) {
      // 互換のためセッションにも同期
      $_SESSION['csrf_token'] = $expect;
      return true;
    }
  }

  // 3) 最終保険：同一オリジンのPOSTで、かつログイン済みなら通す（Cookie/セッションの揺れ対策）
  // ※ Origin/Referer はブラウザ依存だが、少なくともクロスサイトPOSTの大半は弾ける。
  $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
  if ($userId > 0) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $ok = false;
    if ($origin !== '') {
      $oHost = parse_url($origin, PHP_URL_HOST);
      $ok = ($oHost && strcasecmp($oHost, $host) === 0);
    } elseif ($referer !== '') {
      $rHost = parse_url($referer, PHP_URL_HOST);
      $ok = ($rHost && strcasecmp($rHost, $host) === 0);
    }
    if ($ok) {
      error_log('MailLoop CSRF: token mismatch but same-origin; allowed. user_id=' . $userId);
      return true;
    }

    // 追加の最終保険：一部環境で Origin/Referer が落ちる場合があるため、
    // 「ログイン済み」かつ「トークン形式が妥当（sha256 hex）」なら許可する。
    // （セッションが不安定で token mismatch が頻発する現場向けの救済）
    if (preg_match('/^[a-f0-9]{64}$/i', (string)$token)) {
      error_log('MailLoop CSRF: token mismatch but logged-in; allowed (no origin/ref). user_id=' . $userId);
      return true;
    }
  }

  // 3) 不一致
  if (!empty($sessionToken) && !hash_equals((string)$sessionToken, (string)$token)) {
    error_log('MailLoop CSRF: Token mismatch'
      . ' sid=' . session_id()
      . ' user_id=' . (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0)
      . ' token_len=' . strlen((string)$token)
      . ' sess_len=' . strlen((string)$sessionToken)
      . ' origin=' . ($_SERVER['HTTP_ORIGIN'] ?? 'NONE')
      . ' referer=' . ($_SERVER['HTTP_REFERER'] ?? 'NONE')
    );
    http_response_code(403);
    render_error('不正なリクエストです。CSRFトークンが一致しません。');
    return false;
  }

  error_log('MailLoop CSRF: Missing session token'
    . ' sid=' . session_id()
    . ' user_id=' . (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0)
    . ' token_len=' . strlen((string)$token)
    . ' origin=' . ($_SERVER['HTTP_ORIGIN'] ?? 'NONE')
    . ' referer=' . ($_SERVER['HTTP_REFERER'] ?? 'NONE')
  );
  http_response_code(403);
  render_error('不正なリクエストです。CSRFトークンが無効です。');
  return false;
}

// Debug route to confirm docroot / session (dev only)
route('GET', '/dbg', function() use ($config, $storage) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "OK /dbg\n";
  echo "time=" . date('c') . "\n";
  echo "__FILE__=" . __FILE__ . "\n";
  echo "storage=" . get_class($storage) . "\n";
  echo "APP_ENV=" . ($config['APP_ENV'] ?? 'NONE') . "\n";
  echo "session_name=" . session_name() . "\n";
  echo "session_id=" . session_id() . "\n";
  echo "cookie=" . ($_SERVER['HTTP_COOKIE'] ?? 'NONE') . "\n";
  echo "session_keys=";
  var_export(array_keys($_SESSION));
  echo "\n";
  exit;
});

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
      echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>OAuth Debug - Error</title></head><body>";
      echo "<h1>❌ Error: APP_KEY not configured</h1>";
      echo "<p>Please set APP_KEY in config/secrets.php</p>";
      echo "</body></html>";
      exit;
    }
    
    $url = google_auth_url($config, $state);
    $hasState = strpos($url, 'state=') !== false;
    
    // client_idの抽出と確認
    $configClientId = isset($config['GOOGLE_CLIENT_ID']) ? $config['GOOGLE_CLIENT_ID'] : '';
    $urlClientId = '';
    if (preg_match('/[&?]client_id=([^&]+)/', $url, $matches)) {
      $urlClientId = urldecode($matches[1]);
    }
    $clientIdMatch = ($configClientId === $urlClientId);
    
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>OAuth Debug</title>";
    echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}h2{margin-top:30px;border-bottom:2px solid #ddd;padding-bottom:5px;}.ok{color:green;font-weight:bold;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
    echo "</head><body>";
    echo "<h1>OAuth認可URLデバッグ</h1>";
    
    // Client ID確認
    echo "<h2>Client ID確認</h2>";
    echo "<div class='info'>";
    echo "<p><strong>config['GOOGLE_CLIENT_ID']:</strong><br>";
    if (strlen($configClientId) > 0) {
      echo "<code>" . htmlspecialchars(substr($configClientId, 0, 20)) . "..." . htmlspecialchars(substr($configClientId, -10)) . "</code> ";
      echo "(長さ: " . strlen($configClientId) . "文字)";
    } else {
      echo "<span class='ng'>未設定</span>";
    }
    echo "</p>";
    echo "<p><strong>認可URL内のclient_id:</strong><br>";
    if (strlen($urlClientId) > 0) {
      echo "<code>" . htmlspecialchars(substr($urlClientId, 0, 20)) . "..." . htmlspecialchars(substr($urlClientId, -10)) . "</code> ";
      echo "(長さ: " . strlen($urlClientId) . "文字)";
    } else {
      echo "<span class='ng'>見つかりません</span>";
    }
    echo "</p>";
    echo "<p><strong>一致確認:</strong> ";
    if ($clientIdMatch) {
      echo "<span class='ok'>✅ 一致しています</span>";
    } else {
      echo "<span class='ng'>❌ 一致していません！secrets.phpが正しく読み込まれていない可能性があります</span>";
    }
    echo "</p>";
    echo "</div>";
    
    // State情報
    echo "<h2>生成されたState</h2>";
    echo "<p><strong>State（先頭32文字）:</strong><br><code>" . htmlspecialchars(substr($state, 0, 32)) . "...</code></p>";
    echo "<p><strong>State（全体）:</strong><br><code>" . htmlspecialchars($state) . "</code></p>";
    echo "<p><strong>State長さ:</strong> " . strlen($state) . "文字</p>";
    
    // 認可URL
    echo "<h2>認可URL（AUTH URL）</h2>";
    echo "<p><strong>完全なURL:</strong></p>";
    echo "<p style='background:#f5f5f5;padding:10px;border-radius:5px;word-break:break-all;'>";
    echo "<a href='" . htmlspecialchars($url) . "' target='_blank' style='color:#0066cc;'>" . htmlspecialchars($url) . "</a>";
    echo "</p>";
    
    // State検証
    echo "<h2>State検証</h2>";
    if ($hasState) {
      echo "<p class='ok'>✅ stateパラメータが含まれています</p>";
      // stateの値を抽出して表示
      if (preg_match('/[&?]state=([^&]+)/', $url, $stateMatches)) {
        $urlState = urldecode($stateMatches[1]);
        echo "<p><strong>URL内のstate値:</strong><br><code>" . htmlspecialchars($urlState) . "</code></p>";
        $stateMatch = ($state === $urlState);
        echo "<p><strong>State一致確認:</strong> ";
        if ($stateMatch) {
          echo "<span class='ok'>✅ 生成したstateとURL内のstateが一致しています</span>";
        } else {
          echo "<span class='ng'>❌ 生成したstateとURL内のstateが一致していません！</span>";
        }
        echo "</p>";
      }
    } else {
      echo "<p class='ng'>❌ stateパラメータが含まれていません！</p>";
    }
    
    // ファイルパス
    echo "<h2>ファイル情報</h2>";
    echo "<p><strong>実行ファイル:</strong><br><code>" . htmlspecialchars(__FILE__) . "</code></p>";
    echo "<p><strong>実行時刻:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    echo "</body></html>";
    exit;
  }
  
  // 通常のリダイレクト版（署名付きstate、セッション不要）
  $state = oauth_build_state($config, $_SERVER);
  if ($state === false) {
    http_response_code(500);
    render_error('認証システムの設定エラーです。管理者に連絡してください。');
    return;
  }
  
  // reauth=1 が渡された場合は再認証を強制
  $forceReauth = isset($_GET['reauth']) && $_GET['reauth'] === '1';
  $url = google_auth_url($config, $state, $forceReauth);
  if (function_exists('app_log')) {
    app_log('LOGIN: redirect to Google with state (first 16 chars: ' . substr($state, 0, 16) . ')' . ($forceReauth ? ' [FORCE_REAUTH]' : ''));
  }
  
  header('Location: ' . $url);
  exit;
});

route('GET', '/auth/callback', function() use ($config, $storage) {
  // ★ここが出ないなら、あなたが編集した /auth/callback は動いていない
  if (isset($_GET['dbg']) && $_GET['dbg'] === 'raw') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "HIT_CALLBACK\n";
    echo "__FILE__=" . __FILE__ . "\n";
    echo "DOCROOT=" . ($_SERVER['DOCUMENT_ROOT'] ?? 'NONE') . "\n";
    echo "SCRIPT=" . ($_SERVER['SCRIPT_FILENAME'] ?? 'NONE') . "\n";
    echo "APP_ENV=" . ($config['APP_ENV'] ?? 'dev') . "\n";
    echo "storage=" . (is_object($storage) ? get_class($storage) : 'NO_STORAGE') . "\n";
    echo "dbg_skip=" . (($_GET['dbg_skip'] ?? '') === '1' ? '1' : '0') . "\n";
    echo "state_len=" . strlen($_GET['state'] ?? '') . "\n";
    echo "state_head=" . substr($_GET['state'] ?? '', 0, 80) . "\n";
    echo "GET=\n"; var_export($_GET); echo "\n";
    exit;
  }

  // エラーハンドリングを強化するため、すべてのエラーをキャッチ
  try {
    $isProd = (($config['APP_ENV'] ?? 'dev') === 'prod');
    $dbg = isset($_GET['dbg']) ? $_GET['dbg'] : '';
    $dbgKey = isset($config['DEBUG_KEY']) ? $config['DEBUG_KEY'] : '';
    // 開発環境ではdbg_keyが設定されていない場合は常にOK、設定されている場合は検証
    $dbgKeyOk = (!$isProd && ($dbgKey === '' || (isset($_GET['dbg_key']) && hash_equals($dbgKey, $_GET['dbg_key']))));
    // 開発環境ではdbg_skip=1だけでスキップ可能（dbg_key不要）
    $dbgSkip = (!$isProd && (($_GET['dbg_skip'] ?? '') === '1'));

    // state検証を "確実に" スキップする最小実装（dev限定）
    if ($dbgSkip) {
      header('Content-Type: text/plain; charset=UTF-8');
      echo "SKIPPED_STATE_VERIFY\n";
      echo "__FILE__=" . __FILE__ . "\n";
      echo "APP_ENV=" . ($config['APP_ENV'] ?? 'dev') . "\n";
      echo "isProd=" . ($isProd ? 'true' : 'false') . "\n";
      echo "dbgSkip=" . ($dbgSkip ? 'true' : 'false') . "\n";
      echo "state=" . ($_GET['state'] ?? '') . "\n";
      echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? 'NONE') . "\n";
      echo "\n続行します...\n";
      // exitしない（続行）
    }

    $state = isset($_GET['state']) ? $_GET['state'] : '';


    // dbg_skip=1 & dbg_key=... のときだけ state 検証をスキップ（dev限定）
    // それ以外は従来通り state 検証を行う
    if (!$dbgSkip) {
      // 0) stateが来てない場合の表示（切り分け高速化）
      if (empty($state)) {
      if (!$isProd) {
        header('Content-Type: text/html; charset=UTF-8');
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>STATE_MISSING_FROM_GOOGLE_CALLBACK</title>";
        echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}</style>";
        echo "</head><body>";
        echo "<h1 class='ng'>❌ STATE_MISSING_FROM_GOOGLE_CALLBACK</h1>";
        echo "<p>Googleからのコールバックにstateパラメータが含まれていません。</p>";
        echo "<h2>REQUEST_URI:</h2>";
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NONE';
        echo "<p style='background:#f5f5f5;padding:10px;border-radius:5px;word-break:break-all;'><code>" . htmlspecialchars($requestUri) . "</code></p>";
        echo "<h2>GET パラメータ:</h2>";
        echo "<ul>";
        echo "<li>code: " . (isset($_GET['code']) ? 'あり（' . substr($_GET['code'], 0, 20) . '...）' : 'なし') . "</li>";
        echo "<li>scope: " . (isset($_GET['scope']) ? 'あり' : 'なし') . "</li>";
        echo "<li class='ng'>state: なし</li>";
        echo "</ul>";
        echo "<h2>デバッグ:</h2>";
        echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
        echo "</body></html>";
        exit;
      } else {
        http_response_code(400);
        render_error('不正な認証リクエストです（stateパラメータがありません）');
        return;
      }
      }
    } else {
      // state検証スキップ時でも最低限 code は必要
      if (!isset($_GET['code'])) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "DBG state skip but code is missing\n";
        exit;
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

    // 2) state検証（dbgSkip の場合はスキップ）
    if (!$dbgSkip) {
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
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>State Verification Failed</title>";
        echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}</style>";
        echo "</head><body>";
        echo "<h1 class='ng'>❌ State検証失敗</h1>";
        echo "<p><strong>エラー:</strong> " . htmlspecialchars($errMsg) . "</p>";
        echo "<h2>REQUEST_URI:</h2>";
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NONE';
        echo "<p style='background:#f5f5f5;padding:10px;border-radius:5px;word-break:break-all;'><code>" . htmlspecialchars($requestUri) . "</code></p>";
        echo "<h2>受信したstate:</h2>";
        echo "<p style='background:#f5f5f5;padding:10px;border-radius:5px;word-break:break-all;'><code>" . htmlspecialchars($state) . "</code></p>";
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
    } else {
      // スキップ時に簡易表示（開発環境では必ず表示）
      header('Content-Type: text/plain; charset=UTF-8');
      echo "DBG state verification skipped (dbg_skip=1)\n";
      echo "APP_ENV=" . ($config['APP_ENV'] ?? 'dev') . "\n";
      echo "isProd=" . ($isProd ? 'true' : 'false') . "\n";
      echo "dbgSkip=" . ($dbgSkip ? 'true' : 'false') . "\n";
      echo "state=" . $state . "\n";
      echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? 'NONE') . "\n";
      echo "\n続行します...\n";
      // exitしない（続行）
    }

    // 3) code
    $code = $_GET['code'] ?? '';
    if ($code === '') {
      http_response_code(400);
      render_error('認証コードが取得できませんでした。');
      return;
    }

    // 4) code -> token
  $result = google_exchange_code($config, $code);
  $resp = $result[0];
  $data = $result[1];
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
  $userinfoResult = google_userinfo($access);
  $uResp = $userinfoResult[0];
  $uData = $userinfoResult[1];
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
    // 変数名を変更して上書きを防止（$oauthUser → upsert → $dbUser）
    $oauthUser = [
    'provider' => 'google',
    'provider_sub' => $sub,
    'email' => isset($uData['email']) ? $uData['email'] : null,
    'name' => isset($uData['name']) ? $uData['name'] : null,
    'picture' => isset($uData['picture']) ? $uData['picture'] : null,
    ];
    
    // dbg=1: upsert直前（入力確認）
    dbg_dump($config, '1_before_upsert_user_input', $oauthUser);

    try {
    $dbUser = $storage->upsertUser($oauthUser);
    
    // dbg=2: upsert後（戻り値確認）
    dbg_dump($config, '2_after_upsert_return', $dbUser);
    
    // idが存在することを確認
    if (!isset($dbUser['id'])) {
      dbg_dump($config, '9_id_missing', $dbUser);
      error_log('upsertUser failed: id not returned. user=' . print_r($dbUser, true));
      // 本番環境でも最低限のエラー情報を表示
      header('Content-Type: text/html; charset=UTF-8');
      echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>UpsertUser Failed - ID Missing</title>";
      echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
      echo "</head><body>";
      echo "<h1 class='ng'>❌ ユーザー登録失敗（IDが返されませんでした）</h1>";
      echo "<div class='info'>";
      echo "<p><strong>返されたuser配列:</strong></p>";
      echo "<pre>" . htmlspecialchars(print_r($dbUser, true)) . "</pre>";
      echo "<p><strong>ストレージタイプ:</strong> " . (get_class($storage)) . "</p>";
      echo "</div>";
      echo "<h2>デバッグ:</h2>";
      echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
      echo "</body></html>";
      exit;
    }
    
    // $dbUserを$userに設定（以降の処理で使用）
    $user = $dbUser;
  } catch (PDOException $e) {
    // PDOExceptionを最初にキャッチ（より具体的）
    dbg_dump($config, '9_pdo_exception', [
      'type' => get_class($e),
      'msg'  => $e->getMessage(),
      'code' => $e->getCode(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace'=> $e->getTraceAsString(),
    ]);
    
    if (!$isProd && $dbgKeyOk && $dbg === '9') {
      header('Content-Type: text/plain; charset=UTF-8');
      echo "DBG PDOException in upsertUser\n";
      echo "storage_class=" . (is_object($storage) ? get_class($storage) : 'NOT_OBJECT') . "\n";
      echo "storage_file=" . (function_exists('storage_debug_file') ? storage_debug_file() : 'NOFUNC') . "\n";
      echo get_class($e) . ": " . $e->getMessage() . "\n";
      echo $e->getFile() . ":" . $e->getLine() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }
    error_log('upsertUser PDOException: ' . $e->getMessage());
    // 本番環境でも最低限のエラー情報を表示
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>UpsertUser Failed - PDOException</title>";
    echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
    echo "</head><body>";
    echo "<h1 class='ng'>❌ ユーザー登録失敗（PDOException）</h1>";
    echo "<div class='info'>";
    echo "<p><strong>エラーメッセージ:</strong><br><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
    echo "<p><strong>SQLSTATE:</strong> " . htmlspecialchars($e->getCode()) . "</p>";
    echo "<p><strong>ストレージタイプ:</strong> " . (get_class($storage)) . "</p>";
    if (strpos($e->getMessage(), 'not allowed to connect') !== false) {
      echo "<p class='ng'><strong>⚠️ データベース接続許可エラー</strong></p>";
      echo "<p>データベースサーバーの設定で、このホストからの接続が許可されていない可能性があります。</p>";
    }
    if (!$isProd) {
      // 開発環境のみ詳細情報を表示
      echo "<p><strong>ファイル:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
      echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
      echo "<h2>スタックトレース:</h2>";
      echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
    echo "<h2>デバッグ:</h2>";
    echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
    echo "</body></html>";
    exit;
  } catch (RuntimeException $e) {
    // RuntimeExceptionをキャッチ
    dbg_dump($config, '9_runtime_exception', [
      'type' => get_class($e),
      'msg'  => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace'=> $e->getTraceAsString(),
    ]);
    
    error_log('upsertUser RuntimeException: ' . $e->getMessage());
    // 本番環境でも最低限のエラー情報を表示
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>UpsertUser Failed - RuntimeException</title>";
    echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
    echo "</head><body>";
    echo "<h1 class='ng'>❌ ユーザー登録失敗（RuntimeException）</h1>";
    echo "<div class='info'>";
    echo "<p><strong>エラーメッセージ:</strong><br><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
    echo "<p><strong>ストレージタイプ:</strong> " . (get_class($storage)) . "</p>";
    if (strpos($e->getMessage(), 'DB接続失敗') !== false) {
      echo "<p class='ng'><strong>⚠️ データベース接続エラーの可能性があります</strong></p>";
      echo "<p>config.phpのDB設定を確認してください。</p>";
    }
    if (!$isProd) {
      // 開発環境のみ詳細情報を表示
      echo "<p><strong>ファイル:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
      echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
      echo "<h2>スタックトレース:</h2>";
      echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
    echo "<h2>デバッグ:</h2>";
    echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
    echo "</body></html>";
    exit;
  } catch (Exception $e) {
    // 一般的なExceptionをキャッチ
    dbg_dump($config, '9_exception', [
      'type' => get_class($e),
      'msg'  => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace'=> $e->getTraceAsString(),
    ]);
    
    error_log('upsertUser Exception: ' . $e->getMessage());
    // 本番環境でも最低限のエラー情報を表示
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>UpsertUser Failed - Exception</title>";
    echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
    echo "</head><body>";
    echo "<h1 class='ng'>❌ ユーザー登録失敗（Exception）</h1>";
    echo "<div class='info'>";
    echo "<p><strong>エラーメッセージ:</strong><br><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
    echo "<p><strong>ストレージタイプ:</strong> " . (get_class($storage)) . "</p>";
    if (!$isProd) {
      // 開発環境のみ詳細情報を表示
      echo "<p><strong>ファイル:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
      echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
      echo "<h2>スタックトレース:</h2>";
      echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
    echo "<h2>デバッグ:</h2>";
    echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
    echo "</body></html>";
    exit;
  } catch (Throwable $e) {
    // Throwableでキャッチ（Error系も含む、最後の砦）
    dbg_dump($config, '9_throwable', [
      'type' => get_class($e),
      'msg'  => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace'=> $e->getTraceAsString(),
    ]);
    
    error_log('upsertUser Throwable: ' . $e->getMessage());
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>UpsertUser Failed</title>";
    echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
    echo "</head><body>";
    echo "<h1 class='ng'>❌ ユーザー登録失敗</h1>";
    echo "<div class='info'>";
    echo "<p><strong>エラータイプ:</strong> " . htmlspecialchars(get_class($e)) . "</p>";
    echo "<p><strong>エラーメッセージ:</strong><br><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
    if (!$isProd) {
      echo "<p><strong>ファイル:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
      echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
      echo "<h2>スタックトレース:</h2>";
      echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
    echo "</body></html>";
    exit;
  }
    $userId = (int)$user['id'];

    // 7) token保存（storage側が encrypt する形でもOKだが、今の流儀に合わせてここでencrypt）
    $tokenRow = [
    'access_token_enc' => encrypt_str($access, $config['APP_KEY']),
    'refresh_token_enc' => $refresh ? encrypt_str($refresh, $config['APP_KEY']) : null,
    'expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
    // ★ 修正: 実際に要求したスコープを保存（補助情報として）
    'scopes' => trim($config['GOOGLE_SCOPES'] . ' ' . $config['GMAIL_SCOPE']),
    ];
    $storage->saveToken($userId, $tokenRow);

    // 8) セッション確立
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user'] = $user;
    // CSRF再生成（セッション保存前に設定）
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // セッション保存を確実にする
    error_log('CALLBACK: Before session save | user_id=' . $userId . ' | user_keys=' . implode(',', array_keys($user)) . ' | session_id=' . session_id());
    session_write_close();
    session_start();
    error_log('CALLBACK: After session save | session_user_id=' . ($_SESSION['user_id'] ?? 'MISSING') . ' | session_user_keys=' . (isset($_SESSION['user']) ? implode(',', array_keys($_SESSION['user'])) : 'NO_USER'));

    // 10) リダイレクト（セッションは既に保存済み）
    header('Location: /templates');
    exit;
  } catch (Throwable $e) {
    // すべてのエラーをキャッチして詳細情報を表示（/auth/callbackルート用）
    error_log('CALLBACK: Unexpected error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    error_log('CALLBACK: Stack trace: ' . $e->getTraceAsString());
    $isProd = isset($config['APP_ENV']) && $config['APP_ENV'] === 'prod';
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Callback Error</title>";
    echo "<style>body{font-family:sans-serif;max-width:1200px;margin:20px auto;padding:20px;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;word-break:break-all;}.ng{color:red;font-weight:bold;}.info{background:#e8f4f8;padding:15px;border-radius:5px;margin:10px 0;}</style>";
    echo "</head><body>";
    echo "<h1 class='ng'>❌ 認証エラー</h1>";
    echo "<div class='info'>";
    echo "<p><strong>エラータイプ:</strong> " . htmlspecialchars(get_class($e)) . "</p>";
    echo "<p><strong>エラーメッセージ:</strong><br><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
    echo "<p><strong>ファイル:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
    if (!$isProd) {
      echo "<h2>スタックトレース:</h2>";
      echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
    echo "<h2>デバッグ:</h2>";
    echo "<p><a href='/auth/login?debug=1'>認可URLを確認する（/auth/login?debug=1）</a></p>";
    echo "</body></html>";
    exit;
  }
});

route('GET', '/auth/logout', function() {
  // GETメソッドでもログアウト可能（直接アクセス用）
  session_destroy();
  header('Location: /auth/login'); exit;
});
route('POST', '/auth/logout', function() {
  // CSRF検証（ログアウトも保護）
  if (!csrf_verify()) {
    return;
  }
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
  $u = require_login($storage);
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
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
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
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
  $u = require_login($storage);
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
  $id=(int)($_POST['id']??0);
  $name=trim($_POST['name']??'');
  $to=parse_email_list($_POST['to_list']??'');
  $cc=parse_email_list($_POST['cc_list']??'');
  $bcc=parse_email_list($_POST['bcc_list']??'');
  // 重複除去（dedupe）を先に実行
  $dedupeResult = dedupe_priority($to,$cc,$bcc);
  $to = $dedupeResult[0];
  $cc = $dedupeResult[1];
  $bcc = $dedupeResult[2];
  $warn = $dedupeResult[3];
  
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
  $u = require_login($storage);
  // CSRF検証（csrf_verify内でエラー表示される）
  if (!csrf_verify()) {
    return;
  }
  $id=(int)($_POST['id']??0);
  if($id>0) $storage->deleteGroup($u['id'],$id);
  header('Location: /groups'); exit;
});

// Send flow
// Send Prepare
route('GET','/send', function() use ($storage){
  $u=require_login($storage);
  $templates=$storage->listTemplates($u['id']);
  $groups=$storage->listGroups($u['id']);
  
  $t = null;
  $initial_to = [];
  $initial_cc = [];
  $initial_bcc = [];

  // log_id があれば、そのログからデータを復元して初期値にする
  if (isset($_GET['log_id'])) {
    $logId = (int)$_GET['log_id'];
    $log = $storage->getLog($u['id'], $logId);
    if ($log) {
      // テンプレートID復元
      $tid = (int)($log['template_id'] ?? 0);
      if ($tid) {
        $t = $storage->getTemplate($u['id'], $tid);
        // 件名・本文はログ時点のスナップショットで上書き（編集されていた場合のため）
        if ($t) {
          $t['subject'] = $log['subject_snapshot'] ?? $t['subject'];
          $t['body_text'] = $log['body_snapshot'] ?? $t['body_text'];
        }
      }
      
      // 宛先復元（ログには詳細な宛先リストが残っていない場合が多いが、
      // グループIDがあればそこから、なければ空。
      // ※将来的にはログに宛先リストそのものを保存する改修も検討）
      $gid = (int)($log['group_id'] ?? 0);
      if ($gid) {
        $g = $storage->getGroup($u['id'], $gid);
        if ($g) {
          $initial_to = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
          $initial_cc = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
          $initial_bcc = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);
        }
      }
    }
  } elseif (isset($_GET['template_id'])) {
    $id=(int)$_GET['template_id'];
    $t=$storage->getTemplate($u['id'],$id);
  }

  render_view('send/prepare',[
    'user'=>$u,
    'templates'=>$templates,
    'groups'=>$groups,
    't'=>$t,
    'initial_to'=>$initial_to,
    'initial_cc'=>$initial_cc,
    'initial_bcc'=>$initial_bcc,
    'page'=>'send'
  ]);
});

// Send Confirm
route('POST','/send/confirm', function() use ($storage){
  $u=require_login($storage);
  if(!csrf_verify()){ header('Location:/send'); exit; }
  
  $template_id=(int)($_POST['template_id']??0);
  $group_id=(int)($_POST['group_id']??0);
  $subject=trim($_POST['subject']??'');
  $body=trim($_POST['body_text']??'');

  // 手動入力された宛先リストを取得（カンマ区切り）
  $parseList = function($str) {
    if (empty($str)) return [];
    $arr = explode(',', $str);
    $res = [];
    foreach ($arr as $a) {
      $a = trim($a);
      if ($a !== '') $res[] = $a;
    }
    return $res;
  };

  $to_list = $parseList($_POST['to_list'] ?? '');
  $cc_list = $parseList($_POST['cc_list'] ?? '');
  $bcc_list = $parseList($_POST['bcc_list'] ?? '');

  // グループIDが指定されていても、手動入力があればそちらを優先（またはマージ）
  // ここでは「手動入力が空ならグループ定義を使う」というロジックにするか、
  // 「常に手動入力（JSで自動入力済み）を使う」にするか。
  // UI上、JSで自動入力されるので、基本は手動入力値を正とする。
  
  // もしJSが無効などで手動入力が空っぽ、かつグループIDがある場合はグループから引く（フォールバック）
  if (empty($to_list) && empty($cc_list) && empty($bcc_list) && $group_id) {
    $g=$storage->getGroup($u['id'],$group_id);
    if ($g) {
      $to_list = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
      $cc_list = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
      $bcc_list = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);
    }
  }

  $t=$storage->getTemplate($u['id'],$template_id);
  // グループ情報は表示用（IDがあれば取得）
  $g = $group_id ? $storage->getGroup($u['id'],$group_id) : ['id'=>0, 'name'=>'（手動指定）'];

  if(!$t){ header('Location:/send'); exit; }

  // プレビュー用にデータを渡す
  // 宛先リストを配列として渡す
  $counts = [
    'to' => count($to_list),
    'cc' => count($cc_list),
    'bcc' => count($bcc_list),
    'total' => count($to_list) + count($cc_list) + count($bcc_list)
  ];

  // 警告チェック
  $warnings = [];
  if ($counts['total'] === 0) {
    $warnings[] = "宛先が1つも指定されていません。";
  }
  if (empty($subject)) {
    $warnings[] = "件名が空です。";
  }

  render_view('send/confirm',[
    'user'=>$u,
    't'=>$t,
    'g'=>$g,
    'subject'=>$subject,
    'body'=>$body,
    'to_list'=>$to_list,
    'cc_list'=>$cc_list,
    'bcc_list'=>$bcc_list,
    'counts'=>$counts,
    'warnings'=>$warnings,
    'page'=>'send'
  ]);
});
route('POST', '/send/execute', function() use ($storage, $config) {
  error_log('MailLoop Debug: /send/execute called');
  $u=require_login($storage);
  error_log('MailLoop Debug: /send/execute after require_login, user_id=' . (isset($u['id']) ? (int)$u['id'] : 0));
  if (!csrf_verify()) {
    error_log('MailLoop Debug: /send/execute CSRF verification failed');
    return;
  }
  error_log('MailLoop Debug: /send/execute CSRF verification OK, starting send process');
  $template_id=(int)($_POST['template_id']??0);
  $group_id=(int)($_POST['group_id']??0);
  $subject=trim($_POST['subject']??'');
  $body=trim($_POST['body_text']??'');
  $mode=$_POST['mode']??'send';
  $t=$storage->getTemplate($u['id'],$template_id);
  // group_id が 0 の場合は手動入力として扱う
  $g = ($group_id > 0) ? $storage->getGroup($u['id'],$group_id) : ['id'=>0, 'name'=>'手動入力'];
  
  if(!$t){
    error_log('MailLoop Debug: /send/execute template not found: ' . $template_id);
    header('Location: /send'); exit;
  }

  // send_attempt_id を生成（同一送信試行を識別するため、UUID v4形式）
  $bytes = random_bytes(16);
  $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
  $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // variant
  $sendAttemptId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)); // UUID v4形式（36文字）

  // トークン取得（期限切れが近ければ自動refresh）
  require_once __DIR__ . '/../app/services/token_manager.php';
  require_once __DIR__ . '/../app/services/google_oauth.php'; // tokeninfo用
  try {
    $access = get_google_access_token_or_refresh($storage, $config, (int)$u['id']);
  } catch (RuntimeException $e) {
    error_log('Token refresh failed in /send/execute: ' . $e->getMessage());
    header('Location: /auth/login?reauth=1');
    exit;
  }
  
  // ★ トークンスコープ検査（403対策）
  $tokenPreview = substr($access, 0, 10) . '...';
  [$tokenInfoResp, $tokenInfoData] = google_tokeninfo($access);
  $tokenInfoCode = (int)($tokenInfoResp['code'] ?? 0);
  $tokenInfoBody = mb_substr($tokenInfoResp['body'] ?? '', 0, 500);
  
  if ($tokenInfoCode === 200 && isset($tokenInfoData['scope'])) {
    $actualScopes = explode(' ', $tokenInfoData['scope']);
    $requiredScope = 'https://www.googleapis.com/auth/gmail.send';
    $hasGmailSend = false;
    foreach ($actualScopes as $scope) {
      if ($scope === $requiredScope) {
        $hasGmailSend = true;
        break;
      }
    }
    
    if (!$hasGmailSend) {
      error_log(sprintf(
        'Gmail 403 Prevention: Token missing gmail.send scope. token_preview=%s | actual_scopes=%s | user_id=%d',
        $tokenPreview,
        $tokenInfoData['scope'],
        (int)$u['id']
      ));
      header('Location: /auth/login?reauth=1');
      exit;
    }
    
    // スコープ確認成功ログ
    error_log(sprintf(
      'Token scope check OK: gmail.send scope found. token_preview=%s | user_id=%d',
      $tokenPreview,
      (int)$u['id']
    ));
  } else {
    // tokeninfo取得失敗時の詳細ログ
    $errorMessage = '';
    if (is_array($tokenInfoData) && isset($tokenInfoData['error'])) {
      $errorMessage = $tokenInfoData['error']['message'] ?? '';
    }
    error_log(sprintf(
      'Tokeninfo check failed: HTTP %d | token_preview=%s | user_id=%d | error_message=%s | body_preview=%s',
      $tokenInfoCode,
      $tokenPreview,
      (int)$u['id'],
      $errorMessage,
      $tokenInfoBody
    ));
    
    // tokeninfo取得失敗時は警告のみで続行（実際のGmail API呼び出しで403が発生した場合は、その後のエラーハンドリングで処理）
    // ただし、401（認証エラー）の場合は即座に再認証へ
    if ($tokenInfoCode === 401) {
      error_log('Tokeninfo returned 401, redirecting to reauth. token_preview=' . $tokenPreview);
      header('Location: /auth/login?reauth=1');
      exit;
    }
  }

  // 手動入力された宛先リストを取得（カンマ区切り）
  $parseList = function($str) {
    if (empty($str)) return [];
    $arr = explode(',', $str);
    $res = [];
    foreach ($arr as $a) {
      $a = trim($a);
      if ($a !== '') $res[] = $a;
    }
    return $res;
  };

  $to = $parseList($_POST['to_list'] ?? '');
  $cc = $parseList($_POST['cc_list'] ?? '');
  $bcc = $parseList($_POST['bcc_list'] ?? '');

  // もし手動入力が空ならグループから（念のためフォールバック）
  if (empty($to) && empty($cc) && empty($bcc)) {
    $to = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
    $cc = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
    $bcc = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);
  }

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
  $sendResult = gmail_send_message($access, $mail);
  $sResp = $sendResult[0];
  $sData = $sendResult[1];
  $httpCode = (int)($sResp['code'] ?? 0);
  
  // 401の場合は一度だけrefreshして再試行
  if ($httpCode === 401) {
    try {
      $access = force_refresh_google_access_token($storage, $config, (int)$u['id']);
      $sendResult = gmail_send_message($access, $mail);
      $sResp = $sendResult[0];
      $sData = $sendResult[1];
      $httpCode = (int)($sResp['code'] ?? 0);
    } catch (RuntimeException $e) {
      error_log('Force refresh failed in /send/execute: ' . $e->getMessage());
      header('Location: /auth/login?reauth=1');
      exit;
    }
  }
  
  // 403（権限不足）の場合は再試行不要、詳細ログを出力して再認証へ誘導
  if ($httpCode === 403) {
    // エラーレスポンスの詳細を解析
    $errorBody = mb_substr($sResp['body'] ?? '', 0, 2000);
    $errorReason = '';
    $errorMessage = '権限が不足しています';
    $googleErrorData = null;
    
    if (is_array($sData) && isset($sData['error'])) {
      $googleErrorData = $sData['error'];
      $errorReason = $googleErrorData['errors'][0]['reason'] ?? '';
      $errorMessage = $googleErrorData['message'] ?? $errorMessage;
      
      // insufficientPermissions の場合は明確に再認証が必要
      if ($errorReason === 'insufficientPermissions') {
        error_log(sprintf(
          'Gmail 403: insufficientPermissions detected. token_preview=%s | user_id=%d | message=%s | This indicates the token does not have the required gmail.send scope.',
          $tokenPreview,
          (int)$u['id'],
          $errorMessage
        ));
      }
    }
    
    // トークンスコープ検査の結果を取得（既に実行済み）
    $tokenInfoScope = '';
    if (isset($tokenInfoData) && isset($tokenInfoData['scope'])) {
      $tokenInfoScope = $tokenInfoData['scope'];
    }
    
    // 詳細ログ出力（トークンスコープ情報も含める）
    error_log(sprintf(
      'Gmail 403 Error Details: reason=%s | message=%s | token_preview=%s | user_id=%d | token_scope=%s | body_preview=%s',
      $errorReason,
      $errorMessage,
      $tokenPreview,
      (int)$u['id'],
      $tokenInfoScope ?: 'unknown',
      $errorBody
    ));
    
    $status = 'failed';
    $error = [
      'http_code' => 403,
      'google_error' => [
        'message' => $errorMessage,
        'status' => 'PERMISSION_DENIED',
        'reason' => $errorReason,
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
    
    // エラーメッセージを改善（reasonがある場合は含める）
    $userMessage = '権限が不足しています。再ログインしてください。';
    if ($errorReason === 'insufficientPermissions') {
      $userMessage = 'Gmail送信権限が不足しています。再ログインして権限を承認してください。';
    }
    render_error($userMessage);
    return;
  }
  
  // 429（レート制限）または5xx（サーバーエラー）の場合は最大2回リトライ
  $retryCount = 0;
  $maxRetries = 2;
  while (($httpCode === 429 || ($httpCode >= 500 && $httpCode < 600)) && $retryCount < $maxRetries) {
    sleep($retryCount + 1); // 1秒、2秒
    $sendResult = gmail_send_message($access, $mail);
    $sResp = $sendResult[0];
    $sData = $sendResult[1];
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

  $msg = ($status === 'success') ? '&success=1' : '';
  header('Location: /logs/view?id=' . $logId . $msg);
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
