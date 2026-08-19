<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer {
  private static function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    return $mail;
  }

  // 申請者への受付完了メール
  public static function sendConfirmation(string $to, string $name, int $id, string $editToken): void {
    try {
      $mail = self::createMailer();
      $mail->addAddress($to, $name);
      $mail->Subject = '【三谷市民文化振興財団】助成金申請を受け付けました';
      $editUrl    = APP_URL . '/application?token=' . $editToken;
      $reportUrl  = APP_URL . '/report?token=' . $editToken;
      $receivedAt = $_SERVER['REQUEST_TIME']
        ? date('Y年m月d日 H:i')
        : date('Y年m月d日 H:i');
      $mail->Body    = <<<TEXT
{$name} 様

このたびは助成金申請をいただきありがとうございます。
以下の内容で申請を受け付けました。

受付番号：{$id}
受付日時：{$receivedAt}

内容を確認のうえ、担当者よりご連絡いたします。
しばらくお待ちください。

---
申請内容の確認・修正はこちら：
{$editUrl}

事業完了後の完了報告はこちら：
{$reportUrl}

※ 申請内容の控えは自動では送付されません。
　 必要な場合は送信前にスクリーンショットまたは印刷して保管してください。
※ キャリアメール（docomo・au・softbank等）は受信できない場合があります。
　 できる限りPCメールアドレスをご使用ください。
---

三谷市民文化振興財団
TEXT;
      $mail->send();
    } catch (Exception $e) {
      error_log('申請者へのメール送信失敗: ' . $e->getMessage());
    }
  }

  // 財団スタッフへの通知メール
  public static function sendNotification(int $id, string $applicantName): void {
    try {
      $mail = self::createMailer();
      $mail->addAddress(FOUNDATION_EMAIL, FROM_NAME);
      $mail->Subject = '【新規申請】助成金申請が届きました';
      $receivedAt = $_SERVER['REQUEST_TIME']
        ? date('Y年m月d日 H:i')
        : date('Y年m月d日 H:i');
      $mail->Body    = <<<TEXT
新規の助成金申請が届きました。

受付番号：{$id}
申請団体：{$applicantName}
受付日時：{$receivedAt}

管理画面からご確認ください。

三谷市民文化振興財団 システム
TEXT;
      $mail->send();
    } catch (Exception $e) {
      error_log('財団へのメール送信失敗: ' . $e->getMessage());
    }
  }

  // 完了報告者への受付完了メール
  public static function sendReportConfirmation(string $to, string $name, int $id, string $editToken): void {
    try {
      $mail = self::createMailer();
      $mail->addAddress($to, $name);
      $mail->Subject = '【三谷市民文化振興財団】完了報告を受け付けました';
      $editUrl       = APP_URL . '/report?token=' . $editToken;
      $receivedAt    = date('Y年m月d日 H:i');
      $mail->Body    = <<<TEXT
{$name} 様

完了報告を受け付けました。

受付番号：{$id}
受付日時：{$receivedAt}

内容を確認のうえ、担当者よりご連絡いたします。

---
完了報告内容の確認・修正はこちら：
{$editUrl}
---

三谷市民文化振興財団
TEXT;
      $mail->send();
    } catch (Exception $e) {
      error_log('完了報告者へのメール送信失敗: ' . $e->getMessage());
    }
  }

  // 財団スタッフへの通知メール
  public static function sendReportNotification(int $id, string $applicantName): void {
    try {
      $mail = self::createMailer();
      $mail->addAddress(FOUNDATION_EMAIL, FROM_NAME);
      $mail->Subject = '【完了報告】助成金事業の完了報告が届きました';
      $receivedAt    = date('Y年m月d日 H:i');
      $mail->Body    = <<<TEXT
新規の完了報告が届きました。

受付番号：{$id}
申請団体：{$applicantName}
受付日時：{$receivedAt}

管理画面からご確認ください。

三谷市民文化振興財団 システム
TEXT;
      $mail->send();
    } catch (Exception $e) {
      error_log('財団への完了報告通知メール送信失敗: ' . $e->getMessage());
    }
  }

  public static function sendStatusNotification(string $to, string $name, string $subject, string $body, ?array $attachment = null): void {
    $mail = self::createMailer();
    $mail->addAddress($to, $name);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    if ($attachment !== null) {
      $mail->addStringAttachment($attachment['content'], $attachment['filename']);
    }
    $mail->send();
  }
}
