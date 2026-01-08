<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

function crypto_key(string $appKey): string {
  return hash('sha256', $appKey, true); // 32 bytes
}
function encrypt_str(string $plain, string $appKey): string {
  $key = crypto_key($appKey);
  $iv = random_bytes(12);
  $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
  return base64_encode($iv . $tag . $cipher);
}
function decrypt_str(string $enc, string $appKey): string {
  $raw = base64_decode($enc, true);
  if ($raw === false || strlen($raw) < 28) return '';
  $iv = substr($raw, 0, 12);
  $tag = substr($raw, 12, 16);
  $cipher = substr($raw, 28);
  $key = crypto_key($appKey);
  $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
  return $plain === false ? '' : $plain;
}
