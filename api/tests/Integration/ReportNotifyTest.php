<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';
require_once __DIR__ . '/../../handlers/ReportPatch.php';
require_once __DIR__ . '/../../handlers/ReportNotify.php';

class ReportNotifyTest extends TestCase {

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

  private function createSubmission(): array {
    $_POST = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamNameKana'        => 'テストブンカキョウカイ',
        'teamPostalCode'      => '910-0000',
        'teamAddress'         => '福井県福井市中央1-1',
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
      'photos'          => ['error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/photo1.jpg', '/tmp/photo2.jpg', '/tmp/photo3.jpg'], 'name' => ['photo1.jpg', 'photo2.jpg', 'photo3.jpg']],
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
    $id       = $response['data']['id'];

    $stmt = $this->db->prepare('SELECT edit_token FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    return ['id' => $id, 'edit_token' => $row['edit_token']];
  }

  private function createReport(string $submissionToken): int {
    $_POST = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
        'contactPhone' => '090-1234-5678',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'activityCategory'=> 'その他市民活動',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'actualDetail'    => '実施内容のテキスト',
        'income' => [
          'grantRequest' => 100000, 'memberFees' => 0, 'donations' => 0, 'tickets' => 0,
          'incomeMemo'   => ['grantRequest' => '', 'memberFees' => '', 'donations' => '', 'tickets' => ''],
        ],
        'expenses'   => [],
        'budgetNote' => '',
      ]),
      'submission_token' => $submissionToken,
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg'], 'name' => ['p1.jpg', 'p2.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    ob_start();
    handleReportPost();
    $output   = ob_get_clean();
    $response = json_decode($output, true);
    return $response['data']['id'];
  }

  private function setReportStatus(int $id, string $status): void {
    $GLOBALS['_TEST_INPUT'] = json_encode(['status' => $status]);
    ob_start();
    handleReportPatch($id);
    ob_get_clean();
  }

  private function callHandleNotify(int $id): array {
    ob_start();
    handleReportNotify($id);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_通知メールが送信される(): void {
    $submission = $this->createSubmission();
    $reportId   = $this->createReport($submission['edit_token']);
    $this->setReportStatus($reportId, '要修正');

    $response = $this->callHandleNotify($reportId);

    $this->assertTrue($response['data']['sent']);
    $this->assertCount(1, Mailer::$statusNotifications);
  }

  public function test_正常系_対象外ステータスの場合は送信されない(): void {
    $submission = $this->createSubmission();
    $reportId   = $this->createReport($submission['edit_token']);
    $this->setReportStatus($reportId, '対象外');

    $response = $this->callHandleNotify($reportId);

    $this->assertFalse($response['data']['sent']);
    $this->assertCount(0, Mailer::$statusNotifications);
  }

  // 団体名がメール本文に含まれることを確認する（2ステータス共通）
  public function test_正常系_メール本文に団体名が含まれる(): void {
    foreach (['要修正', '確認済'] as $status) {
      Mailer::$statusNotifications = [];

      $submission = $this->createSubmission();
      $reportId   = $this->createReport($submission['edit_token']);
      $this->setReportStatus($reportId, $status);
      $this->callHandleNotify($reportId);

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
