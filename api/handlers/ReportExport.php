<?php

// 絞り込み条件はReportList.php（一覧取得API）と同じクエリパラメータに
// 対応しており、アプリの一覧画面で現在適用されているフィルター条件のまま
// CSVを出力できる（ページング用のlimit/offsetのみ対象外で、該当する全件を出力する）
function handleReportExport(): void {
  $db = getDB();

  // クエリパラメータ（ReportList.phpと同一の絞り込み条件）
  $status           = $_GET['status']            ?? null;
  $keyword          = $_GET['keyword']            ?? null;
  $activityCategory = $_GET['activity_category']  ?? null;
  $year             = $_GET['year']               ?? null;
  $includeDeleted   = $_GET['include_deleted']    ?? '0';
  $orderBy          = $_GET['order_by']           ?? 'id';
  $order            = strtoupper($_GET['order'] ?? 'ASC') === 'ASC' ? 'ASC' : 'DESC';

  // 許可するカラムのみORDER BYに使う
  $allowedOrderBy = ['id', 'created_at', 'team_name', 'activity_category', 'actual_start_date', 'grant_request_amount'];
  if (!in_array($orderBy, $allowedOrderBy)) {
    $orderBy = 'id';
  }

  $where  = [];
  $params = [];

  if ($status) {
    $where[]           = 'status = :status';
    $params[':status'] = $status;
  }

  if ($keyword) {
    $where[]        = '(team_name LIKE :kw OR project_name LIKE :kw2)';
    $params[':kw']  = "%{$keyword}%";
    $params[':kw2'] = "%{$keyword}%";
  }

  if ($activityCategory) {
    $where[]                      = 'activity_category = :activity_category';
    $params[':activity_category'] = $activityCategory;
  }

  if ($year) {
    $where[]         = "strftime('%Y', actual_start_date) = :year";
    $params[':year'] = $year;
  }

  if ($includeDeleted !== '1') {
    $where[] = 'is_deleted = 0';
  }

  $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  $stmt = $db->prepare("
    SELECT
      id, status, team_name, contact_name, contact_email, contact_phone,
      project_name, activity_category,
      actual_start_date, actual_end_date, actual_venue,
      grant_request_amount, total_expense_amount, grant_usage_amount,
      created_at
    FROM reports
    {$whereClause}
    ORDER BY {$orderBy} {$order}
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
