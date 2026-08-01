<?php

function handleReportList(): void {
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');
  $db = getDB();

  // クエリパラメータ
  $status  = $_GET['status'] ?? null;
  $keyword = $_GET['keyword'] ?? null;
  $activityCategory = $_GET['activity_category'] ?? null;
  $year             = $_GET['year'] ?? null;
  $includeDeleted   = $_GET['include_deleted'] ?? '0';
  $orderBy = $_GET['order_by'] ?? 'id';
  $order   = strtoupper($_GET['order'] ?? 'ASC') === 'ASC' ? 'ASC' : 'DESC';
  $limit   = min((int)($_GET['limit']  ?? 50), 200);
  $offset  = (int)($_GET['offset'] ?? 0);

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
      id, status, team_name, contact_name, contact_email,
      project_name, activity_category, actual_start_date, actual_end_date,
      grant_request_amount, total_expense_amount, grant_usage_amount,
      created_at, updated_at
    FROM reports
    {$whereClause}
    ORDER BY {$orderBy} {$order}
    LIMIT :limit OFFSET :offset
  ");

  foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
  }
  $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();

  $countStmt = $db->prepare("SELECT COUNT(*) FROM reports {$whereClause}");
  foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
  }
  $countStmt->execute();
  $total = (int)$countStmt->fetchColumn();

  Response::success([
    'total'  => $total,
    'limit'  => $limit,
    'offset' => $offset,
    'items'  => $rows,
  ]);
}
