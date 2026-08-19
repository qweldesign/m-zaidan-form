<?php

require_once __DIR__ . '/../helpers/Validator.php';
if (!class_exists('Mailer')) {
  require_once __DIR__ . '/../helpers/Mailer.php';
}

function handleReportPost(): void {
  $db   = getDB();
  $body = $_POST;

  // バリデーション
  $errors = Validator::validateReport($body);
  if (!empty($errors)) {
    Response::error(implode(' / ', $errors), 422);
    return;
  }

  // ファイルアップロード処理
  $uploadedFiles = uploadReportFiles();

  // 編集用トークンを生成
  // リクエストボディからsubmission_tokenを受け取る
  $editToken = $body['submission_token'] ?? bin2hex(random_bytes(16));

  // submission_tokenが申請DBに存在するか確認（オプション・セキュリティ強化）
  if (isset($body['submission_token']) && $body['submission_token'] !== '') {
    $editToken = $body['submission_token'];

    // 申請DBに存在するか確認
    $checkStmt = $db->prepare('SELECT id FROM submissions WHERE edit_token = :token AND is_deleted = 0');
    $checkStmt->execute([':token' => $editToken]);
    if (!$checkStmt->fetch()) {
      Response::error('無効なトークンです', 400);
      return;
    }

    // 重複提出チェック
    $dupStmt = $db->prepare('SELECT id FROM reports WHERE edit_token = :token');
    $dupStmt->execute([':token' => $editToken]);
    if ($dupStmt->fetch()) {
      Response::error('この申請の完了報告はすでに提出されています。', 409);
      return;
    }
  } else {
    $editToken = bin2hex(random_bytes(16));
  }

  $db->beginTransaction();
  try {

    $s1 = json_decode($body['report_section1_json'] ?? '{}', true);
    $s2 = json_decode($body['report_section2_json'] ?? '{}', true) ?? [];

    // アップロードした写真・領収書のパスを section2 に反映
    $s2['photos']   = $uploadedFiles['photos'];
    $s2['receipts'] = $uploadedFiles['receipts'];

    // 支出合計・助成金使用額合計を計算
    $expenses        = $s2['expenses']  ?? [];
    $totalExpense    = array_sum(array_column($expenses, 'amount'));
    $totalGrantUsage = array_sum(array_column($expenses, 'grantUsage'));

    $stmt = $db->prepare('
      INSERT INTO reports (
        team_name, contact_name, contact_email, contact_phone,
        project_name, activity_category, actual_start_date, actual_end_date, actual_venue,
        grant_request_amount, total_expense_amount, grant_usage_amount,
        report_section1_json, report_section2_json, edit_token
      ) VALUES (
        :team_name, :contact_name, :contact_email, :contact_phone,
        :project_name, :activity_category, :actual_start_date, :actual_end_date, :actual_venue,
        :grant_request_amount, :total_expense_amount, :grant_usage_amount,
        :report_section1_json, :report_section2_json, :edit_token
      )
    ');

    $stmt->execute([
      ':team_name'            => $s1['teamName']            ?? '',
      ':contact_name'         => $s1['contactName']         ?? '',
      ':contact_email'        => $s1['contactEmail']        ?? '',
      ':contact_phone'        => $s1['contactPhone']        ?? '',
      ':project_name'         => $s2['projectName']         ?? '',
      ':activity_category'    => $s2['activityCategory']    ?? '',
      ':actual_start_date'    => $s2['actualStartDate']     ?? '',
      ':actual_end_date'      => $s2['actualEndDate']       ?? '',
      ':actual_venue'         => $s2['actualVenue']         ?? '',
      ':grant_request_amount' => (int)($s2['income']['grantRequest'] ?? 0),
      ':total_expense_amount' => $totalExpense,
      ':grant_usage_amount'   => $totalGrantUsage,
      ':report_section1_json' => $body['report_section1_json'] ?? '{}',
      ':report_section2_json' => json_encode($s2, JSON_UNESCAPED_UNICODE),
      ':edit_token'           => $editToken,
    ]);

    $reportId       = (int)$db->lastInsertId();
    $db->commit();

    // メール送信
    $applicantEmail = $s1['contactEmail'] ?? '';
    $applicantName  = $s1['teamName']     ?? '';
    Mailer::sendReportConfirmation($applicantEmail, $applicantName, $reportId, $editToken);
    Mailer::sendReportNotification($reportId, $applicantName);

    Response::success(['id' => $reportId], '完了報告を受け付けました');

  } catch (Exception $e) {
    $db->rollBack();
    Response::error('完了報告の保存に失敗しました: ' . $e->getMessage(), 500);
  }
}

function uploadReportFiles(): array {
  $result = ['photos' => [], 'receipts' => []];
  $year   = date('Y');
  $dir    = UPLOAD_DIR . $year . '/';

  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  // 活動写真
  if (!empty($_FILES['photos'])) {
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
      if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
      $filename = uniqid('report_photo_') . '.' . strtolower($ext);
      move_uploaded_file($tmpName, $dir . $filename);
      $result['photos'][] = "uploads/{$year}/{$filename}";
    }
  }

  // 領収書（複数可）
  if (!empty($_FILES['receipts'])) {
    foreach ($_FILES['receipts']['tmp_name'] as $i => $tmpName) {
      if ($_FILES['receipts']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = strtolower(pathinfo($_FILES['receipts']['name'][$i], PATHINFO_EXTENSION));
      $filename = uniqid('receipt_') . '.' . $ext;
      move_uploaded_file($tmpName, $dir . $filename);
      $result['receipts'][] = "uploads/{$year}/{$filename}";
    }
  }

  return $result;
}
