<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';
require_once __DIR__ . '/../../handlers/ReportGet.php';

class ReportGetTest extends TestCase {

  private PDO $db;

  protected function setUp(): void {
    $this->db = setupTestDb();
    $_POST    = [];
    $_FILES   = [];
  }

  //
  // ヘルパーメソッド
  //

  private function createReport(): int {
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
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p.jpg'], 'name' => ['p.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    ob_start();
    handleReportPost();
    $output   = ob_get_clean();
    $response = json_decode($output, true);
    return $response['data']['id'];
  }

  private function callHandleReportGet(int $id): array {
    ob_start();
    handleReportGet($id);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_存在するIDでデータが取得できる(): void {
    $id       = $this->createReport();
    $response = $this->callHandleReportGet($id);

    $this->assertEquals('success',         $response['message']);
    $this->assertEquals($id,               $response['data']['id']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $response['data']['team_name']);
    $this->assertEquals('地域交流イベント', $response['data']['project_name']);
    $this->assertEquals('確認前',           $response['data']['status']);
  }

  public function test_正常系_JSONカラムがデコードされて返る(): void {
    $id       = $this->createReport();
    $response = $this->callHandleReportGet($id);

    $data = $response['data'];

    $this->assertIsArray($data['report_section1_json']);
    $this->assertIsArray($data['report_section2_json']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $data['report_section1_json']['teamName']);
    $this->assertEquals('地域交流イベント', $data['report_section2_json']['projectName']);
  }

  // アプリの詳細パネルは、一覧で「削除済みを含む」表示にした際にも該当行を
  // クリックして閲覧・復元できる必要があるため、論理削除済みのデータも
  // 個別取得（GET /api/reports/:id）では取得できる仕様になっている
  // （一覧取得・CSVエクスポートでは is_deleted = 0 のもののみに絞り込まれる）
  public function test_正常系_論理削除済みのデータも個別取得できる(): void {
    $id = $this->createReport();

    $stmt = $this->db->prepare('UPDATE reports SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $response = $this->callHandleReportGet($id);

    $this->assertEquals('success', $response['message']);
    $this->assertEquals($id, $response['data']['id']);
    $this->assertEquals(1, $response['data']['is_deleted']);
  }

  public function test_異常系_存在しないIDはエラーが返る(): void {
    $response = $this->callHandleReportGet(99999);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('完了報告データが見つかりません', $response['error']);
  }

}
