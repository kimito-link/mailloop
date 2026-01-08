# サーバー側でのパス確認手順

## 問題

ローカルではファイルが正しく更新されていますが、サーバー側のパス `/home/besttrust/kimito-link.com/_git/mailloop` が見つかりません。

## 解決方法

### 1. サーバーにSSH接続

サーバーにSSH接続して、以下を実行してください：

```bash
# ホームディレクトリに移動
cd ~

# 現在のディレクトリを確認
pwd
```

### 2. リポジトリの場所を探す

```bash
# .gitディレクトリを探す
find ~ -name ".git" -type d 2>/dev/null

# mailloopディレクトリを探す
find ~ -type d -name "mailloop" 2>/dev/null

# または、リポジトリのルートを探す
find ~ -name "db.php" -path "*/app/lib/db.php" 2>/dev/null
```

### 3. 実際のWebサーバーのパスを確認

```bash
# シンボリックリンクを確認
ls -la /home/besttrust/kimito-link.com/

# または、実際のパスを確認
readlink -f /home/besttrust/kimito-link.com/public 2>/dev/null
```

### 4. 正しいパスが見つかったら

```bash
# 正しいパスに移動
cd [見つかったパス]

# git pullを実行
git pull origin main

# ファイルの内容を確認
head -10 app/lib/db.php
```

## よくあるパスパターン

サーバー側のリポジトリは以下のようなパスにある可能性があります：

```bash
# パターン1
~/kimito-link.com/_git/mailloop

# パターン2
~/mailloop

# パターン3
/var/www/html/mailloop

# パターン4
/home/besttrust/public_html/mailloop
```

## 代替方法：直接ファイルを確認

リポジトリのパスが分からない場合、Webサーバーが読み込んでいるファイルを直接確認：

```bash
# Webサーバーのドキュメントルートを確認
# Apacheの場合
grep -r "DocumentRoot" /etc/apache2/sites-enabled/ 2>/dev/null

# または、.htaccessで確認
cat /home/besttrust/kimito-link.com/public/.htaccess | grep -i "rewritebase"
```

## 次のステップ

1. サーバーにSSH接続
2. 上記のコマンドでリポジトリのパスを特定
3. 正しいパスで`git pull`を実行
4. ファイルの内容を確認
5. OPcacheをクリア
