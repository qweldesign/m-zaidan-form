<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';
require_once __DIR__ . '/../../handlers/ReportPatch.php';

class ReportPatchTest extends TestCase {

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
      'photos'   => ['error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg'], 'name' => ['p1.jpg', 'p2.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    ob_start();
    handleReportPost();
    $output   = ob_get_clean();
    $response = json_decode($output, true);
    return $response['data']['id'];
  }

  private function callHandleReportPatch(int $id, array $body): array {
    $GLOBALS['_TEST_INPUT'] = json_encode($body);

    ob_start();
    handleReportPatch($id);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_ステータスを変更できる(): void {
    $id       = $this->createReport();
    $response = $this->callHandleReportPatch($id, ['status' => '確認済']);

    $this->assertEquals('更新しました', $response['message']);

    $stmt = $this->db->prepare('SELECT status FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals('確認済', $row['status']);
  }

  public function test_正常系_論理削除できる(): void {
    $id = $this->createReport();
    $this->callHandleReportPatch($id, ['is_deleted' => 1]);

    $stmt = $this->db->prepare('SELECT is_deleted, deleted_at FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals(1,    $row['is_deleted']);
    $this->assertNotNull($row['deleted_at']);
  }

  public function test_正常系_論理削除を復元できる(): void {
    $id = $this->createReport();
    $this->callHandleReportPatch($id, ['is_deleted' => 1]);
    $this->callHandleReportPatch($id, ['is_deleted' => 0]);

    $stmt = $this->db->prepare('SELECT is_deleted, deleted_at FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals(0,    $row['is_deleted']);
    $this->assertNull($row['deleted_at']);
  }

  public function test_正常系_deleted_atが論理削除時に自動セットされる(): void {
    $id = $this->createReport();
    $this->callHandleReportPatch($id, ['is_deleted' => 1]);

    $stmt = $this->db->prepare('SELECT deleted_at FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNotNull($row['deleted_at']);
    $deletedAt = strtotime($row['deleted_at']);
    $this->assertGreaterThan(time() - 5, $deletedAt);
  }

  public function test_正常系_deleted_atが復元時にクリアされる(): void {
    $id = $this->createReport();
    $this->callHandleReportPatch($id, ['is_deleted' => 1]);
    $this->callHandleReportPatch($id, ['is_deleted' => 0]);

    $stmt = $this->db->prepare('SELECT deleted_at FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNull($row['deleted_at']);
  }

  public function test_正常系_ホワイトリスト外のフィールドは無視される(): void {
    $id = $this->createReport();
    $this->callHandleReportPatch($id, [
      'status'     => '確認済',
      'edit_token' => 'hacked_token',  // ホワイトリスト外
    ]);

    $stmt = $this->db->prepare('SELECT edit_token FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNotEquals('hacked_token', $row['edit_token']);
  }

  public function test_異常系_存在しないIDはエラーが返る(): void {
    $response = $this->callHandleReportPatch(99999, ['status' => '確認済']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('完了報告データが見つかりません', $response['error']);
  }

  public function test_異常系_空のリクエストボディはエラーが返る(): void {
    $id = $this->createReport();
    $GLOBALS['_TEST_INPUT'] = '';

    ob_start();
    handleReportPatch($id);
    $output   = ob_get_clean();
    $response = json_decode($output, true) ?? [];

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('リクエストボディが不正です', $response['error']);
  }

}
