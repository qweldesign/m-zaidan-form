// src/pages/Application/Section5.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch, Control } from 'react-hook-form'
import type { FormData } from '../../types/form'
import PhotoSlots from '../../components/PhotoSlots'
import PdfUploader from '../../components/PdfUploader'

type Props = {
  register: UseFormRegister<FormData>
  errors: FieldErrors<FormData>
  watch: UseFormWatch<FormData>
  control: Control<FormData>
  isEditMode?: boolean
}

const PDF_DOCS = [
  { label: '団体規約',             field: 'docs.regulations'    },
  { label: '直近年度の活動報告書',  field: 'docs.activityReport'  },
  { label: '直近年度の収支決算書',  field: 'docs.financialReport' },
  { label: '直近年度の活動計画書',  field: 'docs.activityPlan'    },
  { label: '直近年度の収支計画書',  field: 'docs.financialPlan'   },
] as const

function Section5({ register, errors, watch, control, isEditMode }: Props) {

  const confirmed = watch('section5.confirmed')

  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 5 / 5</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅴ．添付資料</h2>
        <p className="mt-3 text-slate-600 leading-7">
          必要資料をアップロードしてください。<br />
          PDF資料については、可能な限り1ファイルにまとめてご提出ください。
        </p>
      </div>

      {/* 活動写真 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">活動写真</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  日頃の活動が分かる写真<span className="text-red-500 ml-1">*</span>
                </label>
                <p className="mt-2 text-sm text-slate-500">
                  JPEG / PNG形式・3枚必須（各5MB以内）
                </p>
              </td>
              <td className="block md:table-cell p-5">

                {/* エラーメッセージは PhotoSlots 内部で表示されるため、
                    ここでの重複表示は行わない（以前は同じメッセージが
                    二重に表示されてしまっていた） */}
                <PhotoSlots
                  control={control}
                  errors={errors}
                  name="section5.photos"
                  maxSlots={3}
                  isEditMode={isEditMode}
                />

              </td>
            </tr>
          </tbody>
        </table>
      </section>

      {/* PDF資料 */}
      <section className="rounded-2xl border border-orange-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-orange-50 border-b border-orange-100">
          <h3 className="font-bold text-orange-900 text-lg">PDF資料</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            {PDF_DOCS.map(({ label, field }) => (
              <tr
                key={field}
                className="block md:table-row border-b border-slate-100 last:border-none"
              >
                <td className="block md:table-cell p-5 align-top md:w-70">
                  <label className="block font-bold">
                    {label}<span className="text-red-500 ml-1">*</span>
                  </label>
                  <p className="mt-2 text-sm text-slate-500">PDF形式・10MB以内</p>
                </td>
                <td className="block md:table-cell p-5">
                  <PdfUploader
                    control={control}
                    errors={errors}
                    name={`section5.${field}` as const}
                    label={label}
                    isEditMode={isEditMode}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      {/* 確認チェック */}
      <section className="rounded-2xl border border-slate-200 bg-slate-50 p-6">
        <label className="flex items-start gap-4 cursor-pointer">
          <input
            type="checkbox"
            className="mt-1 w-5 h-5"
            {...register('section5.confirmed', {
              required: '内容を確認してチェックしてください',
            })}
          />
          <span className="leading-7 text-slate-700">
            入力内容および添付資料に誤りがないことを確認しました。
          </span>
        </label>
        {errors.section5?.confirmed && (
          <p className="text-red-500 text-sm mt-2 ml-9">{errors.section5.confirmed.message}</p>
        )}
      </section>

      {/* 送信前の注意 */}
      {confirmed && (
        <div className="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-800 leading-7">
          送信ボタンを押すと申請が完了します。送信内容について、今一度ご確認ください。
        </div>
      )}

    </section>
  )
}

export default Section5
