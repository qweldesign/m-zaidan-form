<?php

require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/Mailer.php';

function handlePost(): void {
  $db = getDB();

  // multipart/form-data で受け取る（ファイルがあるため）
  $body = $_POST;

  // バリデーション
  $errors = Validator::validateSubmission($body);
  if (!empty($errors)) {
    Response::error(implode(' / ', $errors), 422);
    return;
  }

  // ファイルアップロード処理
  $section5Json = uploadFiles();

  // 編集用トークンを生成
  $editToken = bin2hex(random_bytes(16));

  // トランザクション開始
  $db->beginTransaction();
  try {

    $stmt = $db->prepare('
      INSERT INTO submissions (
        team_name, team_name_kana, team_postal_code, team_address,
        established_year, activity_category,
        representative_name, representative_email, representative_phone,
        contact_name, contact_email, contact_phone, same_as_representative,
        project_name, start_date, end_date, venue,
        grant_request_amount, total_expense_amount, grant_usage_amount,
        section1_json, section2_json, section3_json, section4_json, section5_json,
        edit_token
      ) VALUES (
        :team_name, :team_name_kana, :team_postal_code, :team_address,
        :established_year, :activity_category,
        :representative_name, :representative_email, :representative_phone,
        :contact_name, :contact_email, :contact_phone, :same_as_representative,
        :project_name, :start_date, :end_date, :venue,
        :grant_request_amount, :total_expense_amount, :grant_usage_amount,
        :section1_json, :section2_json, :section3_json, :section4_json, :section5_json,
        :edit_token
      )
    ');

    $s1 = json_decode($body['section1_json'] ?? '{}', true);
    $s2 = json_decode($body['section2_json'] ?? '{}', true);
    $s3 = json_decode($body['section3_json'] ?? '{}', true);

    // 支出合計・助成金使用額合計を計算
    $expenses         = $s3['expenses'] ?? [];
    $totalExpense     = array_sum(array_column($expenses, 'amount'));
    $totalGrantUsage  = array_sum(array_column($expenses, 'grantUsage'));

    $stmt->execute([
      ':team_name'              => $s1['teamName']             ?? '',
      ':team_name_kana'         => $s1['teamNameKana']         ?? '',
      ':team_postal_code'       => $s1['teamPostalCode']       ?? '',
      ':team_address'           => $s1['teamAddress']          ?? '',
      ':established_year'       => (int)($s1['establishedYear'] ?? 0),
      ':activity_category'      => $s1['activityCategory']     ?? '',
      ':representative_name'    => $s1['representativeName']   ?? '',
      ':representative_email'   => $s1['representativeEmail']  ?? '',
      ':representative_phone'   => $s1['representativePhone']  ?? '',
      ':contact_name'           => $s1['contactName']          ?? '',
      ':contact_email'          => $s1['contactEmail']         ?? '',
      ':contact_phone'          => $s1['contactPhone']         ?? '',
      ':same_as_representative' => $s1['sameAsRepresentative'] ? 1 : 0,
      ':project_name'           => $s2['projectName']          ?? '',
      ':start_date'             => $s2['startDate']            ?? '',
      ':end_date'               => $s2['endDate']              ?? '',
      ':venue'                  => $s2['venue']                ?? '',
      ':grant_request_amount'   => (int)($s3['income']['grantRequest'] ?? 0),
      ':total_expense_amount'   => $totalExpense,
      ':grant_usage_amount'     => $totalGrantUsage,
      ':section1_json'          => $body['section1_json']      ?? '{}',
      ':section2_json'          => $body['section2_json']      ?? '{}',
      ':section3_json'          => $body['section3_json']      ?? '{}',
      ':section4_json'          => $body['section4_json']      ?? '{}',
      ':section5_json'          => json_encode($section5Json),
      ':edit_token'             => $editToken,
    ]);

    $submissionId = (int)$db->lastInsertId();
    $db->commit();

    // メール送信
    $applicantEmail = $s1['representativeEmail'] ?? '';
    $applicantName  = $s1['representativeName']  ?? '';
    Mailer::sendConfirmation($applicantEmail, $applicantName, $submissionId, $editToken);
    Mailer::sendNotification($submissionId, $applicantName);

    Response::success(['id' => $submissionId], '申請を受け付けました');

  } catch (Exception $e) {
    $db->rollBack();
    Response::error('申請の保存に失敗しました: ' . $e->getMessage(), 500);
  }
}

// ファイルアップロード処理
function uploadFiles(): array {
  $result = ['photos' => [], 'docs' => []];
  $year   = date('Y');
  $dir    = UPLOAD_DIR . $year . '/';

  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  // 写真
  if (!empty($_FILES['photos'])) {
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
      if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
      $filename = uniqid('photo_') . '.' . strtolower($ext);
      move_uploaded_file($tmpName, $dir . $filename);
      $result['photos'][] = "uploads/{$year}/{$filename}";
    }
  }

  // PDF各種
  $docFields = ['regulations', 'activityReport', 'financialReport', 'activityPlan', 'financialPlan'];
  foreach ($docFields as $field) {
    if (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
      $filename = uniqid($field . '_') . '.pdf';
      move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $filename);
      $result['docs'][$field] = "uploads/{$year}/{$filename}";
    }
  }

  return $result;
}
