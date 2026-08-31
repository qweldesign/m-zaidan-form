<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/Validator.php';

class ValidatorTest extends TestCase {

  protected function setUp(): void {
    // $_FILES はテスト間で残らないよう毎回リセットする
    $_FILES = [];
  }

  // 要望申請の添付資料（写真3枚＋PDF5種）が全て揃っている状態の $_FILES モック。
  // テキスト項目のバリデーションだけを見たいテストでは、これを設定して
  // 添付資料由来のエラーが混ざらないようにする。
  // 写真は規定枚数（Validator::SUBMISSION_PHOTO_COUNT）ちょうどが必須。
  private function validSubmissionFiles(): array {
    return [
      'photos'          => [
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg', '/tmp/p3.jpg'],
        'name'     => ['p1.jpg', 'p2.jpg', 'p3.jpg'],
      ],
      'regulations'     => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/r.pdf',  'name' => 'r.pdf'],
      'activityReport'  => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/a.pdf',  'name' => 'a.pdf'],
      'financialReport' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/f.pdf',  'name' => 'f.pdf'],
      'activityPlan'    => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/ap.pdf', 'name' => 'ap.pdf'],
      'financialPlan'   => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/fp.pdf', 'name' => 'fp.pdf'],
    ];
  }

  // 完了報告の添付資料（写真2枚＋領収書1枚）が全て揃っている状態の $_FILES モック。
  // 写真は規定枚数（Validator::REPORT_PHOTO_COUNT）ちょうどが必須。
  private function validReportFiles(): array {
    return [
      'photos'   => [
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg'],
        'name'     => ['p1.jpg', 'p2.jpg'],
      ],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];
  }

  //
  // validateSubmission
  //

  public function test_全項目が揃っていればエラーなし(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'hariisha@example.com',
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertEmpty($errors);
  }

