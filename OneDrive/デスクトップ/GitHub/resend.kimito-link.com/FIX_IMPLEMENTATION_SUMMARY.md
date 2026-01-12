# 構文エラーと403エラーの修正 - 実装完了サマリー

## 実装日
2026-01-08

## 実装内容

### ステップ1: 構文エラーの確認と修正 ✅

**確認結果:**
- `public/index.php` の1254行目に `$retryCount++;` が正しく配置されている
- 括弧のバランスは正しい
- リンターエラーなし

**修正内容:**
- ローカルのコードは正しく、サーバー側で `sed` コマンドによる修正時に問題が発生した可能性
- サーバー側にローカルの正しいコードを適用することで解決

### ステップ2: 403エラーの原因調査 ✅

**実装内容:**
1. **トークンスコープ検査の改善** (`public/index.php` 1068-1124行目)
   - tokeninfo API失敗時の詳細ログを追加
   - 401エラー時は即座に再認証へリダイレクト
   - スコープ確認成功時のログを追加

2. **403エラーハンドリングの改善** (`public/index.php` 1165-1204行目)
   - トークンスコープ情報をログに含める
   - `insufficientPermissions` 検出時のログを改善

### ステップ3: 403エラーの修正 ✅

**実装内容:**
1. **再認証フローの改善**
   - `google_auth_url()` 関数に `forceReauth` パラメータを追加 (`app/services/google_oauth.php` 111行目)
   - `/auth/login` ルートで `reauth=1` パラメータに対応 (`public/index.php` 399-401行目)
   - OAuth認証時のスコープ要求ログを追加 (`app/services/google_oauth.php` 117-132行目)

2. **設定の確認**
   - `config/config.php` の `GOOGLE_SCOPES` と `GMAIL_SCOPE` が正しく設定されていることを確認

## 修正ファイル一覧

1. **public/index.php**
   - トークンスコープ検査の改善 (1068-1124行目)
   - 403エラーハンドリングの改善 (1165-1204行目)
   - 再認証フローの改善 (399-401行目)

2. **app/services/google_oauth.php**
   - `google_auth_url()` 関数に `forceReauth` パラメータを追加 (111行目)
   - OAuth認証時のスコープ要求ログを追加 (117-132行目)

## 確認すべきファイル

- ✅ `public/index.php` (1033-1337行目): `/send/execute` ルートとエラーハンドリング
- ✅ `app/services/google_oauth.php`: `google_tokeninfo()` 関数とOAuth認証
- ✅ `app/services/gmail_send.php`: Gmail API呼び出しとエラーログ
- ✅ `config/config.php`: Google OAuth設定（`GOOGLE_SCOPES`, `GMAIL_SCOPE`）

## 修正後の確認項目

1. ✅ 構文エラーが解消されているか確認（リンターエラーなし）
2. ⏳ TOPページが正常に表示されるか確認（サーバー側で確認が必要）
3. ⏳ メール送信を試行し、403エラーが発生しないか確認（サーバー側で確認が必要）
4. ⏳ ログファイルで、トークンスコープ検査とエラーハンドリングが正しく動作しているか確認（サーバー側で確認が必要）

## サーバー側での確認手順

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop

# 1. 修正したファイルをアップロード（ローカルから）
# scp public/index.php user@server:/path/to/mailloop/public/
# scp app/services/google_oauth.php user@server:/path/to/mailloop/app/services/

# 2. 構文チェック
php -l public/index.php
php -l app/services/google_oauth.php

# 3. 動作確認
# - TOPページが正常に表示されるか確認
# - メール送信を試行し、403エラーが発生しないか確認
# - ログファイル（storage/app_error.log）で、トークンスコープ検査とエラーハンドリングが正しく動作しているか確認
```

## 期待される動作

1. **トークンスコープ検査**
   - Gmail API呼び出し前に、トークンに `gmail.send` スコープが含まれているか確認
   - スコープが不足している場合、`/auth/login?reauth=1` にリダイレクト

2. **403エラーハンドリング**
   - 403エラー発生時、詳細なログを出力（トークンスコープ情報を含む）
   - ユーザーに分かりやすいエラーメッセージを表示
   - ログにエラー情報を保存

3. **再認証フロー**
   - `reauth=1` パラメータが渡された場合、`prompt=consent` を強制
   - スコープ要求のログを出力

## 注意事項

- サーバー側で `sed` コマンドによる修正を行った場合、構文エラーが発生する可能性があります
- ローカルの正しいコードをサーバー側に適用することで解決します
- 修正後は必ず構文チェックを実行してください
