# シンボリックリンク設定手順 - resend.kimito-link.com

## 📋 現在の状況

### 実体のアプリ
```
/kimito-link.com/_git/mailloop/
├── app/
├── config/
├── database/
├── views/
└── public/          # ← これが公開されるべきディレクトリ
```

### 現在の公開先
```
/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/
```

### 目標構成
```
/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/
└── → /kimito-link.com/_git/mailloop/public/  # シンボリックリンク
```

## 🔧 1. シンボリックリンクの作成手順

### ⚠️ 重要：実行前のパス確認（必須）

Xserverの環境によって `/home/besttrust/` が異なる可能性があるため、**実行前に必ずパスを確認**してください。

#### STEP 0: パスの確認（実行前の必須確認）

```bash
# XserverにSSH接続
ssh xserver-besttrust

# 1) ホーム直下確認
pwd
ls -la /home
ls -la ~

# 2) 実体の存在確認（mailloop 側）
ls -la /home/*/kimito-link.com/_git/mailloop/public 2>/dev/null
ls -la ~/kimito-link.com/_git/mailloop/public 2>/dev/null

# 3) 現在の公開先（src側）の存在確認
ls -la ~/kimito-link.com/_git/kimito-link/src 2>/dev/null
ls -la /home/*/kimito-link.com/_git/kimito-link/src 2>/dev/null

# 4) 公開先ディレクトリの実体確認（シンボリックリンク含め）
ls -ld ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com 2>/dev/null
readlink -f ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com 2>/dev/null

# 5) docroot確定（最強の確認方法）
date > ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_probe.txt
```

**確認結果をメモしてください：**
- 正しいホームディレクトリ: `/home/<user>/` （例: `/home/besttrust/`）
- 実体のパス: `/home/<user>/kimito-link.com/_git/mailloop/public`
- 公開先のパス: `/home/<user>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com`

**docroot確定確認:**
- ブラウザで `https://resend.kimito-link.com/_probe.txt` にアクセス
- ファイルの内容（日時）が表示されれば、そこが **100% docroot** です
- 表示されない場合は、公開先ディレクトリが異なる可能性があります

### STEP 1: docroot確定確認（Webで確認）

```bash
# STEP 0で _probe.txt を作成済みの場合、ブラウザで確認
# https://resend.kimito-link.com/_probe.txt
# 日時が表示されれば、そこがdocrootです
```

**重要**: `_probe.txt` がWebで見えない場合、公開先ディレクトリが異なる可能性があります。
Xserverのサーバーパネルで、`resend.kimito-link.com` の公開先ディレクトリを確認してください。

### STEP 2: 既存ディレクトリの確認とバックアップ

```bash
# 現在の公開先ディレクトリに移動
cd /home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/

# 現在の内容を確認
ls -la

# 既存ファイルをバックアップ（必要に応じて）
# 既存の index.html や default_page.png がある場合
mkdir -p /home/besttrust/kimito-link.com/_backup/resend.kimito-link.com
cp -r * /home/besttrust/kimito-link.com/_backup/resend.kimito-link.com/ 2>/dev/null || true
```

### STEP 3: 既存ディレクトリの削除またはリネーム

**注意**: 以下の `<HOME>` は、STEP 0で確認した正しいホームディレクトリに置き換えてください。

```bash
# 親ディレクトリに移動
cd <HOME>/kimito-link.com/_git/kimito-link/src/

# 既存ディレクトリをバックアップ名にリネーム（安全策）
mv resend.kimito-link.com resend.kimito-link.com.backup

# または、既存ディレクトリを削除（注意：バックアップ済み前提）
# rm -rf resend.kimito-link.com
```

### STEP 4: シンボリックリンクの作成

**注意**: 以下の `<HOME>` は、STEP 0で確認した正しいホームディレクトリに置き換えてください。

```bash
# 絶対パスでシンボリックリンクを作成
ln -s <HOME>/kimito-link.com/_git/mailloop/public <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com
```

**実際のコマンド例（`/home/besttrust/` の場合）:**
```bash
cd /home/besttrust/kimito-link.com/_git/kimito-link/src
mv resend.kimito-link.com resend.kimito-link.com.backup
ln -s /home/besttrust/kimito-link.com/_git/mailloop/public resend.kimito-link.com
ls -la resend.kimito-link.com
```

