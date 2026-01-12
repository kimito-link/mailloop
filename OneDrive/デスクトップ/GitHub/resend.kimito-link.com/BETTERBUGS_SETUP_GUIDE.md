# BetterBugs 導入ガイド

## BetterBugsとは

BetterBugsは、バグ報告を効率化するブラウザ拡張機能です。スクリーンショットの撮影、アノテーション、技術情報の自動収集、プロジェクト管理ツールへの連携を自動化します。

## 主な機能

- **スクリーンショット撮影**: 画面全体、選択範囲、ページ全体をキャプチャ
- **アノテーション**: 矢印、図形、テキスト、ハイライトなどで注釈
- **画面録画**: 動的な問題を録画
- **自動技術情報収集**: コンソールログ、ネットワークリクエスト、システム情報を自動で添付
- **AI支援**: 問題の説明、再現手順、原因分析を自動生成
- **プロジェクト管理ツール連携**: Asana、Jiraなどへの連携

## インストール手順

### 1. Chrome拡張機能をインストール

1. **Chrome Web Storeにアクセス**
   - [BetterBugs Chrome Web Store](https://chromewebstore.google.com/detail/betterbugs-a-fresh-approa/mdljmlgokccncglfobogbfjgcijldnaj)
   - または、Chrome Web Storeで「BetterBugs」を検索

2. **拡張機能を追加**
   - 「Chromeに追加」ボタンをクリック
   - 確認ダイアログで「拡張機能を追加」をクリック

3. **拡張機能をピン留め（推奨）**
   - ブラウザのツールバーにBetterBugsアイコンを表示
   - 拡張機能アイコン（パズルピース）をクリック
   - BetterBugsの横のピンアイコンをクリック

### 2. アカウント登録

1. **BetterBugsアイコンをクリック**
   - ブラウザのツールバーにあるBetterBugsアイコンをクリック

2. **サインアップ**
   - メールアドレスで登録（OTPで確認）
   - または、GitHub/Googleアカウントで登録

3. **ログイン**
   - 登録後、ログイン

## 使用方法

### バグを報告する

1. **問題が発生したページに移動**
   - 例: `https://resend.kimito-link.com/send`

2. **BetterBugsアイコンをクリック**
   - キャプチャモードを選択:
     - **Screenshot**: スクリーンショット
     - **Screen Record**: 画面録画

3. **スクリーンショットを撮影**
   - 「Crop & Capture」を選択
   - キャプチャしたい領域をドラッグして選択

4. **アノテーションを追加**
   - 矢印、図形、テキスト、ハイライトなどで注釈
   - 問題箇所を明確に示す

5. **バグの詳細を入力**
   - **タイトル**（必須）: バグの簡潔な説明
   - **説明**: 詳細な説明
   - **再現手順**: 手動入力、またはAIで自動生成
   - **技術情報**: コンソールログ、ネットワークリクエストなどは自動で添付

6. **ワークスペースとプロジェクトを選択**
   - デフォルト: "My Workspace" / "My Project"
   - 必要に応じて変更

7. **バグレポートをアップロード**
   - 「Upload Bug Details」ボタンをクリック
   - レポートのリンクがクリップボードにコピーされる
   - チームと共有

## 現在の403エラー調査での活用例

### 1. 認証ループの問題を報告

1. 認証ループが発生しているページでBetterBugsを起動
2. スクリーンショットを撮影
3. 問題箇所をアノテーション
4. コンソールログとネットワークリクエストが自動で添付
5. タイトル: "認証ループが発生 - require_loginでユーザーIDが見つからない"
6. 説明: ログの内容と再現手順を記載

### 2. 403エラーのログを報告

1. サーバー側のログファイルを表示
2. BetterBugsでスクリーンショットを撮影
3. 重要なログ行をハイライト
4. 技術情報（コンソールログ、ネットワークリクエスト）が自動で添付
5. タイトル: "403エラーログ - Gmail API呼び出し時の権限不足"
6. 説明: ログの内容と調査結果を記載

### 3. セッション管理の問題を報告

1. `/dbg`エンドポイントの出力を表示
2. BetterBugsでスクリーンショットを撮影
3. セッション情報をハイライト
4. タイトル: "セッションディレクトリの問題 - /tmp/mailloop_sessions/が存在しない"
5. 説明: 問題の詳細と解決手順を記載

## プロジェクト管理ツールとの連携

### Asanaとの連携

1. BetterBugsの設定でAsanaを連携
2. バグレポートを直接Asanaタスクとして作成
3. チームと共有

### Jiraとの連携（将来対応予定）

- Jiraへの直接連携機能が追加される予定
- 現在は、バグレポートのリンクをJiraに貼り付けて使用

## 対応ブラウザ

- Google Chrome (109以降)
- Microsoft Edge
- Opera
- Brave
- Arc
- その他のChromiumベースブラウザ

## シークレットモードでの使用

シークレットモードでも使用可能：

1. Chromeの設定を開く
2. 「拡張機能」→「BetterBugs」
3. 「シークレットモードで許可」を有効化

## トラブルシューティング

### 拡張機能が表示されない場合

1. Chromeの拡張機能ページを開く: `chrome://extensions/`
2. BetterBugsが有効になっているか確認
3. 必要に応じて再読み込み

### スクリーンショットが撮影できない場合

1. ページの権限を確認
2. ブラウザの再起動
3. 拡張機能の再インストール

### ログインできない場合

1. メールアドレスが正しいか確認
2. OTPが届かない場合、迷惑メールフォルダを確認
3. GitHub/Googleアカウントで再試行

## 次のステップ

1. **BetterBugsをインストール**
   - [Chrome Web Store](https://chromewebstore.google.com/detail/betterbugs-a-fresh-approa/mdljmlgokccncglfobogbfjgcijldnaj)からインストール

2. **アカウントを登録**
   - メールアドレスまたはGitHub/Googleアカウントで登録

3. **テストレポートを作成**
   - 現在の403エラー調査でBetterBugsを使用
   - スクリーンショットとログを自動で収集

4. **チームと共有**
   - バグレポートのリンクを共有
   - 効率的なコミュニケーションを実現

## 参考リンク

- [BetterBugs公式サイト](https://betterbugs.io/)
- [BetterBugsドキュメント](https://docs.betterbugs.io/)
- [クイックスタートガイド](https://docs.betterbugs.io/getting-started/reporting-your-first-bug)
- [Chrome Web Store](https://chromewebstore.google.com/detail/betterbugs-a-fresh-approa/mdljmlgokccncglfobogbfjgcijldnaj)
