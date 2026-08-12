<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/SubmissionGet.php';

class SubmissionGetTest extends TestCase {

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
        'income' => ['grantRequest' => 100000, 'memberFees' => 0, 'donations' => 0, 'tickets' => 0,
          'incomeMemo' => ['grantRequest' => '', 'memberFees' => '', 'donations' => '', 'tickets' => '']],
        'expenses'   => [],
        'budgetNote' => '',
      ]),
      'section4_json' => json_encode([]),
    ];
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    ob_start();
    handlePost();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    return $response['data']['id'];
  }

  private function callHandleGet(int $id): array {
    ob_start();
    handleGet($id);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_存在するIDでデータが取得できる(): void {
    $id       = $this->createSubmission();
    $response = $this->callHandleGet($id);

    $this->assertEquals('success', $response['message']);
    $this->assertEquals($id, $response['data']['id']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $response['data']['team_name']);
    $this->assertEquals('地域交流イベント', $response['data']['project_name']);
    $this->assertEquals('審査前', $response['data']['status']);
  }

  public function test_正常系_JSONカラムがデコードされて返る(): void {
    $id       = $this->createSubmission();
    $response = $this->callHandleGet($id);

    $data = $response['data'];

    // JSONカラムが配列としてデコードされている
    $this->assertIsArray($data['section1_json']);
    $this->assertIsArray($data['section2_json']);
    $this->assertIsArray($data['section3_json']);
    $this->assertIsArray($data['section4_json']);
    $this->assertIsArray($data['section5_json']);

    // section1_jsonの中身が正しい
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $data['section1_json']['teamName']);
  }

  public function test_正常系_論理削除済みのデータは取得できない(): void {
    $id = $this->createSubmission();

    // 論理削除
    $stmt = $this->db->prepare('UPDATE submissions SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $response = $this->callHandleGet($id);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('申請データが見つかりません', $response['error']);
  }

  public function test_異常系_存在しないIDはエラーが返る(): void {
    $response = $this->callHandleGet(99999);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('申請データが見つかりません', $response['error']);
  }

}
