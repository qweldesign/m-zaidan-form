<?php

// PHPは POST メソッドの場合のみ multipart/form-data のボディを自動的に
// $_POST / $_FILES へパースする。PUT（トークンでの申請・完了報告の編集）
// では multipart/form-data で送信していても $_POST / $_FILES は常に空になり、
// 送信内容が一切反映されない（テキスト項目・添付ファイルとも）。
// このヘルパーは、実際のリクエストのフィールド・ファイルを取得するための
// 共通の入り口を提供し、PUT等でPHPが自動パースしなかった場合は
// php://input を手動でパースする。

// 実際のリクエストのボディ（[$fields, $files]）を取得する。
// $_POST または $_FILES に値がある場合はそれをそのまま使う
// （実際のPOSTリクエスト、およびテストで直接セットされている場合の両方に対応）。
// どちらも空の場合は、PUT等でPHPが自動パースしなかった可能性があるため、
// php://input を multipart/form-data として手動パースする。
function resolveRequestBody(): array {
  if (isset($GLOBALS['_TEST_RAW_BODY'])) {
    $contentType = $GLOBALS['_TEST_CONTENT_TYPE'] ?? ($_SERVER['CONTENT_TYPE'] ?? '');
    $boundary = extractMultipartBoundary($contentType);
    if ($boundary === null) return [[], []];
    return parseMultipartFormData($boundary, $GLOBALS['_TEST_RAW_BODY']);
  }

  if (!empty($_POST) || !empty($_FILES)) {
    return [$_POST, $_FILES];
  }

  $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
  $boundary    = extractMultipartBoundary($contentType);
  if ($boundary === null) return [[], []];

  $raw = file_get_contents('php://input');
  return parseMultipartFormData($boundary, $raw);
}

// Content-Type ヘッダーから multipart の boundary を取り出す（無ければ null）
function extractMultipartBoundary(string $contentType): ?string {
  if (!preg_match('/boundary=(.+)$/i', $contentType, $m)) return null;
  $boundary = trim($m[1]);
  $boundary = trim($boundary, '"');
  $boundary = explode(';', $boundary)[0];
  return $boundary;
}

// multipart/form-data の生ボディを $_POST / $_FILES 相当の構造にパースする
// 戻り値は [フィールド連想配列, $_FILES互換のファイル連想配列]
function parseMultipartFormData(string $boundary, string $raw): array {
  $fields = [];
  $files  = [];

  $parts = explode('--' . $boundary, $raw);

  foreach ($parts as $part) {
    // 空の要素（先頭）と終端マーカー（"--\r\n"）はスキップ。
    // それ以外はここで trim() を使わない（ファイル本文の末尾が \r や \n の
    // バイトで終わる場合に、区切り文字用の改行と一緒に本物のデータまで
    // 削られてしまうため、先頭・末尾の改行はそれぞれ1回だけ厳密に取り除く）
    if ($part === '') continue;
    if (str_starts_with($part, '--')) continue;

    $part = preg_replace('/^\r\n/', '', $part, 1);

    $sep = strpos($part, "\r\n\r\n");
    if ($sep === false) continue;

    $headerText = substr($part, 0, $sep);
    $body       = substr($part, $sep + 4);
    // 次のboundary直前の改行（区切り文字自体の一部）を1回だけ除去
    $body = preg_replace('/\r\n$/', '', $body, 1);

    if (!preg_match('/name="([^"]+)"/', $headerText, $nameMatch)) continue;
    $name = $nameMatch[1];

    $isFile = preg_match('/filename="([^"]*)"/', $headerText, $filenameMatch);

    if ($isFile) {
      $filename = $filenameMatch[1];
      // ファイル未選択（空のfilename）はスキップ
      if ($filename === '') continue;

      $contentType = 'application/octet-stream';
      if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headerText, $ctMatch)) {
        $contentType = trim($ctMatch[1]);
      }

      // move_uploaded_file() は本物のPOSTアップロードにしか使えないため、
      // 通常の一時ファイルとして書き出す（呼び出し側は rename() で移動する）
      $tmpPath = tempnam(sys_get_temp_dir(), 'put_upload_');
      file_put_contents($tmpPath, $body);

      $fileEntry = [
        'name'     => $filename,
        'type'     => $contentType,
        'tmp_name' => $tmpPath,
        'error'    => UPLOAD_ERR_OK,
        'size'     => strlen($body),
      ];

      // name="photos[]" のような複数ファイル形式（$_FILES['photos']['tmp_name'][] 形式）に対応
      if (substr($name, -2) === '[]') {
        $baseName = substr($name, 0, -2);
        if (!isset($files[$baseName])) {
          $files[$baseName] = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
        }
        foreach ($fileEntry as $key => $value) {
          $files[$baseName][$key][] = $value;
        }
      } else {
        $files[$name] = $fileEntry;
      }
    } else {
      $fields[$name] = $body;
    }
  }

  return [$fields, $files];
}

// move_uploaded_file() はPHPが本物のPOSTアップロードとして認識したファイルにしか
// 使えない（is_uploaded_file() が false のファイルには失敗する）。
// PUT等を手動パースして得た一時ファイルはこれに該当しないため、
// rename()（失敗時は copy + unlink）にフォールバックして移動する。
function moveUploadedOrTempFile(string $tmpName, string $dest): bool {
  if (is_uploaded_file($tmpName)) {
    return move_uploaded_file($tmpName, $dest);
  }
  if (@rename($tmpName, $dest)) {
    return true;
  }
  if (@copy($tmpName, $dest)) {
    @unlink($tmpName);
    return true;
  }
  return false;
}
