// src/components/ParticipantCount.tsx

type CountFields = {
  countField: string
  daysField: string
}

type Props = {
  organizer: CountFields
  participant: CountFields
  sectionTitle?: string
}

function ParticipantCount({
  organizer,
  participant,
  sectionTitle = '参加人数',
}: Props) {

  const CountRow = ({
    label,
    total,
  }: {
    label: string
    countField: string
    daysField: string
    total: number
  }) => (
    <tr className="block md:table-row">
      <td className="block md:table-cell p-5 align-top md:w-70">
        <label className="block font-bold">{label}</label>
      </td>
      <td className="block md:table-cell p-5">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="p-4 rounded-xl border border-slate-200 bg-slate-50">
            <label className="block mb-2 text-sm font-bold text-slate-700">人数</label>
            <div className="flex items-center gap-2">
              <input
                type="number"
                min={0}
                className="w-full p-3 border border-slate-300 rounded-lg bg-white"
              />
              <span className="text-slate-500 whitespace-nowrap">名</span>
            </div>
          </div>
          <div className="p-4 rounded-xl border border-slate-200 bg-slate-50">
            <label className="block mb-2 text-sm font-bold text-slate-700">日数</label>
            <div className="flex items-center gap-2">
              <input
                type="number"
                min={0}
                className="w-full p-3 border border-slate-300 rounded-lg bg-white"
              />
              <span className="text-slate-500 whitespace-nowrap">日</span>
            </div>
          </div>
          <div className="p-4 rounded-xl border border-sky-200 bg-sky-50">
            <label className="block mb-2 text-sm font-bold text-sky-800">延べ人数</label>
            <div className="flex items-center gap-2">
              <input
                type="number"
                readOnly
                value={total}
                className="w-full p-3 border border-slate-200 rounded-lg bg-sky-50 text-sky-800 font-bold"
              />
              <span className="text-slate-500 whitespace-nowrap">名</span>
            </div>
          </div>
        </div>
      </td>
    </tr>
  )

  return (
    <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
      <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
        <h3 className="font-bold text-sky-900 text-lg">{sectionTitle}</h3>
      </div>
      <table className="block md:table w-full border-collapse">
        <tbody className="block md:table-row-group">
          <CountRow
            label="申請団体側人数"
            countField={organizer.countField}
            daysField={organizer.daysField}
            total={0} // 後で自動計算を実装
          />
          <CountRow
            label="参加側人数"
            countField={participant.countField}
            daysField={participant.daysField}
            total={0} // 後で自動計算を実装
          />
        </tbody>
      </table>
    </section>
  )
}

export default ParticipantCount