  public function test_section1_jsonが空ならエラーが返る(): void {
    $body = [
      'section1_json' => '{}',
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertNotEmpty($errors);
    $this->assertContains('団体名称は必須です', $errors);
    $this->assertContains('所在地は必須です', $errors);
    $this->assertContains('代表者名は必須です', $errors);
    $this->assertContains('代表者メールアドレスが不正です', $errors);
  }

  public function test_section2_jsonが空ならエラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'hariisha@example.com',
      ]),
      'section2_json' => '{}',  // 空
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(3, $errors);
    $this->assertContains('事業名称は必須です', $errors);
    $this->assertContains('開始日は必須です', $errors);
    $this->assertContains('終了日は必須です', $errors);
  }

  public function test_不正なJSONが渡された場合エラーが返る(): void {
    $body = [
      'section1_json' => 'invalid-json',
      'section2_json' => 'invalid-json',
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    // json_decode が null を返すため全項目がエラーになる
    $this->assertNotEmpty($errors);
    $this->assertContains('団体名称は必須です', $errors);
    $this->assertContains('事業名称は必須です', $errors);
  }

  public function test_teamNameが空ならエラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => '',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'hariisha@example.com',
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(1, $errors);
    $this->assertContains('団体名称は必須です', $errors);
  }

  public function test_representativeEmailが空ならエラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => '',  // 空文字
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => '',  // 空文字
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(2, $errors);
    $this->assertContains('代表者メールアドレスが不正です', $errors);
  }

  public function test_representativeEmailが不正な形式ならエラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'not-an-email',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'not-an-email',
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(2, $errors);
    $this->assertContains('代表者メールアドレスが不正です', $errors);
  }

  public function test_contactEmailが空ならエラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => '',  // 空
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(1, $errors);
    $this->assertContains('担当者メールアドレスが不正です', $errors);
  }

  public function test_contactEmailが不正な形式ならエラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'not-an-email',  // 不正
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(1, $errors);
    $this->assertContains('担当者メールアドレスが不正です', $errors);
  }

  public function test_複数項目が空なら複数エラーが返る(): void {
    $body = [
      'section1_json' => json_encode([
        'teamName'            => '',
        'teamAddress'         => '',
        'representativeName'  => '',
        'representativeEmail' => '',
        'contactName'         => '',
        'contactEmail'        => '',
      ]),
      'section2_json' => json_encode([
        'projectName' => '',
        'startDate'   => '',
        'endDate'     => '',
      ]),
    ];

    $_FILES = $this->validSubmissionFiles();
    $errors = Validator::validateSubmission($body);

    $this->assertCount(9, $errors);
  }

  //
  // validateReport
  //

  public function test_レポート_全項目が揃っていればエラーなし(): void {
    $_FILES = $this->validReportFiles();

    $body = [
      'report_section1_json' => json_encode([
        'teamName'    => 'ギャラリーはりいしゃ運営委員会',
        'contactName' => '伊藤 大悟',
        'contactEmail'=> 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'income'          => ['grantRequest' => 100000],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertEmpty($errors);
  }

  public function test_レポート_contactEmailが不正な形式ならエラーが返る(): void {
    $_FILES = $this->validReportFiles();

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'invalid-email',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'income'          => ['grantRequest' => 100000],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('担当者メールアドレスが不正です', $errors);
  }

  public function test_レポート_grantRequestが0ならエラーが返る(): void {
    $_FILES = $this->validReportFiles();

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'income'          => ['grantRequest' => 0],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('助成金要望額は必須です', $errors);
  }

  public function test_レポート_grantRequestが負の値ならエラーが返る(): void {
    $_FILES = $this->validReportFiles();

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => '福井市民会館',
        'income'          => ['grantRequest' => -1000],  // 負の値
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('助成金要望額は必須です', $errors);
  }

  public function test_レポート_写真がなければエラーが返る(): void {
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'income'          => ['grantRequest' => 100000],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('活動実施写真は2枚必須です', $errors);
  }

  public function test_レポート_写真が規定枚数より少なければエラーが返る(): void {
    $_FILES = [
      'photos'   => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/p.jpg'], 'name' => ['p.jpg']],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'income'          => ['grantRequest' => 100000],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('活動実施写真は2枚必須です', $errors);
  }

  public function test_レポート_写真が規定枚数より多ければエラーが返る(): void {
    $_FILES = [
      'photos'   => [
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg', '/tmp/p3.jpg'],
        'name'     => ['p1.jpg', 'p2.jpg', 'p3.jpg'],
      ],
      'receipts' => ['error' => [UPLOAD_ERR_OK], 'tmp_name' => ['/tmp/r.pdf'], 'name' => ['r.pdf']],
    ];

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => 'ギャラリーはりいしゃ',
        'income'          => ['grantRequest' => 100000],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('活動実施写真は2枚必須です', $errors);
  }

  public function test_レポート_領収書がなければエラーが返る(): void {
    $_FILES = [
      'photos'   => [
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg'],
        'name'     => ['p1.jpg', 'p2.jpg'],
      ],
      'receipts' => ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''],           'name' => ['']],
    ];

    $body = [
      'report_section1_json' => json_encode([
        'teamName'     => 'ギャラリーはりいしゃ運営委員会',
        'contactName'  => '伊藤 大悟',
        'contactEmail' => 'hariisha@example.com',
      ]),
      'report_section2_json' => json_encode([
        'projectName'     => '地域交流イベント',
        'actualStartDate' => '2025-06-01',
        'actualEndDate'   => '2025-06-30',
        'actualVenue'     => '福井市民会館',
        'income'          => ['grantRequest' => 100000],
      ]),
    ];

    $errors = Validator::validateReport($body);

    $this->assertCount(1, $errors);
    $this->assertContains('領収書は必須です', $errors);
  }

  //
  // validateSubmission - 添付資料（新規申請時は必須）
  //
  // 過去に validateSubmission() には添付資料のチェックが一切無く、
  // 写真・PDFを1件も添付しない要望申請がAPI側で受理されてしまう
  // 不具合があったため、そのケースを再現し塞いだことを確認する。

  private function validTextFieldsBody(): array {
    return [
      'section1_json' => json_encode([
        'teamName'            => 'ギャラリーはりいしゃ運営委員会',
        'teamAddress'         => '福井県福井市蒲生町1-42',
        'representativeName'  => '伊藤 大悟',
        'representativeEmail' => 'hariisha@example.com',
        'contactName'         => '伊藤 大悟',
        'contactEmail'        => 'hariisha@example.com',
      ]),
      'section2_json' => json_encode([
        'projectName' => '地域交流イベント',
        'startDate'   => '2025-06-01',
        'endDate'     => '2025-06-30',
      ]),
    ];
  }

  public function test_添付資料が1件も無ければ写真とPDF5種すべてがエラーになる(): void {
    $_FILES = [];  // 添付なし（過去に実際にサーバーが受理してしまっていたケース）

    $errors = Validator::validateSubmission($this->validTextFieldsBody());

    $this->assertCount(6, $errors);
    $this->assertContains('活動写真は3枚必須です', $errors);
    $this->assertContains('団体規約は必須です', $errors);
    $this->assertContains('直近年度の活動報告書は必須です', $errors);
    $this->assertContains('直近年度の収支決算書は必須です', $errors);
    $this->assertContains('直近年度の活動計画書は必須です', $errors);
    $this->assertContains('直近年度の収支計画書は必須です', $errors);
  }

  public function test_写真が無ければ写真のみエラーになる(): void {
    $_FILES = $this->validSubmissionFiles();
    $_FILES['photos'] = ['error' => [UPLOAD_ERR_NO_FILE], 'tmp_name' => [''], 'name' => ['']];

    $errors = Validator::validateSubmission($this->validTextFieldsBody());

    $this->assertCount(1, $errors);
    $this->assertContains('活動写真は3枚必須です', $errors);
  }

  // 写真は「1枚以上」ではなく「規定枚数（3枚）ちょうど」が必須になったため、
  // 1〜2枚だけの場合もエラーになることを確認する。
  public function test_写真が規定枚数より少なければエラーになる(): void {
    $_FILES = $this->validSubmissionFiles();
    $_FILES['photos'] = [
      'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
      'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg'],
      'name'     => ['p1.jpg', 'p2.jpg'],
    ];

    $errors = Validator::validateSubmission($this->validTextFieldsBody());

    $this->assertCount(1, $errors);
    $this->assertContains('活動写真は3枚必須です', $errors);
  }

  // 規定枚数を超えた場合もエラーになることを確認する
  // （フォーム側はスロット数で枚数を制限しているため通常は発生しないが、
  // APIを直接叩かれるケースに備えてサーバー側でも検証する）。
  public function test_写真が規定枚数より多ければエラーになる(): void {
    $_FILES = $this->validSubmissionFiles();
    $_FILES['photos'] = [
      'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
      'tmp_name' => ['/tmp/p1.jpg', '/tmp/p2.jpg', '/tmp/p3.jpg', '/tmp/p4.jpg'],
      'name'     => ['p1.jpg', 'p2.jpg', 'p3.jpg', 'p4.jpg'],
    ];

    $errors = Validator::validateSubmission($this->validTextFieldsBody());

    $this->assertCount(1, $errors);
    $this->assertContains('活動写真は3枚必須です', $errors);
  }

  public function test_PDFの一部が無ければその項目のみエラーになる(): void {
    $_FILES = $this->validSubmissionFiles();
    unset($_FILES['financialPlan']);

    $errors = Validator::validateSubmission($this->validTextFieldsBody());

    $this->assertCount(1, $errors);
    $this->assertContains('直近年度の収支計画書は必須です', $errors);
  }

  public function test_添付資料が全て揃っていればテキスト項目のみでエラーなし(): void {
    $_FILES = $this->validSubmissionFiles();

    $errors = Validator::validateSubmission($this->validTextFieldsBody());

    $this->assertEmpty($errors);
  }

  //
  // validateSubmissionFiles（編集/PUT時：マージ後の最終状態を検証）
  //

  public function test_validateSubmissionFiles_写真もPDFも全て揃っていればエラーなし(): void {
    $section5 = [
      'photos' => ['uploads/2026/photo_1.jpg'],
      'docs'   => [
        'regulations'     => 'uploads/2026/regulations_1.pdf',
        'activityReport'  => 'uploads/2026/activityReport_1.pdf',
        'financialReport' => 'uploads/2026/financialReport_1.pdf',
        'activityPlan'    => 'uploads/2026/activityPlan_1.pdf',
        'financialPlan'   => 'uploads/2026/financialPlan_1.pdf',
      ],
    ];

    $errors = Validator::validateSubmissionFiles($section5);

    $this->assertEmpty($errors);
  }

  public function test_validateSubmissionFiles_写真もPDFも1件も無ければ全項目エラーになる(): void {
    $section5 = ['photos' => [], 'docs' => []];

    $errors = Validator::validateSubmissionFiles($section5);

    $this->assertCount(6, $errors);
    $this->assertContains('活動写真は必須です', $errors);
    $this->assertContains('団体規約は必須です', $errors);
  }

  public function test_validateSubmissionFiles_PDFの一部だけ無ければその項目のみエラーになる(): void {
    $section5 = [
      'photos' => ['uploads/2026/photo_1.jpg'],
      'docs'   => [
        'regulations'     => 'uploads/2026/regulations_1.pdf',
        'activityReport'  => 'uploads/2026/activityReport_1.pdf',
        'financialReport' => 'uploads/2026/financialReport_1.pdf',
        'activityPlan'    => 'uploads/2026/activityPlan_1.pdf',
        // financialPlan が無い
      ],
    ];

    $errors = Validator::validateSubmissionFiles($section5);

    $this->assertCount(1, $errors);
    $this->assertContains('直近年度の収支計画書は必須です', $errors);
  }

  //
  // validateReportFiles（編集/PUT時：マージ後の最終状態を検証）
  //

  public function test_validateReportFiles_写真も領収書も揃っていればエラーなし(): void {
    $section2 = [
      'photos'   => ['uploads/2026/report_photo_1.jpg'],
      'receipts' => ['uploads/2026/receipt_1.pdf'],
    ];

    $errors = Validator::validateReportFiles($section2);

    $this->assertEmpty($errors);
  }

  public function test_validateReportFiles_写真も領収書も無ければ両方エラーになる(): void {
    $section2 = ['photos' => [], 'receipts' => []];

    $errors = Validator::validateReportFiles($section2);

    $this->assertCount(2, $errors);
    $this->assertContains('活動実施写真は必須です', $errors);
    $this->assertContains('領収書は必須です', $errors);
  }

}
