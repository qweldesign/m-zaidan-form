# M ZAIDAN FORM

助成金の要望申請および完了報告フォーム

---

## ディレクトリ構成

### API

```
api/
├── index.php ← エントリーポイント（ルーティング）
├── config.php ← DB接続・定数定義
├── auth.php ← 認証ミドルウェア
├── router.php ← ルーター
├── handlers/
│ ├── SubmissionPost.php ← POST /submissions
│ ├── SubmissionList.php ← GET /submissions
│ ├── SubmissionGet.php ← GET /submissions/:id
│ ├── SubmissionPatch.php ← PATCH /submissions/:id
│ ├── SubmissionNotify.php ← POST /submissions/:id/notify
│ ├── SubmissionExport.php ← GET /submissions/export/csv
│ ├── SubmissionGetByToken.php ← GET /submissions/edit/:token
│ ├── SubmissionPutByToken.php ← PUT /submissions/edit/:token
│ ├── FileServe.php ← GET /api/files
│ ├── ReportPost.php ← POST /reports
│ ├── ReportList.php ← GET /reports
│ ├── ReportGet.php ← GET /reports/:id
│ ├── ReportPatch.php ← PATCH /reports/:id
│ ├── ReportNotify.php ← POST /reports/:id/notify
│ ├── ReportExport.php ← GET /reports/export/csv
│ ├── ReportGetByToken.php ← GET /reports/edit/:token
│ └── ReportPutByToken.php ← PUT /reports/edit/:token
├── helpers/
│ ├── Response.php ← JSON/CSVレスポンスヘルパー
│ ├── Validator.php ← バリデーション
│ ├── Mailer.php ← メール送信
│ └── RequestBody.php ← PUTリクエスト（multipart/form-data）の手動パース
└── uploads/ ← ファイルアップロード先
  └── .htaccess ← PHP実行禁止
```

### データベース

```
db/
├── m-zaidan.sqlite ← データベース本体（.gitignore で除外）
├── schema.sql      ← CREATE文
└── seed.sql        ← INSERT文 (サンプル)
```

### フォーム

```
form/
├── public/
│   └── images/
│      └── m-zaidan_logo.png
└── src/
    ├── components/
    │   ├── BudgetTable.tsx
    │   ├── ParticipantCount.tsx
    │   ├── PhotoSlots.tsx
    │   ├── ReceiptUploader.tsx
    │   ├── ResumeDialog.tsx
    │   └── SaveToast.tsx
    ├── hooks/
    │   └── useStepForm.ts
    ├── pages/
    │   ├── Application/
    │   │   ├── Section1.tsx
    │   │   ├── Section2.tsx
    │   │   ├── Section3.tsx
    │   │   ├── Section4.tsx
    │   │   └── Section5.tsx
    │   ├── Report/
    │   │   ├── Section1.tsx
    │   │   ├── Section2.tsx
    │   │   └── Section3.tsx
    │   ├── Application.tsx
    │   ├── Entrance.tsx
    │   └── Report.tsx
    ├── types/
    │   └── form.ts
    ├── App.tsx
    ├── index.css
    ├── index.tsx
    └── router.tsx
```

---

## API エンドポイント

### 申請者用（認証不要）

| メソッド | パス | 用途 |
|---|---|---|
| POST | /api/submissions | 申請データ新規登録 |
| POST | /api/reports | 完了報告新規登録 |
| GET | /api/submissions/edit/:token | トークンで申請データ取得 |
| PUT | /api/submissions/edit/:token | トークンで申請データ上書き更新 |
| GET | /api/reports/edit/:token | トークンで完了報告データ取得 |
| PUT | /api/reports/edit/:token | トークンで完了報告データ上書き更新 |

### スタッフ用（Bearer トークン認証必要）

