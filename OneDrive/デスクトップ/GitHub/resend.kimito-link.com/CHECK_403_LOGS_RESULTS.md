# 403エラーログ確認結果

## 確認手順

サーバー側で以下のコマンドを実行して、403エラー関連のログを確認してください：

```bash
# サーバーにSSH接続
ssh xserver-besttrust

# リポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/mailloop

# ログ確認スクリプトを実行
bash check_403_logs.sh
```

または、手動でログを確認する場合：

```bash
# ログファイルの場所を確認
ls -lh storage/app_error.log

# 403エラー関連のログを確認
grep -i "Gmail 403 Prevention" storage/app_error.log | tail -20
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20
grep -i "Gmail 403: insufficientPermissions" storage/app_error.log | tail -20
grep -i "Gmail 403 Error Details" storage/app_error.log | tail -20

# 最近のログを確認
tail -50 storage/app_error.log
```

## 確認すべきログメッセージ

コードを確認した結果、以下のログメッセージが出力されることがわかりました：

### 1. トークンスコープ検査のログ（public/index.php 1068-1124行目）

#### スコープ不足の場合
```
Gmail 403 Prevention: Token missing gmail.send scope. token_preview=%s | actual_scopes=%s | user_id=%d
```

#### tokeninfo API呼び出し失敗の場合
```
Tokeninfo check failed: HTTP %d | token_preview=%s | user_id=%d | error_message=%s | body_preview=%s
```

#### スコープ確認成功の場合
```
Token scope check OK: gmail.send scope found. token_preview=%s | user_id=%d
```

### 2. Gmail API呼び出し時の403エラーログ（public/index.php 1165-1204行目）

#### insufficientPermissions検出時
```
Gmail 403: insufficientPermissions detected. token_preview=%s | user_id=%d | message=%s | This indicates the token does not have the required gmail.send scope.
```

#### 403エラーの詳細情報
```
Gmail 403 Error Details: reason=%s | message=%s | token_preview=%s | user_id=%d | token_scope=%s | body_preview=%s
```

### 3. Gmail API呼び出し時のエラーログ（app/services/gmail_send.php 98-105行目）

#### 4xx/5xxエラー全般
```
Gmail API Error: HTTP %d | reason=%s | message=%s | token_preview=%s | body_preview=%s
```

## 調査ポイント

1. **トークンスコープ検査が機能しているか**
   - `Gmail 403 Prevention` ログが出力されている場合、トークンスコープ検査は機能しているが、スコープが不足している
   - `Token scope check OK` ログが出力されている場合、スコープ検査は成功している

2. **tokeninfo APIの呼び出しが成功しているか**
   - `Tokeninfo check failed` ログが出力されている場合、tokeninfo APIの呼び出しが失敗している
   - HTTPステータスコードを確認（401の場合は認証エラー、その他の場合は別の問題）

3. **実際のGmail API呼び出し時の403エラー**
   - `Gmail API Error: HTTP 403` ログが出力されている場合、実際のGmail API呼び出し時に403が発生している
   - `insufficientPermissions` が検出されている場合、スコープが不足している可能性が高い

4. **トークンスコープの実際の値**
   - `token_scope` フィールドに実際のスコープが記録されている
   - `actual_scopes` フィールドに実際のスコープが記録されている

## 次のステップ

ログを確認した後、以下の情報を元に問題を特定してください：

1. **トークンスコープ検査が機能している場合**
   - スコープが不足している場合は、再認証が必要
   - `config/config.php` の `GOOGLE_SCOPES` と `GMAIL_SCOPE` が正しく設定されているか確認

2. **tokeninfo APIの呼び出しが失敗している場合**
   - HTTPステータスコードを確認
   - 401の場合は認証エラー、再認証が必要
   - その他の場合は、API呼び出しの問題を調査

3. **実際のGmail API呼び出し時に403が発生している場合**
   - `insufficientPermissions` が検出されている場合は、スコープが不足している
   - トークンスコープ検査が機能していても、実際のAPI呼び出し時に403が発生する場合は、トークンの有効期限切れや、スコープの不一致の可能性
