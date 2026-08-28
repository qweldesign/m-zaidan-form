<?php

if (!class_exists('Mailer')) {
  require_once __DIR__ . '/../helpers/Mailer.php';
}

function handleReportNotify(int $id): void {
  $db = getDB();

  $stmt = $db->prepare("
    SELECT status, team_name, contact_name, contact_email,
           project_name, edit_token
    FROM reports WHERE id = :id AND is_deleted = 0
  ");
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();

  if (!$row) {
    http_response_code(404);
    Response::error('完了報告が見つかりません');
    return;
  }

  $status    = $row['status'];
  $teamName  = $row['team_name'];
  $toName    = $row['contact_name'];
  $toEmail   = $row['contact_email'];
  $project   = $row['project_name'];
  $editToken = $row['edit_token'];
  $editUrl   = APP_URL . '/report?token=' . $editToken;

  $subjects = [
    '要修正' => '【三谷市民文化振興財団】完了報告の修正をお願いします',
    '確認済' => '【三谷市民文化振興財団】完了報告を確認しました',
  ];

  $bodies = [
    '要修正' => "
{$teamName}
{$toName} 様

「{$project}」の完了報告について、内容の修正をお願いします。
以下のURLから内容をご確認・編集いただけます。

{$editUrl}

ご不明な点がございましたら財団事務局までお問い合わせください。
",
    '確認済' => "
{$teamName}
{$toName} 様

「{$project}」の完了報告を確認しました。
ご報告いただきありがとうございました。

ご不明な点がございましたら財団事務局までお問い合わせください。
",
  ];

  if (!isset($subjects[$status])) {
    Response::success(['sent' => false]);
    return;
  }

  Mailer::sendStatusNotification($toEmail, $toName, $subjects[$status], $bodies[$status]);
  Response::success(['sent' => true]);
}
