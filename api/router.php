<?php

function route(): void {
  $method = $_SERVER['REQUEST_METHOD'];
  $path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

  // /api/submissions
  if (preg_match('#^/api/submissions$#', $path)) {
    if ($method === 'POST') {
      require_once __DIR__ . '/handlers/SubmissionPost.php';
      handlePost();
      return;
    }
    if ($method === 'GET') {
      require_once __DIR__ . '/handlers/SubmissionList.php';
      requireAuth();
      handleList();
      return;
    }
  }

  // /api/submissions/:id
  if (preg_match('#^/api/submissions/(\d+)$#', $path, $matches)) {
    $id = (int)$matches[1];
    if ($method === 'GET') {
      require_once __DIR__ . '/handlers/SubmissionGet.php';
      requireAuth();
      handleGet($id);
      return;
    }
    if ($method === 'PATCH') {
      require_once __DIR__ . '/handlers/SubmissionPatch.php';
      requireAuth();
      handlePatch($id);
      return;
    }
  }

  // /api/submissions/export/csv
  if ($method === 'GET' && preg_match('#^/api/submissions/export/csv$#', $path)) {
    require_once __DIR__ . '/handlers/SubmissionExport.php';
    requireAuth();
    handleExport();
    return;
  }

  // GET /api/submissions/edit/:token
  if ($method === 'GET' && preg_match('#^/api/submissions/edit/([a-f0-9]+)$#', $path, $matches)) {
    require_once __DIR__ . '/handlers/SubmissionGetByToken.php';
    handleGetByToken($matches[1]);
    return;
  }

  // PUT /api/submissions/edit/:token
  if ($method === 'PUT' && preg_match('#^/api/submissions/edit/([a-f0-9]+)$#', $path, $matches)) {
    require_once __DIR__ . '/handlers/SubmissionPutByToken.php';
    handlePutByToken($matches[1]);
    return;
  }

  // GET /api/files
  if ($method === 'GET' && preg_match('#^/api/files$#', $path)) {
    require_once __DIR__ . '/handlers/FileServe.php';
    requireAuth();
    handleFile();
    return;
  }

  // POST /api/reports
  if ($method === 'POST' && preg_match('#^/api/reports$#', $path)) {
    require_once __DIR__ . '/handlers/ReportPost.php';
    handleReportPost();
    return;
  }

  // GET /api/reports
  if ($method === 'GET' && preg_match('#^/api/reports$#', $path)) {
    require_once __DIR__ . '/handlers/ReportList.php';
    requireAuth();
    handleReportList();
    return;
  }

  // GET /api/reports/:id
  if ($method === 'GET' && preg_match('#^/api/reports/(\d+)$#', $path, $matches)) {
    require_once __DIR__ . '/handlers/ReportGet.php';
    requireAuth();
    handleReportGet((int)$matches[1]);
    return;
  }

  // PATCH /api/reports/:id
  if ($method === 'PATCH' && preg_match('#^/api/reports/(\d+)$#', $path, $matches)) {
    require_once __DIR__ . '/handlers/ReportPatch.php';
    requireAuth();
    handleReportPatch((int)$matches[1]);
    return;
  }

  // GET /api/reports/edit/:token
  if ($method === 'GET' && preg_match('#^/api/reports/edit/([a-f0-9]+)$#', $path, $matches)) {
    require_once __DIR__ . '/handlers/ReportGetByToken.php';
    handleReportGetByToken($matches[1]);
    return;
  }

  // PUT /api/reports/edit/:token
  if ($method === 'PUT' && preg_match('#^/api/reports/edit/([a-f0-9]+)$#', $path, $matches)) {
    require_once __DIR__ . '/handlers/ReportPutByToken.php';
    handleReportPutByToken($matches[1]);
    return;
  }

  Response::error('Not Found', 404);
}
