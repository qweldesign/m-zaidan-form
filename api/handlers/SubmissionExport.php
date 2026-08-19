<?php

// 配列（複数選択項目）は「、」区切りの文字列にする。null/未設定は空文字にする
function submissionExportVal($value) {
  if (is_array($value)) return implode('、', $value);
  return $value ?? '';
}

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
      section1_json, section2_json, section4_json,
      created_at
    FROM submissions
    {$where}
    ORDER BY id ASC
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  // CSVヘッダー（基本項目）
  $headers = [
    'ID', 'ステータス', '団体名', '団体名フリガナ', '郵便番号', '所在地',
    '設立年', '活動内容',
    '代表者名', '代表者メール', '代表者電話',
    '担当者名', '担当者メール', '担当者電話',
    '事業名', '開始日', '終了日', '開催場所',
    '助成金要望額', '支出合計', '助成金使用額合計',
  ];

  // CSVヘッダー（section1_json / section2_json / section4_json の詳細）
  $headers = array_merge($headers, [
    // section1_json
    '代表者フリガナ', '担当者フリガナ',
    '会員構成（20歳未満）', '会員構成（21〜40歳）', '会員構成（41〜60歳）', '会員構成（61歳以上）',
    '助成歴（当財団：回数）', '助成歴（当財団：直近年度）',
    '助成歴（他団体：回数）', '助成歴（他団体：直近年度）',
    '応募歴（回数）', '応募歴（直近年度）',
    '応募経緯', '応募経緯（その他）',
    // section2_json
    '募集地域',
    '主催者（人数）', '主催者（日数）', '主催者（延べ人数）',
    '参加者（人数）', '参加者（日数）', '参加者（延べ人数）',
    '事業内容', '事業目的', '実績PR（事業）', '共催団体',
    // section4_json
    '設立目的', '設立背景', '活動頻度', '活動内容（団体）',
    '受賞歴の有無', '受賞歴詳細',
    '地域連携の有無', '地域連携詳細', '実績PR（団体）',
    '申請日時',
  ]);

  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="submissions_' . date('Ymd') . '.csv"');

  $out = fopen('php://output', 'w');
  // BOM（Excelで文字化けしないよう）
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, $headers);

  foreach ($rows as $row) {
    $s1 = json_decode($row['section1_json'] ?? '{}', true) ?? [];
    $s2 = json_decode($row['section2_json'] ?? '{}', true) ?? [];
    $s4 = json_decode($row['section4_json'] ?? '{}', true) ?? [];

    $values = [
      $row['id'], $row['status'], $row['team_name'], $row['team_name_kana'],
      $row['team_postal_code'], $row['team_address'],
      $row['established_year'], $row['activity_category'],
      $row['representative_name'], $row['representative_email'], $row['representative_phone'],
      $row['contact_name'], $row['contact_email'], $row['contact_phone'],
      $row['project_name'], $row['start_date'], $row['end_date'], $row['venue'],
      $row['grant_request_amount'], $row['total_expense_amount'], $row['grant_usage_amount'],

      // section1_json
      submissionExportVal($s1['representativeNameKana'] ?? null),
      submissionExportVal($s1['contactNameKana'] ?? null),
      submissionExportVal($s1['members']['under20'] ?? null),
      submissionExportVal($s1['members']['age21to40'] ?? null),
      submissionExportVal($s1['members']['age41to60'] ?? null),
      submissionExportVal($s1['members']['over61'] ?? null),
      submissionExportVal($s1['grantHistory']['thisFoundationCount'] ?? null),
      submissionExportVal($s1['grantHistory']['thisFoundationLatestYear'] ?? null),
      submissionExportVal($s1['grantHistory']['otherFoundationCount'] ?? null),
      submissionExportVal($s1['grantHistory']['otherFoundationLatestYear'] ?? null),
      submissionExportVal($s1['applicationHistory']['count'] ?? null),
      submissionExportVal($s1['applicationHistory']['latestYear'] ?? null),
      submissionExportVal($s1['applicationRoute'] ?? null),
      submissionExportVal($s1['applicationRouteOther'] ?? null),

      // section2_json
      submissionExportVal($s2['recruitmentArea'] ?? null),
      submissionExportVal($s2['organizer']['count'] ?? null),
      submissionExportVal($s2['organizer']['days'] ?? null),
      submissionExportVal($s2['organizer']['total'] ?? null),
      submissionExportVal($s2['participants']['count'] ?? null),
      submissionExportVal($s2['participants']['days'] ?? null),
      submissionExportVal($s2['participants']['total'] ?? null),
      submissionExportVal($s2['projectDetail'] ?? null),
      submissionExportVal($s2['projectPurpose'] ?? null),
      submissionExportVal($s2['projectPR'] ?? null),
      submissionExportVal($s2['coOrganizers'] ?? null),

      // section4_json
      submissionExportVal($s4['establishmentPurpose'] ?? null),
      submissionExportVal($s4['establishmentBackground'] ?? null),
      submissionExportVal($s4['activityFrequency'] ?? null),
      submissionExportVal($s4['activityContent'] ?? null),
      submissionExportVal($s4['hasAward'] ?? null),
      submissionExportVal($s4['awardDetail'] ?? null),
      submissionExportVal($s4['hasCommunityInvolvement'] ?? null),
      submissionExportVal($s4['communityInvolvementDetail'] ?? null),
      submissionExportVal($s4['prNote'] ?? null),

      $row['created_at'],
    ];

    fputcsv($out, $values);
  }

  fclose($out);
  exit;
}
