# .htaccess修正方法（手動実行）

## 問題
親ディレクトリの`.htaccess`でPHPファイルが拒否されており、解決策A（子ディレクトリでの許可）が効いていない。

## 解決方法
親ディレクトリの`.htaccess`の`<FilesMatch "\.php$">`の**前**に、`resend.kimito-link.com`配下を除外する設定を追加する。

## 手動で実行するコマンド

SSH接続して以下を実行：

```bash
cd ~/kimito-link.com/_git/kimito-link/src

# バックアップ確認
ls -la .htaccess.backup

# .htaccessを編集（viまたはnano）
vi .htaccess
# または
nano .htaccess
```

## 追加する内容

`# PHPファイルの実行を制限（deploy.phpとdownload.php以外）`の**直前**に以下を追加：

```apache
# resend.kimito-link.com 配下のPHPファイルを許可（シンボリックリンク先）
# この設定は「PHPファイルの実行を制限」の前に配置
<Files "resend.kimito-link.com/index.php">
    Order Allow,Deny
    Allow from all
</Files>
<Files "resend.kimito-link.com/*.php">
    Order Allow,Deny
    Allow from all
</Files>
```

または、より確実な方法として、`RewriteRule`を使う：

```apache
# resend.kimito-link.com 配下のPHPファイルを許可（RewriteRuleで除外）
RewriteCond %{REQUEST_URI} ^/resend\.kimito-link\.com/
RewriteRule ^resend\.kimito-link\.com/.*\.php$ - [L]
```

## 注意点

- `<FilesMatch>`ではパスを指定できないため、`<Files>`または`RewriteRule`を使用
- 設定は`<FilesMatch "\.php$">`の**前**に配置する必要がある
- 修正後、ブラウザで`https://resend.kimito-link.com/`にアクセスして確認
