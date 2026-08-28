<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/SubmissionPatch.php';

class SubmissionPatchTest extends TestCase {

  private PDO $db;

  protected function setUp(): void {
    $this->db = setupTestDb();
    $_POST    = [];
    $_FILES   = [];
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
    // （このテストの主眼はPATCHの挙動のため）
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

  // php://input をモックするために一時ファイルを使う
  private function callHandlePatch(int $id, array $body): array {
    // php://input の代替としてストリームを差し替える
    $GLOBALS['_TEST_INPUT'] = json_encode($body);

    ob_start();
    handlePatch($id);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_ステータスを変更できる(): void {
    $id       = $this->createSubmission();
    $response = $this->callHandlePatch($id, ['status' => '審査中']);

    $this->assertEquals('更新しました', $response['message']);

    $stmt = $this->db->prepare('SELECT status FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals('審査中', $row['status']);
  }

  public function test_正常系_複数フィールドを同時に変更できる(): void {
    $id = $this->createSubmission();
    $this->callHandlePatch($id, [
      'status'     => '承認',
      'team_name'  => 'はりいしゃギャラリー運営委員会',
      'venue'      => '国見公民館',
    ]);

    $stmt = $this->db->prepare('SELECT * FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals('承認',          $row['status']);
    $this->assertEquals('はりいしゃギャラリー運営委員会', $row['team_name']);
    $this->assertEquals('国見公民館', $row['venue']);
  }

  public function test_正常系_変更内容がlogsに記録される(): void {
    $id = $this->createSubmission();
    $this->callHandlePatch($id, ['status' => '審査中']);

    $stmt = $this->db->prepare('
      SELECT * FROM submission_logs
      WHERE submission_id = :id AND field_name = :field
    ');
    $stmt->execute([':id' => $id, ':field' => 'status']);
    $log  = $stmt->fetch();

    $this->assertNotFalse($log);
    $this->assertEquals('審査前', $log['old_value']);
    $this->assertEquals('審査中', $log['new_value']);
  }

  public function test_正常系_論理削除できる(): void {
    $id = $this->createSubmission();
    $this->callHandlePatch($id, ['is_deleted' => 1]);

    $stmt = $this->db->prepare('SELECT is_deleted, deleted_at FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals(1, $row['is_deleted']);
    $this->assertNotNull($row['deleted_at']);
  }

  public function test_正常系_論理削除を復元できる(): void {
    $id = $this->createSubmission();

    // 先に削除
    $this->callHandlePatch($id, ['is_deleted' => 1]);

    // 復元
    $this->callHandlePatch($id, ['is_deleted' => 0]);

    $stmt = $this->db->prepare('SELECT is_deleted, deleted_at FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertEquals(0,    $row['is_deleted']);
    $this->assertNull($row['deleted_at']);
  }

  public function test_正常系_deleted_atが論理削除時に自動セットされる(): void {
    $id = $this->createSubmission();
    $this->callHandlePatch($id, ['is_deleted' => 1]);

    $stmt = $this->db->prepare('SELECT deleted_at FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNotNull($row['deleted_at']);
    // deleted_at が現在時刻に近いことを確認
    $deletedAt = strtotime($row['deleted_at']);
    $this->assertGreaterThan(time() - 5, $deletedAt);
  }

  public function test_正常系_deleted_atが復元時にクリアされる(): void {
    $id = $this->createSubmission();
    $this->callHandlePatch($id, ['is_deleted' => 1]);
    $this->callHandlePatch($id, ['is_deleted' => 0]);

    $stmt = $this->db->prepare('SELECT deleted_at FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNull($row['deleted_at']);
  }

  public function test_正常系_ホワイトリスト外のフィールドは無視される(): void {
    $id = $this->createSubmission();
    $this->callHandlePatch($id, [
      'status'     => '審査中',
      'edit_token' => 'hacked_token',  // ホワイトリスト外
    ]);

    $stmt = $this->db->prepare('SELECT edit_token FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    // edit_token は変更されていない
    $this->assertNotEquals('hacked_token', $row['edit_token']);
  }

  public function test_異常系_存在しないIDはエラーが返る(): void {
    $response = $this->callHandlePatch(99999, ['status' => '審査中']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('申請データが見つかりません', $response['error']);
  }

  public function test_異常系_空のリクエストボディはエラーが返る(): void {
    $id = $this->createSubmission();
    $GLOBALS['_TEST_INPUT'] = '';

    ob_start();
    handlePatch($id);
    $output   = ob_get_clean();
    $response = json_decode($output, true) ?? [];

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('リクエストボディが不正です', $response['error']);
  }

}
