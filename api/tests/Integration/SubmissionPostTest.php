<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../helpers/Validator.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';

class SubmissionPostTest extends TestCase {

  private PDO $db;

  protected function setUp(): void {
    $this->db = setupTestDb();

    // $_POST・$_FILES をリセット
    $_POST  = [];
    $_FILES = [];
  }

  //
  // ヘルパーメソッド
  //

  // 最小限の正常系データを生成
  private function validSection1(): array {
    return [
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
    ];
  }

  private function validSection2(): array {
    return [
      'projectName' => '地域交流イベント',
      'startDate'   => '2025-06-01',
      'endDate'     => '2025-06-30',
      'venue'       => 'ギャラリーはりいしゃ',
    ];
  }

  private function validSection3(): array {
    return [
      'income' => [
        'grantRequest' => 100000,
        'memberFees'   => 0,
        'donations'    => 0,
        'tickets'      => 0,
        'incomeMemo'   => [
          'grantRequest' => '',
          'memberFees'   => '',
          'donations'    => '',
          'tickets'      => '',
        ],
      ],
      'expenses' => [
        ['id' => '1', 'subject' => '会場費', 'amount' => 50000, 'grantUsage' => 50000, 'memo' => ''],
        ['id' => '2', 'subject' => '印刷費', 'amount' => 50000, 'grantUsage' => 50000, 'memo' => ''],
      ],
      'budgetNote' => '',
    ];
  }

  // handlePost() を呼び出してレスポンスを返す
  private function callHandlePost(): array {
    ob_start();
    try {
      handlePost();
    } catch (Exception $e) {
      ob_end_clean();
      throw $e;
    }
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_申請データがDBに保存される(): void {
    $_POST = [
      'section1_json' => json_encode($this->validSection1()),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [
      'photos' => ['error' => [], 'tmp_name' => [], 'name' => []],
    ];

    $response = $this->callHandlePost();

    // レスポンスの確認
    $this->assertEquals('申請を受け付けました', $response['message']);
    $this->assertArrayHasKey('id', $response['data']);

    // DBに実際に保存されているか確認
    $id   = $response['data']['id'];
    $stmt = $this->db->prepare('SELECT * FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $this->assertNotFalse($row);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $row['team_name']);
    $this->assertEquals('地域交流イベント', $row['project_name']);
    $this->assertEquals(100000, $row['grant_request_amount']);
    $this->assertEquals(100000, $row['total_expense_amount']);
    $this->assertEquals(100000, $row['grant_usage_amount']);
    $this->assertEquals('審査前', $row['status']);
    $this->assertNotEmpty($row['edit_token']);
    $this->assertEquals(0, $row['is_deleted']);
  }

  public function test_正常系_edit_tokenが生成される(): void {
    $_POST = [
      'section1_json' => json_encode($this->validSection1()),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [
      'photos' => ['error' => [], 'tmp_name' => [], 'name' => []],
    ];

    $this->callHandlePost();

    $stmt = $this->db->query('SELECT edit_token FROM submissions LIMIT 1');
    $row  = $stmt->fetch();

    $this->assertNotEmpty($row['edit_token']);
    $this->assertEquals(32, strlen($row['edit_token'])); // bin2hex(16) = 32文字
  }

  public function test_正常系_複数申請でedit_tokenが重複しない(): void {
    $_POST = [
      'section1_json' => json_encode($this->validSection1()),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [
      'photos' => ['error' => [], 'tmp_name' => [], 'name' => []],
    ];

    // 1件目
    $this->callHandlePost();

    // 2件目
    $this->callHandlePost();

    $stmt   = $this->db->query('SELECT edit_token FROM submissions');
    $tokens = array_column($stmt->fetchAll(), 'edit_token');

    // 2件登録されている
    $this->assertCount(2, $tokens);

    // トークンが重複していない
    $this->assertEquals(2, count(array_unique($tokens)));
  }

  public function test_異常系_必須項目が空ならDBに保存されない(): void {
    $_POST = [
      'section1_json' => '{}',  // 空
      'section2_json' => '{}',  // 空
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [];

    $response = $this->callHandlePost();

    // エラーレスポンスが返る
    $this->assertArrayHasKey('error', $response);

    // DBに保存されていないことを確認
    $stmt  = $this->db->query('SELECT COUNT(*) as cnt FROM submissions');
    $count = $stmt->fetch()['cnt'];
    $this->assertEquals(0, $count);
  }
  
  public function test_正常系_same_as_representativeがtrueのとき1が保存される(): void {
    $s1 = $this->validSection1();
    $s1['sameAsRepresentative'] = true;

    $_POST = [
      'section1_json' => json_encode($s1),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    $this->callHandlePost();

    $stmt = $this->db->query('SELECT same_as_representative FROM submissions LIMIT 1');
    $row  = $stmt->fetch();

    $this->assertEquals(1, $row['same_as_representative']);
  }

  public function test_正常系_same_as_representativeがfalseのとき0が保存される(): void {
    $s1 = $this->validSection1();
    $s1['sameAsRepresentative'] = false;

    $_POST = [
      'section1_json' => json_encode($s1),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    $this->callHandlePost();

    $stmt = $this->db->query('SELECT same_as_representative FROM submissions LIMIT 1');
    $row  = $stmt->fetch();

    $this->assertEquals(0, $row['same_as_representative']);
  }

  public function test_正常系_収支の合計が正しく計算される(): void {
    $section3 = $this->validSection3();
    // 支出を変更して合計が変わることを確認
    $section3['expenses'] = [
      ['id' => '1', 'subject' => '会場費', 'amount' => 80000, 'grantUsage' => 60000, 'memo' => ''],
      ['id' => '2', 'subject' => '印刷費', 'amount' => 20000, 'grantUsage' => 20000, 'memo' => ''],
    ];

    $_POST = [
      'section1_json' => json_encode($this->validSection1()),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($section3),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [
      'photos' => ['error' => [], 'tmp_name' => [], 'name' => []],
    ];

    $this->callHandlePost();

    $stmt = $this->db->query('SELECT * FROM submissions LIMIT 1');
    $row  = $stmt->fetch();

    $this->assertEquals(100000, $row['grant_request_amount']);
    $this->assertEquals(100000, $row['total_expense_amount']);  // 80000 + 20000
    $this->assertEquals(80000,  $row['grant_usage_amount']);    // 60000 + 20000
  }

  public function test_正常系_section5_jsonが保存される(): void {
    $_POST = [
      'section1_json' => json_encode($this->validSection1()),
      'section2_json' => json_encode($this->validSection2()),
      'section3_json' => json_encode($this->validSection3()),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [
      'photos' => ['error' => [], 'tmp_name' => [], 'name' => []],
    ];

    $response = $this->callHandlePost();
    $id       = $response['data']['id'];

    $stmt = $this->db->prepare('SELECT section5_json FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    // section5_json が保存されている
    $this->assertNotEmpty($row['section5_json']);

    // JSONとしてデコードできる
    $decoded = json_decode($row['section5_json'], true);
    $this->assertIsArray($decoded);

    // photos・docs キーが存在する
    $this->assertArrayHasKey('photos', $decoded);
    $this->assertArrayHasKey('docs',   $decoded);
  }

}
