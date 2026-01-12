# gmail_send.php サーバー反映手順

## 状況

✅ ローカルでの修正完了
✅ GitHubへのプッシュ完了（コミット: 4dc6ece）

## サーバーへの反映手順

### 方法1: SSH接続してgit pull（推奨）

1. **Git Bashまたはターミナルを開く**

2. **サーバーにSSH接続**
   ```bash
   ssh xserver-besttrust
   ```

3. **リポジトリディレクトリに移動**
   ```bash
   cd /home/besttrust/kimito-link.com/_git/mailloop
   ```

4. **最新版を取得**
   ```bash
   git pull origin main
   ```

5. **更新を確認**
   ```bash
   git log -1 --name-only
   head -20 app/services/gmail_send.php
   ```

6. **接続を終了**
   ```bash
   exit
   ```

### 方法2: ワンライナーコマンド

Git Bashで以下を実行：

```bash
ssh xserver-besttrust "cd /home/besttrust/kimito-link.com/_git/mailloop && git pull origin main"
```

### 方法3: 作成したスクリプトを使用

Git Bashで以下を実行：

```bash
cd "c:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"
bash deploy_gmail_send.sh
```

## 確認事項

デプロイ後、以下を確認してください：

1. ✅ `extract_emails()`関数が実装されているか
2. ✅ 連想配列形式の宛先に対応しているか
3. ✅ メール送信が正常に動作するか

## トラブルシューティング

### git pullが失敗する場合

```bash
# 変更を一時保存
git stash

# 最新版を取得
git pull origin main

# 保存した変更を復元（必要に応じて）
git stash pop
```

### 競合が発生した場合

```bash
# 強制更新（ローカル変更を破棄）
git reset --hard origin/main
git pull origin main
```