| メソッド | パス | 用途 |
|---|---|---|
| GET | /api/submissions | 申請一覧取得 |
| GET | /api/submissions/:id | 個別申請取得（論理削除済みも取得できる。下記「個別取得の仕様」を参照） |
| PATCH | /api/submissions/:id | 申請内容修正 |
| POST | /api/submissions/:id/notify | ステータス変更メール送信（body に `{ pdf: base64文字列 }` を渡すとメールにPDFを添付できる。任意） |
| GET | /api/submissions/export/csv | CSV エクスポート（`section1_json`/`section2_json`/`section4_json` の詳細項目を含む。クエリパラメータは下記「CSVエクスポートのクエリパラメータ」を参照） |
| GET | /api/reports | 完了報告一覧取得 |
| GET | /api/reports/:id | 個別完了報告取得（論理削除済みも取得できる。下記「個別取得の仕様」を参照） |
| PATCH | /api/reports/:id | 完了報告内容修正 |
| POST | /api/reports/:id/notify | ステータス変更メール送信 |
| GET | /api/reports/export/csv | CSV エクスポート（基本項目のみ。JSON項目の展開は行わない） |
| GET | /api/files | ファイル取得 |

### GET /api/submissions クエリパラメータ

| パラメータ | デフォルト | 説明 |
|---|---|---|
| status | なし | `審査前` / `審査中` / `承認` / `否決` / `対象外` |
| keyword | なし | 団体名・事業名で部分一致検索 |
| activity_category | なし | `ボランティア活動` / `スポーツ活動` / `その他市民活動` |
| year | なし | 申請年（例：`2025`） |
| include_deleted | `0` | `1` を指定すると論理削除済みを含む |
| order_by | `id` | `id` / `created_at` / `team_name` / `activity_category` / `start_date` / `status` / `grant_request_amount` |
| order | `ASC` | `ASC` / `DESC` |
| limit | `50` | 最大200 |
| offset | `0` | オフセット |

### GET /api/reports クエリパラメータ

| パラメータ | デフォルト | 説明 |
|---|---|---|
| status | なし | `確認前` / `要修正` / `確認済` |
| keyword | なし | 団体名・事業名で部分一致検索 |
| activity_category | なし | `ボランティア活動` / `スポーツ活動` / `その他市民活動` |
| year | なし | 実施年（actual_start_date から計算、例：`2025`） |
| include_deleted | `0` | `1` を指定すると論理削除済みを含む |
| order_by | `id` | `id` / `created_at` / `team_name` / `activity_category` / `actual_start_date` / `grant_request_amount` |
| order | `ASC` | `ASC` / `DESC` |
| limit | `50` | 最大200 |
| offset | `0` | オフセット |

### CSVエクスポートのクエリパラメータ

`GET /api/submissions/export/csv` ・ `GET /api/reports/export/csv` は、それぞれ `GET /api/submissions` ・ `GET /api/reports`（一覧取得API）と同じ絞り込み・並び順のクエリパラメータ（`status` / `keyword` / `activity_category` / `year` / `include_deleted` / `order_by` / `order`）に対応している。アプリの一覧画面で現在絞り込まれているデータのみをそのままCSV出力できる。`limit` / `offset` には対応しておらず、絞り込み条件に該当する全件を出力する。

出力する列は一覧取得APIとは異なる（上記「API エンドポイント」の表を参照）。申請CSVは `team_name` 等の基本項目に加えて `section1_json` / `section2_json` / `section4_json` の詳細項目を展開して含むが、完了報告CSVは基本項目のみで、配列項目（応募経緯・設立目的など複数選択の項目）は「、」区切りの文字列としてCSVに出力する。

### 個別取得の仕様

`GET /api/submissions/:id` ・ `GET /api/reports/:id` はいずれも `is_deleted` による絞り込みを**行わない**（論理削除済みのデータも通常どおり取得できる）。これは、アプリの一覧画面で「削除済みを含む」表示にした際に該当行をクリックして詳細を閲覧し、復元（`PATCH` で `is_deleted: 0` に戻す）できるようにするための意図的な仕様。一覧取得・CSVエクスポートは `include_deleted=1` を指定しない限り `is_deleted = 0` のもののみに絞り込まれる。

