# セッション管理デバッグガイド

## 現在の問題

ログから、認証ループが発生していることが確認されています：

```
require_login called
require_login getUser result: user found, id=2
require_login about to return, user_id=2, user_keys=provider, provider_sub, email, name, picture, id
require_login user missing id, redirecting to login
require_login user not found, redirecting to login
```

この問題により、メール送信処理（`/send/execute`）が実行される前に認証が失敗し、403エラーのログまで到達していない可能性があります。

## セッション管理の確認

### 1. セッションディレクトリの確認

```bash
# セッションディレクトリの場所を確認
ls -la /tmp/mailloop_sessions/

# セッションファイルの権限を確認
ls -la /tmp/mailloop_sessions/ | head -10

# セッションファイルの数とサイズを確認
ls -lh /tmp/mailloop_sessions/ | wc -l
du -sh /tmp/mailloop_sessions/
```

### 2. セッションの状態を確認

`/dbg`エンドポイントでセッションの状態を確認：

```bash
# ブラウザでアクセス
https://resend.kimito-link.com/dbg
```

または、curlで確認：

```bash
curl -v -H "Cookie: mailloop_session=YOUR_SESSION_ID" https://resend.kimito-link.com/dbg
```

### 3. セッションタイムアウトの確認

`app/bootstrap.php`の79-93行目で、セッションタイムアウト（30分）が設定されています。タイムアウトチェックが原因でセッションがクリアされている可能性があります。

ログで`last_seen`の更新を確認：

```bash
grep -i "last_seen" storage/app_error.log | tail -20
```

## 認証ループの原因

### 可能性1: セッションの競合

複数のリクエストが同時に処理され、セッションが競合している可能性があります。

**確認方法:**
```bash
# 同時リクエストのログを確認
grep -i "require_login called" storage/app_error.log | tail -50
```

### 可能性2: セッションタイムアウト

セッションタイムアウト（30分）が原因で、セッションがクリアされている可能性があります。

**確認方法:**
```bash
# タイムアウト関連のログを確認
grep -i "timeout" storage/app_error.log | tail -20
```

### 可能性3: セッションファイルの権限問題

セッションファイルの権限が正しくない可能性があります。

**確認方法:**
```bash
# セッションディレクトリの権限を確認
ls -ld /tmp/mailloop_sessions/
```

### 可能性4: セッションクッキーの問題

セッションクッキーが正しく設定されていない、または送信されていない可能性があります。

**確認方法:**
- ブラウザの開発者ツールでクッキーを確認
- `mailloop_session`クッキーが存在するか確認
- クッキーの`domain`、`path`、`secure`、`httponly`、`samesite`属性を確認

## デバッグログの追加

`require_login`関数にセッション情報を含むデバッグログを追加しました：

- `session_id`: セッションID
- `session_keys`: セッションに保存されているキーの一覧
- `session_user`: セッションに保存されているユーザー情報

これらのログで、セッションの状態を詳細に確認できます。

## 403エラーログの確認

認証ループが解決された後、403エラー関連のログを確認してください：

```bash
# 403エラー関連のログを確認
grep -i "Gmail 403" storage/app_error.log | tail -20
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20
```

## 次のステップ

1. **セッションの状態を確認**
   - `/dbg`エンドポイントでセッションの状態を確認
   - セッションディレクトリの権限を確認

2. **認証ループのログを確認**
   - 追加したデバッグログでセッションの状態を確認
   - セッションIDの変化を確認

3. **403エラーのログを確認**
   - 認証ループが解決された後、403エラー関連のログを確認
   - トークンスコープ検査の結果を確認

4. **問題を解決**
   - 特定された問題に応じて対処法を実施
   - 対処後、再度メール送信を試行
