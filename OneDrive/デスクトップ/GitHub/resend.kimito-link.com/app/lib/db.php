<?php
// declare(strict_types=1); // 古いPHPで詰む可能性があるので外す

function db_pdo($config) {
  if (!isset($config['STORAGE_DRIVER'])) return null;
  if ($config['STORAGE_DRIVER'] !== 'mysql') return null;

  $host = isset($config['DB_HOST']) ? $config['DB_HOST'] : '';
  $name = isset($config['DB_NAME']) ? $config['DB_NAME'] : '';
  $user = isset($config['DB_USER']) ? $config['DB_USER'] : '';
  $pass = isset($config['DB_PASS']) ? $config['DB_PASS'] : '';
  $charset = isset($config['DB_CHARSET']) ? $config['DB_CHARSET'] : 'utf8mb4';

  if ($host === '' || $name === '' || $user === '') {
    error_log('db_pdo: missing DB config');
    return null;
  }

  $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . $charset;

  try {
    $pdo = new PDO($dsn, $user, $pass, array(
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
    return $pdo;
  } catch (Exception $e) {
    // ★ここを絶対にログに出す（原因が一発で分かる）
    error_log('db_pdo connect failed: ' . $e->getMessage());
    return null;
  }
}
