<?php

function handleReportExport(): void {
  $db = getDB();

  $status  = $_GET['status']  ?? null;
  $where   = $status ? 'WHERE status = :status AND is_deleted = 0' : 'WHERE is_deleted = 0';
  $params  = $status ? [':status' => $status] : [];

  $stmt = $db->prepare("
    SELECT
      id, status, team_name, contact_name, contact_email, contact_phone,
      project_name, activity_category,
      actual_start_date, actual_end_date, actual_venue,
      grant_request_amount, total_expense_amount, grant_usage_amount,
      created_at
    FROM reports
    {$where}
    ORDER BY id ASC
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  // CSVヘッダー
  $headers = [
    'ID', 'ステータス', '団体名',
    '担当者名', '担当者メール', '担当者電話',
    '事業名', '活動内容',
    '実施開始日', '実施終了日', '実施場所',
    '助成金要望額', '支出合計', '助成金使用額合計',
    '報告日時',
  ];

  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="reports_' . date('Ymd') . '.csv"');

  $out = fopen('php://output', 'w');
  // BOM（Excelで文字化けしないよう）
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, $headers);

  foreach ($rows as $row) {
    fputcsv($out, array_values($row));
  }

  fclose($out);
  exit;
}
