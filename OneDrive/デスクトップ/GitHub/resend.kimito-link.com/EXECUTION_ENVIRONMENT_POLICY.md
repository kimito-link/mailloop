# 実行環境統一方針

## 方針

**Web実行とCLI実行（cron含む）でPHPバージョン差が出ないよう、プロジェクト側で使用PHPを固定する。**

CLI実行は固定されたPHPへの参照（例：フルパス/ラッパースクリプト）を用い、OSの `php` コマンドの既定値には依存しない。

## 背景

XServer Business環境では、以下のようなPHPバージョンの不一致が発生する可能性があります：

- **Webサーバー**: PHP 8.3.21（管理画面で設定）
- **CLI（デフォルト）**: PHP 5.4.16（OSのデフォルト）

この不一致により、以下の問題が発生する可能性があります：

1. `php -l` などのCLI構文チェックが、Web実行環境と異なる結果を返す
2. `??` 演算子（PHP 7.0以降）などの新機能がCLIでエラーになる
3. cronジョブが古いPHPで実行され、予期しない動作をする

## 実装方法

### 1. PHPラッパースクリプトの使用

プロジェクトルートの `bin/php` ラッパースクリプトを使用して、固定されたPHPバージョンでCLIスクリプトを実行します。

```bash
# 従来（非推奨）
php public/clear_cache.php

# 推奨
bin/php public/clear_cache.php
```

### 2. シェルスクリプト内での使用

サーバー側で実行するシェルスクリプトでは、必ず `bin/php` を使用してください：

```bash
#!/bin/bash
cd /home/besttrust/kimito-link.com/_git/mailloop
bin/php public/clear_cache.php
```

### 3. Cronジョブでの使用

cronジョブを設定する際は、フルパスで `bin/php` を指定してください：

```bash
# 例: 毎日午前3時にキャッシュクリア
0 3 * * * /home/besttrust/kimito-link.com/_git/mailloop/bin/php /home/besttrust/kimito-link.com/_git/mailloop/public/clear_cache.php
```

または、プロジェクトルートに移動してから実行：

```bash
0 3 * * * cd /home/besttrust/kimito-link.com/_git/mailloop && bin/php public/clear_cache.php
```

## ラッパースクリプトの動作

`bin/php` は以下の順序でPHP 8.3系を検索します：

1. `/usr/bin/php8.3`
2. `/usr/bin/php83`
3. `/usr/local/bin/php8.3`
4. `/usr/local/bin/php83`
5. `/opt/php83/bin/php`
6. `/usr/bin/php`（フォールバック）

見つかったPHPのバージョンを確認し、PHP 8.3系であることを検証してから実行します。

## デバッグ

ラッパースクリプトの動作を確認するには、環境変数を設定します：

```bash
DEBUG_PHP_WRAPPER=1 bin/php public/clear_cache.php
```

これにより、使用しているPHPのパスとバージョンが表示されます。

## 既存スクリプトの更新

以下のスクリプトを更新して、`bin/php` を使用するようにしてください：

- `server_update.sh`
- `server_quick_check.sh`
- `server_verify_update.sh`
- その他のCLI実行を行うスクリプト

## 注意事項

1. **実行権限**: `bin/php` に実行権限があることを確認してください：
   ```bash
   chmod +x bin/php
   ```

2. **パスの確認**: サーバー環境で実際に使用可能なPHPのパスを確認し、必要に応じて `bin/php` を修正してください。

3. **Web実行環境との一致**: Webサーバーで使用しているPHPバージョンと、`bin/php` が使用するPHPバージョンが一致していることを確認してください。

## トラブルシューティング

### PHPが見つからないエラー

```
エラー: PHP 8.3系が見つかりませんでした。
```

**対処法**:
1. サーバーで利用可能なPHPのパスを確認：
   ```bash
   which -a php php8.3 php83
   ls -la /usr/bin/php*
   ```
2. 見つかったパスを `bin/php` の `PHP_CANDIDATES` 配列に追加

### バージョン不一致の警告

ラッパースクリプトがPHP 8.3系を検出できない場合、エラーを出力して終了します。

**対処法**: サーバー管理者に相談し、PHP 8.3系のパスを確認してください。
