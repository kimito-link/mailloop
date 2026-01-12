# サーバー側でのデプロイ手順（gmail_send.php反映）

## 現在の状況

✅ **ローカル**: コミット `4dc6ece` をGitHubにプッシュ済み
⚠️ **サーバー**: 別のコミット `61d18f0` を作成しようとしているが、プッシュに失敗

## 解決方法

サーバー側でローカルの変更を取得する必要があります。

### 手順1: サーバーにSSH接続

```bash
ssh besttrust@sv16.sixcore.ne.jp -p 10022
```

または、SSH設定を使用している場合：

```bash
ssh xserver-besttrust
```

### 手順2: リポジトリディレクトリに移動

```bash
cd /home/besttrust/kimito-link.com/_git/mailloop
```

### 手順3: 現在の状態を確認

```bash
# 現在のブランチと状態を確認
git status

# 最新のコミットを確認
git log --oneline -3

# リモートURLを確認
git remote -v
```

### 手順4: リモートURLが正しく設定されているか確認

リモートURLが `https://github.com/...` になっている場合、SSH形式に変更する必要があります：

```bash
# リモートURLをSSH形式に変更
git remote set-url origin git@github.com-kimitolink:kimito-link/mailloop.git

# 確認
git remote -v
```

**注意**: サーバー側のSSH設定（`~/.ssh/config`）に `github.com-kimitolink` の設定が必要です。

### 手順5: ローカルの変更を取得

```bash
# ローカルの変更を取得
git pull origin main
```

### 手順6: サーバー側で作成したコミットがある場合の対処

もしサーバー側で未コミットの変更がある場合：

```bash
# 変更を一時保存
git stash

# 最新版を取得
git pull origin main

# 保存した変更を確認（必要に応じて）
git stash list
```

### 手順7: 競合が発生した場合

```bash
# サーバー側の変更を破棄して、リモートの最新版を取得
git reset --hard origin/main
git pull origin main
```

### 手順8: 更新を確認

```bash
# 更新されたファイルを確認
git log -1 --name-only

# gmail_send.phpの内容を確認
head -30 app/services/gmail_send.php | grep -A 5 "extract_emails"
```

## トラブルシューティング

### 1. SSH接続が失敗する場合

```bash
# 直接IPアドレスとポートを指定
ssh besttrust@202.226.36.17 -p 10022
```

### 2. GitHub認証エラーが発生する場合

サーバー側のSSH鍵がGitHubに登録されているか確認：

```bash
# GitHub接続テスト
ssh -T git@github.com-kimitolink
```

### 3. リモートURLがHTTPS形式の場合

```bash
# SSH形式に変更
git remote set-url origin git@github.com-kimitolink:kimito-link/mailloop.git
```

### 4. サーバー側のSSH設定を確認

```bash
# SSH設定ファイルを確認
cat ~/.ssh/config
```

以下の設定が必要です：

```
Host github.com-kimitolink
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_kimitolink
  IdentitiesOnly yes
```

## 確認事項

デプロイ後、以下を確認してください：

1. ✅ `app/services/gmail_send.php` に `extract_emails()` 関数が実装されているか
2. ✅ 連想配列形式の宛先に対応しているか
3. ✅ コミット `4dc6ece` がサーバー側に反映されているか
