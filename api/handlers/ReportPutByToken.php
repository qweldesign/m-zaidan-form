<?php

require_once __DIR__ . '/../helpers/RequestBody.php';

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

  // PHPはPUTメソッドの場合、multipart/form-dataのボディを自動的に
  // $_POST / $_FILES へパースしないため、手動で取得する
  [$body, $files] = resolveRequestBody();

  $db->beginTransaction();
  try {
    $s1 = json_decode($body['report_section1_json'] ?? '{}', true);

    // report_section2_json が送られてこなかった／不正なJSONだった場合は、
    // ほとんどのフィールドが空になってしまうのを防ぐため既存データにフォールバックする
    // （テキスト項目は送信内容で上書きする、という通常時の仕様はそのまま維持される）
    $currentSection2 = json_decode($current['report_section2_json'], true) ?? [];
    $s2 = isset($body['report_section2_json'])
      ? (json_decode($body['report_section2_json'], true) ?? $currentSection2)
      : $currentSection2;

    // 写真・領収書は section2 の JSON では送られてこないため、
    // 既存データを引き継いだうえで新規アップロード分を追加マージする
    $s2['photos']     = $currentSection2['photos']   ?? [];
    $s2['receipts']   = $currentSection2['receipts'] ?? [];
    $s2 = mergeReportUploadedFiles($s2, $files);

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
        activity_category    = :activity_category,
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
      ':team_name'            => $s1['teamName']        ?? $current['team_name'],
      ':contact_name'         => $s1['contactName']     ?? $current['contact_name'],
      ':contact_email'        => $s1['contactEmail']    ?? $current['contact_email'],
      ':contact_phone'        => $s1['contactPhone']    ?? $current['contact_phone'],
      ':project_name'         => $s2['projectName']     ?? $current['project_name'],
      ':activity_category'    => $s2['activityCategory'] ?? $current['activity_category'],
      ':actual_start_date'    => $s2['actualStartDate'] ?? $current['actual_start_date'],
      ':actual_end_date'      => $s2['actualEndDate']   ?? $current['actual_end_date'],
      ':actual_venue'         => $s2['actualVenue']     ?? $current['actual_venue'],
      ':grant_request_amount' => (int)($s2['income']['grantRequest'] ?? $current['grant_request_amount']),
      ':total_expense_amount' => $totalExpense,
      ':grant_usage_amount'   => $totalGrantUsage,
      ':report_section1_json' => $body['report_section1_json'] ?? $current['report_section1_json'],
      ':report_section2_json' => json_encode($s2, JSON_UNESCAPED_UNICODE),
      ':token'                => $token,
    ]);

    $db->commit();
    Response::success(['id' => (int)$current['id']], '完了報告内容を更新しました');

  } catch (Exception $e) {
    $db->rollBack();
    Response::error('更新に失敗しました: ' . $e->getMessage(), 500);
  }
}

function mergeReportUploadedFiles(array $section2, array $files): array {
  $year = date('Y');
  $dir  = UPLOAD_DIR . $year . '/';

  if (!is_dir($dir)) mkdir($dir, 0755, true);

  // 写真の追加
  if (!empty($files['photos'])) {
    if (!isset($section2['photos'])) $section2['photos'] = [];
    foreach ($files['photos']['tmp_name'] as $i => $tmpName) {
      if ($files['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = strtolower(pathinfo($files['photos']['name'][$i], PATHINFO_EXTENSION));
      $filename = uniqid('report_photo_') . '.' . $ext;
      moveUploadedOrTempFile($tmpName, $dir . $filename);
      $section2['photos'][] = "uploads/{$year}/{$filename}";
    }
  }

  // 領収書の追加
  if (!empty($files['receipts'])) {
    if (!isset($section2['receipts'])) $section2['receipts'] = [];
    foreach ($files['receipts']['tmp_name'] as $i => $tmpName) {
      if ($files['receipts']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = strtolower(pathinfo($files['receipts']['name'][$i], PATHINFO_EXTENSION));
      $filename = uniqid('receipt_') . '.' . $ext;
      moveUploadedOrTempFile($tmpName, $dir . $filename);
      $section2['receipts'][] = "uploads/{$year}/{$filename}";
    }
  }

  return $section2;
}
