# サーバー側でのログ確認ガイド

## 現在の状況

サーバー側のログから、認証ループが発生していることが確認されています。403エラー関連のログはまだ表示されていないため、実際のGmail API呼び出しまで到達していない可能性があります。

## ログ確認手順

### 1. 現在のディレクトリを確認

```bash
pwd
# 出力: /home/besttrust/kimito-link.com/_git/mailloop
```

### 2. ログファイルの存在確認

```bash
ls -lh storage/app_error.log
ls -lh storage/app_debug.log
```

### 3. 403エラー関連のログを確認

```bash
# 1. Gmail 403 Prevention ログ
grep -i "Gmail 403 Prevention" storage/app_error.log | tail -20

# 2. Tokeninfo check failed ログ
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20

# 3. Gmail API Error: HTTP 403 ログ
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20

# 4. Gmail 403: insufficientPermissions ログ
grep -i "Gmail 403: insufficientPermissions" storage/app_error.log | tail -20

# 5. Gmail 403 Error Details ログ
grep -i "Gmail 403 Error Details" storage/app_error.log | tail -20
```

### 4. 認証ループ関連のログを確認

```bash
# require_login関連のログ
grep -i "require_login" storage/app_error.log | tail -30

# /send/execute関連のログ
grep -i "/send/execute" storage/app_error.log | tail -30
```

### 5. 最近のログを確認

```bash
# 最後の50行を表示
tail -50 storage/app_error.log

# リアルタイムでログを監視（Ctrl+Cで終了）
tail -f storage/app_error.log
```

## ログ確認スクリプトの使用

`check_logs_on_server.sh`スクリプトを作成しました。サーバー側で実行してください：

```bash
# スクリプトに実行権限を付与
chmod +x check_logs_on_server.sh

# スクリプトを実行
bash check_logs_on_server.sh
```

または、直接実行：

```bash
bash check_logs_on_server.sh
```

## 想定される結果

### パターン1: 403エラーログが見つからない場合

認証ループにより、実際のGmail API呼び出しまで到達していない可能性があります。

**対処法:**
1. 認証ループの問題を解決する
2. セッション管理を改善する
3. 再度メール送信を試行する

### パターン2: 403エラーログが見つかった場合

以下の情報を確認してください：

1. **トークンスコープ検査の結果**
   - `Gmail 403 Prevention` ログが出力されている場合、スコープが不足している
   - `Token scope check OK` ログが出力されている場合、スコープ検査は成功している

2. **tokeninfo APIの呼び出し結果**
   - `Tokeninfo check failed` ログが出力されている場合、API呼び出しが失敗している
   - HTTPステータスコードを確認（401の場合は認証エラー）

3. **実際のGmail API呼び出し時のエラー**
   - `Gmail API Error: HTTP 403` ログが出力されている場合、実際のAPI呼び出し時に403が発生している
   - `insufficientPermissions` が検出されている場合、スコープが不足している

## 認証ループの問題について

現在のログから、認証ループが発生していることが確認されています：

```
require_login user found, id=2
require_login user not found, redirecting to login
require_login user missing id, redirecting to login
```

この問題により、メール送信処理（`/send/execute`）が実行される前に認証が失敗し、403エラーのログまで到達していない可能性があります。

### 認証ループの原因

1. **セッションの不安定性**: `$_SESSION['user']`が存在したり存在しなかったりしている
2. **ユーザーIDの欠落**: セッションに`user`配列は存在するが、`id`キーが欠落している
3. **セッションの破損**: セッションデータが部分的に破損している可能性

### 認証ループの解決方法

1. **サーバー側のコードを最新化**
   ```bash
   git pull origin main
   ```

2. **セッションの状態を確認**
   ```bash
   # セッションディレクトリを確認
   ls -la /tmp/mailloop_sessions/
   
   # セッションファイルの権限を確認
   ls -la /tmp/mailloop_sessions/ | head -10
   ```

3. **OPcacheをクリア**
   ```bash
   php public/clear_cache.php
   ```

## 次のステップ

1. **ログファイルを確認**
   - 上記のコマンドで403エラー関連のログを確認
   - 認証ループ関連のログを確認

2. **問題を特定**
   - 403エラーログが見つかった場合、`403_ERROR_LOG_ANALYSIS.md`を参照
   - 認証ループが続いている場合、`AUTH_LOOP_ANALYSIS.md`を参照

3. **問題を解決**
   - 特定された問題に応じて対処法を実施
   - 対処後、再度メール送信を試行
