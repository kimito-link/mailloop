# SSH接続問題の解決手順

## 問題の確認

Git Bashで`ssh xserver-besttrust`が「Could not resolve hostname」で失敗する問題を解決します。

## 確認済み事項

✅ WindowsのSSH設定ファイル（`C:\Users\info\.ssh\config`）は存在し、`xserver-besttrust`の設定が正しく含まれています。
✅ SSH鍵ファイル（`C:\Users\info\.ssh\id_rsa`）も存在しています。

## 解決方法

### 方法1: Git BashでSSH設定ファイルの場所を確認・修正

1. **Git Bashを開く**

2. **ホームディレクトリを確認**
   ```bash
   echo $HOME
   pwd
   ```

3. **SSH設定ファイルの存在確認**
   ```bash
   ls -la ~/.ssh/config
   # または
   ls -la /c/Users/info/.ssh/config
   ```

4. **設定ファイルが存在しない場合、シンボリックリンクまたはコピーを作成**

   **オプションA: シンボリックリンクを作成（推奨）**
   ```bash
   mkdir -p ~/.ssh
   ln -s /c/Users/info/.ssh/config ~/.ssh/config
   ```

   **オプションB: ファイルをコピー**
   ```bash
   mkdir -p ~/.ssh
   cp /c/Users/info/.ssh/config ~/.ssh/config
   chmod 600 ~/.ssh/config
   ```

5. **SSH鍵ファイルも同様に確認・設定**
   ```bash
   # 鍵ファイルの確認
   ls -la /c/Users/info/.ssh/id_rsa
   
   # 必要に応じてシンボリックリンクまたはコピー
   ln -s /c/Users/info/.ssh/id_rsa ~/.ssh/id_rsa
   ln -s /c/Users/info/.ssh/id_rsa.pub ~/.ssh/id_rsa.pub
   chmod 600 ~/.ssh/id_rsa
   chmod 644 ~/.ssh/id_rsa.pub
   ```

6. **接続テスト**
   ```bash
   ssh xserver-besttrust
   ```

### 方法2: 直接IPアドレスで接続（代替方法）

SSH設定ファイルの問題が解決できない場合は、直接IPアドレスとポート番号を指定して接続できます。

```bash
ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

または、Windowsのパスを直接指定：

```bash
ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

### 方法3: エイリアスまたはスクリプトを作成

Git Bashの`~/.bashrc`または`~/.bash_profile`に以下を追加：

```bash
# SSH接続エイリアス
alias ssh-xserver='ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp'
```

または、スクリプトファイルを作成：

```bash
# ~/ssh-xserver.sh を作成
cat > ~/ssh-xserver.sh << 'EOF'
#!/bin/bash
ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
EOF

chmod +x ~/ssh-xserver.sh
```

使用時：
```bash
~/ssh-xserver.sh
```

## トラブルシューティング

### 問題1: "Could not resolve hostname"

**原因**: Git BashでSSH設定ファイルが読み込まれていない

**解決策**: 
- 方法1を実行してSSH設定ファイルをGit Bashのホームディレクトリに配置
- または方法2で直接IPアドレスを使用

### 問題2: "Permission denied (publickey)"

**原因**: SSH鍵ファイルの権限が正しく設定されていない

**解決策**:
```bash
chmod 600 ~/.ssh/id_rsa
chmod 644 ~/.ssh/id_rsa.pub
chmod 700 ~/.ssh
```

### 問題3: "No such file or directory"

**原因**: パスが正しくない

**解決策**: 絶対パスを使用
```bash
ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

## 接続情報の確認

- **ホスト名**: sv16.sixcore.ne.jp
- **IPアドレス**: 202.226.36.17
- **ユーザー名**: besttrust
- **ポート番号**: 10022
- **秘密鍵**: C:\Users\info\.ssh\id_rsa

## 次のステップ

SSH接続が成功したら、以下のタスクに進みます：
1. サーバー側のSSH設定ファイルに`github.com-kimitolink`の設定を追加
2. `fix_page_not_found.sh`をサーバーにアップロード
3. サーバー側でスクリプトを実行してPage Not Foundエラーを修正
