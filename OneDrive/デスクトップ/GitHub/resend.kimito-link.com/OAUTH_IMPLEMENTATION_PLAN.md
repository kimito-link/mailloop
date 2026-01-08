# OAuth実装計画

## ✅ 確認結果

### 既存の関数

1. **`google_userinfo(string $accessToken): array`** ✅ 存在
   - 戻り値: `[$resp, $data]` 形式
   - `$resp`: HTTPレスポンス（`code`, `body`等）
   - `$data`: JSONデコードされたユーザー情報（`sub`, `email`, `name`, `picture`等）

2. **`google_exchange_code(array $config, string $code): array`** ✅ 存在
   - 戻り値: `[$resp, $data]` 形式
   - `$data['access_token']`, `$data['refresh_token']`, `$data['expires_in']` 等

3. **`google_auth_url(array $config): string`** ✅ 存在
   - ただし、`state` パラメータが未実装

### 必要な追加実装

1. **usersテーブルの作成**（schema.sqlに追加）
2. **`/auth/login` にstate生成を追加**
3. **`/auth/callback` の改善**（state検証、エラーハンドリング統一、usersテーブル保存）
4. **`MysqlStorage->upsertUser()` の実装**（usersテーブルへの保存）

---

## 📋 実装手順

### Step 1: usersテーブルの追加

`database/schema.sql` に以下を追加：

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(20) NOT NULL,
  provider_sub VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  name VARCHAR(255) NULL,
  picture TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_sub (provider, provider_sub),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 2: /auth/login にstate生成を追加

`public/index.php` の `/auth/login` ルートを修正：

```php
route('GET', '/auth/login', function() use ($config) {
  // state生成（CSRF対策）
  $_SESSION['oauth_state'] = bin2hex(random_bytes(32));
  
  // google_auth_url() を修正してstateを含める必要がある
  // または、ここで直接URLを生成
  $params = [
    'client_id' => $config['GOOGLE_CLIENT_ID'],
    'redirect_uri' => $config['GOOGLE_REDIRECT_URI'],
    'response_type' => 'code',
    'scope' => implode(' ', [
      $config['GMAIL_SCOPE'],
      'openid', 'email', 'profile',
    ]),
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => $_SESSION['oauth_state'], // 追加
  ];
  $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
  
  header('Location: ' . $url);
  exit;
});
```

### Step 3: MysqlStorage->upsertUser() の実装

`app/services/storage.php` の `MysqlStorage` クラスに以下を追加：

```php
public function upsertUser(array $u): array {
  $pdo = $this->requirePdo();
  
  // provider + provider_sub で検索
  $stmt = $pdo->prepare("SELECT id FROM users WHERE provider = :provider AND provider_sub = :sub LIMIT 1");
  $stmt->execute([
    ':provider' => $u['provider'],
    ':sub' => $u['provider_sub'],
  ]);
  $existing = $stmt->fetch();
  
  if ($existing) {
    // 更新
    $stmt = $pdo->prepare("UPDATE users SET email = :email, name = :name, picture = :picture, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
      ':id' => $existing['id'],
      ':email' => $u['email'] ?? null,
      ':name' => $u['name'] ?? null,
      ':picture' => $u['picture'] ?? null,
    ]);
    $u['id'] = $existing['id'];
  } else {
    // 新規作成
    $stmt = $pdo->prepare("INSERT INTO users (provider, provider_sub, email, name, picture, created_at, updated_at) VALUES (:provider, :sub, :email, :name, :picture, NOW(), NOW())");
    $stmt->execute([
      ':provider' => $u['provider'],
      ':sub' => $u['provider_sub'],
      ':email' => $u['email'] ?? null,
      ':name' => $u['name'] ?? null,
      ':picture' => $u['picture'] ?? null,
    ]);
    $u['id'] = (int)$pdo->lastInsertId();
  }
  
  // セッションにも保存（既存の動作を維持）
  $_SESSION['user'] = $u;
  
  return $u;
}
```

### Step 4: /auth/callback の改善

`public/index.php` の `/auth/callback` ルートを以下に置き換え：

```php
route('GET', '/auth/callback', function() use ($config, $storage) {
  // 1) ユーザーが拒否した場合
  if (!empty($_GET['error'])) {
    error_log('OAuth error: ' . ($_GET['error_description'] ?? $_GET['error']));
    render_error('認証がキャンセルされました。もう一度お試しください。');
    return;
  }

  // 2) state検証
  $state = $_GET['state'] ?? '';
  if (empty($state) || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
    http_response_code(400);
    render_error('不正な認証リクエストです（state不一致）');
    return;
  }
  unset($_SESSION['oauth_state']); // 使い捨て

  // 3) code取得
  $code = $_GET['code'] ?? '';
  if (empty($code)) {
    http_response_code(400);
    render_error('認証コードが取得できませんでした。');
    return;
  }

  // 4) code -> token
  [$resp, $data] = google_exchange_code($config, $code);
  if (($resp['code'] ?? 0) !== 200 || empty($data['access_token'])) {
    error_log('Token exchange failed: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
    render_error('認証に失敗しました（token取得失敗）');
    return;
  }

  $access = $data['access_token'];
  $refresh = $data['refresh_token'] ?? '';

  // 5) userinfo取得（sub/email/name）
  [$uResp, $uData] = google_userinfo($access);
  if (($uResp['code'] ?? 0) !== 200 || empty($uData['sub'])) {
    error_log('Userinfo failed: ' . json_encode($uData, JSON_UNESCAPED_UNICODE));
    render_error('認証に失敗しました（ユーザー情報取得失敗）');
    return;
  }

  // 6) users upsert（provider+sub）
  $user = [
    'provider' => 'google',
    'provider_sub' => $uData['sub'],
    'email' => $uData['email'] ?? null,
    'name' => $uData['name'] ?? null,
    'picture' => $uData['picture'] ?? null,
  ];
  $user = $storage->upsertUser($user); // usersテーブルに保存してidを取得
  $userId = $user['id'];

  // 7) token保存（暗号化）
  $token = [
    'access_token_enc' => encrypt_str($access, $config['APP_KEY']),
    'refresh_token_enc' => $refresh ? encrypt_str($refresh, $config['APP_KEY']) : null,
    'expires_at' => date('Y-m-d H:i:s', time() + (int)($data['expires_in'] ?? 3500)),
    'scopes' => $config['GMAIL_SCOPE'],
  ];
  $storage->saveToken($userId, $token);

  // 8) セッション確立（固定化対策）
  session_regenerate_id(true);
  $_SESSION['user_id'] = $userId;
  $_SESSION['user'] = $user; // 既存の動作を維持

  // 9) CSRF再生成
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

  // 10) 遷移
  header('Location: /templates');
  exit;
});
```

### Step 5: require_login() の復活

`/templates` と `/groups` のルートからダミーユーザーのバイパスを削除：

```php
route('GET', '/templates', function() use ($storage) {
  $u = require_login($storage); // ダミーユーザーのバイパスを削除
  // ...
});
```

---

## 🔍 確認事項

### google_userinfo() の戻り値形式

既存の実装では `[$resp, $data]` 形式で返すため、GPTのテンプレートを以下のように修正：

- GPTテンプレート: `$me = google_userinfo(...)`
- 実際の実装: `[$uResp, $uData] = google_userinfo($access)`

### usersテーブルの必要性

GPTの推奨に従い、`users` テーブルを追加することを推奨します：
- `provider` + `provider_sub` でユーザーを一意に識別
- `email` は補助情報として保存（変更される可能性があるため）

---

**次のステップ**: 上記の実装を順番に適用していきます。
