// src/components/ParticipantCount.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch, Path, FieldValues } from 'react-hook-form'

type CountFields = {
  countField: string
  daysField: string
}

type Props<T extends FieldValues> = {
  register: UseFormRegister<T>
  errors: FieldErrors<T>
  watch: UseFormWatch<T>
  organizer: CountFields
  participant: CountFields
  sectionTitle?: string
}

function ParticipantCount<T extends FieldValues>({
  register,
  watch,
  organizer,
  participant,
  sectionTitle = '参加人数',
}: Props<T>) {

  const organizerCount   = Number(watch(organizer.countField as Path<T>))   || 0
  const organizerDays    = Number(watch(organizer.daysField  as Path<T>))   || 0
  const participantCount = Number(watch(participant.countField as Path<T>)) || 0
  const participantDays  = Number(watch(participant.daysField  as Path<T>)) || 0

  const CountRow = ({
    label,
    countField,
    daysField,
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
                className="w-full px-2 py-3 border border-slate-300 rounded-lg bg-white text-right"
                {...register(countField as Path<T>, {
                  min: { value: 0, message: '0以上を入力してください' },
                })}
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
                className="w-full px-2 py-3 border border-slate-300 rounded-lg bg-white text-right"
                {...register(daysField as Path<T>, {
                  min: { value: 0, message: '0以上を入力してください' },
                })}
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
                className="w-full px-2 py-3 border border-slate-200 rounded-lg bg-sky-50 text-sky-800 font-bold text-right"
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
            total={organizerCount * organizerDays}
          />
          <CountRow
            label="参加側人数"
            countField={participant.countField}
            daysField={participant.daysField}
            total={participantCount * participantDays}
          />
        </tbody>
      </table>
    </section>
  )
}

export default ParticipantCount
