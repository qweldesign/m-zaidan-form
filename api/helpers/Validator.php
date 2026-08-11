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
