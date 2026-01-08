# サーバー側でのgit pull実行手順（完全版）

## ⚠️ 重要な注意

**`/home/besttrust/kimito-link.com/_git/mailloop` はサーバー側のパスです。**
ローカルのMINGW64ターミナルでは実行できません。**サーバーにSSH接続して実行する必要があります。**

## 手順1: サーバーにSSH接続

### 方法A: SSH設定を使用（推奨）

```bash
ssh xserver-besttrust
```

### 方法B: 直接SSH接続

```bash
ssh besttrust@sv16.sixcore.ne.jp
```

パスワードを入力するか、SSH鍵が設定されている場合は自動的に接続されます。

## 手順2: サーバー側でリポジトリに移動

```bash
# サーバー側のリポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/mailloop

# 現在のディレクトリを確認
pwd
```

## 手順3: 現在の状態を確認

```bash
# 現在のブランチを確認
git branch

# 現在の状態を確認
git status

# 最新のコミットを確認
git log -1 --oneline
```

## 手順4: git pullを実行

```bash
# 最新版を取得
git pull origin main
```

## 手順5: 更新されたファイルを確認

```bash
# 更新されたファイルを確認
git log -1 --name-only

# ファイルの内容を確認（declareがコメントアウトされているか）
head -10 app/lib/db.php

# エラーログ出力部分を確認
grep -A 3 "error_log('db_pdo connect failed" app/lib/db.php
```

## 手順6: OPcacheをクリア

```bash
# OPcacheをクリア
php public/clear_cache.php

# または、Webサーバーを再起動
sudo systemctl restart apache2
```

## パスが違う場合の対処

もし `/home/besttrust/kimito-link.com/_git/mailloop` が存在しない場合：

### 1. リポジトリを探す

```bash
# サーバー側で実行
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

### SSH接続できない場合

```bash
# SSH接続情報を確認
cat ~/.ssh/config | grep xserver-besttrust

# または、直接接続を試す
ssh -v besttrust@sv16.sixcore.ne.jp
```

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
   ```

3. **OPcacheをクリア**
   ```bash
   php public/clear_cache.php
   ```

4. **再度OAuth認証を試す**
