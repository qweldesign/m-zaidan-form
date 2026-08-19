<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/RequestBody.php';

class RequestBodyTest extends TestCase {

  //
  // ヘルパー：multipart/form-data の生ボディを組み立てる
  //

  // $files は [name, filename, type, content] のリスト（同名 "photos[]" を複数渡せるように連想配列ではなくリストにしている）
  private function buildMultipart(array $fields, array $files = []): array {
    $boundary = 'TestBoundary' . uniqid();
    $lines    = [];

    foreach ($fields as $name => $value) {
      $lines[] = "--{$boundary}\r\n";
      $lines[] = "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
      $lines[] = "{$value}\r\n";
    }

    foreach ($files as [$name, $filename, $type, $content]) {
      $lines[] = "--{$boundary}\r\n";
      $lines[] = "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
      $lines[] = "Content-Type: {$type}\r\n\r\n";
      $lines[] = $content . "\r\n";
    }

    $lines[] = "--{$boundary}--\r\n";

    return [implode('', $lines), $boundary];
  }

  protected function tearDown(): void {
    unset($GLOBALS['_TEST_RAW_BODY'], $GLOBALS['_TEST_CONTENT_TYPE']);
  }

  //
  // extractMultipartBoundary
  //

  public function test_ContentTypeヘッダーからboundaryを取り出せる(): void {
    $boundary = extractMultipartBoundary('multipart/form-data; boundary=----WebKitFormBoundaryABC123');
    $this->assertSame('----WebKitFormBoundaryABC123', $boundary);
  }

  public function test_multipartでないContentTypeはnullを返す(): void {
    $this->assertNull(extractMultipartBoundary('application/json'));
  }

  //
  // parseMultipartFormData
  //

  public function test_テキストフィールドが正しくパースされる(): void {
    [$raw, $boundary] = $this->buildMultipart([
      'section1_json' => '{"teamName":"テスト団体"}',
      'section3_json' => '{"income":{"grantRequest":50000}}',
    ]);

    [$fields, $files] = parseMultipartFormData($boundary, $raw);

    $this->assertSame('{"teamName":"テスト団体"}', $fields['section1_json']);
    $this->assertSame('{"income":{"grantRequest":50000}}', $fields['section3_json']);
    $this->assertEmpty($files);
  }

  public function test_単一ファイル項目が正しくパースされる(): void {
    [$raw, $boundary] = $this->buildMultipart([], [
      ['regulations', 'kiyaku.pdf', 'application/pdf', '%PDF-1.4 dummy'],
    ]);

    [, $files] = parseMultipartFormData($boundary, $raw);

    $this->assertSame('kiyaku.pdf', $files['regulations']['name']);
    $this->assertSame(UPLOAD_ERR_OK, $files['regulations']['error']);
    $this->assertFileExists($files['regulations']['tmp_name']);
    $this->assertSame('%PDF-1.4 dummy', file_get_contents($files['regulations']['tmp_name']));

    unlink($files['regulations']['tmp_name']);
  }

  public function test_複数ファイル項目photos配列が正しくパースされる(): void {
    [$raw, $boundary] = $this->buildMultipart([], [
      ['photos[]', 'p1.jpg', 'image/jpeg', 'binary-data-1'],
      ['photos[]', 'p2.jpg', 'image/jpeg', 'binary-data-2'],
    ]);

    [, $files] = parseMultipartFormData($boundary, $raw);

    $this->assertCount(2, $files['photos']['tmp_name']);
    $this->assertSame(['p1.jpg', 'p2.jpg'], $files['photos']['name']);
    $this->assertSame('binary-data-1', file_get_contents($files['photos']['tmp_name'][0]));
    $this->assertSame('binary-data-2', file_get_contents($files['photos']['tmp_name'][1]));

    unlink($files['photos']['tmp_name'][0]);
    unlink($files['photos']['tmp_name'][1]);
  }

  public function test_ファイル未選択のfilename空文字はスキップされる(): void {
    [$raw, $boundary] = $this->buildMultipart([], [
      ['regulations', '', 'application/octet-stream', ''],
    ]);

    [, $files] = parseMultipartFormData($boundary, $raw);

    $this->assertArrayNotHasKey('regulations', $files);
  }

  // 本文の末尾がちょうど改行文字（\n）で終わるファイルでも、区切り文字用の
  // 改行とファイル自身の末尾バイトを混同して欠落させないことを確認する回帰テスト
  public function test_末尾が改行文字で終わるファイルもバイト単位で復元される(): void {
    $content = "line1\nline2\n";
    [$raw, $boundary] = $this->buildMultipart([], [
      ['regulations', 'ends-with-newline.txt', 'text/plain', $content],
    ]);

    [, $files] = parseMultipartFormData($boundary, $raw);

    $this->assertSame($content, file_get_contents($files['regulations']['tmp_name']));
    $this->assertSame(strlen($content), $files['regulations']['size']);

    unlink($files['regulations']['tmp_name']);
  }

  //
  // resolveRequestBody
  //

  public function test_POSTやFILESが空でもテスト用オーバーライドから解決できる(): void {
    $_POST  = [];
    $_FILES = [];

    [$raw, $boundary] = $this->buildMultipart(['report_section2_json' => '{"projectName":"テスト事業"}']);
    $GLOBALS['_TEST_RAW_BODY']     = $raw;
    $GLOBALS['_TEST_CONTENT_TYPE'] = "multipart/form-data; boundary={$boundary}";

    [$fields, $files] = resolveRequestBody();

    $this->assertSame('{"projectName":"テスト事業"}', $fields['report_section2_json']);
    $this->assertEmpty($files);
  }

  public function test_POSTに値があればそれをそのまま使う(): void {
    $_POST  = ['foo' => 'bar'];
    $_FILES = [];

    [$fields, $files] = resolveRequestBody();

    $this->assertSame(['foo' => 'bar'], $fields);
    $this->assertEmpty($files);
  }
}
