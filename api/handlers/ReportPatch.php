<?php

function handleReportPatch(int $id): void {
  $db = getDB();

  // 対象レコードの存在確認
  $stmt = $db->prepare('SELECT id FROM reports WHERE id = :id');
  $stmt->execute([':id' => $id]);
  if (!$stmt->fetch()) {
    Response::error('完了報告データが見つかりません', 404);
    return;
  }

  $body = json_decode(file_get_contents('php://input'), true);
  if (!$body) {
    Response::error('リクエストボディが不正です', 400);
    return;
  }

  // 編集可能フィールドのホワイトリスト
  $allowed = [
    'status',
    'team_name', 'contact_name', 'contact_email', 'contact_phone',
    'project_name', 'actual_start_date', 'actual_end_date', 'actual_venue',
    'grant_request_amount',
    'report_section1_json', 'report_section2_json',
    'is_deleted',
  ];

  $sets   = [];
  $params = [':id' => $id];

  // 現在値を取得（ログ用）
  $currentStmt = $db->prepare('SELECT * FROM reports WHERE id = :id');
  $currentStmt->execute([':id' => $id]);
  $current = $currentStmt->fetch();

  $db->beginTransaction();
  try {

    foreach ($body as $field => $value) {
      if (!in_array($field, $allowed)) continue;
      $sets[]              = "{$field} = :{$field}";
      $params[":{$field}"] = is_array($value)
        ? json_encode($value, JSON_UNESCAPED_UNICODE)
        : $value;

      // is_deleted = 1 のとき deleted_at をセット
      if ($field === 'is_deleted' && (int)$value === 1) {
        $sets[]                  = "deleted_at = :deleted_at";
        $params[':deleted_at']   = date('Y-m-d H:i:s');
      }
      // is_deleted = 0 に戻したとき deleted_at をクリア
      if ($field === 'is_deleted' && (int)$value === 0) {
        $sets[]                  = "deleted_at = :deleted_at";
        $params[':deleted_at']   = null;
      }

      // 変更ログを記録
      $logStmt = $db->prepare('
        INSERT INTO submission_logs (submission_id, field_name, old_value, new_value)
        VALUES (:submission_id, :field_name, :old_value, :new_value)
      ');
      $logStmt->execute([
        ':submission_id' => $id,
        ':field_name'    => 'report.' . $field,
        ':old_value'     => $current[$field] ?? null,
        ':new_value'     => is_array($value)
          ? json_encode($value, JSON_UNESCAPED_UNICODE)
          : $value,
      ]);
    }

    if (empty($sets)) {
      Response::error('更新可能なフィールドがありません', 400);
      return;
    }

    $updateStmt = $db->prepare('
      UPDATE reports SET ' . implode(', ', $sets) . ' WHERE id = :id
    ');
    $updateStmt->execute($params);
    $db->commit();

    Response::success(['id' => $id], '更新しました');

  } catch (Exception $e) {
    $db->rollBack();
    Response::error('更新に失敗しました: ' . $e->getMessage(), 500);
  }
}
