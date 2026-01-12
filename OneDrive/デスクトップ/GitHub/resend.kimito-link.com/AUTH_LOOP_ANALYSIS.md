# 認証ループ問題の分析レポート

## 問題の概要

サーバー側のログから、認証ループが発生していることが確認されました：

```
require_login user missing id, redirecting to login
require_login getUser result: user not found, redirecting to login
require_login getUser result: user found, id=2
require_login user missing id, redirecting to login
```

このループにより、メール送信処理（`/send/execute`）が実行される前に認証が失敗し、403エラーの根本原因となっている可能性があります。

## コード分析

### 現在の`require_login`関数（public/index.php 72-87行目）

```php
function require_login($storage) {
  $u = $storage->getUser();
  if (!$u) {
    header('Location: /auth/login'); exit;
  }
  // idが存在することを確認
  if (!isset($u['id'])) {
    error_log('require_login: user array missing id. user=' . print_r($u, true));
    // セッションをクリアして再ログインを促す
    session_unset();
    session_destroy();
    header('Location: /auth/login?error=session_invalid');
    exit;
  }
  return $u;
}
```

### `MysqlStorage->getUser()`（app/services/storage.php 184-188行目）

```php
public function getUser(): ?array {
  if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) return null;
  if (!isset($_SESSION['user']['id'])) return null;
  return $_SESSION['user'];
}
```

## 問題の原因

ログから以下の問題が推測されます：

1. **セッションの不安定性**: `$_SESSION['user']`が存在したり存在しなかったりしている
2. **ユーザーIDの欠落**: セッションに`user`配列は存在するが、`id`キーが欠落している
3. **セッションの破損**: セッションデータが部分的に破損している可能性

## ログに表示されているメッセージ

現在のコードには存在しないデバッグログが表示されています：

- `require_login called`
- `require_login getUser result: user not found, redirecting to login`
- `require_login getUser result: user found, id=2`
- `require_login user missing id, redirecting to login`

これは、**サーバー側のコードが古い**か、**デバッグログが追加されている**可能性があります。

## 解決策

### 1. サーバー側のコードを最新化

サーバー側のコードを最新の状態に更新する必要があります：

```bash
ssh xserver-besttrust
cd /home/besttrust/kimito-link.com/_git/mailloop
git pull origin main
```

### 2. セッション管理の改善

`require_login`関数にデバッグログを追加し、セッションの状態を確認できるようにします。

### 3. セッションの整合性チェック

`MysqlStorage->getUser()`で、セッションデータの整合性をより厳密にチェックします。

## 403エラーとの関連性

認証ループが発生している場合、以下の流れで403エラーが発生する可能性があります：

1. `/send/execute`が呼び出される
2. `require_login()`が呼び出される
3. セッションからユーザー情報が取得できない
4. ログインページにリダイレクトされる
5. ユーザーが再ログインする
6. トークンが取得されるが、スコープが不足している
7. Gmail API呼び出し時に403エラーが発生

または：

1. `/send/execute`が呼び出される
2. `require_login()`が呼び出される
3. セッションからユーザー情報が取得できるが、`id`が欠落している
4. セッションがクリアされ、ログインページにリダイレクトされる
5. 認証ループが発生

## 次のステップ

1. **サーバー側のコードを最新化**
   ```bash
   ssh xserver-besttrust
   cd /home/besttrust/kimito-link.com/_git/mailloop
   git pull origin main
   ```

2. **セッションの状態を確認**
   - `/dbg`エンドポイントでセッションの状態を確認
   - セッションファイルの権限を確認

3. **認証ループの原因を特定**
   - `require_login`関数にデバッグログを追加
   - セッションの状態を詳細にログ出力

4. **403エラーのログを確認**
   - `storage/app_error.log`で403エラー関連のログを確認
   - トークンスコープ検査の結果を確認
