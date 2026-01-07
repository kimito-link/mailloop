<?php

require_once __DIR__ . '/../lib/db.php';

interface Storage {
  public function getUser(): ?array;
  public function upsertUser(array $u): array;

  public function saveToken(int $userId, array $token): void;
  public function getToken(int $userId): ?array;

  public function listTemplates(int $userId, string $q=''): array;
  public function getTemplate(int $userId, int $id): ?array;
  public function createTemplate(int $userId, array $t): int;
  public function updateTemplate(int $userId, int $id, array $t): void;
  public function deleteTemplate(int $userId, int $id): void;

  public function listGroups(int $userId, string $q=''): array;
  public function getGroup(int $userId, int $id): ?array;
  public function createGroup(int $userId, array $g): int;
  public function updateGroup(int $userId, int $id, array $g): void;
  public function deleteGroup(int $userId, int $id): void;

  public function createLog(int $userId, array $log): int;
  public function listLogs(int $userId): array;
  public function getLog(int $userId, int $id): ?array;
}

// デバッグ用: 読み込まれた storage.php の実体パスを返す
if (!function_exists('storage_debug_file')) {
  function storage_debug_file() {
    return __FILE__;
  }
}

function create_storage(array $config): Storage {
  return ($config['STORAGE_DRIVER'] ?? 'mysql') === 'file'
    ? new FileStorage($config)
    : new MysqlStorage($config);
}

final class FileStorage implements Storage {
  private string $dir;
  private array $config;
  public function __construct(array $config) {
    $this->config = $config;
    $this->dir = __DIR__ . '/../../storage';
    if (!is_dir($this->dir)) mkdir($this->dir, 0775, true);
  }
  private function path(string $name): string { return $this->dir . '/' . $name . '.json'; }
  private function read(string $name, $default) {
    $p = $this->path($name);
    if (!file_exists($p)) return $default;
    $j = json_decode(file_get_contents($p), true);
    return is_array($j) ? $j : $default;
  }
  private function write(string $name, $data): void {
    file_put_contents($this->path($name), json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  }
  public function getUser(): ?array {
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) return null;
    // idが無い場合は無効なセッションとして扱う
    if (!isset($_SESSION['user']['id'])) return null;
    return $_SESSION['user'];
  }
  public function upsertUser(array $u): array {
    // idが未設定の場合は1を設定（FileStorageでは固定ID）
    if (!isset($u['id'])) {
      $u['id'] = 1;
    }
    $_SESSION['user'] = $u;
    return $u;
  }

  public function saveToken(int $userId, array $token): void {
    $all = $this->read('tokens', []);
    $all[(string)$userId] = $token;
    $this->write('tokens', $all);
  }
  public function getToken(int $userId): ?array {
    $all = $this->read('tokens', []);
    return $all[(string)$userId] ?? null;
  }

  public function listTemplates(int $userId, string $q=''): array {
    $all = $this->read('templates', []);
    $items = $all[(string)$userId] ?? [];
    if ($q !== '') {
      $qq = mb_strtolower($q);
      $items = array_values(array_filter($items, fn($t)=>strpos(mb_strtolower(($t['title']??'').($t['subject']??'')), $qq)!==false));
    }
    usort($items, fn($a,$b)=>($b['updated_at']??0)<=>($a['updated_at']??0));
    return $items;
  }
  public function getTemplate(int $userId, int $id): ?array {
    foreach ($this->listTemplates($userId) as $t) if ((int)$t['id']===$id) return $t;
    return null;
  }
  public function createTemplate(int $userId, array $t): int {
    $all = $this->read('templates', []);
    $items = $all[(string)$userId] ?? [];
    $id = (int)(microtime(true)*1000);
    $t['id']=$id; $t['updated_at']=time(); $t['created_at']=time();
    $items[]=$t; $all[(string)$userId]=$items; $this->write('templates',$all);
    return $id;
  }
  public function updateTemplate(int $userId, int $id, array $t): void {
    $all=$this->read('templates',[]); $items=$all[(string)$userId]??[];
    foreach ($items as &$it) { if ((int)$it['id']===$id) { $it=array_merge($it,$t); $it['updated_at']=time(); } }
    $all[(string)$userId]=$items; $this->write('templates',$all);
  }
  public function deleteTemplate(int $userId, int $id): void {
    $all=$this->read('templates',[]); $items=$all[(string)$userId]??[];
    $items=array_values(array_filter($items, fn($it)=>(int)$it['id']!==$id));
    $all[(string)$userId]=$items; $this->write('templates',$all);
  }