### トークンエンドポイントの仕様

`edit_token` は申請送信時に自動生成され、申請完了メールに記載されます。  
完了報告フォームの `edit_token` は申請の `edit_token` と共用です。  

| 条件 | 動作 |
|---|---|
| `GET /api/submissions/edit/:token` で 404 | 無効なトークン |
| `PUT /api/submissions/edit/:token` で status が `審査前` 以外 | 403 エラー（編集不可） |
| `GET /api/reports/edit/:token` で 404 | 完了報告未提出（申請データを初期値として新規作成） |
| `PUT /api/reports/edit/:token` で status が `確認前` 以外 | 403 エラー（編集不可） |

#### 添付ファイルの扱い（PUT時）

`PUT /api/submissions/edit/:token` ・ `PUT /api/reports/edit/:token` はいずれも、テキスト項目は送信内容で**上書き**される一方、添付ファイル（写真・PDF・領収書）は以下のルールで**追加（append）**される。上書き・削除は行われない。

- そのフィールドに新しいファイルを添付しなかった場合：既存のファイルをそのまま保持する
- 新しいファイルを添付した場合：既存のファイルに追加する（申請PDF・完了報告の写真／領収書のいずれも同様）

そのため、同じ書類フィールド（例：`docs.regulations`）へ複数回ファイルを追加すると、そのフィールドの値は文字列1件ではなく配列（複数件）になる。詳細は後述の DB スキーマの節を参照。

**実装メモ**：PHPは `POST` メソッドの場合のみ `multipart/form-data` のボディを自動的に `$_POST` / `$_FILES` へパースする。`PUT` では自動パースされないため、`api/helpers/RequestBody.php` の `resolveRequestBody()` が `$_POST` / `$_FILES` が空の場合に `php://input` を手動でパースするフォールバックを提供している。この2つのPUTハンドラを変更する際は、必ず `resolveRequestBody()` 経由でリクエストボディを取得すること（直接 `$_POST` / `$_FILES` を参照すると、実際のPUTリクエストでは常に空になる）。

---

## DB スキーマ

### submissions（要望申請）

| カラム | 型 | デフォルト | 説明 |
|---|---|---|---|
| id | INTEGER | AUTOINCREMENT | 主キー |
| status | TEXT | `審査前` | `審査前` / `審査中` / `承認` / `否決` / `対象外` |
| team_name | TEXT | — | 団体名 |
| team_name_kana | TEXT | — | 団体名フリガナ |
| team_postal_code | TEXT | — | 郵便番号 |
| team_address | TEXT | — | 所在地 |
| established_year | INTEGER | — | 設立年 |
| activity_category | TEXT | — | `ボランティア活動` / `スポーツ活動` / `その他市民活動` |
| representative_name | TEXT | — | 代表者名 |
| representative_email | TEXT | — | 代表者メール |
| representative_phone | TEXT | — | 代表者電話 |
| contact_name | TEXT | — | 担当者名 |
| contact_email | TEXT | — | 担当者メール |
| contact_phone | TEXT | — | 担当者電話 |
| same_as_representative | INTEGER | `0` | 担当者＝代表者フラグ（0/1） |
| project_name | TEXT | — | 事業名 |
| start_date | TEXT | — | 開始日（YYYY-MM-DD） |
| end_date | TEXT | — | 終了日（YYYY-MM-DD） |
| venue | TEXT | — | 開催場所 |
| grant_request_amount | INTEGER | `0` | 助成金要望額 |
| total_expense_amount | INTEGER | `0` | 支出合計 |
| grant_usage_amount | INTEGER | `0` | 助成金使用額合計 |
| section1_json | TEXT | `{}` | 会員構成・助成歴・応募歴・応募の経緯など |
| section2_json | TEXT | `{}` | 参加人数・事業内容・共催情報など |
| section3_json | TEXT | `{}` | 収入明細・支出明細・備考 |
| section4_json | TEXT | `{}` | 設立背景・活動内容・実績PRなど |
| section5_json | TEXT | `{}` | ファイルパスなど（形式は下記を参照） |
| edit_token | TEXT | NULL | 編集用トークン（UNIQUE） |
| created_at | TEXT | `datetime('now', 'localtime')` | 申請日時 |
| updated_at | TEXT | `datetime('now', 'localtime')` | 更新日時（トリガーで自動更新） |
| is_deleted | INTEGER | `0` | 論理削除フラグ（0/1） |
| deleted_at | TEXT | NULL | 削除日時 |

