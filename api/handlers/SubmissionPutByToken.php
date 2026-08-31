<?php

require_once __DIR__ . '/../helpers/RequestBody.php';
require_once __DIR__ . '/../helpers/Validator.php';

function handlePutByToken(string $token): void {
  $db = getDB();

  // トークンで対象レコードを取得
  $stmt = $db->prepare('SELECT * FROM submissions WHERE edit_token = :token AND is_deleted = 0');
  $stmt->execute([':token' => $token]);
  $current = $stmt->fetch();

  if (!$current) {
    Response::error('無効なトークンです', 404);
    return;
  }

  // ステータスチェック（審査前のみ編集可能）
  if ($current['status'] !== '審査前') {
    Response::error('この申請はすでに受理されているため、内容を変更できません。', 403);
    return;
  }

  $id = (int)$current['id'];

  // PHPはPUTメソッドの場合、multipart/form-dataのボディを自動的に
  // $_POST / $_FILES へパースしないため、手動で取得する
  [$body, $files] = resolveRequestBody();

  // ファイルアップロード（追加モード）
  $section5 = json_decode($current['section5_json'], true) ?? ['photos' => [], 'docs' => []];
  $section5 = mergeUploadedFiles($section5, $files);

  // 編集時は毎回ファイルを選び直す必要はないが（既存ファイルは維持される）、
  // マージ後の最終状態として必須の添付が最低1件も無い場合はエラーにする。
  // これが無いと、写真・PDFが1件も無いまま作成された申請（フォーム側の
  // 不具合や直接のAPI操作等）を、添付を追加しないまま何度でも更新できてしまう。
  $fileErrors = Validator::validateSubmissionFiles($section5);
  if (!empty($fileErrors)) {
    Response::error(implode(' / ', $fileErrors), 422);
    return;
  }

  $db->beginTransaction();
  try {
    $s1 = json_decode($body['section1_json'] ?? '{}', true);
    $s2 = json_decode($body['section2_json'] ?? '{}', true);
    $s3 = json_decode($body['section3_json'] ?? '{}', true);

    $expenses        = $s3['expenses']  ?? [];
    $totalExpense    = array_sum(array_column($expenses, 'amount'));
    $totalGrantUsage = array_sum(array_column($expenses, 'grantUsage'));

    // 変更ログ用に比較して記録
    $fields = [
      'team_name'           => $s1['teamName']        ?? '',
      'team_name_kana'      => $s1['teamNameKana']     ?? '',
      'team_postal_code'    => $s1['teamPostalCode']   ?? '',
      'team_address'        => $s1['teamAddress']      ?? '',
      'project_name'        => $s2['projectName']      ?? '',
      'start_date'          => $s2['startDate']        ?? '',
      'end_date'            => $s2['endDate']          ?? '',
      'venue'               => $s2['venue']            ?? '',
      'grant_request_amount'=> (int)($s3['income']['grantRequest'] ?? 0),
      'total_expense_amount'=> $totalExpense,
      'grant_usage_amount'  => $totalGrantUsage,
    ];

    foreach ($fields as $field => $newValue) {
      if ((string)$current[$field] !== (string)$newValue) {
        $logStmt = $db->prepare('
          INSERT INTO submission_logs (submission_id, field_name, old_value, new_value, changed_by)
          VALUES (:submission_id, :field_name, :old_value, :new_value, :changed_by)
        ');
        $logStmt->execute([
          ':submission_id' => $id,
          ':field_name'    => $field,
          ':old_value'     => $current[$field],
          ':new_value'     => $newValue,
          ':changed_by'    => 'applicant',
        ]);
      }
    }

    $updateStmt = $db->prepare('
      UPDATE submissions SET
        team_name            = :team_name,
        team_name_kana       = :team_name_kana,
        team_postal_code     = :team_postal_code,
        team_address         = :team_address,
        established_year     = :established_year,
        activity_category    = :activity_category,
        representative_name  = :representative_name,
        representative_email = :representative_email,
        representative_phone = :representative_phone,
        contact_name         = :contact_name,
        contact_email        = :contact_email,
        contact_phone        = :contact_phone,
        same_as_representative = :same_as_representative,
        project_name         = :project_name,
        start_date           = :start_date,
        end_date             = :end_date,
        venue                = :venue,
        grant_request_amount = :grant_request_amount,
        total_expense_amount = :total_expense_amount,
        grant_usage_amount   = :grant_usage_amount,
        section1_json        = :section1_json,
        section2_json        = :section2_json,
        section3_json        = :section3_json,
        section4_json        = :section4_json,
        section5_json        = :section5_json
      WHERE edit_token = :token
    ');

    $updateStmt->execute([
      ':team_name'              => $s1['teamName']              ?? $current['team_name'],
      ':team_name_kana'         => $s1['teamNameKana']          ?? $current['team_name_kana'],
      ':team_postal_code'       => $s1['teamPostalCode']        ?? $current['team_postal_code'],
      ':team_address'           => $s1['teamAddress']           ?? $current['team_address'],
      ':established_year'       => (int)($s1['establishedYear'] ?? $current['established_year']),
      ':activity_category'      => $s1['activityCategory']      ?? $current['activity_category'],
      ':representative_name'    => $s1['representativeName']    ?? $current['representative_name'],
      ':representative_email'   => $s1['representativeEmail']   ?? $current['representative_email'],
      ':representative_phone'   => $s1['representativePhone']   ?? $current['representative_phone'],
      ':contact_name'           => $s1['contactName']           ?? $current['contact_name'],
      ':contact_email'          => $s1['contactEmail']          ?? $current['contact_email'],
      ':contact_phone'          => $s1['contactPhone']          ?? $current['contact_phone'],
      ':same_as_representative' => isset($s1['sameAsRepresentative'])
                                    ? ($s1['sameAsRepresentative'] ? 1 : 0)
                                    : $current['same_as_representative'],
      ':project_name'           => $s2['projectName']           ?? $current['project_name'],
      ':start_date'             => $s2['startDate']             ?? $current['start_date'],
      ':end_date'               => $s2['endDate']               ?? $current['end_date'],
      ':venue'                  => $s2['venue']                 ?? $current['venue'],
      ':grant_request_amount'   => (int)($s3['income']['grantRequest'] ?? $current['grant_request_amount']),
      ':total_expense_amount'   => $totalExpense,
      ':grant_usage_amount'     => $totalGrantUsage,
      ':section1_json'          => $body['section1_json']       ?? $current['section1_json'],
      ':section2_json'          => $body['section2_json']       ?? $current['section2_json'],
      ':section3_json'          => $body['section3_json']       ?? $current['section3_json'],
      ':section4_json'          => $body['section4_json']       ?? $current['section4_json'],
      ':section5_json'          => json_encode($section5, JSON_UNESCAPED_UNICODE),
      ':token'                  => $token,
    ]);

    $db->commit();
    Response::success(['id' => $id], '申請内容を更新しました');

  } catch (Exception $e) {
    $db->rollBack();
    Response::error('更新に失敗しました: ' . $e->getMessage(), 500);
  }
}

// ファイルを既存データに追加マージ
function mergeUploadedFiles(array $section5, array $files): array {
  $year = date('Y');
  $dir  = UPLOAD_DIR . $year . '/';

  if (!is_dir($dir)) mkdir($dir, 0755, true);

  // 写真の追加
  if (!empty($files['photos'])) {
    foreach ($files['photos']['tmp_name'] as $i => $tmpName) {
      if ($files['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext      = strtolower(pathinfo($files['photos']['name'][$i], PATHINFO_EXTENSION));
      $filename = uniqid('photo_') . '.' . $ext;
      moveUploadedOrTempFile($tmpName, $dir . $filename);
      $section5['photos'][] = "uploads/{$year}/{$filename}";
    }
  }

  // PDF各種の追加（'other' はその他の補足資料。任意のため Validator の必須チェック対象外）
  $docFields = ['regulations', 'activityReport', 'financialReport', 'activityPlan', 'financialPlan', 'other'];
  foreach ($docFields as $field) {
    if (!empty($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
      $filename = uniqid($field . '_') . '.pdf';
      moveUploadedOrTempFile($files[$field]['tmp_name'], $dir . $filename);
      // 既存パスを配列に変換して追記
      if (!isset($section5['docs'][$field])) {
        $section5['docs'][$field] = [];
      }
      if (is_string($section5['docs'][$field])) {
        $section5['docs'][$field] = [$section5['docs'][$field]];
      }
      $section5['docs'][$field][] = "uploads/{$year}/{$filename}";
    }
  }

  return $section5;
}
