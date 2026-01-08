# 問題特定のための実行手順

## ステップ1: 実ファイルテスト（最優先）

### 1-1. ファイルをアップロード

1. このリポジトリの`auth_callback_test_file`をダウンロード
2. FTPまたはファイルマネージャーで以下にアップロード:
   - **パス**: `/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/auth/callback`
   - **注意**: 拡張子なし、ファイル名は`callback`のみ

### 1-2. ブラウザで確認

以下のURLにアクセス:
```
https://resend.kimito-link.com/auth/callback
```

### 1-3. 結果の判定

- ✅ `THIS_IS_REAL_FILE_CALLBACK`が表示される
  → Rewriteが効いている（ルータ経由）
  → **ステップ2へ進む**

- ❌ 表示されない（エラー画面になる / 404になる）
  → 別の入口に吸われている
  → **ステップ3へ進む**

---

## ステップ2: DocumentRoot直下の`index.php`を確認・修正

### 2-1. 現在の`index.php`を確認

FTPまたはファイルマネージャーで以下を確認:
- **パス**: `/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php`
- **内容**: 最初の10行程度を確認

### 2-2. 修正が必要な場合

現在の`index.php`が編集したコードを読み込んでいない場合、以下の内容に置き換えてください:

```php
<?php
require __DIR__ . '/../_git/mailloop/public/index.php';
```

**または**、このリポジトリの`public_html_index.php`の内容を使用してください。

**注意**: パスは実際の構造に合わせて調整してください。シンボリックリンクの場合は、リンク先を確認してください。

### 2-3. 確認

修正後、以下にアクセス:
```
https://resend.kimito-link.com/auth/callback?dbg=raw
```

`INDEX_PHP_HIT`または`HIT_CALLBACK`が表示されれば成功です。

---

## ステップ3: SSHで確認（可能な場合）

以下のコマンドを実行して結果を共有してください:

```bash
# 1. DocumentRoot直下のindex.phpの実体を確認
readlink -f /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php

# 2. .htaccessのRewriteRuleを確認
cat /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/.htaccess

# 3. /authディレクトリの内容を確認
ls -la /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/auth/
```

---

## ステップ4: .htaccessの確認

FTPまたはファイルマネージャーで以下を確認:
- **パス**: `/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/.htaccess`
- **内容**: `RewriteRule`の部分を確認

`RewriteRule ^ index.php [QSA,L]`が正しく設定されているか確認してください。

---

## トラブルシューティング

### パスが分からない場合

1. `callback_dbg.php`が動いているパスを確認
2. そのパスから`../`でDocumentRootを特定
3. そのDocumentRoot直下の`index.php`を確認

### ファイルをアップロードできない場合

1. ファイルマネージャーの権限を確認
2. FTPの接続情報を確認
3. サーバー管理者に相談

### 修正後も動かない場合

1. ブラウザのキャッシュをクリア
2. ファイルのパーミッションを確認（644推奨）
3. `.htaccess`の設定を再確認
