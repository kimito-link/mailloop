# セッションディレクトリ作成後の確認手順

## 完了した作業

✅ セッションディレクトリ `/tmp/mailloop_sessions/` を作成
✅ 権限を `700` に設定（所有者のみ読み書き実行可能）

## 次のステップ

### 1. コードを最新化

サーバー側で以下のコマンドを実行してください：

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
git pull origin main
```

### 2. OPcacheをクリア

PHPのキャッシュをクリアして、変更を反映させます：

```bash
php public/clear_cache.php
```

### 3. セッション管理のログを確認

修正後、以下のログメッセージが表示されるはずです：

```bash
# セッション管理関連のログを確認
grep -i "MailLoop Session" storage/app_error.log | tail -20
```

**期待されるログメッセージ:**
```
MailLoop Session: Created session directory: /tmp/mailloop_sessions
MailLoop Session: Using session directory: /tmp/mailloop_sessions
```

または、既にディレクトリが存在する場合：

```
MailLoop Session: Using session directory: /tmp/mailloop_sessions
```

### 4. 認証ループが解決されたか確認

```bash
# 認証ループ関連のログを確認
grep -i "require_login" storage/app_error.log | tail -30
```

**期待される動作:**
- `require_login called` の後に `require_login getUser result: user found, id=2` が表示される
- `require_login user missing id` や `require_login user not found` が表示されなくなる
- `/send/execute` ルートが正常に実行される

### 5. 403エラーログを確認

認証ループが解決された後、403エラー関連のログを確認してください：

```bash
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

## トラブルシューティング

### 認証ループが続く場合

1. **セッションディレクトリの権限を再確認**
   ```bash
   ls -la /tmp/mailloop_sessions/
   # 出力: drwx------ 2 besttrust members 10 ...
   ```

2. **セッションファイルが作成されているか確認**
   ```bash
   ls -la /tmp/mailloop_sessions/
   # セッションファイル（sess_*）が表示されるはず
   ```

3. **セッション管理のログを確認**
   ```bash
   grep -i "MailLoop Session" storage/app_error.log | tail -20
   ```

### 403エラーログが見つからない場合

認証ループが解決されても403エラーログが見つからない場合：

1. **メール送信を試行**
   - ブラウザでメール送信を試行
   - エラーが発生した場合、ログを確認

2. **トークンスコープ検査のログを確認**
   ```bash
   grep -i "Token scope check" storage/app_error.log | tail -20
   grep -i "Tokeninfo check" storage/app_error.log | tail -20
   ```

## 関連ファイル

- `app/bootstrap.php` - セッション管理の設定（修正済み）
- `FIX_SESSION_DIRECTORY.md` - セッションディレクトリの問題と修正
- `SESSION_DEBUG_GUIDE.md` - セッション管理デバッグガイド
- `check_logs_on_server.sh` - ログ確認スクリプト
