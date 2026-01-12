# Page Not Found エラー修正手順

## 問題
`https://resend.kimito-link.com` にアクセスすると「Page Not Found」エラーが表示される

## 考えられる原因

1. **シンボリックリンクが切れている**
2. **index.phpが存在しない、または正しいパスにない**
3. **.htaccessの設定問題**
4. **ドキュメントルートの設定が間違っている**

## 緊急対応手順

### STEP 1: サーバーにSSH接続

```bash
ssh xserver-besttrust
```

### STEP 2: ドキュメントルートの確認

まず、実際のドキュメントルートを確認します：

```bash
# パス1: public_html の場合
ls -la ~/kimito-link.com/public_html/resend.kimito-link.com/

# パス2: src の場合
ls -la ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/

# どちらが存在するか確認
```

### STEP 3: index.phpの存在確認

```bash
# public_html の場合
ls -la ~/kimito-link.com/public_html/resend.kimito-link.com/index.php

# src の場合
ls -la ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/index.php
```

### STEP 4: シンボリックリンクの確認

```bash
# シンボリックリンクかどうか確認
readlink -f ~/kimito-link.com/public_html/resend.kimito-link.com/index.php 2>/dev/null
readlink -f ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/index.php 2>/dev/null

# ディレクトリ全体がシンボリックリンクの場合
ls -ld ~/kimito-link.com/public_html/resend.kimito-link.com/
ls -ld ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/
```

### STEP 5: 実体ファイルの確認

```bash
# 実体のindex.phpが存在するか確認
ls -la ~/kimito-link.com/_git/mailloop/public/index.php
```

## 修正方法

### パターンA: public_html がドキュメントルートの場合

#### 方法1: シンボリックリンクを作成（推奨）

```bash
# 既存のディレクトリをバックアップ
mv ~/kimito-link.com/public_html/resend.kimito-link.com ~/kimito-link.com/public_html/resend.kimito-link.com.backup.$(date +%Y%m%d_%H%M%S)

# シンボリックリンクを作成
ln -s ~/kimito-link.com/_git/mailloop/public ~/kimito-link.com/public_html/resend.kimito-link.com
```

#### 方法2: index.phpを直接配置

```bash
# ラッパーファイルを作成
cat > ~/kimito-link.com/public_html/resend.kimito-link.com/index.php << 'EOF'
<?php
require __DIR__ . '/../_git/mailloop/public/index.php';
EOF

# .htaccessもコピー
cp ~/kimito-link.com/_git/mailloop/public/.htaccess ~/kimito-link.com/public_html/resend.kimito-link.com/.htaccess
```

### パターンB: src がドキュメントルートの場合

```bash
# 既存のディレクトリをバックアップ
mv ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com.backup.$(date +%Y%m%d_%H%M%S)

# シンボリックリンクを作成
ln -s ~/kimito-link.com/_git/mailloop/public ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com
```

## 確認手順

### 1. ファイルの存在確認

```bash
# ブラウザでアクセス可能か確認するためのテストファイル
echo "test" > ~/kimito-link.com/public_html/resend.kimito-link.com/_test.txt
# または
echo "test" > ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_test.txt
```

ブラウザで `https://resend.kimito-link.com/_test.txt` にアクセスして、`test` が表示されれば正しいドキュメントルートです。

### 2. index.phpの動作確認

```bash
# デバッグモードで確認
curl "https://resend.kimito-link.com/?dbg=raw"
```

`INDEX_PHP_HIT` が表示されれば、index.phpは正しく動作しています。

### 3. .htaccessの確認

```bash
# .htaccessが存在するか確認
ls -la ~/kimito-link.com/public_html/resend.kimito-link.com/.htaccess
# または
ls -la ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/.htaccess

# 内容を確認
cat ~/kimito-link.com/public_html/resend.kimito-link.com/.htaccess
```

## よくある問題と解決方法

### 問題1: シンボリックリンクが切れている

**症状**: `ls -l` で `->` の後に何も表示されない、または `broken link` と表示される

**解決方法**:
```bash
# シンボリックリンクを削除して再作成
rm ~/kimito-link.com/public_html/resend.kimito-link.com
ln -s ~/kimito-link.com/_git/mailloop/public ~/kimito-link.com/public_html/resend.kimito-link.com
```

### 問題2: パーミッションエラー

**症状**: 403 Forbidden エラー

**解決方法**:
```bash
# ディレクトリのパーミッションを確認
ls -ld ~/kimito-link.com/public_html/resend.kimito-link.com

# 必要に応じて修正（通常は755でOK）
chmod 755 ~/kimito-link.com/public_html/resend.kimito-link.com
```

### 問題3: .htaccessが読み込まれない

**症状**: ルーティングが機能しない

**解決方法**:
```bash
# .htaccessが存在するか確認
ls -la ~/kimito-link.com/public_html/resend.kimito-link.com/.htaccess

# 存在しない場合はコピー
cp ~/kimito-link.com/_git/mailloop/public/.htaccess ~/kimito-link.com/public_html/resend.kimito-link.com/.htaccess
```

## クイック修正スクリプト

以下のスクリプトを実行すると、自動的に修正を試みます：

```bash
#!/bin/bash
set -e

REPO_DIR="$HOME/kimito-link.com/_git/mailloop"
PUBLIC_DIR="$REPO_DIR/public"

# パス1: public_html を試す
PUBLIC_HTML_DIR="$HOME/kimito-link.com/public_html/resend.kimito-link.com"
if [ -d "$(dirname $PUBLIC_HTML_DIR)" ]; then
    echo "=== public_html パスで修正 ==="
    if [ -L "$PUBLIC_HTML_DIR" ]; then
        echo "既存のシンボリックリンクを削除"
        rm "$PUBLIC_HTML_DIR"
    elif [ -d "$PUBLIC_HTML_DIR" ]; then
        echo "既存ディレクトリをバックアップ"
        mv "$PUBLIC_HTML_DIR" "${PUBLIC_HTML_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
    fi
    echo "シンボリックリンクを作成"
    ln -s "$PUBLIC_DIR" "$PUBLIC_HTML_DIR"
    echo "✓ 完了: $PUBLIC_HTML_DIR -> $PUBLIC_DIR"
fi

# パス2: src を試す
SRC_DIR="$HOME/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com"
if [ -d "$(dirname $SRC_DIR)" ]; then
    echo "=== src パスで修正 ==="
    if [ -L "$SRC_DIR" ]; then
        echo "既存のシンボリックリンクを削除"
        rm "$SRC_DIR"
    elif [ -d "$SRC_DIR" ]; then
        echo "既存ディレクトリをバックアップ"
        mv "$SRC_DIR" "${SRC_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
    fi
    echo "シンボリックリンクを作成"
    ln -s "$PUBLIC_DIR" "$SRC_DIR"
    echo "✓ 完了: $SRC_DIR -> $PUBLIC_DIR"
fi

echo ""
echo "=== 確認 ==="
echo "ブラウザで以下にアクセスして確認してください:"
echo "https://resend.kimito-link.com/?dbg=raw"
```

このスクリプトを `fix_page_not_found.sh` として保存し、実行してください：

```bash
chmod +x fix_page_not_found.sh
./fix_page_not_found.sh
```

## 次のステップ

修正後、以下を確認してください：

1. `https://resend.kimito-link.com/?dbg=raw` にアクセス
2. `INDEX_PHP_HIT` が表示されることを確認
3. 通常のアクセス `https://resend.kimito-link.com/` で正常に表示されることを確認

問題が解決しない場合は、サーバーのエラーログを確認してください：

```bash
tail -n 50 ~/log/resend.kimito-link.com_error_log
```
