<?php

class Validator {
  public static function validateSubmission(array $body): array {
    $errors = [];
    $s1 = json_decode($body['section1_json'] ?? '{}', true) ?? [];
    $s2 = json_decode($body['section2_json'] ?? '{}', true) ?? [];

    if (empty($s1['teamName']))           $errors[] = '団体名称は必須です';
    if (empty($s1['teamAddress']))        $errors[] = '所在地は必須です';
    if (empty($s1['representativeName'])) $errors[] = '代表者名は必須です';
    if (empty($s1['representativeEmail'])
      || !filter_var($s1['representativeEmail'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = '代表者メールアドレスが不正です';
    }
    if (empty($s1['contactName']))        $errors[] = '担当者名は必須です';
    if (empty($s1['contactEmail'])
      || !filter_var($s1['contactEmail'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = '担当者メールアドレスが不正です';
    }
    if (empty($s2['projectName']))        $errors[] = '事業名称は必須です';
    if (empty($s2['startDate']))          $errors[] = '開始日は必須です';
    if (empty($s2['endDate']))            $errors[] = '終了日は必須です';

    // 添付資料（新規申請時は必須。$_FILES はリクエストボディとは別の
    // スーパーグローバルのため、validateReport() と同様にここで直接参照する）
    if (empty($_FILES['photos']) || $_FILES['photos']['error'][0] !== UPLOAD_ERR_OK) {
      $errors[] = '活動写真は必須です';
    }
    foreach (self::PDF_DOC_LABELS as $field => $label) {
      if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "{$label}は必須です";
      }
    }

    return $errors;
  }

  // 要望申請の添付資料（PDF5種）のフィールド名とラベルの対応。
  // Section5.tsx（フォーム側）の PDF_DOCS と対応させること。
  const PDF_DOC_LABELS = [
    'regulations'     => '団体規約',
    'activityReport'  => '直近年度の活動報告書',
    'financialReport' => '直近年度の収支決算書',
    'activityPlan'    => '直近年度の活動計画書',
    'financialPlan'   => '直近年度の収支計画書',
  ];

  // 編集（PUT）時：新規アップロード分を既存データにマージした「最終状態」の
  // section5 に対して、必須の添付が最低1件は揃っているかを確認する。
  // 編集時は毎回ファイルを選び直す必要はない（マージ方式）ため、
  // 「今回アップロードされたか」ではなく「マージ後に存在するか」で判定する。
  public static function validateSubmissionFiles(array $section5): array {
    $errors = [];

    if (empty($section5['photos'])) {
      $errors[] = '活動写真は必須です';
    }

    $docs = $section5['docs'] ?? [];
    foreach (self::PDF_DOC_LABELS as $field => $label) {
      if (empty($docs[$field])) {
        $errors[] = "{$label}は必須です";
      }
    }

    return $errors;
  }

  // 完了報告版（編集/PUT時）：写真・領収書が最終的に最低1件ずつ揃っているかを確認する。
  public static function validateReportFiles(array $section2): array {
    $errors = [];

    if (empty($section2['photos'])) {
      $errors[] = '活動実施写真は必須です';
    }
    if (empty($section2['receipts'])) {
      $errors[] = '領収書は必須です';
    }

    return $errors;
  }

  public static function validateReport(array $body): array {
    $errors = [];
    $s1 = json_decode($body['report_section1_json'] ?? '{}', true) ?? [];
    $s2 = json_decode($body['report_section2_json'] ?? '{}', true) ?? [];

    if (empty($s1['teamName']))     $errors[] = '団体名称は必須です';
    if (empty($s1['contactName']))  $errors[] = '担当者名は必須です';
    if (empty($s1['contactEmail'])
      || !filter_var($s1['contactEmail'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = '担当者メールアドレスが不正です';
    }
    if (empty($s2['projectName']))      $errors[] = '事業名称は必須です';
    if (empty($s2['actualStartDate']))  $errors[] = '開始日は必須です';
    if (empty($s2['actualEndDate']))    $errors[] = '終了日は必須です';
    if (empty($s2['actualVenue']))      $errors[] = '実施場所は必須です';
    if (empty($s2['income']['grantRequest']) || (int)$s2['income']['grantRequest'] <= 0) {
      $errors[] = '助成金要望額は必須です';
    }

    if (empty($_FILES['photos']) || $_FILES['photos']['error'][0] !== UPLOAD_ERR_OK) {
      $errors[] = '活動実施写真は必須です';
    }
    if (empty($_FILES['receipts']) || $_FILES['receipts']['error'][0] !== UPLOAD_ERR_OK) {
      $errors[] = '領収書は必須です';
    }

    return $errors;
  }
}