  public function listGroups(int $userId, string $q=''): array {
    $all=$this->read('groups',[]); $items=$all[(string)$userId]??[];
    if ($q!=='') {
      $qq=mb_strtolower($q);
      $items=array_values(array_filter($items, fn($g)=>strpos(mb_strtolower($g['name']??''),$qq)!==false));
    }
    usort($items, fn($a,$b)=>($b['updated_at']??0)<=>($a['updated_at']??0));
    return $items;
  }
  public function getGroup(int $userId, int $id): ?array {
    foreach ($this->listGroups($userId) as $g) if ((int)$g['id']===$id) return $g;
    return null;
  }
  public function createGroup(int $userId, array $g): int {
    $all=$this->read('groups',[]); $items=$all[(string)$userId]??[];
    $id=(int)(microtime(true)*1000);
    $g['id']=$id; $g['updated_at']=time(); $g['created_at']=time();
    $items[]=$g; $all[(string)$userId]=$items; $this->write('groups',$all);
    return $id;
  }
  public function updateGroup(int $userId, int $id, array $g): void {
    $all=$this->read('groups',[]); $items=$all[(string)$userId]??[];
    foreach ($items as &$it){ if((int)$it['id']===$id){ $it=array_merge($it,$g); $it['updated_at']=time(); } }
    $all[(string)$userId]=$items; $this->write('groups',$all);
  }
  public function deleteGroup(int $userId, int $id): void {
    $all=$this->read('groups',[]); $items=$all[(string)$userId]??[];
    $items=array_values(array_filter($items, fn($it)=>(int)$it['id']!==$id));
    $all[(string)$userId]=$items; $this->write('groups',$all);
  }

  public function createLog(int $userId, array $log): int {
    $all=$this->read('logs',[]); $items=$all[(string)$userId]??[];
    $id=(int)(microtime(true)*1000);
    $log['id']=$id; $log['created_at']=time();
    $items[]=$log; $all[(string)$userId]=$items; $this->write('logs',$all);
    return $id;
  }
  public function listLogs(int $userId): array {
    $all=$this->read('logs',[]); $items=$all[(string)$userId]??[];
    usort($items, fn($a,$b)=>($b['created_at']??0)<=>($a['created_at']??0));
    return $items;
  }
  public function getLog(int $userId, int $id): ?array {
    foreach ($this->listLogs($userId) as $l) if ((int)$l['id']===$id) return $l;
    return null;
  }
}

