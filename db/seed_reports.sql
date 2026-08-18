BEGIN TRANSACTION;

INSERT INTO reports (
  status, team_name, contact_name, contact_email, contact_phone,
  project_name, activity_category,
  actual_start_date, actual_end_date, actual_venue,
  grant_request_amount, total_expense_amount, grant_usage_amount,
  report_section1_json, report_section2_json,
  edit_token, is_deleted, deleted_at,
  created_at, updated_at
) VALUES
(
  '確認前', '三谷混声合唱団', '田中 義雄', 'tanaka@example.com', '090-1234-5678',
  '定期演奏会開催事業', 'その他市民活動',
  '2025-06-26', '2025-08-21', '福井市民会館大ホール',
  300000, 507171, 300000,
  '{"teamName":"三谷混声合唱団","contactName":"田中 義雄","contactEmail":"tanaka@example.com","contactPhone":"090-1234-5678"}',
  '{"projectName":"定期演奏会開催事業","activityCategory":"その他市民活動","actualStartDate":"2025-06-26","actualEndDate":"2025-08-21","actualVenue":"福井市民会館大ホール","organizerCount":20,"organizerDays":2,"participantCount":300,"participantDays":1,"actualDetail":"定期演奏会を福井市民会館大ホールにて開催。合唱団員による演奏と地域住民との交流を実施した。","income":{"grantRequest":300000,"memberFees":50000,"donations":20000,"tickets":137171,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":150000,"grantUsage":150000,"memo":"福井市民会館大ホール使用料"},{"id":"2","subject":"音響・照明費","amount":200000,"grantUsage":100000,"memo":""},{"id":"3","subject":"印刷費","amount":80000,"grantUsage":50000,"memo":"プログラム・チラシ"},{"id":"4","subject":"広告宣伝費","amount":77171,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2025/report_photo_abc001.jpg"],"receipts":["uploads/2025/receipt_abc001.pdf"]}',
  'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4', 0, NULL,
  '2025-09-01 10:00:00', '2025-09-01 10:00:00'
),
(
  '確認済', 'みたに絵画サークル', '鈴木 花子', 'suzuki@example.com', '090-2345-6789',
  '地域アート展示会', 'その他市民活動',
  '2025-08-09', '2025-10-21', '越前市文化センター',
  80000, 105745, 80000,
  '{"teamName":"みたに絵画サークル","contactName":"鈴木 花子","contactEmail":"suzuki@example.com","contactPhone":"090-2345-6789"}',
  '{"projectName":"地域アート展示会","activityCategory":"その他市民活動","actualStartDate":"2025-08-09","actualEndDate":"2025-10-21","actualVenue":"越前市文化センター","organizerCount":15,"organizerDays":3,"participantCount":200,"participantDays":1,"actualDetail":"地域住民の絵画作品を展示する展示会を開催。約200名の来場者があり地域文化の振興に貢献した。","income":{"grantRequest":80000,"memberFees":10000,"donations":5000,"tickets":10745,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":50000,"grantUsage":50000,"memo":"越前市文化センター使用料"},{"id":"2","subject":"展示資材費","amount":30000,"grantUsage":30000,"memo":"パネル・額縁"},{"id":"3","subject":"印刷費","amount":25745,"grantUsage":0,"memo":"案内状・チラシ"}],"budgetNote":"","photos":["uploads/2025/report_photo_abc002.jpg"],"receipts":["uploads/2025/receipt_abc002.pdf"]}',
  'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5', 0, NULL,
  '2025-11-01 10:00:00', '2025-11-01 10:00:00'
),
(
  '確認済', '三谷伝統芸能保存会', '佐藤 誠一', 'sato@example.com', '090-3456-7890',
  '伝統芸能継承ワークショップ', 'その他市民活動',
  '2025-07-29', '2025-09-02', '坂井市春江文化ホール',
  300000, 486891, 300000,
  '{"teamName":"三谷伝統芸能保存会","contactName":"佐藤 誠一","contactEmail":"sato@example.com","contactPhone":"090-3456-7890"}',
  '{"projectName":"伝統芸能継承ワークショップ","activityCategory":"その他市民活動","actualStartDate":"2025-07-29","actualEndDate":"2025-09-02","actualVenue":"坂井市春江文化ホール","organizerCount":10,"organizerDays":5,"participantCount":50,"participantDays":5,"actualDetail":"伝統芸能の継承を目的としたワークショップを5日間にわたり開催。若い世代への技術継承が図られた。","income":{"grantRequest":300000,"memberFees":30000,"donations":10000,"tickets":146891,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":100000,"grantUsage":100000,"memo":"坂井市春江文化ホール使用料"},{"id":"2","subject":"講師謝金","amount":200000,"grantUsage":150000,"memo":""},{"id":"3","subject":"備品費","amount":100000,"grantUsage":50000,"memo":"衣装・小道具"},{"id":"4","subject":"印刷費","amount":86891,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2025/report_photo_abc003.jpg"],"receipts":["uploads/2025/receipt_abc003.pdf"]}',
  'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6', 0, NULL,
  '2025-10-01 10:00:00', '2025-10-01 10:00:00'
),
(
  '確認前', '三谷ジャズオーケストラ', '伊藤 美咲', 'ito@example.com', '090-4567-8901',
  'ジャズフェスティバル開催', 'その他市民活動',
  '2025-08-20', '2025-10-14', 'あわら市総合体育館',
  50000, 70775, 50000,
  '{"teamName":"三谷ジャズオーケストラ","contactName":"伊藤 美咲","contactEmail":"ito@example.com","contactPhone":"090-4567-8901"}',
  '{"projectName":"ジャズフェスティバル開催","activityCategory":"その他市民活動","actualStartDate":"2025-08-20","actualEndDate":"2025-10-14","actualVenue":"あわら市総合体育館","organizerCount":30,"organizerDays":1,"participantCount":500,"participantDays":1,"actualDetail":"ジャズフェスティバルを開催し約500名が来場。地域の文化振興と観光促進に貢献した。","income":{"grantRequest":50000,"memberFees":5000,"donations":0,"tickets":15775,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":30000,"grantUsage":30000,"memo":""},{"id":"2","subject":"音響費","amount":20000,"grantUsage":20000,"memo":""},{"id":"3","subject":"広告費","amount":20775,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2025/report_photo_abc004.jpg"],"receipts":["uploads/2025/receipt_abc004.pdf"]}',
  'd4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1', 0, NULL,
  '2025-11-15 10:00:00', '2025-11-15 10:00:00'
),
(
  '確認済', 'みたに写真クラブ', '渡辺 健太', 'watanabe@example.com', '090-5678-9012',
  '写真展・地域の記憶', 'その他市民活動',
  '2025-09-03', '2025-12-01', '永平寺町コミュニティセンター',
  200000, 252930, 200000,
  '{"teamName":"みたに写真クラブ","contactName":"渡辺 健太","contactEmail":"watanabe@example.com","contactPhone":"090-5678-9012"}',
  '{"projectName":"写真展・地域の記憶","activityCategory":"その他市民活動","actualStartDate":"2025-09-03","actualEndDate":"2025-12-01","actualVenue":"永平寺町コミュニティセンター","organizerCount":12,"organizerDays":4,"participantCount":150,"participantDays":1,"actualDetail":"地域の歴史的写真を展示する写真展を開催。150名の来場者に地域の記憶を伝えることができた。","income":{"grantRequest":200000,"memberFees":20000,"donations":10000,"tickets":22930,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":80000,"grantUsage":80000,"memo":""},{"id":"2","subject":"展示資材費","amount":100000,"grantUsage":100000,"memo":"パネル・額縁・照明"},{"id":"3","subject":"印刷費","amount":72930,"grantUsage":20000,"memo":"図録・案内状"}],"budgetNote":"","photos":["uploads/2025/report_photo_abc005.jpg"],"receipts":["uploads/2025/receipt_abc005.pdf"]}',
  'e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', 0, NULL,
  '2026-01-05 10:00:00', '2026-01-05 10:00:00'
),
(
  '要修正', '三谷卓球連盟', '吉田 陽子', 'yoshida@example.com', '090-0123-4567',
  '卓球オープントーナメント', 'スポーツ活動',
  '2025-12-07', '2026-01-27', '小浜市文化会館',
  80000, 129497, 80000,
  '{"teamName":"三谷卓球連盟","contactName":"吉田 陽子","contactEmail":"yoshida@example.com","contactPhone":"090-0123-4567"}',
  '{"projectName":"卓球オープントーナメント","activityCategory":"スポーツ活動","actualStartDate":"2025-12-07","actualEndDate":"2026-01-27","actualVenue":"小浜市文化会館","organizerCount":8,"organizerDays":2,"participantCount":80,"participantDays":2,"actualDetail":"卓球オープントーナメントを2日間開催。80名の参加者が集まり活発な競技が行われた。","income":{"grantRequest":80000,"memberFees":10000,"donations":0,"tickets":39497,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":60000,"grantUsage":60000,"memo":""},{"id":"2","subject":"賞品費","amount":30000,"grantUsage":20000,"memo":""},{"id":"3","subject":"印刷費","amount":39497,"grantUsage":0,"memo":""}],"budgetNote":"領収書の一部が不鮮明のため再提出を求められた。","photos":["uploads/2025/report_photo_abc006.jpg"],"receipts":["uploads/2025/receipt_abc006.pdf"]}',
  'e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b3', 0, NULL,
  '2026-02-10 10:00:00', '2026-02-10 10:00:00'
),
(
  '確認前', 'みたに水泳クラブ', '田中 義雄', 'tanaka@example.com', '090-1234-5678',
  '市民水泳大会', 'スポーツ活動',
  '2025-12-21', '2026-02-05', '福井市民会館大ホール',
  200000, 304650, 200000,
  '{"teamName":"みたに水泳クラブ","contactName":"田中 義雄","contactEmail":"tanaka@example.com","contactPhone":"090-1234-5678"}',
  '{"projectName":"市民水泳大会","activityCategory":"スポーツ活動","actualStartDate":"2025-12-21","actualEndDate":"2026-02-05","actualVenue":"福井市民プール","organizerCount":15,"organizerDays":2,"participantCount":120,"participantDays":2,"actualDetail":"市民水泳大会を開催。120名の参加者が各種目で競技し盛大に開催できた。","income":{"grantRequest":200000,"memberFees":30000,"donations":5000,"tickets":69650,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":120000,"grantUsage":120000,"memo":""},{"id":"2","subject":"備品費","amount":80000,"grantUsage":80000,"memo":"タイム計測機器レンタル"},{"id":"3","subject":"賞品費","amount":60000,"grantUsage":0,"memo":""},{"id":"4","subject":"印刷費","amount":44650,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2025/report_photo_abc007.jpg"],"receipts":["uploads/2025/receipt_abc007.pdf"]}',
  'f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c4', 0, NULL,
  '2026-03-01 10:00:00', '2026-03-01 10:00:00'
),
(
  '確認済', 'みたに環境を守る会', '伊藤 美咲', 'ito@example.com', '090-4567-8901',
  '環境啓発イベント', 'ボランティア活動',
  '2026-02-11', '2026-03-24', 'あわら市総合体育館',
  150000, 197461, 150000,
  '{"teamName":"みたに環境を守る会","contactName":"伊藤 美咲","contactEmail":"ito@example.com","contactPhone":"090-4567-8901"}',
  '{"projectName":"環境啓発イベント","activityCategory":"ボランティア活動","actualStartDate":"2026-02-11","actualEndDate":"2026-03-24","actualVenue":"あわら市総合体育館","organizerCount":20,"organizerDays":3,"participantCount":200,"participantDays":1,"actualDetail":"環境問題をテーマにした啓発イベントを開催。地域住民200名が参加し環境意識の向上に貢献した。","income":{"grantRequest":150000,"memberFees":20000,"donations":15000,"tickets":12461,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":80000,"grantUsage":80000,"memo":""},{"id":"2","subject":"講師謝金","amount":50000,"grantUsage":50000,"memo":""},{"id":"3","subject":"資料印刷費","amount":40000,"grantUsage":20000,"memo":""},{"id":"4","subject":"備品費","amount":27461,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2026/report_photo_abc008.jpg"],"receipts":["uploads/2026/receipt_abc008.pdf"]}',
  'd4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a3', 0, NULL,
  '2026-04-01 10:00:00', '2026-04-01 10:00:00'
),
(
  '確認前', '三谷子ども食堂運営会', '中村 浩二', 'nakamura@example.com', '090-7890-1234',
  '子ども食堂運営支援', 'ボランティア活動',
  '2026-04-29', '2026-06-22', '大野市文化会館',
  150000, 220410, 150000,
  '{"teamName":"三谷子ども食堂運営会","contactName":"中村 浩二","contactEmail":"nakamura@example.com","contactPhone":"090-7890-1234"}',
  '{"projectName":"子ども食堂運営支援","activityCategory":"ボランティア活動","actualStartDate":"2026-04-29","actualEndDate":"2026-06-22","actualVenue":"大野市文化会館","organizerCount":10,"organizerDays":8,"participantCount":50,"participantDays":8,"actualDetail":"子ども食堂を月2回開催。延べ400名の子どもたちに温かい食事を提供することができた。","income":{"grantRequest":150000,"memberFees":10000,"donations":30000,"tickets":30410,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"食材費","amount":120000,"grantUsage":100000,"memo":""},{"id":"2","subject":"会場費","amount":50000,"grantUsage":50000,"memo":""},{"id":"3","subject":"備品費","amount":30000,"grantUsage":0,"memo":"調理器具"},{"id":"4","subject":"消耗品費","amount":20410,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2026/report_photo_abc009.jpg"],"receipts":["uploads/2026/receipt_abc009.pdf"]}',
  'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d7', 0, NULL,
  '2026-07-01 10:00:00', '2026-07-01 10:00:00'
),
(
  '確認前', 'みたに防災サポーターズ', '小林 さくら', 'kobayashi@example.com', '090-8901-2345',
  '防災訓練・啓発事業', 'ボランティア活動',
  '2026-05-20', '2026-08-10', '鯖江市まなべの館',
  100000, 162749, 100000,
  '{"teamName":"みたに防災サポーターズ","contactName":"小林 さくら","contactEmail":"kobayashi@example.com","contactPhone":"090-8901-2345"}',
  '{"projectName":"防災訓練・啓発事業","activityCategory":"ボランティア活動","actualStartDate":"2026-05-20","actualEndDate":"2026-08-10","actualVenue":"鯖江市まなべの館","organizerCount":12,"organizerDays":4,"participantCount":100,"participantDays":2,"actualDetail":"防災訓練と啓発セミナーを計4回開催。地域住民の防災意識向上と自助・共助の精神醸成に貢献した。","income":{"grantRequest":100000,"memberFees":10000,"donations":20000,"tickets":32749,"incomeMemo":{"grantRequest":"","memberFees":"","donations":"","tickets":""}},"expenses":[{"id":"1","subject":"会場費","amount":60000,"grantUsage":60000,"memo":""},{"id":"2","subject":"資材費","amount":60000,"grantUsage":40000,"memo":"防災グッズ・資料"},{"id":"3","subject":"講師謝金","amount":30000,"grantUsage":0,"memo":""},{"id":"4","subject":"印刷費","amount":12749,"grantUsage":0,"memo":""}],"budgetNote":"","photos":["uploads/2026/report_photo_abc010.jpg"],"receipts":["uploads/2026/receipt_abc010.pdf"]}',
  'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e8', 0, NULL,
  '2026-08-15 10:00:00', '2026-08-15 10:00:00'
);

COMMIT;
