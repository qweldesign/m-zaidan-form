<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../handlers/SubmissionPost.php';
require_once __DIR__ . '/../../handlers/ReportPost.php';
require_once __DIR__ . '/../../handlers/ReportPutByToken.php';

class ReportPutByTokenTest extends TestCase {

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

  private function createReport(string $submissionToken): array {
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
      'submission_token' => $submissionToken,
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p.jpg'], 'name' => ['p.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    ob_start();
    handleReportPost();
    $output   = ob_get_clean();
    $response = json_decode($output, true);
    $id       = $response['data']['id'];

    $stmt = $this->db->prepare('SELECT edit_token FROM reports WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    return ['id' => $id, 'edit_token' => $row['edit_token']];
  }

  private function callHandleReportPutByToken(string $token): array {
    ob_start();
    handleReportPutByToken($token);
    $output = ob_get_clean();
    return json_decode($output, true) ?? [];
  }

  //
  // テスト
  //

  public function test_正常系_有効なトークンでデータを更新できる(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);

    $_POST = [
      'report_section1_json' => json_encode([
        'teamName'     => 'はりいしゃギャラリー運営委員会', // 変更
        'contactName'  => '小佐野 直子',
        'contactEmail' => 'gallery@example.com',
        'contactPhone' => '090-8765-4321',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '文化交流イベント', // 変更
        'activityCategory'=> 'ボランティア活動',
        'actualStartDate' => '2025-07-01',
        'actualEndDate'   => '2025-07-31',
        'actualVenue'     => '国見公民館',
        'actualDetail'    => '更新後の実施内容',
        'income' => [
          'grantRequest' => 100000, 'memberFees' => 0, 'donations' => 0, 'tickets' => 0,
          'incomeMemo'   => ['grantRequest' => '', 'memberFees' => '', 'donations' => '', 'tickets' => ''],
        ],
        'expenses'   => [],
        'budgetNote' => '',
      ]),
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
      'receipts' => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
    ];

    $response = $this->callHandleReportPutByToken($report['edit_token']);

    $this->assertEquals('完了報告内容を更新しました', $response['message']);

    $stmt = $this->db->prepare('SELECT * FROM reports WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);
    $row  = $stmt->fetch();

    $this->assertEquals('はりいしゃギャラリー運営委員会', $row['team_name']);
    $this->assertEquals('小佐野 直子',     $row['contact_name']);
    $this->assertEquals('文化交流イベント', $row['project_name']);
  }

  public function test_正常系_写真が既存データに追加される(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);

    // 初回の写真パスを確認
    $stmt = $this->db->prepare('SELECT report_section2_json FROM reports WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);
    $row     = $stmt->fetch();
    $section2 = json_decode($row['report_section2_json'], true);
    $initialPhotoCount = count($section2['photos'] ?? []);

    // 更新時に写真を追加
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
      'photos'   => [
        'error'    => [UPLOAD_ERR_OK],
        'tmp_name' => ['/tmp/new_photo.jpg'],
        'name'     => ['new_photo.jpg'],
      ],
      'receipts' => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
    ];

    $this->callHandleReportPutByToken($report['edit_token']);

    $stmt = $this->db->prepare('SELECT report_section2_json FROM reports WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);
    $row      = $stmt->fetch();
    $section2 = json_decode($row['report_section2_json'], true);

    // 写真が追加されている
    $this->assertGreaterThan($initialPhotoCount, count($section2['photos'] ?? []));
  }

  public function test_正常系_領収書が既存データに追加される(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);

    $stmt = $this->db->prepare('SELECT report_section2_json FROM reports WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);
    $row      = $stmt->fetch();
    $section2 = json_decode($row['report_section2_json'], true);
    $initialReceiptCount = count($section2['receipts'] ?? []);

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
      'photos'   => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
      'receipts' => [
        'error'    => [UPLOAD_ERR_OK],
        'tmp_name' => ['/tmp/new_receipt.pdf'],
        'name'     => ['new_receipt.pdf'],
      ],
    ];

    $this->callHandleReportPutByToken($report['edit_token']);

    $stmt = $this->db->prepare('SELECT report_section2_json FROM reports WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);
    $row      = $stmt->fetch();
    $section2 = json_decode($row['report_section2_json'], true);

    $this->assertGreaterThan($initialReceiptCount, count($section2['receipts'] ?? []));
  }

  public function test_異常系_無効なトークンはエラーが返る(): void {
    $_POST = [
      'report_section1_json' => '{}',
      'report_section2_json' => '{}',
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
      'receipts' => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
    ];

    $response = $this->callHandleReportPutByToken('invalidtoken0000invalidtoken0000');

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

  public function test_異常系_確認前以外のステータスは更新できない(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);

    // ステータスを確認済みに変更
    $stmt = $this->db->prepare('UPDATE reports SET status = :status WHERE id = :id');
    $stmt->execute([':status' => '確認済', ':id' => $report['id']]);

    $_POST = [
      'report_section1_json' => json_encode(['teamName' => 'はりいしゃギャラリー運営委員会']),
      'report_section2_json' => '{}',
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
      'receipts' => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
    ];

    $response = $this->callHandleReportPutByToken($report['edit_token']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('この完了報告はすでに確認済みのため、内容を変更できません。', $response['error']);
  }

  public function test_異常系_論理削除済みのデータは更新できない(): void {
    $submission = $this->createSubmission();
    $report     = $this->createReport($submission['edit_token']);

    $stmt = $this->db->prepare('UPDATE reports SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id' => $report['id']]);

    $_POST = [
      'report_section1_json' => json_encode(['teamName' => 'はりいしゃギャラリー運営委員会']),
      'report_section2_json' => '{}',
    ];
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
      'receipts' => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
    ];

    $response = $this->callHandleReportPutByToken($report['edit_token']);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals('無効なトークンです', $response['error']);
  }

}
