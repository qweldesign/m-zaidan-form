<?php

// テスト用定数（config.php より先に定義）
define('DB_PATH',          __DIR__ . '/test.sqlite');
define('UPLOAD_DIR',       __DIR__ . '/uploads/');
define('STAFF_TOKEN',      'test-token');
define('FOUNDATION_EMAIL', 'foundation@example.com');
define('FROM_EMAIL',       'test@example.com');
define('FROM_NAME',        '財団テスト');
define('APP_URL',          'http://localhost');

// config.php は読み込まない（定数の二重定義を防ぐ）
// getDB() だけ必要なので直接定義する
function getDB(): PDO {
  // staticキャッシュを使わず毎回DB_PATHに接続する
  $pdo = new PDO('sqlite:' . DB_PATH);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys=ON');
  return $pdo;
}

// テスト用DBをリセットして再作成する関数
function setupTestDb(): PDO {
  $dbPath = DB_PATH;

  if (file_exists($dbPath)) {
    unlink($dbPath);
  }

  $pdo = new PDO('sqlite:' . $dbPath);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys=ON');
  $pdo->exec('PRAGMA journal_mode=WAL');

  $schema = file_get_contents(__DIR__ . '/../../db/schema.sql');
  $pdo->exec($schema);

  return $pdo;
}

// ヘルパークラスの読み込み
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
