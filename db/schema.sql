-- 申請データ本体
CREATE TABLE IF NOT EXISTS submissions (
  id                      INTEGER PRIMARY KEY AUTOINCREMENT,

  -- ステータス管理（財団スタッフが更新）
  status                  TEXT NOT NULL DEFAULT '審査前'
                          CHECK(status IN ('審査前', '審査中', '承認', '否決', '対象外')),

  -- Section1: 団体情報（検索・ソート用に個別カラム）
  team_name               TEXT NOT NULL,
  team_name_kana          TEXT NOT NULL,
  team_postal_code        TEXT NOT NULL,
  team_address            TEXT NOT NULL,
  established_year        INTEGER NOT NULL,
  activity_category       TEXT NOT NULL
                          CHECK(activity_category IN ('ボランティア活動', 'スポーツ活動', 'その他市民活動')),

  -- 代表者情報（連絡先として個別カラム）
  representative_name     TEXT NOT NULL,
  representative_email    TEXT NOT NULL,
  representative_phone    TEXT NOT NULL,

  -- 担当者情報
  contact_name            TEXT NOT NULL,
  contact_email           TEXT NOT NULL,
  contact_phone           TEXT NOT NULL,
  same_as_representative  INTEGER NOT NULL DEFAULT 0 CHECK(same_as_representative IN (0, 1)),

  -- Section2: 事業情報（検索用に一部個別カラム）
  project_name            TEXT NOT NULL,
  start_date              TEXT NOT NULL,  -- YYYY-MM-DD
  end_date                TEXT NOT NULL,  -- YYYY-MM-DD
  venue                   TEXT NOT NULL,

  -- Section3: 収支（集計用に個別カラム）
  grant_request_amount    INTEGER NOT NULL DEFAULT 0,  -- 助成金要望額
  total_expense_amount    INTEGER NOT NULL DEFAULT 0,  -- 支出合計
  grant_usage_amount      INTEGER NOT NULL DEFAULT 0,  -- 助成金使用額合計

  -- 詳細データはJSON格納
  section1_json           TEXT NOT NULL DEFAULT '{}',  -- 会員構成・助成歴・応募歴・応募の経緯など
  section2_json           TEXT NOT NULL DEFAULT '{}',  -- 参加人数・事業内容・共催情報など
  section3_json           TEXT NOT NULL DEFAULT '{}',  -- 収入明細・支出明細・備考
  section4_json           TEXT NOT NULL DEFAULT '{}',  -- 設立背景・活動内容・実績PRなど
  section5_json           TEXT NOT NULL DEFAULT '{}',  -- ファイルパスなど

  -- 編集用トークン
  edit_token              TEXT UNIQUE,

  -- タイムスタンプ
  created_at              TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
  updated_at              TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),

  -- 削除フラグ
  is_deleted              INTEGER NOT NULL DEFAULT 0 CHECK(is_deleted IN (0, 1)),
  deleted_at              TEXT DEFAULT NULL
);

-- 編集履歴（証跡テーブル）
CREATE TABLE IF NOT EXISTS submission_logs (
  id                      INTEGER PRIMARY KEY AUTOINCREMENT,
  submission_id           INTEGER NOT NULL REFERENCES submissions(id),
  changed_by              TEXT NOT NULL DEFAULT 'staff',  -- 将来的にスタッフ名を入れる
  changed_at              TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
  field_name              TEXT NOT NULL,   -- 変更されたフィールド名
  old_value               TEXT,            -- 変更前の値
  new_value               TEXT            -- 変更後の値
);

-- インデックス（Electronアプリのソート・フィルタ高速化）
CREATE INDEX IF NOT EXISTS idx_submissions_status       ON submissions(status);
CREATE INDEX IF NOT EXISTS idx_submissions_team_name    ON submissions(team_name);
CREATE INDEX IF NOT EXISTS idx_submissions_start_date   ON submissions(start_date);
CREATE INDEX IF NOT EXISTS idx_submissions_created_at   ON submissions(created_at);
CREATE INDEX IF NOT EXISTS idx_logs_submission_id       ON submission_logs(submission_id);

-- updated_at を自動更新するトリガー
CREATE TRIGGER IF NOT EXISTS trg_submissions_updated_at
  AFTER UPDATE ON submissions
  FOR EACH ROW
BEGIN
  UPDATE submissions
  SET updated_at = datetime('now', 'localtime')
  WHERE id = OLD.id;
END;

-- 報告データ本体
CREATE TABLE IF NOT EXISTS reports (
  id                      INTEGER PRIMARY KEY AUTOINCREMENT,

  -- ステータス管理（財団スタッフが更新）
  status                  TEXT NOT NULL DEFAULT '確認前'
                          CHECK(status IN ('確認前', '要修正', '確認済')),

  -- 基本情報（検索・ソート用に個別カラム）
  team_name               TEXT NOT NULL,
  contact_name            TEXT NOT NULL,
  contact_email           TEXT NOT NULL,
  contact_phone           TEXT NOT NULL,
  project_name            TEXT NOT NULL,
  activity_category       TEXT NOT NULL DEFAULT ''
                          CHECK(activity_category IN ('ボランティア活動', 'スポーツ活動', 'その他市民活動')),
  actual_start_date       TEXT NOT NULL,
  actual_end_date         TEXT NOT NULL,
  actual_venue            TEXT NOT NULL,

  -- 集計（検索・ソート用）
  grant_request_amount    INTEGER NOT NULL DEFAULT 0,
  total_expense_amount    INTEGER NOT NULL DEFAULT 0,
  grant_usage_amount      INTEGER NOT NULL DEFAULT 0,

  -- 詳細データ
  report_section1_json    TEXT NOT NULL DEFAULT '{}',
  report_section2_json    TEXT NOT NULL DEFAULT '{}',

  -- 編集用トークン
  edit_token              TEXT UNIQUE,

  -- タイムスタンプ
  created_at              TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
  updated_at              TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),

  -- 削除フラグ
  is_deleted              INTEGER NOT NULL DEFAULT 0 CHECK(is_deleted IN (0, 1)),
  deleted_at              TEXT DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_reports_team_name      ON reports(team_name);
CREATE INDEX IF NOT EXISTS idx_reports_created_at     ON reports(created_at);
CREATE INDEX IF NOT EXISTS idx_reports_actual_start   ON reports(actual_start_date);

CREATE TRIGGER IF NOT EXISTS trg_reports_updated_at
  AFTER UPDATE ON reports
  FOR EACH ROW
BEGIN
  UPDATE reports
  SET updated_at = datetime('now', 'localtime')
  WHERE id = OLD.id;
END;
