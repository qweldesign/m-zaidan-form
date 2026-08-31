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
    // 活動写真は「1枚以上」ではなく、規定枚数（3枚）ちょうどが必須。
    if (self::countUploadedFiles($_FILES['photos'] ?? null) !== self::SUBMISSION_PHOTO_COUNT) {
      $errors[] = '活動写真は' . self::SUBMISSION_PHOTO_COUNT . '枚必須です';
    }
    foreach (self::PDF_DOC_LABELS as $field => $label) {
      if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "{$label}は必須です";
      }
    }

    return $errors;
  }

  // 要望申請・完了報告の活動写真の規定枚数（ちょうどこの枚数を必須とする）。
  // Section5.tsx（要望申請フォーム）・Report/Section3.tsx（完了報告フォーム）の
  // PhotoSlots の maxSlots と対応させること。
  const SUBMISSION_PHOTO_COUNT = 3;
  const REPORT_PHOTO_COUNT     = 2;

  // 複数ファイル形式（photos[]）の $_FILES サブ配列から、アップロードに
  // 成功した（UPLOAD_ERR_OK の）ファイル数を数える。未添付や不正な形式の
  // 場合は 0 を返す。
  private static function countUploadedFiles(?array $files): int {
    if (empty($files['error']) || !is_array($files['error'])) {
      return 0;
    }
    return count(array_filter($files['error'], fn($e) => $e === UPLOAD_ERR_OK));
  }

  // 要望申請の必須添付資料（PDF5種）のフィールド名とラベルの対応。
  // Section5.tsx（フォーム側）の PDF_DOCS のうち required: true の項目と
  // 対応させること。'other'（その他の補足資料）は任意のため、意図的に
  // ここには含めていない（必須チェックの対象外）。
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
  //
  // 写真の規定枚数（SUBMISSION_PHOTO_COUNT）ちょうどは新規申請（POST）時の
  // validateSubmission() でのみ強制し、ここでは「最低1件」のみを見る。
  // 写真は追加（append）のみで既存分の削除・置換ができない仕様のため、
  // 編集のたびに規定枚数ちょうどを要求すると、他の項目を直すだけの編集や、
  // 規定枚数変更前に作成された既存データの編集が行えなくなってしまう。
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
  // validateSubmissionFiles() と同様、写真の規定枚数（REPORT_PHOTO_COUNT）
  // ちょうどはPOST（新規提出）時のみ強制し、編集時は最低1件のみを見る。
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

    // 活動実施写真は「1枚以上」ではなく、規定枚数（2枚）ちょうどが必須。
    if (self::countUploadedFiles($_FILES['photos'] ?? null) !== self::REPORT_PHOTO_COUNT) {
      $errors[] = '活動実施写真は' . self::REPORT_PHOTO_COUNT . '枚必須です';
    }
    if (empty($_FILES['receipts']) || $_FILES['receipts']['error'][0] !== UPLOAD_ERR_OK) {
      $errors[] = '領収書は必須です';
    }

    return $errors;
  }
}
