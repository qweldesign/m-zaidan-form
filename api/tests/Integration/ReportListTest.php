<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';
require_once __DIR__ . '/../../handlers/ReportList.php';

class ReportListTest extends TestCase {

  private PDO $db;

  protected function setUp(): void {
    $this->db = setupTestDb();
    $_POST    = [];
    $_FILES   = [];
    $_GET     = [];
  }

  //
  // ヘルパーメソッド
  //

  private function createReport(array $overrides = []): int {
    $_POST = [
      'report_section1_json' => json_encode(array_merge([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
        'contactPhone' => '090-1234-5678',
      ], $overrides['section1'] ?? [])),
      'report_section2_json' => json_encode(array_merge([
        'projectName'     => '地域交流イベント',
        'activityCategory'=> 'その他市民活動',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'actualDetail'    => '実施内容のテキスト',
        'income' => [
          'grantRequest' => $overrides['grantRequest'] ?? 100000,
          'memberFees' => 0, 'donations' => 0, 'tickets' => 0,
          'incomeMemo' => ['grantRequest' => '', 'memberFees' => '', 'donations' => '', 'tickets' => ''],
        ],
        'expenses'   => [],
        'budgetNote' => '',
      ], $overrides['section2'] ?? [])),
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

  private function callHandleReportList(): array {
    ob_start();
    handleReportList();
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_全件取得できる(): void {
    $this->createReport();
    $this->createReport();
    $this->createReport();

    $response = $this->callHandleReportList();

    $this->assertEquals('success', $response['message']);
    $this->assertEquals(3, $response['data']['total']);
    $this->assertCount(3, $response['data']['items']);
  }

  public function test_正常系_statusでフィルタできる(): void {
    $id = $this->createReport();
    $this->createReport();

    $stmt = $this->db->prepare('UPDATE reports SET status = :status WHERE id = :id');
    $stmt->execute([':status' => '確認済', ':id' => $id]);

    $_GET     = ['status' => '確認済'];
    $response = $this->callHandleReportList();

    $this->assertEquals(1, $response['data']['total']);
    $this->assertEquals('確認済', $response['data']['items'][0]['status']);
  }

  public function test_正常系_keywordで団体名検索できる(): void {
    $this->createReport(['section1' => ['teamName' => 'ABC文化協会']]);
    $this->createReport(['section1' => ['teamName' => 'XYZ文化協会']]);

    $_GET     = ['keyword' => 'ABC'];
    $response = $this->callHandleReportList();

    $this->assertEquals(1, $response['data']['total']);
    $this->assertStringContainsString('ABC', $response['data']['items'][0]['team_name']);
  }

  public function test_正常系_keywordで事業名検索できる(): void {
    $this->createReport(['section2' => ['projectName' => '音楽フェスティバル']]);
    $this->createReport(['section2' => ['projectName' => '地域清掃活動']]);

    $_GET     = ['keyword' => '音楽'];
    $response = $this->callHandleReportList();

    $this->assertEquals(1, $response['data']['total']);
    $this->assertStringContainsString('音楽', $response['data']['items'][0]['project_name']);
  }

  public function test_正常系_activity_categoryでフィルタできる(): void {
    $this->createReport(['section2' => ['activityCategory' => 'スポーツ活動']]);
    $this->createReport(['section2' => ['activityCategory' => 'ボランティア活動']]);
    $this->createReport(['section2' => ['activityCategory' => 'スポーツ活動']]);

    $_GET     = ['activity_category' => 'スポーツ活動'];
    $response = $this->callHandleReportList();

    $this->assertEquals(2, $response['data']['total']);
    foreach ($response['data']['items'] as $item) {
      $this->assertEquals('スポーツ活動', $item['activity_category']);
    }
  }

  public function test_正常系_yearで実施年フィルタできる(): void {
    $this->createReport(['section2' => ['actualStartDate' => '2024-06-01']]);
    $this->createReport(['section2' => ['actualStartDate' => '2025-06-01']]);
    $this->createReport(['section2' => ['actualStartDate' => '2025-09-01']]);

    $_GET     = ['year' => '2025'];
    $response = $this->callHandleReportList();

    $this->assertEquals(2, $response['data']['total']);
    foreach ($response['data']['items'] as $item) {
      $this->assertStringStartsWith('2025', $item['actual_start_date']);
    }
  }

  public function test_正常系_論理削除済みはデフォルトで除外される(): void {
    $id = $this->createReport();
    $this->createReport();

    $stmt = $this->db->prepare('UPDATE reports SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $response = $this->callHandleReportList();

    $this->assertEquals(1, $response['data']['total']);
  }

  public function test_正常系_include_deleted_1で論理削除済みを含む(): void {
    $id = $this->createReport();
    $this->createReport();

    $stmt = $this->db->prepare('UPDATE reports SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $_GET     = ['include_deleted' => '1'];
    $response = $this->callHandleReportList();

    $this->assertEquals(2, $response['data']['total']);
  }

  public function test_正常系_order_byとorderでソートできる(): void {
    $this->createReport(['grantRequest' => 100000]);
    $this->createReport(['grantRequest' => 300000]);
    $this->createReport(['grantRequest' => 200000]);

    $_GET     = ['order_by' => 'grant_request_amount', 'order' => 'DESC'];
    $response = $this->callHandleReportList();

    $items = $response['data']['items'];
    $this->assertEquals(300000, $items[0]['grant_request_amount']);
    $this->assertEquals(200000, $items[1]['grant_request_amount']);
    $this->assertEquals(100000, $items[2]['grant_request_amount']);
  }

  public function test_正常系_limitとoffsetでページネーションできる(): void {
    $this->createReport();
    $this->createReport();
    $this->createReport();
    $this->createReport();
    $this->createReport();

    $_GET     = ['limit' => '2', 'offset' => '2'];
    $response = $this->callHandleReportList();

    $this->assertEquals(5, $response['data']['total']);
    $this->assertCount(2,  $response['data']['items']);
    $this->assertEquals(2, $response['data']['limit']);
    $this->assertEquals(2, $response['data']['offset']);
  }

  public function test_正常系_totalが正しく返る(): void {
    $this->createReport();
    $this->createReport();
    $this->createReport();

    $_GET     = ['limit' => '1'];
    $response = $this->callHandleReportList();

    $this->assertEquals(3, $response['data']['total']);
    $this->assertCount(1,  $response['data']['items']);
  }

  public function test_異常系_ホワイトリスト外のorder_byはidにフォールバックされる(): void {
    $this->createReport();
    $this->createReport();

    $_GET     = ['order_by' => 'invalid_column'];
    $response = $this->callHandleReportList();

    $this->assertEquals('success', $response['message']);
    $this->assertEquals(2, $response['data']['total']);
  }

}
