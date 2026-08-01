# M ZAIDAN FORM

助成金の要望申請および完了報告フォーム

---

## ディレクトリ構成

```

public/
└── images/
    └── m-zaidan_logo.png
src/
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
| section5_json | TEXT | `{}` | ファイルパスなど |
| edit_token | TEXT | NULL | 編集用トークン（UNIQUE） |
| created_at | TEXT | `datetime('now', 'localtime')` | 申請日時 |
| updated_at | TEXT | `datetime('now', 'localtime')` | 更新日時（トリガーで自動更新） |
| is_deleted | INTEGER | `0` | 論理削除フラグ（0/1） |
| deleted_at | TEXT | NULL | 削除日時 |

### submission_logs（編集履歴）

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
| report_section2_json | TEXT | `{}` | 事業情報・参加人数・収支決算・ファイルパス |
| edit_token | TEXT | NULL | 編集用トークン（submissions.edit_token と共用・UNIQUE） |
| created_at | TEXT | `datetime('now', 'localtime')` | 報告日時 |
| updated_at | TEXT | `datetime('now', 'localtime')` | 更新日時（トリガーで自動更新） |
| is_deleted | INTEGER | `0` | 論理削除フラグ（0/1） |
| deleted_at | TEXT | NULL | 削除日時 |

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

## ライセンス

Copyright (c) 2026 QWEL.DESIGN  

本ソフトウェアは三谷市民文化振興財団からの委託により開発されました。  
無断での複製・改変・再配布を禁じます。  

---

## 制作者 | Author

[QWEL.DESIGN](https://qwel.design)  
福井を拠点に活動するフロントエンド開発者  
Front-end developer based in Fukui, Japan  
