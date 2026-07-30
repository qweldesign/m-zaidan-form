// src/pages/Entrance.tsx

import { Link } from 'react-router-dom'

function Entrance() {
  return (
    <>
      <div className="p-3 leading-relaxed">
        <h2 className="my-3 font-bold text-2xl">要望申請フォーム</h2>
        <p>助成金の要望申請はこちらから行ってください。入力途中で保存・再編集が可能です。
          <br />※ブラウザをプライベートモードで使用する場合、入力途中で保存・再編集ができませんのでご注意ください。
        </p>
        <h3 className="my-3 font-bold text-xl">ご入力頂く内容</h3>
        <ol className="list-[upper-roman] list-inside">
          <li>申請団体の概要</li>
          <li>申請事業の内容</li>
          <li>収支予算書</li>
          <li>申請団体の日頃の活動内容</li>
        </ol>
        <h3 className="my-3 font-bold text-xl">添付資料</h3>
        <ul className="list-disc list-inside">
          <li>日頃の活動が分かる写真3枚 (普段の活動の様子が分かるもの)</li>
          <li>団体規約 (あるいは会則、定款、これに類するもの) (PDF形式)</li>
          <li>直近年度の活動報告書 (申請団体が作成した様式で可) (PDF形式)</li>
          <li>直近年度の収支決算書 (申請団体が作成した様式で可) (PDF形式)</li>
          <li>直近年度の活動計画書 (申請団体が作成した様式で可) (PDF形式)</li>
          <li>直近年度の収支計画書 (申請団体が作成した様式で可) (PDF形式)</li>
        </ul>
        <Link to="/application" className="block w-3xs my-6 mx-auto py-3 rounded bg-sky-500 hover:bg-sky-200 text-white hover:text-black text-center transition-colors duration-300">
          要望申請フォームへ
        </Link>
      </div>
      <div className="p-3 leading-relaxed">
        <h2 className="my-3 font-bold text-2xl">完了報告フォーム</h2>
        <p>助成金の完了報告はこちらから行ってください。入力途中で保存・再編集が可能です。
          <br />※ブラウザをプライベートモードで使用する場合、入力途中で保存・再編集ができませんのでご注意ください。
        </p>
        <h3 className="my-3 font-bold text-xl">ご入力頂く主な内容</h3>
        <ul className="list-disc list-inside">
          <li>実施内容</li>
          <li>収支決算内容</li>
        </ul>
        <h3 className="my-3 font-bold text-xl">添付資料</h3>
        <ul className="list-disc list-inside">
          <li>申請事業の活動実施写真 (1～2枚)</li>
          <li>領収書の写し (助成金使用額分だけでなく申請事業に掛かった全額分)</li>
        </ul>
        <Link to="/report" className="block w-3xs my-6 mx-auto py-3 rounded bg-orange-500 hover:bg-orange-200 text-white hover:text-black text-center transition-colors duration-300">
          完了報告フォームへ
        </Link>
      </div>
    </>
  )
}

export default Entrance
