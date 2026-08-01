<?php

function handleReportGet(int $id): void {
  $db   = getDB();
  $stmt = $db->prepare('SELECT * FROM reports WHERE id = :id AND is_deleted = 0');
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();

  if (!$row) {
    Response::error('完了報告データが見つかりません', 404);
    return;
  }

  $row['report_section1_json'] = json_decode($row['report_section1_json'], true);
  $row['report_section2_json'] = json_decode($row['report_section2_json'], true);

  Response::success($row);
}