`section5_json` は `{ "photos": string[], "docs": { [field]: string | string[] } }` の形。`photos` は常に配列。`docs.*` は初回提出時は文字列1件、編集で追加された後は配列になる（フロント・API側とも文字列・配列の両方に対応済み）。

### submission_logs（編集履歴）

`submissions` の変更履歴のみを記録する。`reports`（完了報告）には対応する履歴テーブルが存在せず、完了報告の変更は記録されない（`ReportPatch` は意図的にログ記録をスキップしている）。

| カラム | 型 | デフォルト | 説明 |
|---|---|---|---|
| id | INTEGER | AUTOINCREMENT | 主キー |
| submission_id | INTEGER | — | submissions への外部キー |
| changed_by | TEXT | `staff` | 変更者（`staff` / `applicant`） |
| changed_at | TEXT | `datetime('now', 'localtime')` | 変更日時 |
| field_name | TEXT | — | 変更フィールド名 |
| old_value | TEXT | — | 変更前の値 |
| new_value | TEXT | — | 変更後の値 |

### reports（完了報告）

| カラム | 型 | デフォルト | 説明 |
|---|---|---|---|
| id | INTEGER | AUTOINCREMENT | 主キー |
| status | TEXT | `確認前` | `確認前` / `要修正` / `確認済` |
| team_name | TEXT | — | 団体名 |
| contact_name | TEXT | — | 担当者名 |
| contact_email | TEXT | — | 担当者メール |
| contact_phone | TEXT | — | 担当者電話 |
| project_name | TEXT | — | 事業名 |
| activity_category | TEXT | `''` | `ボランティア活動` / `スポーツ活動` / `その他市民活動` |
| actual_start_date | TEXT | — | 実施開始日（YYYY-MM-DD） |
| actual_end_date | TEXT | — | 実施終了日（YYYY-MM-DD） |
| actual_venue | TEXT | — | 実施場所 |
| grant_request_amount | INTEGER | `0` | 助成金要望額 |
| total_expense_amount | INTEGER | `0` | 支出合計 |
| grant_usage_amount | INTEGER | `0` | 助成金使用額合計 |
| report_section1_json | TEXT | `{}` | 団体名・担当者情報 |
| report_section2_json | TEXT | `{}` | 事業情報・参加人数・収支決算・ファイルパス（形式は下記を参照） |
| edit_token | TEXT | NULL | 編集用トークン（submissions.edit_token と共用・UNIQUE） |
| created_at | TEXT | `datetime('now', 'localtime')` | 報告日時 |
| updated_at | TEXT | `datetime('now', 'localtime')` | 更新日時（トリガーで自動更新） |
| is_deleted | INTEGER | `0` | 論理削除フラグ（0/1） |
| deleted_at | TEXT | NULL | 削除日時 |

`report_section2_json` は事業情報・収支決算のフィールドに加え、`photos` / `receipts`（いずれも常に配列）を同一オブジェクト内に持つ。新規提出・編集のいずれでもこの2フィールドにファイルパスが反映される。編集時、テキスト項目は送信内容で上書きされるが、`photos` / `receipts` は既存分を保持したうえで新規アップロード分を追加する。

