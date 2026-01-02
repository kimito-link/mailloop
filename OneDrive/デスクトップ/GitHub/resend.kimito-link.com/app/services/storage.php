<?php
declare(strict_types=1);

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
  public function getUser(): ?array { return $_SESSION['user'] ?? null; }
  public function upsertUser(array $u): array { $_SESSION['user'] = $u; return $u; }

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
  private function requirePdo(): PDO {
    if (!$this->pdo) throw new RuntimeException('DB接続失敗。configとテーブル作成を確認してください。');
    return $this->pdo;
  }
  public function getUser(): ?array { return $_SESSION['user'] ?? null; }
  public function upsertUser(array $u): array { $_SESSION['user']=$u; return $u; }

  public function saveToken(int $userId, array $token): void {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("REPLACE INTO oauth_tokens (user_id, provider, access_token_enc, refresh_token_enc, expires_at, scopes, updated_at, created_at)
                         VALUES (:uid,'google',:at,:rt,:exp,:sc,NOW(),NOW())");
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
      $stmt=$pdo->prepare("SELECT * FROM message_templates WHERE user_id=:uid AND (title LIKE :q OR subject LIKE :q) ORDER BY updated_at DESC");
      $stmt->execute([':uid'=>$userId, ':q'=>'%'.$q.'%']);
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
    $stmt=$pdo->prepare("INSERT INTO message_templates (user_id,title,subject,body_text,attachments_json,last_sent_at,created_at,updated_at)
                         VALUES (:uid,:title,:subject,:body,:att,NULL,NOW(),NOW())");
    $stmt->execute([
      ':uid'=>$userId,
      ':title'=>$t['title'],
      ':subject'=>$t['subject'],
      ':body'=>$t['body_text'],
      ':att'=>json_encode($t['attachments'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
    ]);
    return (int)$pdo->lastInsertId();
  }
  public function updateTemplate(int $userId, int $id, array $t): void {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("UPDATE message_templates SET title=:title, subject=:subject, body_text=:body, attachments_json=:att, updated_at=NOW()
                         WHERE user_id=:uid AND id=:id");
    $stmt->execute([
      ':uid'=>$userId, ':id'=>$id,
      ':title'=>$t['title'], ':subject'=>$t['subject'], ':body'=>$t['body_text'],
      ':att'=>json_encode($t['attachments'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
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
      $stmt=$pdo->prepare("SELECT * FROM recipient_groups WHERE user_id=:uid AND name LIKE :q ORDER BY updated_at DESC");
      $stmt->execute([':uid'=>$userId, ':q'=>'%'.$q.'%']);
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
    $stmt=$pdo->prepare("INSERT INTO recipient_groups (user_id,name,to_json,cc_json,bcc_json,created_at,updated_at)
                         VALUES (:uid,:name,:to,:cc,:bcc,NOW(),NOW())");
    $stmt->execute([
      ':uid'=>$userId, ':name'=>$g['name'],
      ':to'=>json_encode($g['to'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':cc'=>json_encode($g['cc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':bcc'=>json_encode($g['bcc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
    ]);
    return (int)$pdo->lastInsertId();
  }
  public function updateGroup(int $userId, int $id, array $g): void {
    $pdo=$this->requirePdo();
    $stmt=$pdo->prepare("UPDATE recipient_groups SET name=:name, to_json=:to, cc_json=:cc, bcc_json=:bcc, updated_at=NOW()
                         WHERE user_id=:uid AND id=:id");
    $stmt->execute([
      ':uid'=>$userId, ':id'=>$id, ':name'=>$g['name'],
      ':to'=>json_encode($g['to'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':cc'=>json_encode($g['cc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':bcc'=>json_encode($g['bcc'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
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
