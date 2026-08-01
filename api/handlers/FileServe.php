<?php

function handleFile(): void {

  $path = $_GET['path'] ?? '';

  // パストラバーサル対策
  $realBase = realpath(__DIR__ . '/../../../');
  $realPath = realpath($realBase . '/' . $path);

  if (
    !$realPath ||
    !str_starts_with($realPath, $realBase) ||
    !file_exists($realPath)
  ) {
    Response::error('ファイルが見つかりません', 404);
    return;
  }

  // 許可する拡張子のみ
  $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
  $mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf',
  ];

  if (!isset($mimeTypes[$ext])) {
    Response::error('許可されていないファイル形式です', 403);
    return;
  }

  header('Content-Type: ' . $mimeTypes[$ext]);
  header('Content-Length: ' . filesize($realPath));
  readfile($realPath);
  exit;
}
