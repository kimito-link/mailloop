# SSH接続設定ガイド

このディレクトリには、SSH接続問題を解決するためのスクリプトとドキュメントが含まれています。

## 📁 ファイル一覧

### スクリプトファイル

1. **`setup_gitbash_ssh.sh`** - Git Bash用のSSH接続設定スクリプト
   - Git Bashで実行して、SSH設定ファイルと鍵ファイルを設定します
   - 使用方法: `chmod +x setup_gitbash_ssh.sh && ./setup_gitbash_ssh.sh`

2. **`setup_ssh_connection.ps1`** - PowerShell用のSSH接続確認スクリプト
   - Windows PowerShellで実行して、SSH設定を確認・テストします
   - 使用方法: `powershell -ExecutionPolicy Bypass -File setup_ssh_connection.ps1`

3. **`test_ssh_connection.sh`** - SSH接続設定をテストするスクリプト（Git Bash用）
   - 実際に接続せずに設定を確認します
   - 使用方法: `chmod +x test_ssh_connection.sh && ./test_ssh_connection.sh`

### ドキュメントファイル

1. **`FIX_SSH_CONNECTION.md`** - SSH接続問題の解決手順
   - 詳細な手順とトラブルシューティング情報

2. **`SSH_INFO.md`** - SSH接続情報のリファレンス
   - 接続情報、設定ファイルの内容、よく使うコマンド

## 🚀 クイックスタート

### 方法1: Git Bashで自動設定（推奨）

```bash
# スクリプトに実行権限を付与
chmod +x setup_gitbash_ssh.sh

# スクリプトを実行
./setup_gitbash_ssh.sh

# 接続テスト
ssh xserver-besttrust
```

### 方法2: PowerShellで確認・テスト

```powershell
# PowerShellでスクリプトを実行
powershell -ExecutionPolicy Bypass -File setup_ssh_connection.ps1
```

### 方法3: 手動設定

Git Bashで以下を実行：

```bash
# .sshディレクトリを作成
mkdir -p ~/.ssh

# 設定ファイルをシンボリックリンクで設定
ln -s /c/Users/info/.ssh/config ~/.ssh/config

# 鍵ファイルをシンボリックリンクで設定
ln -s /c/Users/info/.ssh/id_rsa ~/.ssh/id_rsa
ln -s /c/Users/info/.ssh/id_rsa.pub ~/.ssh/id_rsa.pub

# 接続テスト
ssh xserver-besttrust
```

### 方法4: 直接IPアドレスで接続

SSH設定ファイルを使わずに直接接続：

```bash
ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

または、Windowsのパスを直接指定：

```bash
ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

## 🔧 トラブルシューティング

### 問題: "Could not resolve hostname"

**原因**: Git BashでSSH設定ファイルが読み込まれていない

**解決策**:
1. `setup_gitbash_ssh.sh`を実行
2. または、直接IPアドレスで接続（方法4）

### 問題: "Permission denied (publickey)"

**原因**: SSH鍵ファイルの権限が正しく設定されていない

**解決策**:
```bash
chmod 600 ~/.ssh/id_rsa
chmod 644 ~/.ssh/id_rsa.pub
chmod 700 ~/.ssh
```

### 問題: "No such file or directory"

**原因**: パスが正しくない

**解決策**: 絶対パスを使用
```bash
ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

## 📋 接続情報

- **ホスト名**: sv16.sixcore.ne.jp
- **IPアドレス**: 202.226.36.17
- **ユーザー名**: besttrust
- **ポート番号**: 10022
- **秘密鍵**: C:\Users\info\.ssh\id_rsa
- **SSH設定名**: xserver-besttrust

## 📚 関連ドキュメント

- [FIX_SSH_CONNECTION.md](FIX_SSH_CONNECTION.md) - 詳細な解決手順
- [SSH_INFO.md](SSH_INFO.md) - SSH接続情報のリファレンス

## ✅ 次のステップ

SSH接続が成功したら、以下のタスクに進みます：

1. サーバー側のSSH設定ファイルに`github.com-kimitolink`の設定を追加
2. `fix_page_not_found.sh`をサーバーにアップロード
3. サーバー側でスクリプトを実行してPage Not Foundエラーを修正
