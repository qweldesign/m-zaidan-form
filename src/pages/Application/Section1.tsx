// src/pages/Application/Section1.tsx

const APPLICATION_ROUTES = [
  '当財団のホームページ',
  '新聞広告',
  '福井新聞「ぷりん」',
  'テレビ',
  'ラジオ',
  'ポスター',
  '財団広報誌「遊楽彩祭」',
  '他団体からの紹介',
]

function Section1() {
  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 1 / 5</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅰ．申請団体の概要</h2>
      </div>

      {/* 団体情報 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">団体情報</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 団体名称 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">団体名称<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

            {/* 団体名称フリガナ */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">団体名称フリガナ<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

            {/* 団体所在地 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  団体所在地<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5 space-y-3">

                {/* 郵便番号 */}
                <div className="flex items-center gap-3">
                  <span className="text-slate-500 whitespace-nowrap">〒</span>
                  <input
                    type="text"
                    inputMode="numeric"
                    maxLength={8}
                    className="w-48 p-3 border border-slate-300 rounded-lg bg-white"
                  />
                  <button
                    type="button"
                    className="px-4 py-3 rounded-lg bg-sky-500 text-white text-sm font-bold hover:bg-sky-600 transition whitespace-nowrap"
                  >
                    住所を検索
                  </button>
                </div>

                {/* 住所 */}
                <input
                  type="text"
                  placeholder="住所（番地・建物名まで）"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                />

              </td>
            </tr>

            {/* 設立年 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">設立年<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  inputMode="numeric"
                  maxLength={4}
                  className="w-32 p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

            {/* 会員構成 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">会員構成</label>
                <p className="mt-2 text-sm text-orange-600">※ 申請時点の人数で構いません</p>
              </td>
              <td className="block md:table-cell p-5">
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                  {(
                    [
                      { label: '20歳以下', field: 'section1.members.under20' },
                      { label: '21歳～40歳', field: 'section1.members.age21to40' },
                      { label: '41歳～60歳', field: 'section1.members.age41to60' },
                      { label: '61歳以上', field: 'section1.members.over61' },
                    ] as const
                  ).map(({ label, field }) => (
                    <div key={field} className="p-4 rounded-xl bg-slate-50 border border-slate-200">
                      <label className="block mb-2 text-sm font-bold text-slate-700">{label}</label>
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={0}
                          placeholder="0"
                          className="w-full max-w-24 p-2 border border-slate-300 rounded bg-white"
                        />
                        <span className="text-sm text-slate-500 whitespace-nowrap">名</span>
                      </div>
                    </div>
                  ))}

                  {/* 合計（表示のみ・読み取り専用） */}
                  <div className="p-4 rounded-xl bg-sky-50 border border-sky-200">
                    <label className="block mb-2 text-sm font-bold text-sky-800">合計</label>
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        readOnly
                        value={0} // 後で自動計算を実装
                        className="w-full max-w-24 p-2 border border-slate-200 rounded bg-sky-50 text-sky-800 font-bold"
                      />
                      <span className="text-sm text-slate-500 whitespace-nowrap">名</span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            {/* 活動内容 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">活動内容<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <div className="flex flex-col md:flex-row gap-3">
                  {(['ボランティア活動', 'スポーツ活動', 'その他市民活動'] as const).map((label) => (
                    <label
                      key={label}
                      className="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50"
                    >
                      <input
                        type="radio"
                        value={label}
                      />
                      <span>{label}</span>
                    </label>
                  ))}
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 助成歴・応募歴 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">助成歴・応募歴</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 助成歴 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">助成歴</label>
                <p className="mt-2 text-sm text-slate-500">過去の助成実績をご記入ください</p>
              </td>
              <td className="block md:table-cell p-5">
                <div className="space-y-4">

                  {/* 当財団 */}
                  <div className="p-4 rounded-xl border border-slate-200 bg-slate-50">
                    <p className="font-bold text-slate-700 mb-4">当財団より助成を受けた回数</p>
                    <div className="flex flex-wrap items-center gap-3">
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={0}
                          placeholder="0"
                          className="w-24 p-3 border border-slate-300 rounded-lg bg-white"
                        />
                        <span className="text-slate-600">回</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-slate-600">最新</span>
                        <input
                          type="text"
                          inputMode="numeric"
                          maxLength={4}
                          className="w-28 p-3 border border-slate-300 rounded-lg bg-white"
                        />
                        <span className="text-slate-600">年</span>
                      </div>
                    </div>
                  </div>

                  {/* 他財団 */}
                  <div className="p-4 rounded-xl border border-slate-200 bg-slate-50">
                    <p className="font-bold text-slate-700 mb-4">他財団等より助成を受けた回数</p>
                    <div className="flex flex-wrap items-center gap-3">
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={0}
                          placeholder="0"
                          className="w-24 p-3 border border-slate-300 rounded-lg bg-white"
                        />
                        <span className="text-slate-600">回</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-slate-600">最新</span>
                        <input
                          type="text"
                          inputMode="numeric"
                          maxLength={4}
                          className="w-28 p-3 border border-slate-300 rounded-lg bg-white"
                        />
                        <span className="text-slate-600">年</span>
                      </div>
                    </div>
                  </div>

                </div>
              </td>
            </tr>

            {/* 応募歴 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">応募歴</label>
                <p className="mt-2 text-sm text-slate-500">今回を含まない過去応募回数</p>
              </td>
              <td className="block md:table-cell p-5">
                <div className="p-4 rounded-xl border border-slate-200 bg-slate-50">
                  <p className="font-bold text-slate-700 mb-4">当財団へ応募した回数</p>
                  <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        min={0}
                        placeholder="0"
                        className="w-24 p-3 border border-slate-300 rounded-lg bg-white"
                      />
                      <span className="text-slate-600">回</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-slate-600">最新</span>
                      <input
                        type="text"
                        inputMode="numeric"
                        maxLength={4}
                        className="w-28 p-3 border border-slate-300 rounded-lg bg-white"
                      />
                      <span className="text-slate-600">年</span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            {/* 応募の経緯 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">応募の経緯</label>
                <p className="mt-2 text-sm text-orange-600">※ 複数選択可</p>
              </td>
              <td className="block md:table-cell p-5">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {APPLICATION_ROUTES.map((label) => (
                    <label
                      key={label}
                      className="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50"
                    >
                      <input
                        type="checkbox"
                        value={label}
                        className="w-4 h-4"
                      />
                      <span className="text-slate-700">{label}</span>
                    </label>
                  ))}
                </div>
                <div className="mt-5">
                  <label className="block font-bold mb-2">その他</label>
                  <input
                    type="text"
                    placeholder="自由入力"
                    className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  />
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 代表者情報 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">代表者情報</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">代表者名<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">代表者名フリガナ<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">電話番号<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="tel"
                  className="w-full md:w-[320px] p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">メールアドレス<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="email"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                />
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 担当者情報 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100 flex items-center justify-between">
          <h3 className="font-bold text-sky-900 text-lg">担当者情報</h3>
          <label className="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
            <input
              type="checkbox"
            />
            代表者と同じ
          </label>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">担当者名<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white disabled:bg-slate-100 disabled:text-slate-400"
                />
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">担当者名フリガナ<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white disabled:bg-slate-100 disabled:text-slate-400"
                />
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">電話番号<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="tel"
                  className="w-full md:w-[320px] p-3 border border-slate-300 rounded-lg bg-white disabled:bg-slate-100 disabled:text-slate-400"
                />
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">メールアドレス<span className="text-red-500 ml-1">*</span></label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="email"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white disabled:bg-slate-100 disabled:text-slate-400"
                />
              </td>
            </tr>

          </tbody>
        </table>
      </section>

    </section>
  )
}

export default Section1