### STEP 5: リンクの確認

```bash
# リンクが正しく作成されたか確認
ls -la <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com

# 期待される出力例：
# lrwxrwxrwx 1 besttrust members 52 1月 15 10:00 resend.kimito-link.com -> /home/besttrust/kimito-link.com/_git/mailloop/public

# リンク先の内容を確認
ls -la <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/

# 実体の public/ ディレクトリの内容が表示されることを確認
# index.php, .htaccess, assets/ などが表示されればOK
```

**成功判定（二段階チェック）:**

1. **リンクの確認**
   - `resend.kimito-link.com -> .../_git/mailloop/public` と表示される
   - `ls -la <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/` で実体の内容が表示される

2. **Webで確認**
   - ブラウザで `https://resend.kimito-link.com/` にアクセス
   - Xserver初期ページが消えて、仮トップページが表示される
   - `https://resend.kimito-link.com/templates/new` が表示される

3. **DBで確認（最終確認・オプション）**
   - テンプレートを1件保存
   - phpMyAdminで `message_templates` テーブルに1行追加されることを確認

## 📁 2. 既存ファイル（index.html / default_page.png）の扱い

### 結論：削除しなくてOK

**理由**: 
- 手順では `mv resend... resend...backup` でフォルダごとバックアップするため
- 既存ファイルは全てバックアップ側に移動します
- 結果として「削除する/しない」の悩みが発生しません

**シンボリックリンク作成後:**
- リンク先（`mailloop/public/`）の内容が表示されます
- 既存の `index.html` はバックアップ側にあるため、表示に影響しません
- 必要に応じて、後でバックアップから削除することも可能です

### 注意点

- **シンボリックリンク作成後は、リンク先（`mailloop/public/`）の内容が表示されます**
- 既存ファイルはバックアップ側に移動しているため、表示に影響しません
- 復元が必要な場合は、バックアップディレクトリから復元できます

## ⚙️ 3. .htaccess とパス解決の注意点

### 3-1. .htaccess の設定確認

現在の `public/.htaccess` の内容：

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

**この設定はシンボリックリンクでも問題なく動作します。**

### 3-2. FollowSymLinks オプション

Xserverでは通常、`FollowSymLinks` オプションが有効になっていますが、確認が必要な場合：

```bash
# .htaccess に明示的に追加（通常は不要）
# Options +FollowSymLinks
```

**注意**: Xserverの設定で既に有効な場合、`.htaccess` に追加するとエラーになる可能性があります。まずは追加せずに動作確認してください。

### 3-3. PHPのパス解決（重要）

#### `__DIR__` の動作

PHPの `__DIR__` は**シンボリックリンクを解決した後の実際のパス**を返します。

```php
// public/index.php 内で
echo __DIR__;
// 出力: /home/besttrust/kimito-link.com/_git/mailloop/public
// （シンボリックリンクの実体パス）
```

#### 現在のコードでのパス解決

```php
// public/index.php
require_once __DIR__ . '/../app/bootstrap.php';
```

**このパス解決は正しく動作します：**
- `__DIR__` = `/home/besttrust/kimito-link.com/_git/mailloop/public`
- `__DIR__ . '/../app/bootstrap.php'` = `/home/besttrust/kimito-link.com/_git/mailloop/app/bootstrap.php` ✅

#### 確認すべきパス解決箇所

以下のコードはすべて `__DIR__` を使用しているため、問題ありません：

1. `public/index.php`:
   ```php
   require_once __DIR__ . '/../app/bootstrap.php';
   require_once __DIR__ . '/../views/helpers/emails.php';
   ```

2. `app/bootstrap.php`:
   ```php
   $config = require __DIR__ . '/../config/config.php';
   ```

3. `config/config.php`:
   ```php
   $secretsFile = __DIR__ . '/secrets.php';
   ```

4. `app/services/storage.php` (FileStorage):
   ```php
   $this->dir = __DIR__ . '/../../storage';
   ```

**すべて相対パスで `__DIR__` を使用しているため、シンボリックリンクでも正しく動作します。**

### 3-4. セキュリティ上の注意点

