<?php

function handleReportGetByToken(string $token): void {
  $db   = getDB();
  $stmt = $db->prepare('
    SELECT
      id, status,
      team_name, contact_name, contact_email, contact_phone,
      project_name, actual_start_date, actual_end_date, actual_venue,
      grant_request_amount,
      report_section1_json, report_section2_json
    FROM reports
    WHERE edit_token = :token AND is_deleted = 0
  ');
  $stmt->execute([':token' => $token]);
  $row = $stmt->fetch();

  if (!$row) {
    Response::error('無効なトークンです', 404);
    return;
  }

  $row['report_section1_json'] = json_decode($row['report_section1_json'], true);
  $row['report_section2_json'] = json_decode($row['report_section2_json'], true);

  Response::success($row);
}
