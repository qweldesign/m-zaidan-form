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
    // 要望申請は写真・PDF5種が必須になったため、全て揃った状態で作成する。
    // これによりこのファイル内の各PUTテストは、編集時にファイルを
    // 再送しなくても「マージ後の最終状態」の必須チェックを満たせる
    // （＝既存ファイルが引き継がれるという通常の編集フローを再現できる）。
    $_FILES = [
      'photos'          => ['error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/photo1.jpg', '/tmp/photo2.jpg', '/tmp/photo3.jpg'], 'name' => ['photo1.jpg', 'photo2.jpg', 'photo3.jpg']],
      'regulations'     => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/regulations.pdf',     'name' => 'regulations.pdf'],
      'activityReport'  => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/activityReport.pdf',  'name' => 'activityReport.pdf'],
      'financialReport' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/financialReport.pdf', 'name' => 'financialReport.pdf'],
      'activityPlan'    => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/activityPlan.pdf',    'name' => 'activityPlan.pdf'],
      'financialPlan'   => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/financialPlan.pdf',   'name' => 'financialPlan.pdf'],
    ];

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
    // 要望申請は写真・PDF5種が必須になったため、regulations以外もダミーで揃える
    // （このテストの主眼は「編集時に既存ファイルが保持されるか」のため）
    $_FILES = [
      'photos'          => ['error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/photo1.jpg', '/tmp/photo2.jpg', '/tmp/photo3.jpg'], 'name' => ['photo1.jpg', 'photo2.jpg', 'photo3.jpg']],
      'regulations'     => ['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmpPdf, 'name' => 'kiyaku.pdf'],
      'activityReport'  => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/activityReport.pdf',  'name' => 'activityReport.pdf'],
      'financialReport' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/financialReport.pdf', 'name' => 'financialReport.pdf'],
      'activityPlan'    => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/activityPlan.pdf',    'name' => 'activityPlan.pdf'],
      'financialPlan'   => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/financialPlan.pdf',   'name' => 'financialPlan.pdf'],
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

  //
  // 回帰テスト：実際のPUTリクエスト（multipart/form-data）を模したケース
  //
  // PHPはPUTメソッドの場合 $_POST / $_FILES を自動的にパースしないため、
  // このテストでは $_POST / $_FILES をあえて空のままにし、resolveRequestBody() の
  // 手動パース経路（$GLOBALS['_TEST_RAW_BODY']）を通してハンドラを呼び出す。
  // $_POST / $_FILES を直接セットする他のテストでは、この本番環境特有の不具合は
  // 検出できない。
  //

  private function buildMultipart(array $fields, array $files = []): array {
    $boundary = 'TestBoundary' . uniqid();
    $lines    = [];

    foreach ($fields as $name => $value) {
      $lines[] = "--{$boundary}\r\n";
      $lines[] = "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
      $lines[] = "{$value}\r\n";
    }

    // $files は [name, filename, type, content] のリスト
    foreach ($files as [$name, $filename, $type, $content]) {
      $lines[] = "--{$boundary}\r\n";
      $lines[] = "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
      $lines[] = "Content-Type: {$type}\r\n\r\n";
      $lines[] = $content . "\r\n";
    }

    $lines[] = "--{$boundary}--\r\n";

    return [implode('', $lines), $boundary];
  }

  protected function tearDownRawBodyOverride(): void {
    unset($GLOBALS['_TEST_RAW_BODY'], $GLOBALS['_TEST_CONTENT_TYPE']);
  }

  public function test_正常系_実際のPUTリクエストで収支予算の変更がDBに反映される(): void {
    $submission = $this->createSubmission();

    $_POST  = [];
    $_FILES = [];

    [$raw, $boundary] = $this->buildMultipart([
      'section1_json' => json_encode(['teamName' => 'ギャラリーはりいしゃ運営委員会']),
      'section2_json' => json_encode(['projectName' => '地域交流イベント']),
      'section3_json' => json_encode([
        'income'   => ['grantRequest' => 300000],
        'expenses' => [['id' => '1', 'subject' => '会場費', 'amount' => 90000, 'grantUsage' => 90000, 'memo' => '']],
        'budgetNote' => '実際のPUTリクエストによる変更後の備考',
      ]),
      'section4_json' => json_encode([]),
    ]);
    $GLOBALS['_TEST_RAW_BODY']     = $raw;
    $GLOBALS['_TEST_CONTENT_TYPE'] = "multipart/form-data; boundary={$boundary}";

    try {
      $response = $this->callHandlePutByToken($submission['edit_token']);
      $this->assertEquals('申請内容を更新しました', $response['message']);

      $stmt = $this->db->prepare('SELECT section3_json, grant_request_amount, total_expense_amount FROM submissions WHERE id = :id');
      $stmt->execute([':id' => $submission['id']]);
      $row = $stmt->fetch();

      $section3 = json_decode($row['section3_json'], true);
      $this->assertEquals(300000, $section3['income']['grantRequest']);
      $this->assertEquals('実際のPUTリクエストによる変更後の備考', $section3['budgetNote']);
      $this->assertEquals(300000, $row['grant_request_amount']);
      $this->assertEquals(90000, $row['total_expense_amount']);
    } finally {
      $this->tearDownRawBodyOverride();
    }
  }

  public function test_正常系_実際のPUTリクエストで添付ファイルが追加される(): void {
    $submission = $this->createSubmission();

    $_POST  = [];
    $_FILES = [];

    [$raw, $boundary] = $this->buildMultipart(
      [
        'section1_json' => json_encode(['teamName' => 'ギャラリーはりいしゃ運営委員会']),
        'section2_json' => json_encode(['projectName' => '地域交流イベント']),
        'section3_json' => json_encode(['income' => ['grantRequest' => 100000], 'expenses' => []]),
        'section4_json' => json_encode([]),
      ],
      [
        ['regulations', 'kiyaku.pdf', 'application/pdf', '%PDF-1.4 dummy regulations'],
        ['photos[]',    'p1.jpg',     'image/jpeg',      'dummy-photo-bytes'],
      ]
    );
    $GLOBALS['_TEST_RAW_BODY']     = $raw;
    $GLOBALS['_TEST_CONTENT_TYPE'] = "multipart/form-data; boundary={$boundary}";

    try {
      $this->callHandlePutByToken($submission['edit_token']);

      $stmt = $this->db->prepare('SELECT section5_json FROM submissions WHERE id = :id');
      $stmt->execute([':id' => $submission['id']]);
      $row = $stmt->fetch();

      $section5 = json_decode($row['section5_json'], true);
      $this->assertNotEmpty($section5['docs']['regulations'] ?? null);
      $this->assertNotEmpty($section5['photos'] ?? null);

      // 実際にファイルが物理的に保存されていることも確認する。
      // 申請作成時（createSubmission()）にも regulations のダミーが1件
      // 登録されているため、マージ後の配列には2件並ぶ。追加マージは常に
      // 末尾に追記される仕様なので、今回のPUTで追加された分は最後の要素になる。
      $regulationsDocs = is_array($section5['docs']['regulations'])
        ? $section5['docs']['regulations']
        : [$section5['docs']['regulations']];
      $regPath = UPLOAD_DIR . preg_replace('#^uploads/#', '', end($regulationsDocs));
      $this->assertFileExists($regPath);
      $this->assertStringContainsString('dummy regulations', file_get_contents($regPath));
    } finally {
      $this->tearDownRawBodyOverride();
    }
  }

}