#### ディレクトリトラバーサル対策

現在のコードは相対パス（`../`）を使用していますが、`__DIR__` を使用しているため安全です：

- ✅ `__DIR__` は常に実体のディレクトリを指す
- ✅ シンボリックリンクを経由しても、実体のパスが使用される
- ✅ ディレクトリトラバーサル攻撃のリスクは低い

#### ファイル権限の確認

**基本は触らなくてOK**。ただし **403エラーが出た場合のみ** 確認します。

```bash
# 実体ディレクトリの権限確認
ls -ld /home/*/kimito-link.com/_git/mailloop/public 2>/dev/null
ls -la /home/*/kimito-link.com/_git/mailloop/public/index.php 2>/dev/null
```

**目安:**
- ディレクトリ: `drwxr-xr-x`（755）
- PHPファイル: `-rw-r--r--`（644）

**権限修正が必要な場合（403エラー時のみ）:**
```bash
chmod 755 <HOME>/kimito-link.com/_git/mailloop/public
chmod 644 <HOME>/kimito-link.com/_git/mailloop/public/*.php
```

## ✅ 4. 動作確認手順

### STEP 1: リンクの確認

```bash
# シンボリックリンクが正しく作成されているか
ls -la /home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com

# リンク先の内容が表示されるか
ls -la /home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/
```

### STEP 2: Webアクセステスト

1. ブラウザで `https://resend.kimito-link.com/` にアクセス
2. トップページが表示されることを確認
3. エラーが出ないことを確認

### STEP 3: PHPパスの確認（オプション）

```bash
# サーバー上で簡単なテストスクリプトを作成
cat > /home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/test-path.php << 'EOF'
<?php
echo "__DIR__: " . __DIR__ . "\n";
echo "realpath(__DIR__): " . realpath(__DIR__) . "\n";
echo "file_exists(__DIR__ . '/../app/bootstrap.php'): " . (file_exists(__DIR__ . '/../app/bootstrap.php') ? 'YES' : 'NO') . "\n";
EOF

# ブラウザで https://resend.kimito-link.com/test-path.php にアクセス
# 確認後、テストファイルを削除
rm /home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/test-path.php
```

## 🚨 トラブルシューティング

### 問題1: `mv: cannot stat` エラー

**原因**: そこにフォルダが無い（公開先が違う）

**対処**:
```bash
# 正しい公開先を確認
ls -la <HOME>/kimito-link.com/_git/kimito-link/src/
# または
find <HOME> -name "resend.kimito-link.com" -type d 2>/dev/null
```

### 問題2: `ln: failed to create symbolic link: File exists`

**原因**: `resend.kimito-link.com` が残っている（削除かリネームが必要）

**対処**:
```bash
# 既存のディレクトリ/リンクを確認
ls -la <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com

# 既存のものを削除またはリネーム
rm -rf <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com
# または
mv <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com.backup
```

### 問題3: ブラウザがまだ初期ページを表示

**原因**: 公開先が `src/resend.kimito-link.com` じゃない（別のdocroot）

**対処**:
```bash
# Xserverのサーバーパネルで、resend.kimito-link.com の公開先ディレクトリを確認
# 実際の公開先が異なる場合は、そのディレクトリに対してシンボリックリンクを作成
```

### 問題4: シンボリックリンクが表示されない

```bash
# リンクが正しく作成されているか確認
ls -la <HOME>/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com

# 実体パスが存在するか確認
ls -la <HOME>/kimito-link.com/_git/mailloop/public
```

### 問題2: 403 Forbidden エラー

```bash
# 実体ディレクトリの権限を確認
ls -ld /home/besttrust/kimito-link.com/_git/mailloop/public

# 権限を修正（必要に応じて）
chmod 755 /home/besttrust/kimito-link.com/_git/mailloop/public
```

### 問題3: 500 Internal Server Error

```bash
# エラーログを確認
tail -f /home/besttrust/kimito-link.com/log/resend.kimito-link.com_error_log

# パス解決の問題の可能性がある場合
# test-path.php で確認
```

### 問題4: ファイルが見つからないエラー

```bash
# 実体のディレクトリ構造を確認
ls -la <HOME>/kimito-link.com/_git/mailloop/

# app/, config/, views/ が存在するか確認
```

