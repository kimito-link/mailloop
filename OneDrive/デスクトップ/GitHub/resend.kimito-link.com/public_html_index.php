<?php
/**
 * DocumentRoot直下のindex.php（ラッパーファイル）
 * 
 * このファイルを以下のパスに配置してください：
 * /home/besttrust/kimito-link.com/public_html/resend.kimito-link.com/index.php
 * 
 * または、既存のindex.phpの内容をこの内容に置き換えてください。
 */

// 編集したindex.phpを読み込む
require __DIR__ . '/../_git/mailloop/public/index.php';
