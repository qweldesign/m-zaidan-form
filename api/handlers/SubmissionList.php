<?php

function handleList(): void {
  $db = getDB();

  // クエリパラメータ
  $status   = $_GET['status']   ?? null;
  $keyword  = $_GET['keyword']  ?? null;
  $activityCategory = $_GET['activity_category'] ?? null;
  $year             = $_GET['year']              ?? null;
  $includeDeleted   = $_GET['include_deleted'] ?? '0';
  $orderBy  = $_GET['order_by'] ?? 'id';
  $order    = strtoupper($_GET['order'] ?? 'ASC') === 'ASC' ? 'ASC' : 'DESC';
  $limit    = min((int)($_GET['limit']  ?? 50), 200);
  $offset   = (int)($_GET['offset'] ?? 0);

  // 許可するカラムのみORDER BYに使う
  $allowedOrderBy = ['id', 'created_at', 'team_name', 'activity_category', 'start_date', 'status', 'grant_request_amount'];
  if (!in_array($orderBy, $allowedOrderBy)) {
    $orderBy = 'id';
  }

  $where  = [];
  $params = [];

  if ($status) {
    $where[]          = 'status = :status';
    $params[':status'] = $status;
  }

  if ($keyword) {
    $where[]            = '(team_name LIKE :kw OR project_name LIKE :kw2)';
    $params[':kw']      = "%{$keyword}%";
    $params[':kw2']     = "%{$keyword}%";
  }

  if ($activityCategory) {
    $where[]                      = 'activity_category = :activity_category';
    $params[':activity_category'] = $activityCategory;
  }

  if ($year) {
    $where[]         = "strftime('%Y', created_at) = :year";
    $params[':year'] = $year;
  }
  
  if ($includeDeleted !== '1') {
    $where[] = 'is_deleted = 0';
  }

  $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  $stmt = $db->prepare("
    SELECT
      id, status, team_name, team_name_kana,
      activity_category, project_name, start_date, end_date,
      grant_request_amount, grant_usage_amount,
      representative_name, representative_email,
      created_at, updated_at
    FROM submissions
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

  // 件数取得
  $countStmt = $db->prepare("SELECT COUNT(*) FROM submissions {$whereClause}");
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