### インデックス

| インデックス名 | 対象テーブル | カラム |
|---|---|---|
| idx_submissions_status | submissions | status |
| idx_submissions_team_name | submissions | team_name |
| idx_submissions_start_date | submissions | start_date |
| idx_submissions_created_at | submissions | created_at |
| idx_logs_submission_id | submission_logs | submission_id |
| idx_reports_team_name | reports | team_name |
| idx_reports_created_at | reports | created_at |
| idx_reports_actual_start | reports | actual_start_date |

### トリガー

| トリガー名 | 対象 | 動作 |
|---|---|---|
| trg_submissions_updated_at | submissions | UPDATE時に`updated_at`を自動更新 |
| trg_reports_updated_at | reports | UPDATE時に`updated_at`を自動更新 |

---

## レンタルサーバーへの配置

```
公開ディレクトリ/
├── index.html   ← form/dist/ の中身をここに配置
├── assets/
├── api/
└── ...
db/              ← 公開ディレクトリの外（直接アクセス不可）
uploads/         ← 公開ディレクトリの外（直接アクセス不可）
```

---

## セットアップ

### 1. 環境変数の設定

`api/.env.example` をコピーして `api/.env` を作成し、各値を設定してください。

```bash
cp api/.env.example api/.env
```

```
APP_URL=
SMTP_HOST=
SMTP_USER=
SMTP_PASS=
STAFF_TOKEN=
FOUNDATION_EMAIL=
FOUNDATION_NAME=
```

`STAFF_TOKEN` は以下のコマンドで生成してください。

