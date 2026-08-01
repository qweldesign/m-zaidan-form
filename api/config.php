<?php

$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $value) {
  $_ENV[$key] = $value;
}

define('APP_URL', $_ENV['APP_URL']);
define('DB_PATH', __DIR__ . '/../../db/m-zaidan.sqlite');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('STAFF_TOKEN', $_ENV['STAFF_TOKEN']);
define('FOUNDATION_EMAIL', $_ENV['FOUNDATION_EMAIL']);
define('FROM_EMAIL', $_ENV['FOUNDATION_EMAIL']);
define('FROM_NAME', $_ENV['FOUNDATION_NAME']);

function getDB(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL'); // 同時アクセス耐性を上げる
    $pdo->exec('PRAGMA foreign_keys=ON');
  }
  return $pdo;
}
