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

// Mailer のモック（メールを実際には送らない）
class Mailer {
  public static function sendConfirmation(
    string $to,
    string $name,
    int $id,
    string $editToken
  ): void {
    // テスト時は何もしない
  }

  public static function sendNotification(int $id, string $applicantName): void {
    // テスト時は何もしない
  }

  public static function sendReportConfirmation(
    string $to,
    string $name,
    int $id,
    string $editToken
  ): void {
    // テスト時は何もしない
  }

  public static function sendReportNotification(int $id, string $applicantName): void {
    // テスト時は何もしない
  }

  // テストから呼び出し内容を検証できるよう記録する
  public static array $statusNotifications = [];

  public static function sendStatusNotification(string $to, string $name, string $subject, string $body, ?array $attachment = null): void {
    self::$statusNotifications[] = [
      'to'         => $to,
      'name'       => $name,
      'subject'    => $subject,
      'body'       => $body,
      'attachment' => $attachment,
    ];
  }
}

// Response のテスト用オーバーライド（exit しない）
class Response {
  public static function json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    // exit しない
  }

  public static function error(string $message, int $status = 400): void {
    self::json(['error' => $message], $status);
  }

  public static function success($data = null, string $message = 'success'): void {
    self::json(['message' => $message, 'data' => $data]);
  }
}

// ヘルパークラスの読み込み
// require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
