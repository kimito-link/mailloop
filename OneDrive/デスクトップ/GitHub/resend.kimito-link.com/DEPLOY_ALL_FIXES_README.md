# すべての修正を自動実行 - 統合スクリプト

このスクリプトは、Page Not Foundエラーを修正するために必要なすべての作業を自動的に実行します。

## 🚀 クイックスタート

### Git Bashで実行（推奨）

```bash
# 実行権限を付与
chmod +x deploy_all_fixes.sh

# スクリプトを実行
./deploy_all_fixes.sh
```

### PowerShellで実行

```powershell
powershell -ExecutionPolicy Bypass -File deploy_all_fixes.ps1
```

## 📋 実行内容

このスクリプトは以下の5つのフェーズを自動実行します：

### Phase 1: SSH接続の確認
- SSH鍵ファイルの存在確認
- サーバーへの接続テスト

### Phase 2: サーバー側のGit設定を修正
- サーバー側のSSH設定ファイル（`~/.ssh/config`）に`github.com-kimitolink`の設定を追加
- 設定が既に存在する場合はスキップ

### Phase 3: 修正スクリプトをサーバーにアップロード
- `fix_page_not_found.sh`をサーバーにアップロード
- 実行権限を付与

### Phase 4: サーバー側で修正スクリプトを実行
- サーバー側で`fix_page_not_found.sh`を実行
- シンボリックリンクの作成・修正
- `.htaccess`ファイルの確認・コピー

### Phase 5: 動作確認
- テストファイルの存在確認
- 結果の表示

## ⚙️ 前提条件

1. **SSH鍵ファイル**: `C:\Users\info\.ssh\id_rsa`が存在すること
2. **ローカルスクリプト**: `fix_page_not_found.sh`が現在のディレクトリに存在すること
3. **SSH接続**: サーバーへのSSH接続が可能であること

## 📝 実行後の確認

スクリプト実行後、以下のURLにアクセスして確認してください：

1. **テストファイル**: https://resend.kimito-link.com/_test.txt
   - テストファイルが表示されれば正しいドキュメントルートです

2. **デバッグモード**: https://resend.kimito-link.com/?dbg=raw
   - `INDEX_PHP_HIT`が表示されれば`index.php`は正しく動作しています

3. **通常アクセス**: https://resend.kimito-link.com/
   - 正常に表示されれば問題解決です

## 🔧 トラブルシューティング

### SSH接続エラー

```
❌ SSH接続に失敗しました
```

**解決策**:
1. SSH鍵ファイルのパスを確認
2. ネットワーク接続を確認
3. 手動で接続テスト: `ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp`

### スクリプトのアップロードエラー

```
❌ スクリプトのアップロードに失敗しました
```

**解決策**:
1. `fix_page_not_found.sh`が現在のディレクトリに存在することを確認
2. ファイルの読み取り権限を確認
3. 手動でアップロード: `scp -i ~/.ssh/id_rsa -P 10022 fix_page_not_found.sh besttrust@sv16.sixcore.ne.jp:~/kimito-link.com/_git/mailloop/`

### 修正スクリプトの実行エラー

```
❌ 修正スクリプトの実行中にエラーが発生しました
```

**解決策**:
1. サーバーにSSH接続
2. 手動でスクリプトを実行:
   ```bash
   ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp
   cd ~/kimito-link.com/_git/mailloop
   bash fix_page_not_found.sh
   ```

## 📚 関連ファイル

- `fix_page_not_found.sh` - Page Not Foundエラー修正スクリプト（サーバー側で実行）
- `SSH_INFO.md` - SSH接続情報
- `FIX_SERVER_SSH_CONFIG.md` - サーバー側SSH設定修正手順

## ⚠️ 注意事項

1. **バックアップ**: スクリプトは既存のディレクトリをバックアップしてから削除します
2. **実行時間**: スクリプトの実行には数秒から数分かかる場合があります
3. **ネットワーク**: 安定したネットワーク接続が必要です

## ✅ 実行後のクリーンアップ

確認後、テストファイルを削除してください：

```bash
# サーバーに接続
ssh -i ~/.ssh/id_rsa -p 10022 besttrust@sv16.sixcore.ne.jp

# テストファイルを削除
rm ~/kimito-link.com/public_html/resend.kimito-link.com/_test.txt
# または
rm ~/kimito-link.com/_git/kimito-link/src/resend.kimito-link.com/_test.txt
```
