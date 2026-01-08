#!/bin/bash
# サーバー側で実行する修正スクリプト

echo "=== Fixing FileStorage upsertUser on server ==="

cd /home/besttrust/kimito-link.com/_git/mailloop

# バックアップ作成
cp app/services/storage.php app/services/storage.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✓ Backup created"

# FileStorageのupsertUser関数を修正
# 55行目付近の関数を置き換え
sed -i '55s/.*/  public function upsertUser(array $u): array {/' app/services/storage.php
sed -i '56s/.*/    \/\/ idが未設定の場合は1を設定（FileStorageでは固定ID）/' app/services/storage.php
sed -i '57s/.*/    if (!isset($u['\''id'\''])) {/' app/services/storage.php
sed -i '58s/.*/      $u['\''id'\''] = 1;/' app/services/storage.php
sed -i '59s/.*/    }/' app/services/storage.php
sed -i '60s/.*/    $_SESSION['\''user'\''] = $u;/' app/services/storage.php
sed -i '61s/.*/    return $u;/' app/services/storage.php
sed -i '62s/.*/  }/' app/services/storage.php

echo "✓ File updated"
echo ""
echo "=== Verifying ==="
sed -n '55,62p' app/services/storage.php
echo ""
echo "=== Done ==="
