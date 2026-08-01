<?php

function handleGetByToken(string $token): void {
  $db   = getDB();
  $stmt = $db->prepare('
    SELECT
      id, status, team_name, team_name_kana, team_postal_code, team_address,
      established_year, activity_category,
      representative_name, representative_email, representative_phone,
      contact_name, contact_email, contact_phone, same_as_representative,
      project_name, start_date, end_date, venue,
      grant_request_amount,
      section1_json, section2_json, section3_json, section4_json, section5_json
    FROM submissions
    WHERE edit_token = :token AND is_deleted = 0
  ');
  $stmt->execute([':token' => $token]);
  $row = $stmt->fetch();

  if (!$row) {
    Response::error('無効なトークンです', 404);
    return;
  }

  // JSONカラムをデコード
  foreach (['section1_json', 'section2_json', 'section3_json', 'section4_json', 'section5_json'] as $col) {
    $row[$col] = json_decode($row[$col], true);
  }

  Response::success($row);
}
