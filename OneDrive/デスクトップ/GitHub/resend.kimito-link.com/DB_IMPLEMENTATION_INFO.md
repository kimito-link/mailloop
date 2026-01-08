# データベース実装情報（GPT相談用）

## 1. database/schema.sql のテーブル定義

```sql
-- MailLoop MySQL schema (MVP)

CREATE TABLE IF NOT EXISTS oauth_tokens (
  user_id INT NOT NULL,
  provider VARCHAR(20) NOT NULL,
  access_token_enc TEXT NOT NULL,
  refresh_token_enc TEXT NULL,
  expires_at DATETIME NULL,
  scopes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_text LONGTEXT NOT NULL,
  attachments_json JSON NULL,
  last_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_user_updated (user_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recipient_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  to_json JSON NULL,
  cc_json JSON NULL,
  bcc_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_user_updated (user_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS send_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  template_id INT NOT NULL,
  group_id INT NOT NULL,
  subject_snapshot VARCHAR(255) NOT NULL,
  body_snapshot LONGTEXT NOT NULL,
  attachments_snapshot_json JSON NULL,
  recipient_counts_json JSON NULL,
  status VARCHAR(20) NOT NULL,
  error_json JSON NULL,
  gmail_message_id VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 2. oauth_tokens テーブルのカラム案（現在の実装）

### テーブル定義
```sql
CREATE TABLE IF NOT EXISTS oauth_tokens (
  user_id INT NOT NULL,
  provider VARCHAR(20) NOT NULL,
  access_token_enc TEXT NOT NULL,
  refresh_token_enc TEXT NULL,
  expires_at DATETIME NULL,
  scopes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### カラム説明
- `user_id`: ユーザーID（INT）
- `provider`: プロバイダー名（'google'固定）
- `access_token_enc`: 暗号化されたアクセストークン（TEXT）
- `refresh_token_enc`: 暗号化されたリフレッシュトークン（TEXT、NULL可）
- `expires_at`: トークンの有効期限（DATETIME、NULL可）
- `scopes`: スコープ情報（TEXT、NULL可）
- `created_at`: 作成日時（DATETIME）
- `updated_at`: 更新日時（DATETIME）

### 実装上の注意点
- `access_token_enc`と`refresh_token_enc`は暗号化して保存（`crypto.php`の`encrypt_str()`を使用）
- `REPLACE INTO`で保存（`saveToken()`メソッド）
- 複合主キー（`user_id`, `provider`）で1ユーザー1プロバイダー

## 3. /templates 周りの DB 保存コード（MysqlStorage の該当部分）

### テンプレート一覧取得（listTemplates）
```php
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
```

### テンプレート取得（getTemplate）
```php
public function getTemplate(int $userId, int $id): ?array {
  $pdo=$this->requirePdo();
  $stmt=$pdo->prepare("SELECT * FROM message_templates WHERE user_id=:uid AND id=:id LIMIT 1");
  $stmt->execute([':uid'=>$userId, ':id'=>$id]);
  $row=$stmt->fetch();
  return $row ?: null;
}
```

### テンプレート作成（createTemplate）
```php
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
```

### テンプレート更新（updateTemplate）
```php
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
```

### テンプレート削除（deleteTemplate）
```php
public function deleteTemplate(int $userId, int $id): void {
  $pdo=$this->requirePdo();
  $stmt=$pdo->prepare("DELETE FROM message_templates WHERE user_id=:uid AND id=:id");
  $stmt->execute([':uid'=>$userId, ':id'=>$id]);
}
```

## 4. 現在の実装状況

### データ構造の対応
- **FileStorage**: `time()`でタイムスタンプ（整数）
- **MysqlStorage**: `NOW()`でDATETIME（文字列）
- **attachments**: FileStorageでは配列、MysqlStorageではJSON文字列

### 注意点
- `body_text`は`LONGTEXT`型（長文対応）
- `attachments_json`はJSON型（MySQL 5.7+）
- `user_id`で必ずフィルタリング（セキュリティ）
- `updated_at`でソート（最新順）

## 5. バリデーション（現在未実装）

GPTの推奨に基づき、以下のバリデーションを追加すべき：

```php
// テンプレート保存時のバリデーション例
function validateTemplate(array $t): array {
  $errors = [];
  if (empty($t['title'])) $errors[] = 'タイトルは必須です';
  if (mb_strlen($t['title']) > 100) $errors[] = 'タイトルは100文字以内です';
  if (empty($t['subject'])) $errors[] = '件名は必須です';
  if (mb_strlen($t['subject']) > 150) $errors[] = '件名は150文字以内です';
  if (empty($t['body_text'])) $errors[] = '本文は必須です';
  if (mb_strlen($t['body_text']) > 20000) $errors[] = '本文は20,000文字以内です';
  return $errors;
}
```

---

**この情報をGPTに共有すれば、phpMyAdminでのテーブル作成手順と、バリデーション実装の具体的な指示が得られます。**
