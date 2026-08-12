<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';

class ReportPostTest extends TestCase {

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
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

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

  private function validReportPost(array $overrides = []): void {
    $_POST = array_merge([
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
        'expenses'   => [
          ['id' => '1', 'subject' => '会場費', 'amount' => 50000, 'grantUsage' => 50000, 'memo' => ''],
          ['id' => '2', 'subject' => '印刷費', 'amount' => 50000, 'grantUsage' => 50000, 'memo' => ''],
        ],
        'budgetNote' => '',
      ]),
    ], $overrides);

    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p.jpg'], 'name' => ['p.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];
  }

  private function callHandleReportPost(): array {
    ob_start();
    handleReportPost();
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_完了報告データがDBに保存される(): void {
    $this->validReportPost();
    $response = $this->callHandleReportPost();

    $this->assertEquals('完了報告を受け付けました', $response['message']);
    $this->assertArrayHasKey('id', $response['data']);

    $id   = $response['data']['id'];
    $stmt = $this->db->prepare('SELECT * FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNotFalse($row);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $row['team_name']);
    $this->assertEquals('地域交流イベント', $row['project_name']);
    $this->assertEquals('確認前',          $row['status']);
    $this->assertEquals(0,                 $row['is_deleted']);
  }

  public function test_正常系_edit_tokenが生成される(): void {
    $this->validReportPost();
    $this->callHandleReportPost();

    $stmt = $this->db->query('SELECT edit_token FROM reports LIMIT 1');
    $row  = $stmt->fetch();

    $this->assertNotEmpty($row['edit_token']);
    $this->assertEquals(32, strlen($row['edit_token']));
  }

  public function test_正常系_submission_tokenを使うとedit_tokenが共用される(): void {
    $submission = $this->createSubmission();
    $this->validReportPost(['submission_token' => $submission['edit_token']]);
    $this->callHandleReportPost();

    $stmt = $this->db->query('SELECT edit_token FROM reports LIMIT 1');
    $row  = $stmt->fetch();

    // submissions の edit_token と同じ値が使われている
    $this->assertEquals($submission['edit_token'], $row['edit_token']);
  }

  public function test_正常系_収支の合計が正しく計算される(): void {
    $this->validReportPost();
    $response = $this->callHandleReportPost();
    $id       = $response['data']['id'];

    $stmt = $this->db->prepare('SELECT * FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals(100000, $row['grant_request_amount']);
    $this->assertEquals(100000, $row['total_expense_amount']); // 50000 + 50000
    $this->assertEquals(100000, $row['grant_usage_amount']);   // 50000 + 50000
  }

  public function test_異常系_必須項目が空ならDBに保存されない(): void {
    $_POST = [
      'report_section1_json' => '{}',
      'report_section2_json' => '{}',
    ];
    $_FILES = [];

    $response = $this->callHandleReportPost();

    $this->assertArrayHasKey('error', $response);

    $stmt  = $this->db->query('SELECT COUNT(*) as cnt FROM reports');
    $count = $stmt->fetch()['cnt'];
    $this->assertEquals(0, $count);
  }

  public function test_異常系_無効なsubmission_tokenはエラーが返る(): void {
    $this->validReportPost(['submission_token' => 'invalidtoken0000invalidtoken0000']);
    $response = $this->callHandleReportPost();

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_同一submission_tokenで2回提出するとエラーが返る(): void {
    $submission = $this->createSubmission();

    // 1回目
    $this->validReportPost(['submission_token' => $submission['edit_token']]);
    $this->callHandleReportPost();

    // 2回目
    $this->validReportPost(['submission_token' => $submission['edit_token']]);
    $response = $this->callHandleReportPost();

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('この申請の完了報告はすでに提出されています。', $response['error']);
  }

}
