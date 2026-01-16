# SSH接続 クイックスタート

## 🎯 最も簡単な方法

### Git Bashで接続する場合

```bash
# 1. 自動設定スクリプトを実行
chmod +x setup_gitbash_ssh.sh
./setup_gitbash_ssh.sh

# 2. 接続
ssh xserver-besttrust
```

### 直接接続する場合（設定不要）

```bash
ssh -i /c/Users/info/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
```

## 📝 接続情報

- **ホスト**: sv16.sixcore.ne.jp
- **ユーザー**: besttrust
- **ポート**: 10022
- **鍵**: C:\Users\info\.ssh\id_rsa

## 🔍 問題が発生した場合

1. **"Could not resolve hostname"** → `setup_gitbash_ssh.sh`を実行
2. **"Permission denied"** → 鍵ファイルの権限を確認
3. **その他** → `FIX_SSH_CONNECTION.md`を参照
