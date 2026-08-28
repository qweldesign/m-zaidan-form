<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/SubmissionList.php';

class SubmissionListTest extends TestCase {

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

  private function createSubmission(array $overrides = []): int {
    $defaults = [
      'section1_json' => json_encode(array_merge([
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
      ], $overrides['section1'] ?? [])),
      'section2_json' => json_encode(array_merge([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
        'venue'       => 'ギャラリーはりいしゃ',
      ], $overrides['section2'] ?? [])),
      'section3_json' => json_encode([
        'income' => [
          'grantRequest' => $overrides['grantRequest'] ?? 100000,
          'memberFees' => 0, 'donations' => 0, 'tickets' => 0,
          'incomeMemo' => ['grantRequest' => '', 'memberFees' => '', 'donations' => '', 'tickets' => ''],
        ],
        'expenses'   => [],
        'budgetNote' => '',
      ]),
      'section4_json' => json_encode([]),
    ];

    $_POST  = $defaults;
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

  private function callHandleList(): array {
    ob_start();
    handleList();
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_全件取得できる(): void {
    $this->createSubmission();
    $this->createSubmission();
    $this->createSubmission();

    $response = $this->callHandleList();

    $this->assertEquals('success', $response['message']);
    $this->assertEquals(3, $response['data']['total']);
    $this->assertCount(3, $response['data']['items']);
  }

  public function test_正常系_statusでフィルタできる(): void {
    $id = $this->createSubmission();
    $this->createSubmission();

    // 1件だけ審査中に変更
    $stmt = $this->db->prepare('UPDATE submissions SET status = :status WHERE id = :id');
    $stmt->execute([':status' => '審査中', ':id' => $id]);

    $_GET     = ['status' => '審査中'];
    $response = $this->callHandleList();

    $this->assertEquals(1, $response['data']['total']);
    $this->assertEquals('審査中', $response['data']['items'][0]['status']);
  }

  public function test_正常系_keywordで団体名検索できる(): void {
    $this->createSubmission(['section1' => ['teamName' => 'ABC文化協会']]);
    $this->createSubmission(['section1' => ['teamName' => 'XYZ文化協会']]);

    $_GET     = ['keyword' => 'ABC'];
    $response = $this->callHandleList();

    $this->assertEquals(1, $response['data']['total']);
    $this->assertStringContainsString('ABC', $response['data']['items'][0]['team_name']);
  }

  public function test_正常系_keywordで事業名検索できる(): void {
    $this->createSubmission(['section2' => ['projectName' => '音楽フェスティバル']]);
    $this->createSubmission(['section2' => ['projectName' => '地域清掃活動']]);

    $_GET     = ['keyword' => '音楽'];
    $response = $this->callHandleList();

    $this->assertEquals(1, $response['data']['total']);
    $this->assertStringContainsString('音楽', $response['data']['items'][0]['project_name']);
  }

  public function test_正常系_activity_categoryでフィルタできる(): void {
    $this->createSubmission(['section1' => ['activityCategory' => 'スポーツ活動']]);
    $this->createSubmission(['section1' => ['activityCategory' => 'ボランティア活動']]);
    $this->createSubmission(['section1' => ['activityCategory' => 'スポーツ活動']]);

    $_GET     = ['activity_category' => 'スポーツ活動'];
    $response = $this->callHandleList();

    $this->assertEquals(2, $response['data']['total']);
    foreach ($response['data']['items'] as $item) {
      $this->assertEquals('スポーツ活動', $item['activity_category']);
    }
  }

  public function test_正常系_論理削除済みはデフォルトで除外される(): void {
    $id = $this->createSubmission();
    $this->createSubmission();

    // 1件を論理削除
    $stmt = $this->db->prepare('UPDATE submissions SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $response = $this->callHandleList();

    $this->assertEquals(1, $response['data']['total']);
  }

  public function test_正常系_include_deleted_1で論理削除済みを含む(): void {
    $id = $this->createSubmission();
    $this->createSubmission();

    // 1件を論理削除
    $stmt = $this->db->prepare('UPDATE submissions SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $_GET     = ['include_deleted' => '1'];
    $response = $this->callHandleList();

    $this->assertEquals(2, $response['data']['total']);
  }

  public function test_正常系_order_byとorderでソートできる(): void {
    $this->createSubmission(['grantRequest' => 100000]);
    $this->createSubmission(['grantRequest' => 300000]);
    $this->createSubmission(['grantRequest' => 200000]);

    $_GET     = ['order_by' => 'grant_request_amount', 'order' => 'DESC'];
    $response = $this->callHandleList();

    $items = $response['data']['items'];
    $this->assertEquals(300000, $items[0]['grant_request_amount']);
    $this->assertEquals(200000, $items[1]['grant_request_amount']);
    $this->assertEquals(100000, $items[2]['grant_request_amount']);
  }

  public function test_正常系_limitとoffsetでページネーションできる(): void {
    $this->createSubmission();
    $this->createSubmission();
    $this->createSubmission();
    $this->createSubmission();
    $this->createSubmission();

    $_GET     = ['limit' => '2', 'offset' => '2'];
    $response = $this->callHandleList();

    $this->assertEquals(5,  $response['data']['total']); // 総件数は5
    $this->assertCount(2,   $response['data']['items']); // 取得は2件
    $this->assertEquals(2,  $response['data']['limit']);
    $this->assertEquals(2,  $response['data']['offset']);
  }

  public function test_正常系_totalが正しく返る(): void {
    $this->createSubmission();
    $this->createSubmission();
    $this->createSubmission();

    $_GET     = ['limit' => '1'];
    $response = $this->callHandleList();

    // limitに関わらずtotalは全件数
    $this->assertEquals(3, $response['data']['total']);
    $this->assertCount(1,  $response['data']['items']);
  }

  public function test_異常系_ホワイトリスト外のorder_byはidにフォールバックされる(): void {
    $this->createSubmission();
    $this->createSubmission();

    $_GET     = ['order_by' => 'invalid_column'];
    $response = $this->callHandleList();

    // エラーにならずidでソートされて返る
    $this->assertEquals('success', $response['message']);
    $this->assertEquals(2, $response['data']['total']);
  }

}
