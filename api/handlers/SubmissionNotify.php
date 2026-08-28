<?php

if (!class_exists('Mailer')) {
  require_once __DIR__ . '/../helpers/Mailer.php';
}

function handleSubmissionNotify(int $id): void {
  $db = getDB();

  $stmt = $db->prepare("
    SELECT status, contact_name, contact_email,
           representative_name, representative_email,
           project_name, edit_token
    FROM submissions WHERE id = :id AND is_deleted = 0
  ");
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();

  if (!$row) {
    http_response_code(404);
    Response::error('申請が見つかりません');
    return;
  }

  $status    = $row['status'];
  $toName    = $row['contact_name'] ?: $row['representative_name'];
  $toEmail   = $row['contact_email'] ?: $row['representative_email'];
  $project   = $row['project_name'];
  $editToken = $row['edit_token'];
  $editUrl   = APP_URL . '/application?token=' . $editToken;

  $subjects = [
    '審査前' => '【三谷市民文化振興財団】申請内容の編集が可能になりました',
    '審査中' => '【三谷市民文化振興財団】申請を開始いたしました',
    '承認'   => '【三谷市民文化振興財団】助成金申請が受理されました',
    '否決'   => '【三谷市民文化振興財団】助成金申請の審査結果について',
  ];

  $bodies = [
    '審査前' => "
{$toName} 様

「{$project}」の申請内容が編集可能な状態に戻りました。
以下のURLから内容をご確認・編集いただけます。

{$editUrl}

ご不明な点がございましたら財団事務局までお問い合わせください。
",
    '審査中' => "
{$toName} 様

「{$project}」の審査を開始いたしました。
審査中は申請内容の編集ができません。
審査結果が出次第、改めてご連絡いたします。

ご不明な点がございましたら財団事務局までお問い合わせください。
",
    '承認' => "
{$toName} 様

「{$project}」の助成金申請が受理されました。

ご不明な点がございましたら財団事務局までお問い合わせください。
",
    '否決' => "
{$toName} 様

「{$project}」の助成金申請について、
今回は採択に至りませんでした。
ご応募いただきありがとうございました。

ご不明な点がございましたら財団事務局までお問い合わせください。
",
  ];

  if (!isset($subjects[$status])) {
    Response::success(['sent' => false]);
    return;
  }

  // デスクトップアプリから送信されたPDF（審査中への変更時、任意）を添付する
  $input = isset($GLOBALS['_TEST_INPUT'])
    ? $GLOBALS['_TEST_INPUT']
    : file_get_contents('php://input');
  $requestBody = json_decode($input ?: '', true) ?? [];

  $attachment = null;
  if (!empty($requestBody['pdf']) && is_string($requestBody['pdf'])) {
    $pdfContent = base64_decode($requestBody['pdf'], true);
    if ($pdfContent !== false) {
      $attachment = [
        'content'  => $pdfContent,
        'filename' => "submission_{$id}.pdf",
      ];
    }
  }

  Mailer::sendStatusNotification($toEmail, $toName, $subjects[$status], $bodies[$status], $attachment);
  Response::success(['sent' => true]);
}
