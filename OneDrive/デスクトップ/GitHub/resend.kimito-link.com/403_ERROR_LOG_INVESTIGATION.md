# 403エラーログ調査結果

## 調査日時
2025年1月29日

## ログファイルの場所

ログファイルは以下の場所に出力されます：
- **メインログ**: `storage/app_error.log`（`app/bootstrap.php`の22行目で設定）
- **デバッグログ**: `storage/app_debug.log`（`app_log()`関数で出力）
- **フォールバック**: `/tmp/mailloop_debug.log`（書き込み可能な場合）

## 確認すべきログメッセージ

### 1. トークンスコープ検査のログ

#### スコープ不足検出時
```
Gmail 403 Prevention: Token missing gmail.send scope. token_preview=%s | actual_scopes=%s | user_id=%d
```
- **場所**: `public/index.php` 1086-1091行目
- **条件**: tokeninfo APIで取得したスコープに`gmail.send`が含まれていない場合
- **アクション**: 即座に`/auth/login?reauth=1`へリダイレクト

#### スコープ確認成功時
```
Token scope check OK: gmail.send scope found. token_preview=%s | user_id=%d
```
- **場所**: `public/index.php` 1097-1101行目
- **条件**: tokeninfo APIで`gmail.send`スコープが確認できた場合

#### tokeninfo取得失敗時
```
Tokeninfo check failed: HTTP %d | token_preview=%s | user_id=%d | error_message=%s | body_preview=%s
```
- **場所**: `public/index.php` 1108-1115行目
- **条件**: tokeninfo APIの呼び出しが失敗した場合（200以外のHTTPステータス）
- **アクション**: 
  - 401の場合は即座に再認証へリダイレクト
  - その他の場合は警告のみで続行（実際のGmail API呼び出しで403が発生した場合は、その後のエラーハンドリングで処理）

### 2. Gmail API呼び出し時のログ

#### 403エラー発生時（gmail_send.php）
```
Gmail API Error: HTTP 403 | reason=%s | message=%s | token_preview=%s | body_preview=%s
```
- **場所**: `app/services/gmail_send.php` 98-105行目
- **条件**: Gmail API呼び出し時にHTTP 403が返された場合
- **情報**: 
  - `reason`: Google APIのエラー理由（例: `insufficientPermissions`）
  - `message`: エラーメッセージ
  - `token_preview`: トークンの先頭10文字
  - `body_preview`: レスポンスボディの先頭2000文字

### 3. 403エラー詳細ログ（index.php）

#### insufficientPermissions検出時
```
Gmail 403: insufficientPermissions detected. token_preview=%s | user_id=%d | message=%s | This indicates the token does not have the required gmail.send scope.
```
- **場所**: `public/index.php` 1180-1185行目
- **条件**: Gmail APIから`insufficientPermissions`というreasonが返された場合

#### 403エラー詳細情報
```
Gmail 403 Error Details: reason=%s | message=%s | token_preview=%s | user_id=%d | token_scope=%s | body_preview=%s
```
- **場所**: `public/index.php` 1196-1204行目
- **条件**: Gmail API呼び出し時にHTTP 403が返された場合
- **情報**:
  - `reason`: Google APIのエラー理由
  - `message`: エラーメッセージ
  - `token_preview`: トークンの先頭10文字
  - `user_id`: ユーザーID
  - `token_scope`: tokeninfo APIで取得したスコープ（取得できた場合）
  - `body_preview`: レスポンスボディの先頭2000文字

## ログ確認手順

### サーバー側での確認方法

1. **SSHでサーバーに接続**
   ```bash
   ssh [サーバー情報]
   ```

2. **ログファイルの場所を確認**
   ```bash
   cd /path/to/resend.kimito-link.com
   ls -la storage/app_error.log
   ```

