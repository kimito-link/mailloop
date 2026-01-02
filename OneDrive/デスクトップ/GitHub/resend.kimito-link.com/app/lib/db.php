<?php
declare(strict_types=1);

function db_pdo(array $config): ?PDO {
  if (($config['STORAGE_DRIVER'] ?? 'mysql') !== 'mysql') return null;
  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
    $config['DB_HOST'], $config['DB_NAME'], $config['DB_CHARSET'] ?? 'utf8mb4'
  );
  try {
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
  } catch (Throwable $e) {
    return null;
  }
}
