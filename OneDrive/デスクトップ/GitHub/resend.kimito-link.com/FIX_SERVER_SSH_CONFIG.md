# サーバー側SSH設定の修正手順

## 問題

`git pull origin main`を実行した際に以下のエラーが発生：
```
ssh: Could not resolve hostname github.com-kimitolink: Name or service not known
```

## 原因

サーバー側のSSH設定ファイル（`~/.ssh/config`）に`github.com-kimitolink`の設定がないため、ホスト名が解決できません。

## 解決方法

### 手順1: サーバー側のSSH設定ファイルを確認

```bash
# SSH設定ファイルを確認
cat ~/.ssh/config
```

### 手順2: SSH設定ファイルを作成または編集

```bash
# SSH設定ディレクトリが存在するか確認
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# SSH設定ファイルを編集
nano ~/.ssh/config
```

### 手順3: 以下の設定を追加

```bash
# GitHub接続用設定（プロジェクト用）
Host github.com-kimitolink
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_kimitolink
  IdentitiesOnly yes
```

### 手順4: SSH鍵ファイルを確認

```bash
# SSH鍵ファイルが存在するか確認
ls -la ~/.ssh/id_kimitolink*

# 存在しない場合は、ローカルからコピーする必要があります
```

### 手順5: 権限を設定

```bash
# SSH設定ファイルの権限を設定
chmod 600 ~/.ssh/config

# SSH鍵ファイルの権限を設定（存在する場合）
chmod 600 ~/.ssh/id_kimitolink
chmod 644 ~/.ssh/id_kimitolink.pub
```

### 手順6: GitHub接続をテスト

```bash
# GitHub接続テスト
ssh -T git@github.com-kimitolink
```

成功すると以下のようなメッセージが表示されます：
```
Hi kimito-link/mailloop! You've successfully authenticated, but GitHub does not provide shell access.
```

### 手順7: git pullを再実行

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
git pull origin main
```

## 代替方法: 直接github.comを使用

SSH設定が難しい場合は、リモートURLを直接`github.com`に変更することもできます：

```bash
# リモートURLを直接github.comに変更
git remote set-url origin git@github.com:kimito-link/mailloop.git

# ただし、この場合はサーバー側にSSH鍵（id_rsaまたはid_kimitolink）が必要です
```

## SSH鍵をサーバーにコピーする方法

サーバー側にSSH鍵がない場合、ローカルからコピーする必要があります：

### 方法1: SCPを使用（ローカルから実行）

```bash
# ローカルのGit Bashまたはターミナルで実行
scp -i ~/.ssh/id_rsa -P 10022 ~/.ssh/id_kimitolink besttrust@sv16.sixcore.ne.jp:~/.ssh/
scp -i ~/.ssh/id_rsa -P 10022 ~/.ssh/id_kimitolink.pub besttrust@sv16.sixcore.ne.jp:~/.ssh/
```

### 方法2: サーバー側で新規鍵を生成

```bash
# サーバー側で実行
ssh-keygen -t ed25519 -f ~/.ssh/id_kimitolink -C "admin@kimito-link.com"

# 公開鍵をGitHubに登録
cat ~/.ssh/id_kimitolink.pub
# この内容をGitHubのSettings > SSH and GPG keysに追加
```

## トラブルシューティング

### 1. SSH鍵のパスフレーズが設定されている場合

```bash
# SSH agentに鍵を追加
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_kimitolink
```

### 2. 接続テストでエラーが出る場合

```bash
# 詳細なデバッグ情報を表示
ssh -vT git@github.com-kimitolink
```

### 3. 権限エラーが発生する場合

```bash
# すべての権限を確認
ls -la ~/.ssh/
# 必要に応じて修正
chmod 700 ~/.ssh
chmod 600 ~/.ssh/config
chmod 600 ~/.ssh/id_kimitolink
```
