# サーバー側での更新手順

## 方法1: SSH接続して手動実行

サーバーにSSH接続して、以下のコマンドを実行してください：

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
git pull origin main
```

## 方法2: スクリプトをアップロードして実行

1. `server_update.sh` をサーバーにアップロード
2. 実行権限を付与：
   ```bash
   chmod +x server_update.sh
   ```
3. 実行：
   ```bash
   ./server_update.sh
   ```

## 更新後の確認

更新が完了したら、以下を確認してください：

1. **ファイルが更新されているか確認**：
   ```bash
   git log -1 --name-only
   ```

2. **変更されたファイルの内容を確認**（必要に応じて）：
   ```bash
   git diff HEAD~1 app/lib/db.php
   git diff HEAD~1 app/services/storage.php
   git diff HEAD~1 public/index.php
   ```

3. **OPcacheをクリア**（PHPのキャッシュをクリア）：
   ```bash
   # OPcacheクリア用のPHPファイルを実行
   php public/clear_cache.php
   ```
   または、Webサーバーを再起動：
   ```bash
   # Apacheの場合
   sudo systemctl restart apache2
   # または
   sudo service apache2 restart
   ```

## 更新内容

以下のファイルが更新されます：

- `app/lib/db.php` - 古いPHP互換版に修正、エラーログ追加
- `app/services/storage.php` - requirePdo()のエラーメッセージ強化
- `public/index.php` - require_login()と/sendルートでid存在チェック追加

## トラブルシューティング

### git pullが失敗する場合

```bash
# リモートの最新情報を取得
git fetch origin

# 現在のブランチを確認
git branch

# 強制的にリセット（注意：ローカルの変更が失われます）
git reset --hard origin/main
```

### ファイルの権限エラーが発生する場合

```bash
# ファイルの所有者を確認
ls -la app/lib/db.php app/services/storage.php public/index.php

# 必要に応じて所有者を変更
sudo chown besttrust:besttrust app/lib/db.php app/services/storage.php public/index.php
```
