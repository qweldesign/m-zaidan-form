<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/SubmissionPatch.php';
require_once __DIR__ . '/../../handlers/SubmissionNotify.php';

class SubmissionNotifyTest extends TestCase {

  private PDO $db;

  protected function setUp(): void {
    $this->db = setupTestDb();
    $_POST    = [];
    $_FILES   = [];
    Mailer::$statusNotifications = [];
  }

  //
  // ヘルパーメソッド
  //

  private function createSubmission(): int {
    $_POST = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamNameKana'        => 'ギャラリーハリイシャウンエイイインカイ',
        'teamPostalCode'      => '910-3553',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'establishedYear'     => '2000',
        'activityCategory'    => 'その他市民活動',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'representativePhone' => '090-1234-5678',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'hariisha@example.com',
        'contactPhone'        => '090-1234-5678',
        'sameAsRepresentative'=> true,
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
        'venue'       => 'ギャラリーはりいしゃ',
      ]),
      'section3_json' => json_encode([
        'income' => [
          'grantRequest' => 100000, 'memberFees' => 0, 'donations' => 0, 'tickets' => 0,
          'incomeMemo'   => ['grantRequest' => '', 'memberFees' => '', 'donations' => '', 'tickets' => ''],
        ],
        'expenses'   => [],
        'budgetNote' => '',
      ]),
      'section4_json' => json_encode([]),
    ];
    // 要望申請は写真・PDF5種が必須になったため、全て揃った状態にする
    $_FILES = [
      'photos'          => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/photo.jpg'], 'name' => ['photo.jpg']],
      'regulations'     => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/regulations.pdf',     'name' => 'regulations.pdf'],
      'activityReport'  => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/activityReport.pdf',  'name' => 'activityReport.pdf'],
      'financialReport' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/financialReport.pdf', 'name' => 'financialReport.pdf'],
      'activityPlan'    => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/activityPlan.pdf',    'name' => 'activityPlan.pdf'],
      'financialPlan'   => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/financialPlan.pdf',   'name' => 'financialPlan.pdf'],
    ];

    ob_start();
    handlePost();
    $output   = ob_get_clean();
    $response = json_decode($output, true);
    return $response['data']['id'];
  }

  private function setSubmissionStatus(int $id, string $status): void {
    $GLOBALS['_TEST_INPUT'] = json_encode(['status' => $status]);
    ob_start();
    handlePatch($id);
    ob_get_clean();
  }

  private function callHandleNotify(int $id, array $body = []): array {
    $GLOBALS['_TEST_INPUT'] = json_encode($body);
    ob_start();
    handleSubmissionNotify($id);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_PDF添付なしで通知メールが送信される(): void {
    $id = $this->createSubmission();
    $this->setSubmissionStatus($id, '審査中');

    $response = $this->callHandleNotify($id);

    $this->assertTrue($response['data']['sent']);
    $this->assertCount(1, Mailer::$statusNotifications);
    $this->assertNull(Mailer::$statusNotifications[0]['attachment']);
  }

  public function test_正常系_審査中への変更でPDFが添付される(): void {
    $id = $this->createSubmission();
    $this->setSubmissionStatus($id, '審査中');

    $pdfContent = '%PDF-1.4 dummy pdf content';
    $response = $this->callHandleNotify($id, ['pdf' => base64_encode($pdfContent)]);

    $this->assertTrue($response['data']['sent']);
    $this->assertCount(1, Mailer::$statusNotifications);
    $attachment = Mailer::$statusNotifications[0]['attachment'];
    $this->assertNotNull($attachment);
    $this->assertSame($pdfContent, $attachment['content']);
    $this->assertSame("submission_{$id}.pdf", $attachment['filename']);
  }

  public function test_異常系_不正なbase64は添付なしとして扱われる(): void {
    $id = $this->createSubmission();
    $this->setSubmissionStatus($id, '審査中');

    $response = $this->callHandleNotify($id, ['pdf' => '!!!not-valid-base64!!!']);

    $this->assertTrue($response['data']['sent']);
    $this->assertCount(1, Mailer::$statusNotifications);
    $this->assertNull(Mailer::$statusNotifications[0]['attachment']);
  }

  public function test_正常系_対象外ステータスの場合は送信されない(): void {
    $id = $this->createSubmission();
    $this->setSubmissionStatus($id, '対象外');

    $response = $this->callHandleNotify($id);

    $this->assertFalse($response['data']['sent']);
    $this->assertCount(0, Mailer::$statusNotifications);
  }

  // 団体名がメール本文に含まれることを確認する（4ステータス共通）
  public function test_正常系_メール本文に団体名が含まれる(): void {
    foreach (['審査前', '審査中', '承認', '否決'] as $status) {
      Mailer::$statusNotifications = [];

      $id = $this->createSubmission();
      $this->setSubmissionStatus($id, $status);
      $this->callHandleNotify($id);

      $this->assertCount(1, Mailer::$statusNotifications, "status={$status}");
      $body = Mailer::$statusNotifications[0]['body'];
      $this->assertStringContainsString(
        'ギャラリーはりいしゃ運営委員会',
        $body,
        "status={$status} の本文に団体名が含まれていない"
      );
    }
  }
}
