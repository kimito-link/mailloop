# GitHubリポジトリ作成手順 - mailloop

## 📋 リポジトリ作成時の設定

### 1. Owner（所有者）
- **設定**: `kimito-link` ✅（既に選択されています）

### 2. Repository name（リポジトリ名）*
- **推奨**: `mailloop`
- **理由**: サーバー上のディレクトリ名と一致させるため
- **入力**: `mailloop`

### 3. Description（説明）
- **推奨**: `Gmail再利用・事故防止ツール - MailLoop`
- **または**: `MailLoop - Gmail再利用・事故防止ツール（CC/BCC対応）`

### 4. Visibility（公開設定）*
- **推奨**: `Private`（機密情報を含む可能性があるため）
- **または**: `Public`（公開しても問題ない場合）

### 5. Add README
- **設定**: `Off` ✅（既にローカルにREADME.mdがあるため）

### 6. Add .gitignore
- **設定**: `No .gitignore` ✅（後で手動で作成します）
- **理由**: `config/secrets.php` を除外する必要があるため

### 7. Add license
- **設定**: `No license` ✅（お好みで）

---

## ✅ 推奨設定まとめ

```
Owner: kimito-link
Repository name: mailloop
Description: Gmail再利用・事故防止ツール - MailLoop
Visibility: Private（推奨）または Public
Add README: Off
Add .gitignore: No .gitignore
Add license: No license
```

---

## 🚀 リポジトリ作成後の手順

### STEP 1: .gitignore の作成

リポジトリ作成後、ローカルで `.gitignore` を作成します：

```bash
cd "C:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"
```

`.gitignore` ファイルを作成して、以下を追加：

```
# 機密情報
config/secrets.php

# 一時ファイル
*.log
*.tmp

# OS固有ファイル
.DS_Store
Thumbs.db

# エディタ固有
.vscode/
.idea/
*.swp
*.swo
```

### STEP 2: Gitリモートの設定

```bash
# 現在のリモートを確認
git remote -v

# 新しいリモートを設定（既存のリモートを削除してから）
git remote remove origin
git remote add origin git@github.com-kimitolink:kimito-link/mailloop.git

# 確認
git remote -v
```

### STEP 3: 初回コミットとプッシュ

```bash
# .gitignore を追加
git add .gitignore

# 変更をステージング
git add .

# 初回コミット
git commit -m "Initial commit: MailLoop application for resend.kimito-link.com"

# GitHubにプッシュ
git push -u origin main
```

---

## 📝 注意事項

1. **`config/secrets.php` はGit管理外**
   - `.gitignore` に追加する必要があります
   - サーバー上で手動で作成します

2. **リポジトリ名は `mailloop` を推奨**
   - サーバー上のディレクトリ名と一致させます

3. **Visibility は Private を推奨**
   - 機密情報を含む可能性があるため

---

リポジトリを作成したら、次のステップに進みます。
