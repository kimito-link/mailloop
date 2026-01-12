# Git Push認証エラーの解決方法

## 問題

`git push origin main` を実行した際、認証エラーが発生しています：

```
fatal: Authentication failed for 'https://github.com/kimito-link/mailloop.git/'
```

## 解決方法

### 方法1: リモートURLをSSH形式に変更（推奨）

SSH鍵を使用して認証する方法です。`SSH_INFO.md`によると、SSH鍵が設定されているはずです。

#### 1. 現在のリモートURLを確認

```bash
# ローカルで実行
cd "c:\Users\info\OneDrive\デスクトップ\GitHub\resend.kimito-link.com"
git remote -v
```

#### 2. リモートURLをSSH形式に変更

```bash
# プロジェクト用のSSH設定を使用
git remote set-url origin git@github.com-kimitolink:kimito-link/mailloop.git
```

または、直接SSH形式に変更：

```bash
git remote set-url origin git@github.com:kimito-link/mailloop.git
```

#### 3. リモートURLを確認

```bash
git remote -v
```

**期待される出力:**
```
origin  git@github.com-kimitolink:kimito-link/mailloop.git (fetch)
origin  git@github.com-kimitolink:kimito-link/mailloop.git (push)
```

#### 4. 再度プッシュ

```bash
git push origin main
```

### 方法2: Personal Access Tokenを使用（HTTPS形式を維持する場合）

HTTPS形式を維持したい場合は、Personal Access Tokenを使用します。

#### 1. GitHubでPersonal Access Tokenを作成

1. GitHubにログイン
2. Settings → Developer settings → Personal access tokens → Tokens (classic)
3. "Generate new token (classic)" をクリック
4. 必要なスコープを選択（`repo` など）
5. トークンを生成し、コピー

#### 2. リモートURLを確認

```bash
git remote -v
```

#### 3. プッシュ時にトークンを使用

```bash
# ユーザー名: あなたのGitHubユーザー名
# パスワード: Personal Access Token
git push origin main
```

または、URLにトークンを埋め込む（非推奨、セキュリティ上の理由）：

```bash
git remote set-url origin https://YOUR_TOKEN@github.com/kimito-link/mailloop.git
```

### 方法3: Git Credential Managerを使用（Windows）

Windowsの場合、Git Credential Managerを使用して認証情報を保存できます。

#### 1. 認証情報をクリア

```bash
git credential reject https://github.com
```

#### 2. 再度プッシュ（認証情報を入力）

```bash
git push origin main
# ユーザー名とパスワード（Personal Access Token）を入力
```

## 推奨される方法

**方法1（SSH形式）** を推奨します。理由：

1. **セキュリティ**: SSH鍵はパスワードより安全
2. **利便性**: 一度設定すれば、パスワードを入力する必要がない
3. **既存の設定**: `SSH_INFO.md`によると、SSH鍵が既に設定されている

## SSH鍵の確認

SSH鍵が正しく設定されているか確認：

```bash
# SSH鍵の存在確認
ls -la ~/.ssh/id_kimitolink*

# GitHub接続テスト
ssh -T git@github.com-kimitolink
```

**期待される出力:**
```
Hi kimito-link! You've successfully authenticated, but GitHub does not provide shell access.
```

## トラブルシューティング

### SSH接続が失敗する場合

1. **SSH鍵のパスを確認**
   ```bash
   cat ~/.ssh/config
   ```

2. **SSH鍵の権限を確認**
   ```bash
   ls -la ~/.ssh/id_kimitolink
   # 出力: -rw------- (600)
   ```

3. **SSH鍵の権限を修正**
   ```bash
   chmod 600 ~/.ssh/id_kimitolink
   ```

### リモートURLが正しく設定されていない場合

```bash
# リモートURLを確認
git remote -v

# 正しいURLに変更
git remote set-url origin git@github.com-kimitolink:kimito-link/mailloop.git
```

## 次のステップ

認証が成功した後：

1. **サーバー側でコードを最新化**
   ```bash
   cd /home/besttrust/kimito-link.com/_git/mailloop
   git pull origin main
   ```

2. **OPcacheをクリア**
   ```bash
   php public/clear_cache.php
   ```

3. **セッション管理と認証ループの確認**
   ```bash
   grep -i "MailLoop Session" storage/app_error.log | tail -20
   grep -i "require_login" storage/app_error.log | tail -30
   ```

4. **403エラーログを確認**
   ```bash
   grep -i "Gmail 403" storage/app_error.log | tail -20
   ```

## 関連ファイル

- `SSH_INFO.md` - SSH接続情報とGitHub設定
- `FINAL_VERIFICATION_STEPS.md` - 最終確認手順
