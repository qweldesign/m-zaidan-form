// src/pages/Report/Section2.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch, Control } from 'react-hook-form'
import type { ReportFormData } from '../../types/form'
import ParticipantCount from '../../components/ParticipantCount'
import BudgetTable from '../../components/BudgetTable'
import { getYearRange } from '../../utils/dateRange'

type Props = {
  register: UseFormRegister<ReportFormData>
  errors: FieldErrors<ReportFormData>
  watch: UseFormWatch<ReportFormData>
  control: Control<ReportFormData>
}

function ReportSection2({ register, errors, watch, control }: Props) {

  const actualStartDate = watch('reportSection2.actualStartDate')

  // 実施時期は「去年から3年間」に制限する（例: 現在2026年なら2025年〜2027年）。
  // 完了報告は既に実施済みの事業の実績を報告するものなので、
  // 申請フォーム（現在年起点）とは異なり前年を起点にする。
  const { min: dateMin, max: dateMax, minYear, maxYear } = getYearRange(new Date().getFullYear() - 1, 3)
  const dateRangeMessage = `実施時期は${minYear}年〜${maxYear}年の範囲で入力してください`

  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 2 / 3</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅱ．申請事業（助成対象事業）について</h2>
      </div>

      {/* 事業基本情報 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">事業基本情報</h3>
        </div>
        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 事業名称 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  申請事業の名称<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('reportSection2.projectName', {
                    required: '事業名称を入力してください',
                  })}
                />
                {errors.reportSection2?.projectName && (
                  <p className="text-red-500 text-sm mt-1">{errors.reportSection2.projectName.message}</p>
                )}
              </td>
            </tr>

            {/* カテゴリー */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  活動カテゴリー<span className="text-red-500 ml-1">*</span>
                </label>
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
                        {...register('reportSection2.activityCategory', {
                          required: '活動カテゴリーを選択してください',
                        })}
                      />
                      <span>{label}</span>
                    </label>
                  ))}
                </div>
                {errors.reportSection2?.activityCategory && (
                  <p className="text-red-500 text-sm mt-2">{errors.reportSection2.activityCategory.message}</p>
                )}
              </td>
            </tr>

            {/* 実施時期 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  実施時期<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <div className="flex flex-col md:flex-row items-start md:items-center gap-3">
                  <div className="flex flex-col gap-1">
                    <input
                      type="date"
                      min={dateMin}
                      max={dateMax}
                      className="w-full md:w-auto p-3 border border-slate-300 rounded-lg bg-white"
                      {...register('reportSection2.actualStartDate', {
                        required: '開始日を入力してください',
                        validate: (v) =>
                          !v || (v >= dateMin && v <= dateMax) || dateRangeMessage,
                      })}
                    />
                    {errors.reportSection2?.actualStartDate && (
                      <p className="text-red-500 text-sm">{errors.reportSection2.actualStartDate.message}</p>
                    )}
                  </div>
                  <span className="text-slate-500">〜</span>
                  <div className="flex flex-col gap-1">
                    <input
                      type="date"
                      min={dateMin}
                      max={dateMax}
                      className="w-full md:w-auto p-3 border border-slate-300 rounded-lg bg-white"
                      {...register('reportSection2.actualEndDate', {
                        required: '終了日を入力してください',
                        validate: {
                          afterStart: (v) =>
                            !actualStartDate || v >= actualStartDate || '終了日は開始日以降を入力してください',
                          inRange: (v) =>
                            !v || (v >= dateMin && v <= dateMax) || dateRangeMessage,
                        },
                      })}
                    />
                    {errors.reportSection2?.actualEndDate && (
                      <p className="text-red-500 text-sm">{errors.reportSection2.actualEndDate.message}</p>
                    )}
                  </div>
                </div>
              </td>
            </tr>

            {/* 実施場所 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  実施場所<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('reportSection2.actualVenue', {
                    required: '実施場所を入力してください',
                  })}
                />
                {errors.reportSection2?.actualVenue && (
                  <p className="text-red-500 text-sm mt-1">{errors.reportSection2.actualVenue.message}</p>
                )}
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 参加人数 */}
      <ParticipantCount
        register={register}
        errors={errors}
        watch={watch}
        organizer={{ countField: 'reportSection2.organizerCount', daysField: 'reportSection2.organizerDays' }}
        participant={{ countField: 'reportSection2.participantCount', daysField: 'reportSection2.participantDays' }}
        sectionTitle="参加人数（実績）"
      />

      {/* 実施内容 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">実施内容</h3>
        </div>
        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  実施内容の詳細<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  className="w-full min-h-60 p-4 border border-slate-300 rounded-xl bg-white"
                  {...register('reportSection2.actualDetail', {
                    required: '実施内容を入力してください',
                    maxLength: { value: 1000, message: '1000文字以内で入力してください' },
                  })}
                />
                <div className="flex justify-between mt-1">
                  {errors.reportSection2?.actualDetail
                    ? <p className="text-red-500 text-sm">{errors.reportSection2.actualDetail.message}</p>
                    : <span />
                  }
                  <p className="text-sm text-slate-400">
                    {watch('reportSection2.actualDetail')?.length ?? 0} / 1000
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      {/* 収支決算報告 */}
      <div>
        <h2 className="text-3xl font-bold text-slate-800 mb-8">収支決算報告</h2>
      </div>

      <div className="rounded-2xl border border-orange-100 bg-orange-50 p-5 mb-8">
        <p className="text-sm leading-7 text-slate-700">
          実際の収支をご記入ください。申請時の予算と異なる場合はそのまま実績をご入力ください。
        </p>
      </div>

      <BudgetTable
        register={register}
        errors={errors}
        watch={watch}
        control={control}
        prefix="reportSection2"
        expenseDescription="申請事業に関する支出実績をご記入ください。必要に応じて行を追加してください。"
        removableFrom={0}
      />

    </section>
  )
}

export default ReportSection2
