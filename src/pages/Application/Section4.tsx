// src/pages/Application/Section4.tsx

const ESTABLISHMENT_PURPOSES = [
  '青少年および高齢者支援', '障がい者支援', '環境美化・保全活動',
  '児童青少年の健全育成のためのスポーツ活動',
  '健康増進のための高齢者スポーツ活動', '障がい者スポーツ活動',
  '地域に密着した諸市民活動', 'その他',
]

function Section4() {
  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 4 / 5</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅳ．団体の活動について</h2>
        <p className="mt-3 text-slate-600 leading-7">
          申請事業以外の日頃の活動についてご記入ください。
        </p>
      </div>

      {/* 設立経緯 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">設立経緯</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 主な設立目的 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">主な設立目的</label>
                <p className="mt-2 text-sm text-sky-600">※ 複数選択可</p>
              </td>
              <td className="block md:table-cell p-5">
                <div className="grid grid-cols-1 gap-3">
                  {ESTABLISHMENT_PURPOSES.map((label) => (
                    <label
                      key={label}
                      className="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50"
                    >
                      <input
                        type="checkbox"
                        value={label}
                      />
                      <span>{label}</span>
                    </label>
                  ))}
                </div>
              </td>
            </tr>

            {/* 設立の背景・きっかけ */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  設立から現在に至るまでの経緯や<br />団体の変遷を記入してください<span className="text-red-500 ml-1">*</span>
                </label>
                <p className="mt-2 text-sm text-slate-500">100〜200文字程度</p>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                />
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 日頃の活動 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">日頃の活動</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 活動頻度 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  活動頻度<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <div className="flex flex-col md:flex-row md:flex-wrap gap-3">
                  {(['毎週', '月数回', '月1回', '年数回', '不定期'] as const).map((label) => (
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

            {/* 活動内容 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  活動内容<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                />
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 実績・PR */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">実績・PR</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 受賞歴 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">受賞歴・表彰歴</label>
              </td>
              <td className="block md:table-cell p-5">
                <div className="flex gap-3 mb-5">
                  {(['あり', 'なし'] as const).map((label) => (
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

                {/* 「あり」のときだけ展開 */}
                <textarea
                  placeholder="受賞歴・表彰歴・戦績など"
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                />
              </td>
            </tr>

            {/* 地域との関わり */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">地域との関わり</label>
              </td>
              <td className="block md:table-cell p-5">
                <div className="flex gap-3 mb-5">
                  {(['あり', 'なし'] as const).map((label) => (
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

                {/* 「あり」のときだけ展開 */}
                <textarea
                  placeholder="地域との関わりの内容をご記入ください"
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                />
              </td>
            </tr>

            {/* その他PR */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">その他PR</label>
                <p className="mt-2 text-sm text-slate-500">自由記述</p>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                />
              </td>
            </tr>

          </tbody>
        </table>
      </section>

    </section>
  )
}

export default Section4