### 問題5: シンボリックリンク禁止/制限（403や無反応）

**症状**: リンクは作れたのに403、または初期ページのまま

**原因**: Xserverの設定でシンボリックリンクが制限されている可能性（通常はOK）

**対処**:
```bash
# docrootの場所を確認
# Xserverのサーバーパネルで、resend.kimito-link.com の公開先ディレクトリを確認
# 必要に応じて、方法①（コピー）に切り替える
```

### 問題6: PHPが動いてない（.phpがダウンロード/表示される）

**症状**: `index.php` が実行されず、ソース表示/ダウンロードされる

**原因**: 公開先が「静的のみ」の領域に当たっている/設定ズレ

**対処**:
```bash
# probeでdocroot確定
# .phpの実行可否確認
# XserverのサーバーパネルでPHP設定を確認
```

### 問題7: Rewriteが効いてない（/templates/new が404）

**症状**: トップは出るがルーティングが効かない

**対処**:
```bash
# .htaccess が読まれているか確認
ls -la <HOME>/kimito-link.com/_git/mailloop/public/.htaccess

# .htaccess の内容確認
cat <HOME>/kimito-link.com/_git/mailloop/public/.htaccess

# サーバー側で AllowOverride 制限の可能性（通常XserverはOK）
```

### 問題8: キャッシュ/反映遅延

**症状**: 作業しても初期画面が残る

**対処**:
- シークレットモードでアクセス
- 別ブラウザで確認
- ハードリロード（Ctrl+F5 / Cmd+Shift+R）
- 数分待ってから再確認

## 📝 まとめ

### 実行すべきコマンド（まとめ）

**重要**: 以下の `<HOME>` は、STEP 0で確認した正しいホームディレクトリに置き換えてください。

```bash
# 0. パス確認（必須・最初に1回だけ）
ssh xserver-besttrust
pwd
ls -la ~
ls -la ~/kimito-link.com/_git/mailloop/public
ls -la ~/kimito-link.com/_git/kimito-link/src

# 0-1. docroot確定（最強の確認）
date > ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_probe.txt
# ブラウザで https://resend.kimito-link.com/_probe.txt が見えるか確認

# 1. 既存ディレクトリのバックアップ
cd <HOME>/kimito-link.com/_git/kimito-link/src/
mv resend.kimito-link.com resend.kimito-link.com.backup

# 2. シンボリックリンクの作成
ln -s <HOME>/kimito-link.com/_git/mailloop/public resend.kimito-link.com

# 3. 確認
ls -la resend.kimito-link.com
```

**実際のコマンド例（`/home/besttrust/` が正しい場合）:**
```bash
# docroot確定
date > /home/besttrust/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_probe.txt
# ブラウザで https://resend.kimito-link.com/_probe.txt を確認

# バックアップとシンボリックリンク作成
cd /home/besttrust/kimito-link.com/_git/kimito-link/src
mv resend.kimito-link.com resend.kimito-link.com.backup
ln -s /home/besttrust/kimito-link.com/_git/mailloop/public resend.kimito-link.com
ls -la resend.kimito-link.com
```

### 実行前チェックリスト

- [ ] SSH接続が可能か
- [ ] 実体のアプリ（`mailloop/public`）が存在するか
- [ ] 現在の公開先ディレクトリが存在するか
- [ ] **docroot確定（`_probe.txt` がWebで見える）** ← 重要
- [ ] バックアップ方針が "mv" で担保されている（復元できる）

### 重要なポイント

1. ✅ **絶対パスでリンクを作成**: 相対パスは避ける
2. ✅ **docroot確定を最優先**: `_probe.txt` でWebから確認
3. ✅ **既存ファイルは自動バックアップ**: `mv` でフォルダごとバックアップされるため削除不要
4. ✅ **`__DIR__` を使用**: シンボリックリンクでも正しく動作
5. ✅ **`.htaccess` はそのまま使用可能**: 追加設定は不要
6. ✅ **実体は常に `mailloop/public` を正とする**: リンク先を変更しない
7. ✅ **二段階チェックで成功判定**: リンク確認→Web確認→DB確認（オプション）

この手順で、シンボリックリンク経由でアプリが正しく動作します。