3. **403エラー関連のログを検索**
   ```bash
   # すべての403関連ログを表示
   grep -i "403" storage/app_error.log | tail -50
   
   # トークンスコープ検査のログを表示
   grep "Gmail 403 Prevention\|Tokeninfo check failed\|Token scope check OK" storage/app_error.log | tail -50
   
   # Gmail API 403エラーのログを表示
   grep "Gmail API Error: HTTP 403\|Gmail 403 Error Details" storage/app_error.log | tail -50
   
   # insufficientPermissionsのログを表示
   grep "insufficientPermissions" storage/app_error.log | tail -50
   ```

4. **最新のログをリアルタイムで監視**
   ```bash
   tail -f storage/app_error.log | grep -i "403\|token\|scope"
   ```

## 想定される問題パターン

### パターン1: トークンスコープ検査で検出される
- **ログ**: `Gmail 403 Prevention: Token missing gmail.send scope`
- **原因**: トークンに`gmail.send`スコープが含まれていない
- **対処**: 再認証フローが自動的に実行される（`/auth/login?reauth=1`）

### パターン2: tokeninfo取得失敗
- **ログ**: `Tokeninfo check failed: HTTP 401` または `Tokeninfo check failed: HTTP 400`
- **原因**: 
  - 401: トークンが無効または期限切れ
  - 400: トークンの形式が不正
- **対処**: 401の場合は再認証へリダイレクト

### パターン3: Gmail API呼び出し時に403が発生
- **ログ**: `Gmail API Error: HTTP 403` または `Gmail 403 Error Details`
- **原因**: 
  - `insufficientPermissions`: トークンに必要なスコープが含まれていない
  - その他の権限エラー
- **対処**: エラーハンドリングで再認証へリダイレクト

### パターン4: tokeninfoでは成功するがGmail APIで403
- **ログ**: 
  - `Token scope check OK: gmail.send scope found`
  - その後 `Gmail API Error: HTTP 403`
- **原因**: 
  - tokeninfo APIとGmail APIでスコープの解釈が異なる可能性
  - トークンは有効だが、実際のAPI呼び出し時に権限が不足
- **対処**: この場合は再認証が必要

## コードの動作フロー

1. **トークン取得**（1057-1066行目）
   - `get_google_access_token_or_refresh()`でトークンを取得
   - 期限切れが近ければ自動refresh

2. **トークンスコープ検査**（1068-1124行目）
   - `google_tokeninfo()`でトークンのスコープを確認
   - `gmail.send`スコープが含まれていない場合は再認証へリダイレクト
   - tokeninfo取得失敗時は警告のみで続行（401の場合は再認証）

3. **Gmail API呼び出し**（1145行目）
   - `gmail_send_message()`でメール送信を試行

4. **エラーハンドリング**（1150-1243行目）
   - 401: トークンをrefreshして再試行
   - 403: 詳細ログを出力して再認証へリダイレクト
   - 429/5xx: 最大2回リトライ

## 推奨される調査手順

1. **最新のログを確認**
   - サーバー側の`storage/app_error.log`で最新の403関連ログを確認

2. **ログの時系列を追跡**
   - トークンスコープ検査のログが出力されているか
   - Gmail API呼び出し前後でどのようなログが出力されているか

3. **エラーパターンの特定**
   - 上記の4つのパターンのうち、どれに該当するかを特定

4. **再認証フローの確認**
   - `google_oauth.php`の`google_auth_url()`で`prompt=consent`が設定されているか確認（115行目）
   - `config.php`の`GOOGLE_SCOPES`と`GMAIL_SCOPE`が正しく設定されているか確認

## 次のステップ

ログファイルを確認した後、以下の情報を収集してください：

1. **トークンスコープ検査の結果**
   - `Token scope check OK`が出力されているか
   - `Gmail 403 Prevention`が出力されているか
   - `Tokeninfo check failed`が出力されているか

2. **Gmail APIエラーの詳細**
   - `reason`の値（特に`insufficientPermissions`かどうか）
   - `message`の内容
   - `token_scope`の値（実際に取得できたスコープ）

3. **再認証フローの動作**
   - `/auth/login?reauth=1`へのリダイレクトが正しく動作しているか
   - 再認証後に403エラーが解消されるか

これらの情報を基に、403エラーの根本原因を特定し、適切な修正を行うことができます。
