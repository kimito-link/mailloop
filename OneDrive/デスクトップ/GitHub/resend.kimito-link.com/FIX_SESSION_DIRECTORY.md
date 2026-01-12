# セッションディレクトリの問題と修正

## 問題の概要

サーバー側で確認した結果、セッションディレクトリ `/tmp/mailloop_sessions/` が存在しないことが判明しました。これが認証ループの根本原因である可能性が高いです。

## 問題の影響

セッションディレクトリが存在しない場合：

1. **セッションデータが保存できない**
   - ユーザー情報が保持されない
   - ログイン状態が維持されない

2. **認証ループが発生**
   - `require_login`でユーザーが見つからない
   - ログインページへのリダイレクトが繰り返される

3. **403エラーのログまで到達しない**
   - 認証が失敗するため、Gmail API呼び出しまで到達しない
   - トークンスコープ検査も実行されない

## 実施した修正

### 1. セッションディレクトリの作成処理を改善

`app/bootstrap.php`のセッションディレクトリ作成処理を改善しました：

- ディレクトリ作成の失敗をログに記録
- フォールバック: `/tmp`に作成できない場合、ホームディレクトリを使用
- セッションディレクトリの状態をログに記録

### 2. デバッグログの追加

セッション管理に関する詳細なログを追加しました：

- セッションディレクトリの作成成功/失敗
- 使用中のセッションディレクトリのパス
- セッションディレクトリの書き込み可能性

## サーバー側での確認と修正

### 1. セッションディレクトリを手動で作成

```bash
# サーバー側で実行
mkdir -p /tmp/mailloop_sessions
chmod 700 /tmp/mailloop_sessions

# 確認
ls -la /tmp/mailloop_sessions/
```

### 2. セッションディレクトリの権限を確認

```bash
# 現在のユーザーで書き込み可能か確認
touch /tmp/mailloop_sessions/test.txt && rm /tmp/mailloop_sessions/test.txt && echo "OK: 書き込み可能"
```

### 3. コードを最新化

```bash
# サーバー側で実行
cd /home/besttrust/kimito-link.com/_git/mailloop
git pull origin main
```

### 4. OPcacheをクリア

```bash
# OPcacheをクリア
php public/clear_cache.php
```

### 5. セッションの状態を確認

```bash
# ログでセッションディレクトリの状態を確認
grep -i "MailLoop Session" storage/app_error.log | tail -20
```

## 確認すべきログメッセージ

修正後、以下のログメッセージが表示されるはずです：

### 正常な場合

```
MailLoop Session: Created session directory: /tmp/mailloop_sessions
MailLoop Session: Using session directory: /tmp/mailloop_sessions
```

### フォールバックが使用された場合

```
MailLoop Session: Failed to create session directory: /tmp/mailloop_sessions | error=...
MailLoop Session: Using fallback session directory: /home/besttrust/mailloop_sessions
MailLoop Session: Using session directory: /home/besttrust/mailloop_sessions
```

### 警告の場合

```
MailLoop Session: WARNING - Session directory not writable: /tmp/mailloop_sessions | exists=yes | writable=no
```

## 次のステップ

1. **セッションディレクトリを手動で作成**
   ```bash
   mkdir -p /tmp/mailloop_sessions
   chmod 700 /tmp/mailloop_sessions
   ```

2. **コードを最新化**
   ```bash
   git pull origin main
   ```

3. **OPcacheをクリア**
   ```bash
   php public/clear_cache.php
   ```

4. **認証ループが解決されたか確認**
   - ログで`require_login`の動作を確認
   - セッションディレクトリの状態を確認

5. **403エラーログを確認**
   - 認証ループが解決された後、403エラー関連のログを確認
   - トークンスコープ検査の結果を確認

## 関連ファイル

- `app/bootstrap.php` - セッション管理の設定（修正済み）
- `SESSION_DEBUG_GUIDE.md` - セッション管理デバッグガイド
- `FINAL_403_INVESTIGATION_SUMMARY.md` - 最終調査サマリー
