// src/pages/Report/Section3.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch, Control } from 'react-hook-form'
import type { ReportFormData } from '../../types/form'
import PhotoSlots from '../../components/PhotoSlots'
import ReceiptUploader from '../../components/ReceiptUploader'

type Props = {
  register: UseFormRegister<ReportFormData>
  errors: FieldErrors<ReportFormData>
  watch: UseFormWatch<ReportFormData>
  control: Control<ReportFormData>
}

function ReportSection3({ register, errors, watch, control }: Props) {

  const confirmed = watch('reportSection3.confirmed')

  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 3 / 3</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅲ．添付資料</h2>
        <p className="mt-3 text-slate-600 leading-7">
          必要資料をアップロードしてください。
        </p>
      </div>

      {/* 活動実施写真 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">活動実施写真</h3>
        </div>
        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  申請事業の活動実施写真<span className="text-red-500 ml-1">*</span>
                </label>
                <p className="mt-2 text-sm text-slate-500">JPEG / PNG形式・1〜2枚</p>
              </td>
              <td className="block md:table-cell p-5">

                <PhotoSlots
                  control={control}
                  errors={errors}
                  name="reportSection3.photos"
                  maxSlots={2}
                  required
                />

                {errors.reportSection3?.photos && (
                  <p className="text-red-500 text-sm mt-2">{errors.reportSection3.photos.message as string}</p>
                )}
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      {/* 領収書 */}
      <section className="rounded-2xl border border-orange-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-orange-50 border-b border-orange-100">
          <h3 className="font-bold text-orange-900 text-lg">領収書の写し</h3>
        </div>
        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  領収書<span className="text-red-500 ml-1">*</span>
                </label>
                <p className="mt-2 text-sm text-slate-500">
                  PDF / JPEG / PNG形式・1ファイル10MB以内<br />
                  助成金使用額分だけでなく申請事業にかかった全額分
                </p>
              </td>
              <td className="block md:table-cell p-5">
                <ReceiptUploader
                  control={control}
                  errors={errors}
                  name="reportSection3.receipts"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      {/* 確認チェック */}
      <section className="rounded-2xl border border-slate-200 bg-slate-50 p-6">
        <label className="flex items-start gap-4 cursor-pointer">
          <input
            type="checkbox"
            className="mt-1 w-5 h-5"
            {...register('reportSection3.confirmed', {
              required: '内容を確認してチェックしてください',
            })}
          />
          <span className="leading-7 text-slate-700">
            入力内容および添付資料に誤りがないことを確認しました。
          </span>
        </label>
        {errors.reportSection3?.confirmed && (
          <p className="text-red-500 text-sm mt-2 ml-9">{errors.reportSection3.confirmed.message}</p>
        )}
      </section>

      {/* 送信前の注意 */}
      {confirmed && (
        <div className="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-800 leading-7">
          送信ボタンを押すと完了報告が完了します。送信後の内容変更はできませんので、今一度ご確認ください。
        </div>
      )}

    </section>
  )
}

export default ReportSection3
