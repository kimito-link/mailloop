# コード更新後の確認手順

## 完了した作業

✅ セッションディレクトリ `/tmp/mailloop_sessions/` を作成
✅ 権限を `700` に設定
✅ コードを最新化（`git pull origin main` - Already up-to-date）

## 次のステップ

### 1. OPcacheをクリア

PHPのキャッシュをクリアして、変更を反映させます：

```bash
php public/clear_cache.php
```

または、Webサーバーを再起動：

```bash
# Apacheの場合（Xserverでは通常不要）
# sudo systemctl restart apache2
```

### 2. セッション管理のログを確認

修正後、以下のログメッセージが表示されるはずです：

```bash
# セッション管理関連のログを確認
grep -i "MailLoop Session" storage/app_error.log | tail -20
```

**期待されるログメッセージ:**
```
MailLoop Session: Using session directory: /tmp/mailloop_sessions
```

### 3. 認証ループが解決されたか確認

```bash
# 認証ループ関連のログを確認
grep -i "require_login" storage/app_error.log | tail -30
```

**期待される動作:**
- `require_login called` の後に `require_login getUser result: user found, id=2` が表示される
- `require_login about to return, user_id=2` が表示される
- `require_login user missing id` や `require_login user not found` が表示されなくなる
- `/send/execute` ルートが正常に実行される

### 4. セッションファイルが作成されているか確認

```bash
# セッションファイル（sess_*）が作成されているか確認
ls -la /tmp/mailloop_sessions/

# セッションファイルが表示されるはず
# 例: sess_abc123def456...
```

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

### 6. メール送信を試行

ブラウザでメール送信を試行し、エラーが発生した場合はログを確認：

```bash
# 最新のログを確認
tail -50 storage/app_error.log
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

1. **セッションファイルが作成されているか確認**
   ```bash
   ls -la /tmp/mailloop_sessions/
   # セッションファイル（sess_*）が表示されるはず
   ```

2. **セッション管理のログを確認**
   ```bash
   grep -i "MailLoop Session" storage/app_error.log | tail -20
   ```

3. **セッションディレクトリの権限を再確認**
   ```bash
   ls -ld /tmp/mailloop_sessions/
   # 出力: drwx------ 2 besttrust members ...
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

3. **最近のログを確認**
   ```bash
   tail -100 storage/app_error.log
   ```

## 関連ファイル

- `app/bootstrap.php` - セッション管理の設定（修正済み）
- `public/index.php` - `require_login`関数（デバッグログ追加済み）
- `FIX_SESSION_DIRECTORY.md` - セッションディレクトリの問題と修正
- `UPDATE_AND_VERIFY_SESSION.md` - セッションディレクトリ作成後の確認手順
- `check_logs_on_server.sh` - ログ確認スクリプト
