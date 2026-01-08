# SSH接続情報 - resend.kimito-link.com

## 🔑 基本的な接続情報

### Xサーバー接続情報
```
ホスト名（Host）: ｓｓかか
実際のホスト名: sv16.sixcore.ne.jp
IPアドレス: 202.226.36.17
ユーザー名: besttrust
ポート番号: 10022
秘密鍵パス: C:\Users\info\.ssh\id_rsa
```

### GitHub接続情報（プロジェクト用）
```
ホスト名（Host）: github.com-kimitolink
実際のホスト名: github.com
ユーザー名: git
秘密鍵パス: C:\Users\info\.ssh\id_kimitolink
```

### GitHub接続情報（サーバー管理用）
```
ホスト名（Host）: github.com-xserver-besttrust
実際のホスト名: github.com
ユーザー名: git
秘密鍵パス: C:\Users\info\.ssh\id_rsa
```

---

## 📁 SSH設定ファイルの場所

### Windows
```
C:\Users\info\.ssh\config
```

### 設定ファイルの内容
```bash
# Xサーバービジネス besttrust アカウント用設定
# サーバー情報: sv16.sixcore.ne.jp (202.226.36.17)

# GitHub接続用設定（プロジェクト用）
Host github.com-kimitolink
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_kimitolink
  IdentitiesOnly yes

# GitHub接続用設定（サーバー管理用）
Host github.com-xserver-besttrust
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_rsa
  IdentitiesOnly yes

# Xサーバー直接接続用設定
Host xserver-besttrust
  HostName sv16.sixcore.ne.jp
  User besttrust
  Port 10022
  IdentityFile ~/.ssh/id_rsa
  ServerAliveInterval 60
  ServerAliveCountMax 10
```

---

## 🔐 SSH鍵ファイルの場所

### 鍵ファイル一覧
```
C:\Users\info\.ssh\
├── config            # SSH接続設定ファイル
├── id_kimitolink      # Ed25519秘密鍵（プロジェクト用）
├── id_kimitolink.pub  # Ed25519公開鍵（プロジェクト用）
├── id_rsa             # RSA秘密鍵（サーバー用）
├── id_rsa.pub         # RSA公開鍵（サーバー用）
└── known_hosts        # 接続履歴ファイル
```

---

## 🚀 接続コマンド

### Xサーバーに接続
```bash
ssh xserver-besttrust
```

### GitHub接続テスト（プロジェクト用）
```bash
ssh -T git@github.com-kimitolink
```

### GitHub接続テスト（サーバー管理用）
```bash
ssh -T git@github.com-xserver-besttrust
```

---

## 📂 サーバー上のディレクトリ構成

### Xサーバー上のプロジェクトディレクトリ（kimito-link.com）
```
/home/besttrust/kimito-link.com/
├── public_html/                    # 公開ディレクトリ（Webサイト）
│   └── → /home/besttrust/kimito-link.com/_git/kimito-link/src  # シンボリックリンク
├── _git/
│   └── kimito-link/               # Gitリポジトリ（クローン先）
│       ├── src/                   # 実際のWebファイル
│       ├── .git/                  # Git管理ファイル
│       ├── README.md
│       └── .gitignore
├── log/                           # ログファイル
├── mail/                          # メール関連
├── script/                        # スクリプトファイル
└── xserver_php/                   # PHP設定
```

### Gitリポジトリのパス（kimito-link.com）
```
/home/besttrust/kimito-link.com/_git/kimito-link
```

### resend.kimito-link.com のディレクトリ構成
```
/home/besttrust/kimito-link.com/
├── _git/
│   ├── kimito-link/
│   │   └── src/
│   │       └── resend.kimito-link.com/  # 公開先ディレクトリ
│   │           └── → /home/besttrust/kimito-link.com/_git/mailloop/public  # シンボリックリンク
│   └── mailloop/                  # 実体のアプリ
│       ├── app/
│       ├── config/
│       ├── database/
│       ├── views/
│       └── public/                # 公開されるディレクトリ
```

