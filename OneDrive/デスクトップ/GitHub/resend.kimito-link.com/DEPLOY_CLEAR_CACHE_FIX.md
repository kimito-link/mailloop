# clear_cache.php の構文エラー修正のデプロイ手順

## 修正内容

`public/clear_cache.php` の7行目で、PHP 7.0未満ではサポートされていない `??` (null合体演算子) を使用していたため、古いPHPバージョンと互換性のある `isset()` を使った書き方に変更しました。

**修正前:**
```php
$env = $config['APP_ENV'] ?? 'dev';
```

**修正後:**
```php
$env = isset($config['APP_ENV']) ? $config['APP_ENV'] : 'dev';
```

## デプロイ手順

### 1. ローカルで変更をコミット・プッシュ

```bash
# ローカルで実行
cd "c:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"

# 変更をステージング
git add public/clear_cache.php

# コミット
git commit -m "Fix PHP syntax error in clear_cache.php for old PHP versions"

# プッシュ
git push origin main
```

### 2. サーバー側でコードを最新化

```bash
# サーバー側で実行
cd /home/besttrust/kimito-link.com/_git/mailloop

# 最新版を取得
git pull origin main
```

### 3. OPcacheをクリア（再試行）

修正後、再度実行してください：

```bash
php public/clear_cache.php
```

**期待される出力:**
```
=== OPcache Clear Script ===

timestamp: 2026-01-08T20:56:00+09:00
__FILE__: /home/besttrust/kimito-link.com/_git/mailloop/public/clear_cache.php
realpath(__FILE__): /home/besttrust/kimito-link.com/_git/mailloop/public/clear_cache.php

OPcache is enabled.
✓ OPcache cleared successfully!
✓ APCu cache cleared.
✓ File stat cache cleared.
...
```

### 4. セッション管理のログを確認

```bash
# セッション管理関連のログを確認
grep -i "MailLoop Session" storage/app_error.log | tail -20
```

### 5. 認証ループが解決されたか確認

```bash
# 認証ループ関連のログを確認
grep -i "require_login" storage/app_error.log | tail -30
```

### 6. 403エラーログを確認

認証ループが解決された後、403エラー関連のログを確認してください：

```bash
# 403エラー関連のログを確認
grep -i "Gmail 403" storage/app_error.log | tail -20
grep -i "Tokeninfo check failed" storage/app_error.log | tail -20
grep -i "Gmail API Error: HTTP 403" storage/app_error.log | tail -20
```

## トラブルシューティング

### git pull が "Already up-to-date" と表示される場合

ローカルの変更がまだプッシュされていない可能性があります。ローカルで `git push origin main` を実行してください。

### まだ構文エラーが発生する場合

1. **ファイルが正しく更新されているか確認**
   ```bash
   # サーバー側で実行
   head -10 public/clear_cache.php
   # 7行目が `$env = isset($config['APP_ENV']) ? $config['APP_ENV'] : 'dev';` になっているか確認
   ```

2. **PHPバージョンを確認**
   ```bash
   php -v
   ```

3. **他の構文エラーがないか確認**
   ```bash
   php -l public/clear_cache.php
   ```

## 関連ファイル

- `public/clear_cache.php` - OPcacheクリアスクリプト（修正済み）
- `VERIFY_AFTER_UPDATE.md` - コード更新後の確認手順
