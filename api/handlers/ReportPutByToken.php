<?php

function handleReportPutByToken(string $token): void {
  $db   = getDB();
  $stmt = $db->prepare('SELECT * FROM reports WHERE edit_token = :token AND is_deleted = 0');
  $stmt->execute([':token' => $token]);
  $current = $stmt->fetch();

  if (!$current) {
    Response::error('無効なトークンです', 404);
    return;
  }

  // ステータスチェック（確認前のみ編集可能）
  if ($current['status'] !== '確認前') {
    Response::error('この完了報告はすでに確認済みのため、内容を変更できません。', 403);
    return;
  }

  $body = $_POST;

  // ファイルをマージ
  $section2 = json_decode($current['report_section2_json'], true) ?? [];
  $section2 = mergeReportUploadedFiles($section2);

  $db->beginTransaction();
  try {
    $s1 = json_decode($body['report_section1_json'] ?? '{}', true);
    $s2 = json_decode($body['report_section2_json'] ?? '{}', true);

    $expenses        = $s2['expenses']  ?? [];
    $totalExpense    = array_sum(array_column($expenses, 'amount'));
    $totalGrantUsage = array_sum(array_column($expenses, 'grantUsage'));

    $updateStmt = $db->prepare('
      UPDATE reports SET
        team_name            = :team_name,
        contact_name         = :contact_name,
        contact_email        = :contact_email,
        contact_phone        = :contact_phone,
        project_name         = :project_name,
        actual_start_date    = :actual_start_date,
        actual_end_date      = :actual_end_date,
        actual_venue         = :actual_venue,
        grant_request_amount = :grant_request_amount,
        total_expense_amount = :total_expense_amount,
        grant_usage_amount   = :grant_usage_amount,
        report_section1_json = :report_section1_json,
        report_section2_json = :report_section2_json
      WHERE edit_token = :token
    ');

    $updateStmt->execute([
      ':team_name'            => $s1['teamName']        ?? '',
      ':contact_name'         => $s1['contactName']     ?? '',
      ':contact_email'        => $s1['contactEmail']    ?? '',
      ':contact_phone'        => $s1['contactPhone']    ?? '',
      ':project_name'         => $s2['projectName']     ?? '',
      ':actual_start_date'    => $s2['actualStartDate'] ?? '',
      ':actual_end_date'      => $s2['actualEndDate']   ?? '',
      ':actual_venue'         => $s2['actualVenue']     ?? '',
      ':grant_request_amount' => (int)($s2['income']['grantRequest'] ?? 0),
      ':total_expense_amount' => $totalExpense,
      ':grant_usage_amount'   => $totalGrantUsage,
      ':report_section1_json' => $body['report_section1_json'] ?? '{}',
      ':report_section2_json' => json_encode($section2, JSON_UNESCAPED_UNICODE),
      ':token'                => $token,
    ]);

    $db->commit();
    Response::success(['id' => (int)$current['id']], '完了報告内容を更新しました');

  } catch (Exception $e) {
    $db->rollBack();
    Response::error('更新に失敗しました: ' . $e->getMessage(), 500);
  }
}

function mergeReportUploadedFiles(array $section2): array {
  $year = date('Y');
  $dir  = UPLOAD_DIR . $year . '/';

  if (!is_dir($dir)) mkdir($dir, 0755, true);

  // 写真の追加
  if (!empty($_FILES['photos'])) {
    if (!isset($section2['photos'])) $section2['photos'] = [];
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
      if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
      $filename = uniqid('report_photo_') . '.' . $ext;
      move_uploaded_file($tmpName, $dir . $filename);
      $section2['photos'][] = "uploads/{$year}/{$filename}";
    }
  }

  // 領収書の追加
  if (!empty($_FILES['receipts'])) {
    if (!isset($section2['receipts'])) $section2['receipts'] = [];
    foreach ($_FILES['receipts']['tmp_name'] as $i => $tmpName) {
      if ($_FILES['receipts']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = strtolower(pathinfo($_FILES['receipts']['name'][$i], PATHINFO_EXTENSION));
      $filename = uniqid('receipt_') . '.' . $ext;
      move_uploaded_file($tmpName, $dir . $filename);
      $section2['receipts'][] = "uploads/{$year}/{$filename}";
    }
  }

  return $section2;
}
