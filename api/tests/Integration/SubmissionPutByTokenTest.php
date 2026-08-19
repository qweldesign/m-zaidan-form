<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/SubmissionPutByToken.php';

class SubmissionPutByTokenTest extends TestCase {

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

    $stmt = $this->db->prepare('SELECT edit_token FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    return ['id' => $id, 'edit_token' => $row['edit_token']];
  }

  private function callHandlePutByToken(string $token): array {
    ob_start();
    handlePutByToken($token);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_有効なトークンでデータを更新できる(): void {
    $submission = $this->createSubmission();

    $_POST = [
      'section1_json' => json_encode([
        'teamName'            => 'はりいしゃギャラリー運営委員会', // 変更
        'teamNameKana'        => 'ハリイシャギャラリーウンエイイインカイ', // 変更
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
        'projectName' => '文化交流イベント', // 変更
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

    $response = $this->callHandlePutByToken($submission['edit_token']);

    $this->assertEquals('申請内容を更新しました', $response['message']);
    $this->assertEquals($submission['id'], $response['data']['id']);

    // DBが更新されているか確認
    $stmt = $this->db->prepare('SELECT * FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $submission['id']]);
    $row  = $stmt->fetch();

    $this->assertEquals('はりいしゃギャラリー運営委員会', $row['team_name']);
    $this->assertEquals('文化交流イベント', $row['project_name']);
  }

  public function test_正常系_変更内容がlogsに記録される(): void {
    $submission = $this->createSubmission();

    $_POST = [
      'section1_json' => json_encode([
        'teamName'            => 'はりいしゃギャラリー運営委員会', // 変更
        'teamNameKana'        => 'ハリイシャギャラリーウンエイイインカイ', // 変更
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

    $this->callHandlePutByToken($submission['edit_token']);

    // ログを確認
    $stmt = $this->db->prepare('
      SELECT * FROM submission_logs
      WHERE submission_id = :id AND field_name = :field
    ');
    $stmt->execute([':id' => $submission['id'], ':field' => 'team_name']);
    $log = $stmt->fetch();

    $this->assertNotFalse($log);
    $this->assertEquals('ギャラリーはりいしゃ運営委員会', $log['old_value']);
    $this->assertEquals('はりいしゃギャラリー運営委員会', $log['new_value']);
    $this->assertEquals('applicant',     $log['changed_by']);
  }

  public function test_正常系_変更のないフィールドはlogsに記録されない(): void {
    $submission = $this->createSubmission();

    // 何も変更しない（同じデータを送る）
    $_POST = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会', // 変更なし
        'teamNameKana'        => 'ギャラリーハリイシャウンエイイインカイ', // 変更なし
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
        'projectName' => '地域交流イベント', // 変更なし
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

    $this->callHandlePutByToken($submission['edit_token']);

    $stmt  = $this->db->prepare('SELECT COUNT(*) as cnt FROM submission_logs WHERE submission_id = :id');
    $stmt->execute([':id' => $submission['id']]);
    $count = $stmt->fetch()['cnt'];

    $this->assertEquals(0, $count);
  }

  public function test_正常系_ファイル未添付なら既存の添付ファイルが保持される(): void {
    $_POST = [
      'section1_json' => json_encode([
        'teamName' => 'ギャラリーはりいしゃ運営委員会', 'teamAddress' => '福井県福井市蒲生町1-42',
        'activityCategory' => 'その他市民活動',
        'representativeName' => '伊藤 大悟', 'representativeEmail' => 'hariisha@example.com',
        'contactName' => '伊藤 大悟', 'contactEmail' => 'hariisha@example.com',
        'sameAsRepresentative' => true,
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント', 'startDate' => '2025-06-01', 'endDate' => '2025-06-30',
      ]),
      'section3_json' => json_encode(['income' => ['grantRequest' => 100000], 'expenses' => []]),
      'section4_json' => json_encode([]),
    ];
    $tmpPdf = sys_get_temp_dir() . '/repro_regulations.pdf';
    file_put_contents($tmpPdf, '%PDF-1.4 dummy');
    $_FILES = [
      'regulations' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmpPdf, 'name' => 'kiyaku.pdf'],
    ];

    ob_start();
    handlePost();
    $output = ob_get_clean();
    $id     = json_decode($output, true)['data']['id'];

    $stmt = $this->db->prepare('SELECT edit_token, section5_json FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    $before = json_decode($row['section5_json'], true);
    $this->assertNotEmpty($before['docs']['regulations'] ?? null);

    // 編集時：ファイルを何も添付せずに送信
    $_POST = [
      'section1_json' => json_encode([
        'teamName' => 'ギャラリーはりいしゃ運営委員会（編集後）', 'teamAddress' => '福井県福井市蒲生町1-42',
        'activityCategory' => 'その他市民活動',
        'representativeName' => '伊藤 大悟', 'representativeEmail' => 'hariisha@example.com',
        'contactName' => '伊藤 大悟', 'contactEmail' => 'hariisha@example.com',
        'sameAsRepresentative' => true,
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント', 'startDate' => '2025-06-01', 'endDate' => '2025-06-30',
      ]),
      'section3_json' => json_encode(['income' => ['grantRequest' => 100000], 'expenses' => []]),
      'section4_json' => json_encode([]),
    ];
    $_FILES = [];

    $this->callHandlePutByToken($row['edit_token']);

    $stmt->execute([':id' => $id]);
    $after = json_decode($stmt->fetch()['section5_json'], true);

    // 添付ファイル（docs/photos）が消えずに引き継がれていること
    $this->assertEquals($before['docs'], $after['docs'] ?? null);
  }

  public function test_異常系_無効なトークンはエラーが返る(): void {
    $_POST  = ['section1_json' => '{}', 'section2_json' => '{}', 'section3_json' => '{}', 'section4_json' => '{}'];
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    $response = $this->callHandlePutByToken('invalidtoken0000invalidtoken0000');

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_審査前以外のステータスは更新できない(): void {
    $submission = $this->createSubmission();

    // ステータスを審査中に変更
    $stmt = $this->db->prepare('UPDATE submissions SET status = :status WHERE id = :id');
    $stmt->execute([':status' => '審査中', ':id' => $submission['id']]);

    $_POST = [
      'section1_json' => json_encode(['teamName' => 'はりいしゃギャラリー運営委員会']), // 変更
      'section2_json' => '{}',
      'section3_json' => '{}',
      'section4_json' => '{}',
    ];
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    $response = $this->callHandlePutByToken($submission['edit_token']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('この申請はすでに受理されているため、内容を変更できません。', $response['error']);
  }

  public function test_異常系_論理削除済みのデータは更新できない(): void {
    $submission = $this->createSubmission();

    // 論理削除
    $stmt = $this->db->prepare('UPDATE submissions SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $submission['id']]);

    $_POST = [
      'section1_json' => json_encode(['teamName' => 'はりいしゃギャラリー運営委員会']), // 変更
      'section2_json' => '{}',
      'section3_json' => '{}',
      'section4_json' => '{}',
    ];
    $_FILES = ['photos' => ['error' => [], 'tmp_name' => [], 'name' => []]];

    $response = $this->callHandlePutByToken($submission['edit_token']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

}
