#!/bin/bash
# PHP 5.4互換性のための ?? 演算子を isset() に置き換えるスクリプト

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "=== PHP 5.4互換性修正スクリプト ==="

# バックアップを作成
BACKUP="public/index.php.backup.$(date +%Y%m%d_%H%M%S)"
cp public/index.php "$BACKUP"
echo "✓ バックアップ作成: $BACKUP"

# PHPスクリプトで安全に置き換え
php << 'EOFPHP'
<?php
$file = 'public/index.php';
$content = file_get_contents($file);

// ?? 演算子を isset() に置き換え
// パターン1: $var ?? 'default' -> (isset($var) ? $var : 'default')
$content = preg_replace('/(\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*\'([^\']+)\'/', '(isset($1) ? $1 : \'$3\')', $content);

// パターン2: $var ?? null -> (isset($var) ? $var : null)
$content = preg_replace('/(\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*null/', '(isset($1) ? $1 : null)', $content);

// パターン3: $var ?? [] -> (isset($var) ? $var : [])
$content = preg_replace('/(\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*\[\]/', '(isset($1) ? $1 : [])', $content);

// パターン4: $var ?? 0 -> (isset($var) ? $var : 0)
$content = preg_replace('/(\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*(\d+)/', '(isset($1) ? $1 : $3)', $content);

// パターン5: ($var ?? 'default') -> (isset($var) ? $var : 'default')
$content = preg_replace('/\((\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*\'([^\']+)\'\)/', '(isset($1) ? $1 : \'$3\')', $content);

// パターン6: ($var ?? '') -> (isset($var) ? $var : '')
$content = preg_replace('/\((\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*\'\'\)/', '(isset($1) ? $1 : \'\')', $content);

// パターン7: ($var ?? []) -> (isset($var) ? $var : [])
$content = preg_replace('/\((\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*)\s*\?\?\s*\[\]\)/', '(isset($1) ? $1 : [])', $content);

file_put_contents($file, $content);
echo "✓ 置き換え完了\n";
EOFPHP

# 構文チェック
echo ""
echo "=== 構文チェック ==="
php -l public/index.php

echo ""
echo "=== 完了 ==="
