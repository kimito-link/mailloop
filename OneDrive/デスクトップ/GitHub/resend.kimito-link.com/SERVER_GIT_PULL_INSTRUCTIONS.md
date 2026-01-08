# サーバー側でのgit pull実行手順

## サーバー側のリポジトリパス

`SSH_INFO.md`によると、サーバー側のリポジトリパスは以下の通りです：

```
/home/besttrust/kimito-link.com/_git/mailloop
```

## 実行手順

### 1. サーバーにSSH接続

```bash
ssh xserver-besttrust
```

または、直接SSH接続：

```bash
ssh besttrust@sv16.sixcore.ne.jp
```

### 2. リポジトリディレクトリに移動

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
```

### 3. 現在の状態を確認

```bash
# 現在のブランチを確認
git branch

# 現在の状態を確認
git status

# 最新のコミットを確認
git log -1 --oneline
```

### 4. git pullを実行

```bash
git pull origin main
```

### 5. 更新されたファイルを確認

```bash
# 更新されたファイルを確認
git log -1 --name-only

# ファイルの内容を確認
head -10 app/lib/db.php
```

### 6. OPcacheをクリア

```bash
# OPcacheをクリア
php public/clear_cache.php

# または、Webサーバーを再起動
sudo systemctl restart apache2
```

## パスが違う場合

もし `/home/besttrust/kimito-link.com/_git/mailloop` が存在しない場合：

### 1. リポジトリを探す

```bash
# .gitディレクトリを探す
find ~ -name ".git" -type d 2>/dev/null | grep mailloop

# または、db.phpファイルを探す
find ~ -name "db.php" -path "*/app/lib/db.php" 2>/dev/null
```

### 2. 見つかったパスでgit pullを実行

```bash
cd [見つかったパス]
git pull origin main
```

## トラブルシューティング

### git pullが失敗する場合

```bash
# リモートの最新情報を取得
git fetch origin

# 強制的にリセット（注意：ローカルの変更が失われます）
git reset --hard origin/main
```

### ファイルの権限エラーが発生する場合

```bash
# ファイルの所有者を確認
ls -la app/lib/db.php

# 必要に応じて所有者を変更
sudo chown besttrust:besttrust app/lib/db.php app/services/storage.php public/index.php
```

## 更新後の確認

1. **ファイルが更新されているか確認**
   ```bash
   git log -1 --name-only
   ```

2. **ファイルの内容を確認**
   ```bash
   head -10 app/lib/db.php
   grep -A 3 "error_log('db_pdo connect failed" app/lib/db.php
   ```

3. **OPcacheをクリア**
   ```bash
   php public/clear_cache.php
   ```

4. **再度OAuth認証を試す**