```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

### 2. データベースの初期化

```bash
sqlite3 db/m-zaidan.sqlite < db/schema.sql
```

### 3. フォームのビルド

```bash
cd form
npm install
npm run build
```

`form/dist/` の中身を公開ディレクトリに配置してください。

### 4. Composer のインストール

```bash
cd api
composer install
```

### 5. テストの実行

API（PHPUnit）:

```bash
cd api
vendor/bin/phpunit
```

フォーム（Vitest）:

```bash
cd form
npm run test:run
```

---

## フォーム設計メモ

### 要望申請フォーム（Application.tsx）

- React Hook Form で状態管理（`useForm` は `Application.tsx` に1つ）
- 各 Section に `register` / `errors` / `watch` / `setValue` / `control` を props で渡す
- LocalStorage に途中保存（同一端末・同一ブラウザ・通常モードのみ）
- STEP 移動時・明示的な保存ボタン押下時に自動保存。**「一時保存」ボタンはバリデーションを行わずに保存する**（下書きとして未入力のまま保存可能）
- フォームアクセス時に保存データを検出 → 再開確認ダイアログを表示
- 再開時、保存内容を全 STEP にわたって再検証する。不備があれば保存時の STEP ではなく不備のある STEP へ誘導し、警告メッセージを表示する（`useStepForm` の `resumeWarning`）
- 送信時はフォーム全体（現在表示していない STEP も含む）を再検証する。不備があった場合は該当 STEP へ自動的に移動し、警告メッセージを表示する（`onInvalid` ハンドラ）。これが無いと、非表示 STEP のエラーにより送信ボタンが無反応に見える不具合につながる
- 送信完了後に LocalStorage をクリア
- ファイル（写真・PDF）は LocalStorage に保存不可のため再開時に再選択が必要
- LocalStorageキー：`zaidan_draft` / `zaidan_draft_step`（通常モード）、`zaidan_draft_edit_<トークン>` / `zaidan_draft_edit_step_<トークン>`（編集トークンでの再編集時）
  - 通常モードと編集モードでキーを分けているのは、共通のキーのままだと「トークン無しで新規入力中の一時保存データ」が「編集トークンでの再編集時」の再開候補として誤って表示されてしまう（またはその逆）ため。編集トークンごとにもキーを分けているため、同一ブラウザで複数の申請を編集トークン経由で編集してもデータは混ざらない
  - 編集トークンアクセス時は、サーバーからの初期データ取得（`reset`）が完了するまで再開ダイアログの判定自体を保留する（`useStepForm` の `enabled` オプション）。これが無いと、サーバーデータの反映前にダイアログが表示され、ユーザーが「再開する」を押した際に取得済みのサーバーデータを古い LocalStorage の内容で上書きしてしまうことがあった
- 「実施時期」（開始日・終了日）は `src/utils/dateRange.ts` の `getYearRange()` により、**現在年から3年間**（例: 2026年なら2026年〜2028年）に制限している。`<input type="date">` の `min` / `max` 属性でブラウザのカレンダーウィジェットの選択可能範囲を制限しつつ、react-hook-form の `validate` でも同じ範囲をチェックする（手入力で範囲外を直接入力された場合の保険）

### STEP 構成（申請フォーム）

| STEP | 内容 |
|---|---|
| 1 | 申請団体の概要（団体情報・助成歴・代表者・担当者） |
| 2 | 申請事業について（基本情報・参加人数・事業内容・共催） |
| 3 | 収支予算書（収入・支出・自動計算） |
| 4 | 団体の活動について（設立背景・活動内容・実績PR） |
| 5 | 添付資料（活動写真・PDF 5種・確認チェック）。編集時（トークンアクセス時）は添付が任意になり、未添付の場合は既存ファイルを保持する |

### 完了報告フォーム（Report.tsx）

- React Hook Form で状態管理（`useForm` は `Report.tsx` に1つ）
- 各 ReportSection に `register` / `errors` / `watch` / `control` を props で渡す
- LocalStorage に途中保存（申請フォームと同じ仕組み。一時保存はバリデーションなし）
- 再開時・送信時の全 STEP 再検証、および `onInvalid` による不備 STEP への自動誘導は申請フォームと同じ仕組みを利用する
- 送信完了後に LocalStorage をクリア
- ファイルは LocalStorage に保存不可のため再開時に再選択が必要
- LocalStorageキー：`zaidan_report_draft` / `zaidan_report_draft_step`（通常モード）、`zaidan_report_draft_edit_<トークン>` / `zaidan_report_draft_edit_step_<トークン>`（編集トークン時）。キー分離の理由・再開ダイアログの判定タイミングについては要望申請フォームと同じ（上記参照）
- 「実施時期」（実施開始日・終了日）も同じ `getYearRange()` で範囲制限しているが、完了報告は**既に実施済みの事業の実績**を報告するものなので、申請フォーム（現在年起点）とは異なり**去年を起点に3年間**（例: 現在2026年なら2025年〜2027年）に制限している

### STEP 構成（完了報告フォーム）

| STEP | 内容 |
|---|---|
| 1 | 申請団体の概要（団体名・担当者情報） |
| 2 | 申請事業について（事業名・実施期間・場所・参加人数・収支決算報告） |
| 3 | 添付資料（活動写真1〜2枚・領収書複数枚・確認チェック）。編集時（トークンアクセス時）は添付が任意になり、未添付の場合は既存ファイルを保持する |

### 郵便番号自動補完

zipcloud API（`https://zipcloud.ibsnet.co.jp/api/search`）を使用。  
市区町村までの自動補完。番地・建物名は手入力。  

### メール送信

- PHPMailer + レンタルサーバーの SMTP を使用
- キャリアメール（docomo・au・softbank）は受信できない場合あり（フォーム上に注意書きを表示）

---

## ライセンス

Copyright (c) 2026 QWEL.DESIGN  

本ソフトウェアは三谷市民文化振興財団からの委託により開発されました。  
無断での複製・改変・再配布を禁じます。  

---

## 制作者 | Author

[QWEL.DESIGN](https://qwel.design)  
福井を拠点に活動するフロントエンド開発者  
Front-end developer based in Fukui, Japan  
