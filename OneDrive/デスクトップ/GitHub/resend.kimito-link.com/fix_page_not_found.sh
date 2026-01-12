#!/bin/bash
# Page Not Found エラー修正スクリプト
# サーバー側で実行してください

set -e

echo "=== Page Not Found エラー修正スクリプト ==="
echo ""

REPO_DIR="$HOME/kimito-link.com/_git/mailloop"
PUBLIC_DIR="$REPO_DIR/public"

# 実体ファイルの存在確認
if [ ! -d "$PUBLIC_DIR" ]; then
    echo "❌ エラー: 実体ディレクトリが見つかりません: $PUBLIC_DIR"
    exit 1
fi

if [ ! -f "$PUBLIC_DIR/index.php" ]; then
    echo "❌ エラー: index.phpが見つかりません: $PUBLIC_DIR/index.php"
    exit 1
fi

echo "✓ 実体ファイルを確認: $PUBLIC_DIR/index.php"
echo ""

# パス1: public_html を試す
PUBLIC_HTML_DIR="$HOME/kimito-link.com/public_html/resend.kimito-link.com"
if [ -d "$(dirname $PUBLIC_HTML_DIR)" ]; then
    echo "=== public_html パスで修正 ==="
    
    # 既存のシンボリックリンクまたはディレクトリを確認
    if [ -L "$PUBLIC_HTML_DIR" ]; then
        CURRENT_TARGET=$(readlink -f "$PUBLIC_HTML_DIR" 2>/dev/null || readlink "$PUBLIC_HTML_DIR")
        echo "既存のシンボリックリンクを発見: $PUBLIC_HTML_DIR -> $CURRENT_TARGET"
        
        if [ "$CURRENT_TARGET" = "$PUBLIC_DIR" ]; then
            echo "✓ シンボリックリンクは既に正しく設定されています"
        else
            echo "シンボリックリンクを削除して再作成"
            rm "$PUBLIC_HTML_DIR"
            ln -s "$PUBLIC_DIR" "$PUBLIC_HTML_DIR"
            echo "✓ シンボリックリンクを再作成: $PUBLIC_HTML_DIR -> $PUBLIC_DIR"
        fi
    elif [ -d "$PUBLIC_HTML_DIR" ]; then
        echo "既存ディレクトリをバックアップ"
        BACKUP_DIR="${PUBLIC_HTML_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
        mv "$PUBLIC_HTML_DIR" "$BACKUP_DIR"
        echo "✓ バックアップ作成: $BACKUP_DIR"
        
        echo "シンボリックリンクを作成"
        ln -s "$PUBLIC_DIR" "$PUBLIC_HTML_DIR"
        echo "✓ シンボリックリンク作成: $PUBLIC_HTML_DIR -> $PUBLIC_DIR"
    else
        echo "ディレクトリが存在しないため、シンボリックリンクを作成"
        ln -s "$PUBLIC_DIR" "$PUBLIC_HTML_DIR"
        echo "✓ シンボリックリンク作成: $PUBLIC_HTML_DIR -> $PUBLIC_DIR"
    fi
    
    # .htaccessの確認
    if [ ! -f "$PUBLIC_HTML_DIR/.htaccess" ]; then
        echo ".htaccessが見つかりません。コピーします"
        cp "$PUBLIC_DIR/.htaccess" "$PUBLIC_HTML_DIR/.htaccess"
        echo "✓ .htaccessをコピー"
    fi
    
    echo ""
fi

# パス2: src を試す
SRC_DIR="$HOME/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com"
if [ -d "$(dirname $SRC_DIR)" ]; then
    echo "=== src パスで修正 ==="
    
    # 既存のシンボリックリンクまたはディレクトリを確認
    if [ -L "$SRC_DIR" ]; then
        CURRENT_TARGET=$(readlink -f "$SRC_DIR" 2>/dev/null || readlink "$SRC_DIR")
        echo "既存のシンボリックリンクを発見: $SRC_DIR -> $CURRENT_TARGET"
        
        if [ "$CURRENT_TARGET" = "$PUBLIC_DIR" ]; then
            echo "✓ シンボリックリンクは既に正しく設定されています"
        else
            echo "シンボリックリンクを削除して再作成"
            rm "$SRC_DIR"
            ln -s "$PUBLIC_DIR" "$SRC_DIR"
            echo "✓ シンボリックリンクを再作成: $SRC_DIR -> $PUBLIC_DIR"
        fi
    elif [ -d "$SRC_DIR" ]; then
        echo "既存ディレクトリをバックアップ"
        BACKUP_DIR="${SRC_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
        mv "$SRC_DIR" "$BACKUP_DIR"
        echo "✓ バックアップ作成: $BACKUP_DIR"
        
        echo "シンボリックリンクを作成"
        ln -s "$PUBLIC_DIR" "$SRC_DIR"
        echo "✓ シンボリックリンク作成: $SRC_DIR -> $PUBLIC_DIR"
    else
        echo "ディレクトリが存在しないため、シンボリックリンクを作成"
        ln -s "$PUBLIC_DIR" "$SRC_DIR"
        echo "✓ シンボリックリンク作成: $SRC_DIR -> $PUBLIC_DIR"
    fi
    
    # .htaccessの確認
    if [ ! -f "$SRC_DIR/.htaccess" ]; then
        echo ".htaccessが見つかりません。コピーします"
        cp "$PUBLIC_DIR/.htaccess" "$SRC_DIR/.htaccess"
        echo "✓ .htaccessをコピー"
    fi
    
    echo ""
fi

# 確認用テストファイルの作成
echo "=== 確認用テストファイルの作成 ==="
TEST_FILE_CONTENT="test_$(date +%Y%m%d_%H%M%S)"

if [ -L "$PUBLIC_HTML_DIR" ] || [ -d "$PUBLIC_HTML_DIR" ]; then
    echo "$TEST_FILE_CONTENT" > "$PUBLIC_HTML_DIR/_test.txt" 2>/dev/null || echo "⚠ public_html パスに書き込めませんでした"
fi

if [ -L "$SRC_DIR" ] || [ -d "$SRC_DIR" ]; then
    echo "$TEST_FILE_CONTENT" > "$SRC_DIR/_test.txt" 2>/dev/null || echo "⚠ src パスに書き込めませんでした"
fi

echo ""
echo "=== 修正完了 ==="
echo ""
echo "次のステップ:"
echo "1. ブラウザで以下にアクセスして確認してください:"
echo "   https://resend.kimito-link.com/_test.txt"
echo "   内容が '$TEST_FILE_CONTENT' と表示されれば正しいドキュメントルートです"
echo ""
echo "2. デバッグモードで確認:"
echo "   https://resend.kimito-link.com/?dbg=raw"
echo "   'INDEX_PHP_HIT' が表示されれば index.php は正しく動作しています"
echo ""
echo "3. 通常アクセスで確認:"
echo "   https://resend.kimito-link.com/"
echo "   正常に表示されれば問題解決です"
echo ""
echo "確認後、テストファイルを削除してください:"
if [ -f "$PUBLIC_HTML_DIR/_test.txt" ]; then
    echo "   rm $PUBLIC_HTML_DIR/_test.txt"
fi
if [ -f "$SRC_DIR/_test.txt" ]; then
    echo "   rm $SRC_DIR/_test.txt"
fi
