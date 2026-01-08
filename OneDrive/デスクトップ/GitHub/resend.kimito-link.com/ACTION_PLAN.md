# 問題特定のためのアクションプラン

## 最優先：実ファイルテスト（HTTPだけで確認可能）

### アクション1: `/auth/callback`という実ファイルを作成

**目的**: `/auth/callback`がRewrite経由か、別の入口に吸われているかを判定

**手順**:
1. FTPまたはファイルマネージャーで以下にアクセス
   - パス: `/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/auth/`
   - または: `public_html/resend.kimito-link.com/auth/`

2. `callback`という名前のファイルを作成（拡張子なし、PHPじゃなくてOK）
   - 内容: `THIS_IS_REAL_FILE_CALLBACK`

3. ブラウザで以下にアクセス:
   ```
   https://resend.kimito-link.com/auth/callback
   ```

**判定**:
- ✅ `THIS_IS_REAL_FILE_CALLBACK`が表示される
  → Rewriteが効いている（ルータ経由で処理されている）
  → 次のステップ: DocumentRootの`index.php`を確認

- ❌ 表示されない（相変わらずエラー画面になる / 404になる）
  → `/auth/callback`は別の入口に吸われている
  → 次のステップ: Apache設定（Alias/VirtualHost）を確認

---

## SSHが使える場合：一発で確定する3つのコマンド

### アクション2: DocumentRoot直下の`index.php`を確認

```bash
readlink -f /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php
```

**確認ポイント**:
- シンボリックリンク先が`_git/mailloop/public/index.php`になっているか
- 別のパスを指していないか

### アクション3: `.htaccess`の`RewriteRule`を確認

```bash
cat /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/.htaccess
```

**確認ポイント**:
- `RewriteRule ^ index.php [QSA,L]`の`index.php`がどこを指しているか
- `/auth/callback`だけ例外で別へ飛ばすルールがないか

### アクション4: `/auth`ディレクトリの確認

```bash
ls -la /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/auth/
```

**確認ポイント**:
- `/auth/.htaccess`が存在しないか
- 別のファイルやディレクトリが存在しないか

---

## SSHが使えない場合：FTP/ファイルマネージャーで確認

### アクション5: DocumentRoot直下の`index.php`の内容を確認

**手順**:
1. FTPまたはファイルマネージャーで以下にアクセス
   - パス: `/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/`

2. `index.php`ファイルを開いて内容を確認

**確認ポイント**:
- 編集した`_git/mailloop/public/index.php`を`require`しているか
- 別のコードが書かれていないか
- シンボリックリンクか実ファイルか

### アクション6: `.htaccess`の内容を確認

**手順**:
1. FTPまたはファイルマネージャーで以下にアクセス
   - パス: `/home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/`

2. `.htaccess`ファイルを開いて内容を確認

**確認ポイント**:
- `RewriteRule ^ index.php [QSA,L]`が正しく設定されているか
- `/auth/callback`だけ例外で別へ飛ばすルールがないか

---

## 修正方法（問題が特定できた場合）

### 修正A: DocumentRootの`index.php`をラッパーにする

**手順**:
1. DocumentRoot直下の`index.php`を開く
2. 内容を以下に置き換える:

```php
<?php
require __DIR__ . '/../_git/mailloop/public/index.php';
```

**注意**: パスは実際の構造に合わせて調整してください。

### 修正B: `.htaccess`の`RewriteRule`を修正

**手順**:
1. `.htaccess`を開く
2. `RewriteRule ^ index.php [QSA,L]`を以下に変更:

```apache
RewriteRule ^ /path/to/_git/mailloop/public/index.php [QSA,L]
```

**注意**: パスは実際の構造に合わせて調整してください。

---

## 推奨される実行順序

1. **アクション1（実ファイルテスト）** ← 最優先（HTTPだけで確認可能）
2. **アクション2-4（SSHコマンド）** ← SSHが使える場合
3. **アクション5-6（FTP/ファイルマネージャー）** ← SSHが使えない場合
4. **修正AまたはB** ← 問題が特定できた場合

---

## 結果を共有する方法

以下の情報を共有してください：

1. **アクション1の結果**
   - `THIS_IS_REAL_FILE_CALLBACK`が表示されたか
   - 表示されなかった場合、何が表示されたか

2. **アクション2-4の結果**（SSHが使える場合）
   - `readlink -f`の結果
   - `.htaccess`の`RewriteRule`の部分
   - `/auth/`ディレクトリの内容

3. **アクション5-6の結果**（SSHが使えない場合）
   - DocumentRoot直下の`index.php`の内容（最初の10行程度）
   - `.htaccess`の`RewriteRule`の部分

これらの情報があれば、問題の原因を特定できます。
