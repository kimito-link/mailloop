# デバッグ機能の使用方法

## 概要

GPTの指摘に基づき、OAuth認証エラーの原因を特定するためのデバッグ機能を実装しました。

## 前提条件

1. **APP_ENVの確認**: `config/config.php`で`APP_ENV`が`prod`以外であることを確認してください（デフォルトは`dev`）。

**注意**: 開発環境では`DEBUG_KEY`の設定は**不要**です。本番環境でのみ必要です。

## デバッグ方法

**注意**: 開発環境（`APP_ENV != 'prod'`）では`dbg_key`パラメータは**省略可能**です。本番環境でのみ必要です。

### 1. 最初の状態確認（raw）

Googleから戻ってきたURLに`&dbg=raw`を追加してアクセス：

```
https://resend.kimito-link.com/auth/callback?state=...&code=...&dbg=raw
```

**表示される情報:**
- 実行ファイルのパス
- ストレージクラス
- セッション情報
- PHP設定（display_errors、log_errors、error_log）
- GETパラメータ

### 2. upsertUser直前の入力確認（dbg=1）

Googleから戻ってきたURLに`&dbg=1`を追加（開発環境では`dbg_key`は不要）：

```
https://resend.kimito-link.com/auth/callback?state=...&code=...&dbg=1
```

**表示される情報:**
- upsertUserに渡される`$oauthUser`配列
- `provider_sub`の値
- ストレージクラスとファイルパス

### 3. upsertUser直後の戻り値確認（dbg=2）

同じURLで`dbg=2`に変更：

```
https://resend.kimito-link.com/auth/callback?state=...&code=...&dbg=2
```

**表示される情報:**
- upsertUserから返された`$dbUser`配列
- `id`キーの有無
- ストレージクラスとファイルパス

### 4. 例外発生時の詳細確認（dbg=9）

例外が発生した場合、自動的に`dbg_dump`が呼ばれます。手動で確認する場合は：

```
https://resend.kimito-link.com/auth/callback?state=...&code=...&dbg=9
```

**表示される情報:**
- 例外タイプ（PDOException、RuntimeException、Exception、Throwable）
- エラーメッセージ
- ファイルと行番号
- スタックトレース

## state検証をスキップする方法（開発環境のみ）

Googleから戻ってきたURLに`&dbg_skip=1`を追加：

```
https://resend.kimito-link.com/auth/callback?state=...&code=...&dbg_skip=1
```

**注意:** これは開発環境（`APP_ENV != 'prod'`）でのみ動作します。

## 実装された改善点

1. **変数名の変更**: `$user` → `$oauthUser`（入力）と`$dbUser`（戻り値）に分離し、上書きを防止
2. **安全なデバッグ関数**: `dbg_dump()`は`DEBUG_KEY`が一致した場合のみ動作
3. **例外の階層的キャッチ**: PDOException → RuntimeException → Exception → Throwable の順でキャッチ
4. **HTTPレスポンスへの出力**: エラーログに依存せず、画面に直接表示

## トラブルシューティング

### デバッグ情報が表示されない場合

1. `APP_ENV`が`prod`でないことを確認（デフォルトは`dev`）
2. URLパラメータが正しく追加されているか確認（`&dbg=1`）
3. 本番環境の場合は`DEBUG_KEY`が必要です

### エラーが続く場合

1. `dbg=raw`で基本情報を確認
2. `dbg=1`でupsertUserへの入力を確認
3. `dbg=2`でupsertUserからの戻り値を確認
4. 例外が発生している場合は、自動的に`dbg_dump`が呼ばれます

## 次のステップ

デバッグ情報を確認したら、以下を確認してください：

1. **データベース接続**: `dbg=raw`で`ini error_log`を確認
2. **provider_subの値**: `dbg=1`で`sub`の値が空でないか確認
3. **upsertUserの戻り値**: `dbg=2`で`id`キーが存在するか確認
4. **例外の詳細**: 例外が発生している場合は、タイプとメッセージを確認
