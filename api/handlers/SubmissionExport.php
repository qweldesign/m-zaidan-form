<?php

function handleExport(): void {
  $db = getDB();

  $status  = $_GET['status']  ?? null;
  $where   = $status ? 'WHERE status = :status AND is_deleted = 0' : 'WHERE is_deleted = 0';
  $params  = $status ? [':status' => $status] : [];

  $stmt = $db->prepare("
    SELECT
      id, status, team_name, team_name_kana, team_postal_code, team_address,
      established_year, activity_category,
      representative_name, representative_email, representative_phone,
      contact_name, contact_email, contact_phone,
      project_name, start_date, end_date, venue,
      grant_request_amount, total_expense_amount, grant_usage_amount,
      created_at
    FROM submissions
    {$where}
    ORDER BY id ASC
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  // CSVヘッダー
  $headers = [
    'ID', 'ステータス', '団体名', '団体名フリガナ', '郵便番号', '所在地',
    '設立年', '活動内容',
    '代表者名', '代表者メール', '代表者電話',
    '担当者名', '担当者メール', '担当者電話',
    '事業名', '開始日', '終了日', '開催場所',
    '助成金要望額', '支出合計', '助成金使用額合計',
    '申請日時',
  ];

  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="submissions_' . date('Ymd') . '.csv"');

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
