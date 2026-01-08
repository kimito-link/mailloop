# 実装完了サマリー

## ✅ 実施した変更

### 1. schema.sql の微調整

- **created_at/updated_at にデフォルト値を追加**
  - `DEFAULT CURRENT_TIMESTAMP` を追加
  - `updated_at` に `ON UPDATE CURRENT_TIMESTAMP` を追加
  - すべてのテーブルに適用

- **send_logs の group_id を NULL可に変更**
  - 単発送信対応のため `INT NULL` に変更

### 2. MysqlStorage の改善

- **JSONエンコード失敗の検出**
  - `createTemplate()` と `updateTemplate()` で `json_encode()` の失敗を検出
  - 失敗時は `RuntimeException` をスロー

- **LIKE検索の明確化**
  - `listTemplates()` でワイルドカードを別変数 `$like` に分離

### 3. バリデーション実装

- **validateTemplate() 関数を追加**
  - タイトル: 必須、100文字以内
  - 件名: 必須、150文字以内
  - 本文: 必須、20,000文字以内

- **/templates/save ルートでバリデーション実行**
  - DB保存前にバリデーション
  - エラー時は編集画面を再表示（入力値を保持）

- **views/templates/edit.php にエラー表示を追加**
  - エラーがある場合は赤文字で表示
  - リスト形式で複数エラーを表示

## 📋 次のステップ

### 1. phpMyAdminでテーブル作成

1. Xserverのサーバーパネル → phpMyAdmin
2. 左で `besttrust_mail` データベースを選択
3. 上の「SQL」タブをクリック
4. `database/schema.sql` の内容を貼り付け
5. 実行ボタンをクリック
6. 4つのテーブルが作成されることを確認：
   - `oauth_tokens`
   - `message_templates`
   - `recipient_groups`
   - `send_logs`

### 2. 動作確認

1. **テンプレート作成テスト**
   - `https://resend.kimito-link.com/templates/new` にアクセス
   - タイトル・件名・本文を入力して保存
   - `message_templates` テーブルにデータが入ることを確認

2. **バリデーションテスト**
   - タイトルを空にして保存 → エラー表示されることを確認
   - 件名を空にして保存 → エラー表示されることを確認
   - 本文を空にして保存 → エラー表示されることを確認

3. **データベース確認**
   - phpMyAdminで `message_templates` テーブルを確認
   - 保存したデータが正しく入っていることを確認

## 🔍 確認ポイント

- ✅ schema.sql の変更が正しく反映されているか
- ✅ バリデーションが正しく動作するか
- ✅ エラー表示が正しく表示されるか
- ✅ データベースに正しく保存されるか

## 📝 注意事項

- XserverのMySQLバージョンによっては `ON UPDATE CURRENT_TIMESTAMP` が制限される場合があります
- その場合は、アプリ側の `NOW()` 運用のままでも問題ありません
- バリデーションエラー時は、入力値が保持されるため、再入力が不要です

---

**実装日**: 2026年1月2日
**次のタスク**: phpMyAdminでのテーブル作成 → 動作確認 → recipient_groups のCRUD実装