final class MysqlStorage implements Storage {
  private array $config;
  private ?PDO $pdo;
  public function __construct(array $config) {
    $this->config=$config;
    $this->pdo=db_pdo($config);
  }
  private function requirePdo() {
    if (!$this->pdo) {
      $h = isset($this->config['DB_HOST']) ? $this->config['DB_HOST'] : '';
      $d = isset($this->config['DB_NAME']) ? $this->config['DB_NAME'] : '';
      $u = isset($this->config['DB_USER']) ? $this->config['DB_USER'] : '';
      error_log('DB pdo is null. host=' . $h . ' db=' . $d . ' user=' . $u);
      throw new RuntimeException('DB接続失敗。configとDB接続元許可を確認してください。');
    }
    return $this->pdo;
  }
  public function getUser(): ?array {
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) return null;
    if (!isset($_SESSION['user']['id'])) return null;
    return $_SESSION['user'];
  }
  public function upsertUser(array $u): array {
    // ログは実際のstorage配下に書く（app/../../storage）
    $logDir = __DIR__ . '/../../storage';
    if (!is_dir($logDir)) mkdir($logDir, 0775, true);
    $logFile = $logDir . '/upsert_debug.log';
    $msgIn = 'DEBUG upsertUser input: ' . json_encode($u);
    error_log($msgIn);
    file_put_contents($logFile, date('c') . ' ' . $msgIn . "\n", FILE_APPEND);
    $pdo = $this->requirePdo();

    $provider = $u['provider'];
    $sub = $u['provider_sub'];

    // provider+sub で確定
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE provider=:p AND provider_sub=:s LIMIT 1");
    $stmt->execute([':p'=>$provider, ':s'=>$sub]);
    $row = $stmt->fetch();

    if ($row) {
      $id = isset($row['id']) ? (int)$row['id'] : 0;

      // emailは衝突する可能性があるので try/catch
      try {
        $stmt = $pdo->prepare("UPDATE users SET email=:email, name=:name, picture=:pic, updated_at=NOW() WHERE id=:id");
        $stmt->execute([
          ':id'=>$id,
          ':email'=>$u['email'] ?? null,
          ':name'=>$u['name'] ?? null,
          ':pic'=>$u['picture'] ?? null,
        ]);
      } catch (PDOException $e) {
        // email UNIQUE など（SQLSTATE 23000）
        error_log('upsertUser email update failed: ' . $e->getMessage());
        // email更新は諦めて name/picture だけ更新（ログインは通す）
        $stmt = $pdo->prepare("UPDATE users SET name=:name, picture=:pic, updated_at=NOW() WHERE id=:id");
        $stmt->execute([
          ':id'=>$id,
          ':name'=>$u['name'] ?? null,
          ':pic'=>$u['picture'] ?? null,
        ]);
      }

      if ($id > 0) {
        $u['id'] = $id;
      }
    } else {
      // 新規
      try {
        $stmt = $pdo->prepare("INSERT INTO users (provider, provider_sub, email, name, picture, created_at, updated_at)
                               VALUES (:p,:s,:email,:name,:pic,NOW(),NOW())");
        $stmt->execute([
          ':p'=>$provider,
          ':s'=>$sub,
          ':email'=>$u['email'] ?? null,
          ':name'=>$u['name'] ?? null,
          ':pic'=>$u['picture'] ?? null,
        ]);
        // lastInsertId が取れない/0 の環境があるため、必ずSELECTで補強する
        $newId = (int)$pdo->lastInsertId();
        if ($newId > 0) {
          $u['id'] = $newId;
        }
        $msgId = 'DEBUG upsertUser lastInsertId=' . $u['id'];
        error_log($msgId);
        file_put_contents($logFile, date('c') . ' ' . $msgId . "\n", FILE_APPEND);
      } catch (PDOException $e) {
        // ここでコケるなら subの同時作成競合の可能性 → 再SELECT
        error_log('upsertUser insert failed: ' . $e->getMessage());
        $stmt = $pdo->prepare("SELECT id FROM users WHERE provider=:p AND provider_sub=:s LIMIT 1");
        $stmt->execute([':p'=>$provider, ':s'=>$sub]);
        $r2 = $stmt->fetch();
        if (!$r2) throw $e;
        $u['id'] = (int)$r2['id'];
      }
    }

    // 最終保険: ここまででidが入らなければ provider+sub で再取得
    if (!isset($u['id']) || (int)$u['id'] <= 0) {
      $stmt = $pdo->prepare("SELECT id FROM users WHERE provider=:p AND provider_sub=:s LIMIT 1");
      $stmt->execute([':p'=>$provider, ':s'=>$sub]);
      $id2 = $stmt->fetchColumn();
      if ($id2) {
        $u['id'] = (int)$id2;
      }
    }

    // 既存動作維持
    $_SESSION['user'] = $u;
    return $u;
  }

  public function saveToken(int $userId, array $token): void {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("INSERT INTO oauth_tokens (user_id, provider, access_token_enc, refresh_token_enc, expires_at, scopes, created_at, updated_at)
                         VALUES (:uid,'google',:at,:rt,:exp,:sc,NOW(),NOW())
                         ON DUPLICATE KEY UPDATE
                           access_token_enc = VALUES(access_token_enc),
                           refresh_token_enc = COALESCE(VALUES(refresh_token_enc), refresh_token_enc),
                           expires_at = VALUES(expires_at),
                           scopes = VALUES(scopes),
                           updated_at = NOW()");
    $stmt->execute([
      ':uid'=>$userId,
      ':at'=>$token['access_token_enc'],
      ':rt'=>$token['refresh_token_enc'] ?? null,
      ':exp'=>$token['expires_at'],
      ':sc'=>$token['scopes'] ?? '',
    ]);
  }
  public function getToken(int $userId): ?array {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("SELECT * FROM oauth_tokens WHERE user_id=:uid AND provider='google' LIMIT 1");
    $stmt->execute([':uid'=>$userId]);
    $row=$stmt->fetch();
    return $row ?: null;
  }

  public function listTemplates(int $userId, string $q=''): array {
    $pdo=$this->requirePdo();
    if ($q!=='') {
      $like = '%'.$q.'%';
      $stmt=$pdo->prepare("SELECT * FROM message_templates WHERE user_id=:uid AND (title LIKE :q OR subject LIKE :q) ORDER BY updated_at DESC");
      $stmt->execute([':uid'=>$userId, ':q'=>$like]);
    } else {
      $stmt=$pdo->prepare("SELECT * FROM message_templates WHERE user_id=:uid ORDER BY updated_at DESC");
      $stmt->execute([':uid'=>$userId]);
    }
    return $stmt->fetchAll() ?: [];
  }
  public function getTemplate(int $userId, int $id): ?array {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("SELECT * FROM message_templates WHERE user_id=:uid AND id=:id LIMIT 1");
    $stmt->execute([':uid'=>$userId, ':id'=>$id]);
    $row=$stmt->fetch();
    return $row ?: null;
  }
  public function createTemplate(int $userId, array $t): int {
    $pdo=$this->requirePdo();
    $att = json_encode($t['attachments'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($att === false) {
      throw new RuntimeException('attachments JSON encode failed');
    }
    $stmt=$pdo->prepare("INSERT INTO message_templates (user_id,title,subject,body_text,attachments_json,last_sent_at,created_at,updated_at)
                         VALUES (:uid,:title,:subject,:body,:att,NULL,NOW(),NOW())");
    $stmt->execute([
      ':uid'=>$userId,
      ':title'=>$t['title'],
      ':subject'=>$t['subject'],
      ':body'=>$t['body_text'],
      ':att'=>$att,
    ]);
    return (int)$pdo->lastInsertId();
  }
  public function updateTemplate(int $userId, int $id, array $t): void {
    $pdo=$this->requirePdo();
    $att = json_encode($t['attachments'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($att === false) {
      throw new RuntimeException('attachments JSON encode failed');
    }
    $stmt=$pdo->prepare("UPDATE message_templates SET title=:title, subject=:subject, body_text=:body, attachments_json=:att, updated_at=NOW()
                         WHERE user_id=:uid AND id=:id");
    $stmt->execute([
      ':uid'=>$userId, ':id'=>$id,
      ':title'=>$t['title'], ':subject'=>$t['subject'], ':body'=>$t['body_text'],
      ':att'=>$att,
    ]);
  }
  public function deleteTemplate(int $userId, int $id): void {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("DELETE FROM message_templates WHERE user_id=:uid AND id=:id");
    $stmt->execute([':uid'=>$userId, ':id'=>$id]);
  }

  public function listGroups(int $userId, string $q=''): array {
    $pdo=$this->requirePdo();
    if ($q!=='') {
      $like = '%'.$q.'%';
      $stmt=$pdo->prepare("SELECT * FROM recipient_groups WHERE user_id=:uid AND name LIKE :q ORDER BY updated_at DESC");
      $stmt->execute([':uid'=>$userId, ':q'=>$like]);
    } else {
      $stmt=$pdo->prepare("SELECT * FROM recipient_groups WHERE user_id=:uid ORDER BY updated_at DESC");
      $stmt->execute([':uid'=>$userId]);
    }
    return $stmt->fetchAll() ?: [];
  }
  public function getGroup(int $userId, int $id): ?array {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("SELECT * FROM recipient_groups WHERE user_id=:uid AND id=:id LIMIT 1");
    $stmt->execute([':uid'=>$userId, ':id'=>$id]);
    $row=$stmt->fetch();
    return $row ?: null;
  }
  public function createGroup(int $userId, array $g): int {
    $pdo=$this->requirePdo();
    $to = json_encode($g['to'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $cc = json_encode($g['cc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $bcc = json_encode($g['bcc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($to === false || $cc === false || $bcc === false) {
      throw new RuntimeException('recipient_groups JSON encode failed');
    }
    $stmt=$pdo->prepare("INSERT INTO recipient_groups (user_id,name,to_json,cc_json,bcc_json,created_at,updated_at)
                         VALUES (:uid,:name,:to,:cc,:bcc,NOW(),NOW())");
    $stmt->execute([
      ':uid'=>$userId, ':name'=>$g['name'],
      ':to'=>$to,
      ':cc'=>$cc,
      ':bcc'=>$bcc,
    ]);
    return (int)$pdo->lastInsertId();
  }
  public function updateGroup(int $userId, int $id, array $g): void {
    $pdo=$this->requirePdo();
    $to = json_encode($g['to'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $cc = json_encode($g['cc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $bcc = json_encode($g['bcc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($to === false || $cc === false || $bcc === false) {
      throw new RuntimeException('recipient_groups JSON encode failed');
    }
    $stmt=$pdo->prepare("UPDATE recipient_groups SET name=:name, to_json=:to, cc_json=:cc, bcc_json=:bcc, updated_at=NOW()
                         WHERE user_id=:uid AND id=:id");
    $stmt->execute([
      ':uid'=>$userId, ':id'=>$id, ':name'=>$g['name'],
      ':to'=>$to,
      ':cc'=>$cc,
      ':bcc'=>$bcc,
    ]);
  }
  public function deleteGroup(int $userId, int $id): void {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("DELETE FROM recipient_groups WHERE user_id=:uid AND id=:id");
    $stmt->execute([':uid'=>$userId, ':id'=>$id]);
  }

  public function createLog(int $userId, array $log): int {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("INSERT INTO send_logs (user_id, template_id, group_id, subject_snapshot, body_snapshot, attachments_snapshot_json, recipient_counts_json, status, error_json, gmail_message_id, created_at)
                         VALUES (:uid,:tid,:gid,:sub,:body,:att,:cnt,:st,:err,:mid,NOW())");
    $stmt->execute([
      ':uid'=>$userId, ':tid'=>$log['template_id'], ':gid'=>$log['group_id'],
      ':sub'=>$log['subject_snapshot'], ':body'=>$log['body_snapshot'],
      ':att'=>json_encode($log['attachments_snapshot'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':cnt'=>json_encode($log['counts'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':st'=>$log['status'],
      ':err'=>isset($log['error'])?json_encode($log['error'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
      ':mid'=>$log['gmail_message_id'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
  }
  /**
   * send_attempt_id を使ってログを upsert（同一送信試行は1行にまとめる）
   * 
   * @param int $userId
   * @param array $log ['send_attempt_id' => string, 'template_id' => int, ...]
   * @return int ログID
   */
  public function upsertLogByAttempt(int $userId, array $log): int {
    $pdo=$this->requirePdo();
    $attemptId = $log['send_attempt_id'] ?? null;
    if (!$attemptId) {
      throw new RuntimeException('send_attempt_id is required for upsertLogByAttempt');
    }
    
    $sql = "
      INSERT INTO send_logs
        (send_attempt_id, user_id, template_id, group_id, subject_snapshot, body_snapshot,
         attachments_snapshot_json, recipient_counts_json, status, error_json, gmail_message_id, created_at, updated_at)
      VALUES
        (:aid, :uid, :tid, :gid, :sub, :body, :att, :cnt, :st, :err, :mid, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        error_json = VALUES(error_json),
        gmail_message_id = COALESCE(VALUES(gmail_message_id), gmail_message_id),
        recipient_counts_json = VALUES(recipient_counts_json),
        updated_at = NOW()
    ";
    $stmt=$pdo->prepare($sql);
    $stmt->execute([
      ':aid'=>$attemptId,
      ':uid'=>$userId,
      ':tid'=>$log['template_id'],
      ':gid'=>$log['group_id'],
      ':sub'=>$log['subject_snapshot'],
      ':body'=>$log['body_snapshot'],
      ':att'=>json_encode($log['attachments_snapshot'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':cnt'=>json_encode($log['counts'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':st'=>$log['status'],
      ':err'=>isset($log['error'])?json_encode($log['error'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
      ':mid'=>$log['gmail_message_id'] ?? null,
    ]);
    
    // INSERT/UPDATEどちらでもIDが欲しいので、別途SELECT
    $stmt2=$pdo->prepare("SELECT id FROM send_logs WHERE send_attempt_id=:aid LIMIT 1");
    $stmt2->execute([':aid'=>$attemptId]);
    $logId = $stmt2->fetchColumn();
    return $logId ? (int)$logId : (int)$pdo->lastInsertId();
  }
  public function listLogs(int $userId): array {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("SELECT * FROM send_logs WHERE user_id=:uid ORDER BY created_at DESC");
    $stmt->execute([':uid'=>$userId]);
    return $stmt->fetchAll() ?: [];
  }
  public function getLog(int $userId, int $id): ?array {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("SELECT * FROM send_logs WHERE user_id=:uid AND id=:id LIMIT 1");
    $stmt->execute([':uid'=>$userId, ':id'=>$id]);
    $row=$stmt->fetch();
    return $row ?: null;
  }
}
