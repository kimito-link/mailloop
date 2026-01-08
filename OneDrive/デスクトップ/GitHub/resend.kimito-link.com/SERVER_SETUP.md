# サーバーセットアップ手順

## ✅ 完了した作業

1. ✅ GitHubリポジトリ `kimito-link/mailloop` を作成
2. ✅ ローカルコードをGitHubにプッシュ完了

## 🚀 次のステップ：サーバー上でのセットアップ

### STEP 1: サーバー上のディレクトリ確認

SSH接続して、以下を実行してください：

```bash
ssh xserver-besttrust

# ホームディレクトリ確認
pwd

# ディレクトリ構造確認
ls -la ~/kimito-link.com/_git/ 2>/dev/null
ls -la ~/kimito-link.com/_git/mailloop/ 2>/dev/null
ls -la ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/ 2>/dev/null
```

### STEP 2: mailloopディレクトリが存在しない場合

```bash
# ディレクトリ作成
mkdir -p ~/kimito-link.com/_git/mailloop
cd ~/kimito-link.com/_git/mailloop

# GitHubからクローン
git clone git@github.com-kimitolink:kimito-link/mailloop.git .
```

### STEP 3: mailloopディレクトリが既に存在する場合

```bash
cd ~/kimito-link.com/_git/mailloop

# 既存のリモートを確認
git remote -v

# リモートを設定（必要に応じて）
git remote add origin git@github.com-kimitolink:kimito-link/mailloop.git
# または
git remote set-url origin git@github.com-kimitolink:kimito-link/mailloop.git

# 最新版を取得
git pull origin main
```

### STEP 4: config/secrets.php の設定

サーバー上で `config/secrets.php` を作成・編集：

```bash
cd ~/kimito-link.com/_git/mailloop
nano config/secrets.php
# または
vi config/secrets.php
```

以下の内容を設定：
```php
<?php
return [
  'DB_HOST' => 'sv16.sixcore.ne.jp',
  'DB_NAME' => 'besttrust_mail',
  'DB_USER' => 'besttrust_mail',
  'DB_PASS' => 'pass369code',
];
```

### STEP 5: シンボリックリンクの設定

```bash
# パス確認
pwd
ls -la ~/kimito-link.com/_git/mailloop/public

# docroot確定
date > ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_probe.txt
# ブラウザで https://resend.kimito-link.com/_probe.txt を確認

# シンボリックリンク作成
cd ~/kimito-link.com/_git/kimito-link/src/
mv resend.kimito-link.com resend.kimito-link.com.backup
ln -s ~/kimito-link.com/_git/mailloop/public resend.kimito-link.com
ls -la resend.kimito-link.com
```

---

## 📋 実行手順（まとめ）

1. **SSH接続**
   ```bash
   ssh xserver-besttrust
   ```

2. **ディレクトリ確認とクローン**
   ```bash
   pwd
   ls -la ~/kimito-link.com/_git/mailloop/ 2>/dev/null
   
   # 存在しない場合
   mkdir -p ~/kimito-link.com/_git/mailloop
   cd ~/kimito-link.com/_git/mailloop
   git clone git@github.com-kimitolink:kimito-link/mailloop.git .
   ```

3. **secrets.php の設定**
   ```bash
   nano config/secrets.php
   # DB設定を入力
   ```

4. **シンボリックリンク設定**
   ```bash
   date > ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_probe.txt
   # ブラウザで確認後
   cd ~/kimito-link.com/_git/kimito-link/src/
   mv resend.kimito-link.com resend.kimito-link.com.backup
   ln -s ~/kimito-link.com/_git/mailloop/public resend.kimito-link.com
   ```

---

SSH接続して、上記の手順を実行してください。各ステップの結果を共有いただければ、次のステップをサポートします。
