# 403エラーログ調査 - 最終サマリー

## 調査完了日
2025-01-XX

## 調査結果

### 1. 認証ループの問題

サーバー側のログから、認証ループが発生していることが確認されました：

```
require_login called
require_login getUser result: user found, id=2
require_login about to return, user_id=2, user_keys=provider, provider_sub, email, name, picture, id
require_login user missing id, redirecting to login
require_login user not found, redirecting to login
```

この問題により、メール送信処理（`/send/execute`）が実行される前に認証が失敗し、**403エラーのログまで到達していない可能性があります**。

### 2. 403エラーログの確認状況

現在のログでは、以下の403エラー関連のログメッセージは**まだ表示されていません**：

- `Gmail 403 Prevention: Token missing gmail.send scope`
- `Tokeninfo check failed`
- `Gmail API Error: HTTP 403`
- `Gmail 403: insufficientPermissions detected`
- `Gmail 403 Error Details`

これは、認証ループにより、実際のGmail API呼び出しまで到達していない可能性を示しています。

## 実施した修正

### 1. `require_login`関数にデバッグログを追加

認証ループの原因を特定するため、セッション情報を含むデバッグログを追加しました：

```php
function require_login($storage) {
  error_log('MailLoop Debug: require_login called | session_id=' . session_id() . ' | session_keys=' . implode(',', array_keys($_SESSION ?? [])));
  // ...
  error_log('MailLoop Debug: require_login getUser result: user found, id=' . (isset($u['id']) ? (int)$u['id'] : 'missing') . ' | session_user_id=' . (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 'missing'));
  // ...
}
```

### 2. ログ確認用のドキュメントとスクリプトを作成

- `check_logs_on_server.sh` - サーバー側でログを確認するスクリプト
- `SERVER_LOG_CHECK_GUIDE.md` - ログ確認手順
- `SESSION_DEBUG_GUIDE.md` - セッション管理デバッグガイド
- `403_ERROR_LOG_ANALYSIS.md` - 403エラーの詳細分析
- `AUTH_LOOP_ANALYSIS.md` - 認証ループの問題分析

## 次のステップ

### 1. 認証ループの問題を解決（最優先）

認証ループが解決されない限り、403エラーのログは確認できません。

**確認事項:**
- セッションの状態を確認（`/dbg`エンドポイント）
- セッションディレクトリの権限を確認
- セッションクッキーの設定を確認

**対処法:**
- セッション管理の改善
- セッションタイムアウトの調整
- セッションファイルの権限修正

### 2. 403エラーログの確認

認証ループが解決された後、以下のコマンドで403エラー関連のログを確認してください：

```bash
# サーバー側で実行
cd /home/besttrust/kimito-link.com/_git/mailloop

# 403エラー関連のログを確認
grep -i "Gmail 403" storage/app_error.log | tail -20
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20
grep -i "Gmail 403: insufficientPermissions" storage/app_error.log | tail -20
grep -i "Gmail 403 Error Details" storage/app_error.log | tail -20
```

または、スクリプトを実行：

```bash
bash check_logs_on_server.sh
```

### 3. 問題の特定と解決

ログを確認した後、以下のドキュメントを参照して問題を特定してください：

- **認証ループが続いている場合**: `SESSION_DEBUG_GUIDE.md`を参照
- **403エラーログが見つかった場合**: `403_ERROR_LOG_ANALYSIS.md`を参照
- **認証ループの問題分析**: `AUTH_LOOP_ANALYSIS.md`を参照

## 確認すべきログメッセージ（認証ループ解決後）

認証ループが解決され、実際のGmail API呼び出しまで到達した場合、以下のログメッセージが表示される可能性があります：

### トークンスコープ検査のログ

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

### Gmail API呼び出し時の403エラーログ

4. **insufficientPermissions検出時**
   ```
   Gmail 403: insufficientPermissions detected. token_preview=%s | user_id=%d | message=%s | This indicates the token does not have the required gmail.send scope.
   ```

5. **403エラーの詳細情報**
   ```
   Gmail 403 Error Details: reason=%s | message=%s | token_preview=%s | user_id=%d | token_scope=%s | body_preview=%s
   ```

## 関連ファイル

- `check_logs_on_server.sh` - ログ確認スクリプト
- `SERVER_LOG_CHECK_GUIDE.md` - ログ確認手順
- `SESSION_DEBUG_GUIDE.md` - セッション管理デバッグガイド
- `403_ERROR_LOG_ANALYSIS.md` - 403エラーの詳細分析
- `AUTH_LOOP_ANALYSIS.md` - 認証ループの問題分析
- `public/index.php` - メインアプリケーションファイル（修正済み）

## 結論

現在の状況では、**認証ループの問題が最優先で解決すべき問題**です。認証ループが解決されない限り、403エラーのログは確認できません。

認証ループが解決された後、上記の手順で403エラー関連のログを確認し、問題を特定してください。
