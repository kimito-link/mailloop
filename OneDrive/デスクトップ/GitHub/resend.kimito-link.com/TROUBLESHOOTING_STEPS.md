# 403エラー解決のための切り分けテスト

## ✅ 実施済み

1. **`.htaccess`を修正**
   - `mailloop/public/.htaccess`を`<FilesMatch>`でPHPファイルを明示的に許可する設定に変更
   - 親ディレクトリの`<FilesMatch "\.php$"> Deny`を打ち消す設定

2. **テストファイルを作成**
   - `_probe.txt`: docroot確認用
   - `index.html`: PHP拒否なのかディレクトリ拒否なのかの切り分け用

## 🔍 次のステップ：ブラウザで確認

### 1. `_probe.txt`の確認
ブラウザで以下にアクセス：
```
https://resend.kimito-link.com/_probe.txt
```

- ✅ **表示される** → docrootは正しい
- ❌ **表示されない** → 別の場所を見ている（docrootが違う）

### 2. `index.html`の確認
ブラウザで以下にアクセス：
```
https://resend.kimito-link.com/index.html
```

- ✅ **表示される** → PHPだけが拒否されている（`.htaccess`の修正で解決するはず）
- ❌ **403エラー** → ディレクトリ自体が拒否されている（Options / AllowOverride / docroot違い等）

### 3. メインページの確認
ブラウザで以下にアクセス：
```
https://resend.kimito-link.com/
```

- ✅ **正常に表示される** → 問題解決！
- ❌ **まだ403エラー** → エラーログを確認

## 📋 エラーログの確認

SSH接続して以下を実行：

```bash
tail -n 50 ~/log/resend.kimito-link.com_error_log
```

403エラーに関連する行を確認してください。

## 🧹 テストファイルの削除

確認が終わったら、テストファイルを削除：

```bash
rm ~/kimito-link.com/_git/mailloop/public/index.html
rm ~/kimito-link.com/_git/mailloop/public/_probe.txt
```

---

## 確認結果を共有してください

1. `https://resend.kimito-link.com/_probe.txt` は表示された？（YES/NO）
2. `https://resend.kimito-link.com/index.html` は表示された？（YES/NO）
3. `https://resend.kimito-link.com/` は表示された？（YES/NO）
4. エラーログの403関連行（可能であれば）
