<?php

function handleGet(int $id): void {
  $db   = getDB();
  $stmt = $db->prepare('
    SELECT * FROM submissions
    WHERE id = :id AND is_deleted = 0
  ');
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();

  if (!$row) {
    Response::error('申請データが見つかりません', 404);
    return;
  }

  // JSONカラムをデコードして返す
  foreach (['section1_json', 'section2_json', 'section3_json', 'section4_json', 'section5_json'] as $col) {
    $row[$col] = json_decode($row[$col], true);
  }

  Response::success($row);
}
