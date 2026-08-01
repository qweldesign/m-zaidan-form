<?php

function requireAuth(): void {
  $headers = getallheaders();
  $authHeader = $headers['Authorization'] ?? '';

  if (!str_starts_with($authHeader, 'Bearer ')) {
    Response::error('認証が必要です', 401);
    exit;
  }

  $token = substr($authHeader, 7);
  if (!hash_equals(STAFF_TOKEN, $token)) {
    Response::error('認証に失敗しました', 403);
    exit;
  }
}
