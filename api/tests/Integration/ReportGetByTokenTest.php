<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';
require_once __DIR__ . '/../../handlers/ReportGetByToken.php';

class ReportGetByTokenTest extends TestCase {

  private PDO $db;

  protected function setUp(): void {
    $this->db = setupTestDb();
    $_POST    = [];
    $_FILES   = [];
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
    $id       = $response['data']['id'];

    $stmt = $this->db->prepare('SELECT edit_token FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    return ['id' => $id, 'edit_token' => $row['edit_token']];
  }

  private function createReport(string $submissionToken): array {
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
      'photos'   => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p.jpg'], 'name' => ['p.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    ob_start();
    handleReportPost();
    $output   = ob_get_clean();
    $response = json_decode($output, true);
    $id       = $response['data']['id'];

    $stmt = $this->db->prepare('SELECT edit_token FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    return ['id' => $id, 'edit_token' => $row['edit_token']];
  }

  private function callHandleReportGetByToken(string $token): array {
    ob_start();
    handleReportGetByToken($token);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_有効なトークンでデータが取得できる(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);
    $response   = $this->callHandleReportGetByToken($report['edit_token']);

    $this->assertEquals('success',         $response['message']);
    $this->assertEquals($report['id'],     $response['data']['id']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $response['data']['team_name']);
    $this->assertEquals('地域交流イベント', $response['data']['project_name']);
    $this->assertEquals('確認前',           $response['data']['status']);
  }

  public function test_正常系_JSONカラムがデコードされて返る(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);
    $response   = $this->callHandleReportGetByToken($report['edit_token']);

    $data = $response['data'];

    $this->assertIsArray($data['report_section1_json']);
    $this->assertIsArray($data['report_section2_json']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $data['report_section1_json']['teamName']);
    $this->assertEquals('地域交流イベント', $data['report_section2_json']['projectName']);
  }

  public function test_正常系_論理削除済みのデータは取得できない(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);

    $stmt = $this->db->prepare('UPDATE reports SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);

    $response = $this->callHandleReportGetByToken($report['edit_token']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_無効なトークンはエラーが返る(): void {
    $response = $this->callHandleReportGetByToken('invalidtoken0000invalidtoken0000');

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_空のトークンはエラーが返る(): void {
    $response = $this->callHandleReportGetByToken('');

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

}
