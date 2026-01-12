# 403エラーログ調査結果サマリー

## 調査完了日
2025-01-XX

## 調査内容

### 1. ログファイルの確認方法

サーバー側で以下のコマンドを実行して、403エラー関連のログを確認してください：

```bash
ssh xserver-besttrust
cd /home/besttrust/kimito-link.com/_git/mailloop

# 403エラー関連のログを確認
grep -i "Gmail 403 Prevention" storage/app_error.log | tail -20
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20
grep -i "Gmail 403: insufficientPermissions" storage/app_error.log | tail -20
grep -i "Gmail 403 Error Details" storage/app_error.log | tail -20

# 最近のログを確認
tail -50 storage/app_error.log
```

### 2. 確認すべきログメッセージ

コードを分析した結果、以下のログメッセージが出力されることが確認されました：

#### トークンスコープ検査のログ（public/index.php 1068-1124行目）

1. **スコープ不足の場合**
   ```
   Gmail 403 Prevention: Token missing gmail.send scope. token_preview=%s | actual_scopes=%s | user_id=%d
   ```

2. **スコープ確認成功の場合**
   ```
   Token scope check OK: gmail.send scope found. token_preview=%s | user_id=%d
   ```

3. **tokeninfo API呼び出し失敗の場合**
   ```
   Tokeninfo check failed: HTTP %d | token_preview=%s | user_id=%d | error_message=%s | body_preview=%s
   ```

#### Gmail API呼び出し時の403エラーログ（public/index.php 1165-1204行目）

4. **insufficientPermissions検出時**
   ```
   Gmail 403: insufficientPermissions detected. token_preview=%s | user_id=%d | message=%s | This indicates the token does not have the required gmail.send scope.
   ```

5. **403エラーの詳細情報**
   ```
   Gmail 403 Error Details: reason=%s | message=%s | token_preview=%s | user_id=%d | token_scope=%s | body_preview=%s
   ```

#### Gmail API呼び出し時のエラーログ（app/services/gmail_send.php 98-105行目）

6. **4xx/5xxエラー全般**
   ```
   Gmail API Error: HTTP %d | reason=%s | message=%s | token_preview=%s | body_preview=%s
   ```

### 3. 認証ループの問題

サーバー側のログから、認証ループが発生していることが確認されました：

```
require_login user missing id, redirecting to login
require_login getUser result: user not found, redirecting to login
require_login getUser result: user found, id=2
require_login user missing id, redirecting to login
```

この問題により、メール送信処理（`/send/execute`）が実行される前に認証が失敗し、403エラーの根本原因となっている可能性があります。

## 問題の関連性

### 認証ループ → 403エラーの流れ

1. `/send/execute`が呼び出される
2. `require_login()`が呼び出される
3. セッションからユーザー情報が取得できない、または`id`が欠落している
4. ログインページにリダイレクトされる
5. 認証ループが発生
6. トークンが取得できない、またはスコープが不足している
7. Gmail API呼び出し時に403エラーが発生

### 解決の優先順位

1. **認証ループの問題を解決**（最優先）
   - セッション管理の改善
   - `require_login`関数のデバッグログ追加（完了）

2. **403エラーのログを確認**
   - サーバー側のログファイルを確認
   - トークンスコープ検査の結果を確認

3. **トークンスコープの問題を解決**
   - スコープが不足している場合は再認証
   - OAuth認証時のスコープ設定を確認

## 実施した修正

### 1. `require_login`関数にデバッグログを追加

認証ループの原因を特定するため、`require_login`関数にデバッグログを追加しました：

```php
function require_login($storage) {
  error_log('MailLoop Debug: require_login called');
  $u = $storage->getUser();
  if (!$u) {
    error_log('MailLoop Debug: require_login getUser result: user not found, redirecting to login');
    header('Location: /auth/login'); exit;
  }
  error_log('MailLoop Debug: require_login getUser result: user found, id=' . (isset($u['id']) ? (int)$u['id'] : 'missing'));
  // idが存在することを確認
  if (!isset($u['id'])) {
    error_log('MailLoop Debug: require_login user missing id, redirecting to login. user=' . print_r($u, true));
    // ...
  }
  error_log('MailLoop Debug: require_login about to return, user_id=' . (int)$u['id'] . ', user_keys=' . implode(', ', array_keys($u)));
  return $u;
}
```

### 2. ログ確認スクリプトの作成

サーバー側で403エラー関連のログを確認するためのスクリプトを作成しました：

- `check_403_logs.sh` - ログ確認スクリプト
- `CHECK_403_LOGS_RESULTS.md` - ログ確認手順
- `403_ERROR_LOG_ANALYSIS.md` - 詳細な分析レポート

## 次のステップ

### 1. サーバー側のコードを最新化

```bash
ssh xserver-besttrust
cd /home/besttrust/kimito-link.com/_git/mailloop
git pull origin main
```

### 2. 認証ループのログを確認

更新後、以下のログを確認してください：

```bash
tail -100 storage/app_error.log | grep -i "require_login"
```

### 3. 403エラーのログを確認

```bash
grep -i "Gmail 403" storage/app_error.log | tail -20
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20
```

### 4. 問題の特定と解決

ログを確認した後、以下のドキュメントを参照して問題を特定してください：

- `403_ERROR_LOG_ANALYSIS.md` - 403エラーの詳細分析
- `AUTH_LOOP_ANALYSIS.md` - 認証ループの問題分析

## 関連ファイル

- `check_403_logs.sh` - ログ確認スクリプト
- `CHECK_403_LOGS_RESULTS.md` - ログ確認手順
- `403_ERROR_LOG_ANALYSIS.md` - 403エラーの詳細分析
- `AUTH_LOOP_ANALYSIS.md` - 認証ループの問題分析
- `public/index.php` - メインアプリケーションファイル（修正済み）
