// src/pages/Report/Section1.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch } from 'react-hook-form'
import type { ReportFormData } from '../../types/form'

type Props = {
  register: UseFormRegister<ReportFormData>
  errors: FieldErrors<ReportFormData>
  watch: UseFormWatch<ReportFormData>
}

function ReportSection1({ register, errors }: Props) {
  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 1 / 3</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅰ．申請団体の概要</h2>
      </div>

      {/* 団体情報 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">団体情報</h3>
        </div>
        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  団体名称<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('reportSection1.teamName', {
                    required: '団体名称を入力してください',
                  })}
                />
                {errors.reportSection1?.teamName && (
                  <p className="text-red-500 text-sm mt-1">{errors.reportSection1.teamName.message}</p>
                )}
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      {/* 担当者情報 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">担当者情報</h3>
        </div>
        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  担当者名<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('reportSection1.contactName', {
                    required: '担当者名を入力してください',
                  })}
                />
                {errors.reportSection1?.contactName && (
                  <p className="text-red-500 text-sm mt-1">{errors.reportSection1.contactName.message}</p>
                )}
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  電話番号<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="tel"
                  className="w-full md:w-[320px] p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('reportSection1.contactPhone', {
                    required: '電話番号を入力してください',
                    pattern: { value: /^[0-9-+().\s]+$/, message: '正しい電話番号を入力してください' },
                  })}
                />
                {errors.reportSection1?.contactPhone && (
                  <p className="text-red-500 text-sm mt-1">{errors.reportSection1.contactPhone.message}</p>
                )}
              </td>
            </tr>

            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  メールアドレス<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="email"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('reportSection1.contactEmail', {
                    required: 'メールアドレスを入力してください',
                    pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: '正しいメールアドレスを入力してください' },
                  })}
                />
                {errors.reportSection1?.contactEmail && (
                  <p className="text-red-500 text-sm mt-1">{errors.reportSection1.contactEmail.message}</p>
                )}
              </td>
            </tr>

          </tbody>
        </table>
      </section>

    </section>
  )
}

export default ReportSection1
