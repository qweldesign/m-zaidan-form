<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/SubmissionGetByToken.php';

class SubmissionGetByTokenTest extends TestCase {

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
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    ob_start();
    handlePost();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $id       = $response['data']['id'];

    // edit_token を取得
    $stmt = $this->db->prepare('SELECT edit_token FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    return ['id' => $id, 'edit_token' => $row['edit_token']];
  }

  private function callHandleGetByToken(string $token): array {
    ob_start();
    handleGetByToken($token);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_有効なトークンでデータが取得できる(): void {
    $submission = $this->createSubmission();
    $response   = $this->callHandleGetByToken($submission['edit_token']);

    $this->assertEquals('success', $response['message']);
    $this->assertEquals($submission['id'], $response['data']['id']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $response['data']['team_name']);
    $this->assertEquals('地域交流イベント', $response['data']['project_name']);
    $this->assertEquals('審査前', $response['data']['status']);
  }

  public function test_正常系_JSONカラムがデコードされて返る(): void {
    $submission = $this->createSubmission();
    $response   = $this->callHandleGetByToken($submission['edit_token']);

    $data = $response['data'];

    $this->assertIsArray($data['section1_json']);
    $this->assertIsArray($data['section2_json']);
    $this->assertIsArray($data['section3_json']);
    $this->assertIsArray($data['section4_json']);
    $this->assertIsArray($data['section5_json']);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $data['section1_json']['teamName']);
  }

  public function test_正常系_論理削除済みのデータは取得できない(): void {
    $submission = $this->createSubmission();

    // 論理削除
    $stmt = $this->db->prepare('UPDATE submissions SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $submission['id']]);

    $response = $this->callHandleGetByToken($submission['edit_token']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_空のトークンはエラーが返る(): void {
    $response = $this->callHandleGetByToken('');

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_無効なトークンはエラーが返る(): void {
    $response = $this->callHandleGetByToken('invalidtoken0000invalidtoken0000');

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

}