---

## 🚀 デプロイ手順

### 1. ローカル開発 → GitHub

```bash
# ローカルで変更をコミット・プッシュ
git add .
git commit -m "変更内容の説明"
git push origin main
```

### 2. Xserverで更新を取得（kimito-link.com）

```bash
# Xserverに接続
ssh xserver-besttrust

# Gitリポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/kimito-link

# 最新版を取得
git pull origin main

# 接続を終了
exit
```

### 3. Xserverで更新を取得（resend.kimito-link.com / mailloop）

```bash
# Xserverに接続
ssh xserver-besttrust

# Gitリポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/mailloop

# 最新版を取得
git pull origin main

# 接続を終了
exit
```

### 4. 競合が発生した場合

```bash
# 変更を一時保存
git stash

# 最新版を取得
git pull origin main

# 保存した変更を復元
git stash pop
```

---

## 🔄 GitリモートURL設定

### プロジェクト用リモートURL
```bash
git remote set-url origin git@github.com-kimitolink:kimito-link/kimito-link.git
```

### リモートURL確認
```bash
git remote -v
```

---

## 📝 よく使うコマンド

### Xサーバーでのデプロイ作業

#### kimito-link.com の場合
```bash
# Xサーバーに接続
ssh xserver-besttrust

# Gitリポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/kimito-link

# 最新版を取得
git pull origin main
```

#### resend.kimito-link.com (mailloop) の場合
```bash
# Xサーバーに接続
ssh xserver-besttrust

# Gitリポジトリディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/mailloop

# 最新版を取得
git pull origin main
```

### ローカルでの開発作業
```bash
# 変更をステージング
git add .

# コミット
git commit -m "変更内容の説明"

# GitHubにプッシュ
git push origin main
```

### Git関連

```bash
# 現在の状態確認
git status
git log --oneline -5

# 強制更新（ローカル変更を破棄）
git reset --hard origin/main
git pull origin main
```

### ファイル操作

```bash
# 権限確認
ls -la public/

# ディスク使用量確認
du -sh _git/
```

### ログ確認

```bash
# アクセスログ
tail -f log/resend.kimito-link.com_access_log

# エラーログ
tail -f log/resend.kimito-link.com_error_log
```

---

## ⚠️ 注意事項

1. **SSH鍵のパスフレーズ**: 鍵ファイルにはパスフレーズが設定されている可能性があります
2. **ホスト名の一致**: GitリモートURLとSSH設定のホスト名が一致している必要があります
3. **ポート番号**: Xサーバーは標準ポート22ではなく10022を使用しています
4. **鍵の使い分け**: 
   - サーバー接続 → `id_rsa`
   - GitHub（プロジェクト用） → `id_kimitolink`
   - GitHub（サーバー管理用） → `id_rsa`
5. **セキュリティ**: 
   - `config/secrets.php` はGit管理外
   - パスワード等の機密情報はコードに直接書かない

---

## 🆘 トラブルシューティング

### SSH接続エラー時
```bash
# SSH接続テスト
ssh -T git@github.com-kimitolink

# 設定ファイル確認
cat ~/.ssh/config
```

### Git認証エラー時
```bash
# リモートURL確認
git remote -v

# リモートURL修正
git remote set-url origin git@github.com-kimitolink:kimito-link/kimito-link.git
```

### 競合解決
```bash
# ローカル変更を一時保存
git stash

# リモート変更を取得
git pull origin main

# 保存した変更を復元
git stash pop
```

---

## 📧 連絡先情報

- **プロジェクト用メール**: admin@kimito-link.com
- **サーバー管理用メール**: info@besttrust
- **Windowsユーザー名**: info
- **会社名**: besttrust
- **プロジェクト名**: kimito-link

---

この情報をClaude Codeや他の開発者に伝える際に使用してください。
