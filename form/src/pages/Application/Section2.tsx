// src/pages//Application/Section2.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch } from 'react-hook-form'
import type { FormData } from '../../types/form'
import ParticipantCount from '../../components/ParticipantCount'

type Props = {
  register: UseFormRegister<FormData>
  errors: FieldErrors<FormData>
  watch: UseFormWatch<FormData>
}

function Section2({ register, errors, watch }: Props) {

  // 日程の前後チェック用
  const startDate = watch('section2.startDate')

  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 2 / 5</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅱ．申請事業（助成対象事業）について</h2>
        <p className="text-sm text-slate-500 my-3">※ 別紙
          <a href="/" target="_blank" className="text-orange-400 hover:underline">
            記載要領
          </a>
          に沿って入力してください。</p>
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
                <p className="mt-2 text-sm text-slate-500">実施目的や内容を端的に表した名称</p>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  placeholder="例：地域交流型 海岸清掃イベント"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('section2.projectName', {
                    required: '事業名称を入力してください',
                    maxLength: { value: 100, message: '100文字以内で入力してください' },
                  })}
                />
                {errors.section2?.projectName && (
                  <p className="text-red-500 text-sm mt-1">{errors.section2.projectName.message}</p>
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
                      className="w-full md:w-auto p-3 border border-slate-300 rounded-lg bg-white"
                      {...register('section2.startDate', {
                        required: '開始日を入力してください',
                      })}
                    />
                    {errors.section2?.startDate && (
                      <p className="text-red-500 text-sm">{errors.section2.startDate.message}</p>
                    )}
                  </div>

                  <span className="text-slate-500">〜</span>

                  <div className="flex flex-col gap-1">
                    <input
                      type="date"
                      className="w-full md:w-auto p-3 border border-slate-300 rounded-lg bg-white"
                      {...register('section2.endDate', {
                        required: '終了日を入力してください',
                        validate: (v) =>
                          !startDate || v >= startDate || '終了日は開始日以降を入力してください',
                      })}
                    />
                    {errors.section2?.endDate && (
                      <p className="text-red-500 text-sm">{errors.section2.endDate.message}</p>
                    )}
                  </div>
                </div>
              </td>
            </tr>

            {/* 開催場所 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  開催場所<span className="text-red-500 ml-1">*</span>
                </label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  placeholder="例：〇〇市民センター"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('section2.venue', {
                    required: '開催場所を入力してください',
                  })}
                />
                {errors.section2?.venue && (
                  <p className="text-red-500 text-sm mt-1">{errors.section2.venue.message}</p>
                )}
              </td>
            </tr>

            {/* 参加を呼びかける範囲・地域 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">参加を呼びかける範囲・地域</label>
              </td>
              <td className="block md:table-cell p-5">
                <input
                  type="text"
                  placeholder="例：福井県内全域"
                  className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                  {...register('section2.recruitmentArea')}
                />
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
        organizer={{ countField: 'section2.organizer.count', daysField: 'section2.organizer.days' }}
        participant={{ countField: 'section2.participants.count', daysField: 'section2.participants.days' }}
      />

      {/* 事業内容 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">事業内容</h3>
          <p className="mt-1 text-sm text-slate-500">※ 選考に重要なポイントですので、詳細に記述して下さい。</p>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">

            {/* 事業詳細 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">
                  実施する事業の詳細<span className="text-red-500 ml-1">*</span>
                </label>
                <p className="mt-2 text-sm text-slate-500">式次第・演目・大会概要など</p>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  placeholder="事業内容をご記入ください"
                  className="w-full min-h-60 p-4 border border-slate-300 rounded-xl bg-white"
                  {...register('section2.projectDetail', {
                    required: '事業の詳細を入力してください',
                    maxLength: { value: 1000, message: '1000文字以内で入力してください' },
                  })}
                />
                <div className="flex justify-between mt-1">
                  {errors.section2?.projectDetail
                    ? <p className="text-red-500 text-sm">{errors.section2.projectDetail.message}</p>
                    : <span />
                  }
                  <p className="text-sm text-slate-400">
                    {watch('section2.projectDetail')?.length ?? 0} / 1000
                  </p>
                </div>
              </td>
            </tr>

            {/* 実施目的 */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">
                  実施目的・ねらい<span className="text-red-500 ml-1">*</span>
                </label>
                <p className="mt-2 text-sm text-slate-500">事業実施によって期待される変化など</p>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  placeholder="目的やねらいをご記入ください"
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                  {...register('section2.projectPurpose', {
                    required: '実施目的・ねらいを入力してください',
                    maxLength: { value: 500, message: '500文字以内で入力してください' },
                  })}
                />
                <div className="flex justify-between mt-1">
                  {errors.section2?.projectPurpose
                    ? <p className="text-red-500 text-sm">{errors.section2.projectPurpose.message}</p>
                    : <span />
                  }
                  <p className="text-sm text-slate-400">
                    {watch('section2.projectPurpose')?.length ?? 0} / 500
                  </p>
                </div>
              </td>
            </tr>

            {/* PRポイント */}
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top">
                <label className="block font-bold">特徴・PRポイント</label>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  placeholder="特徴やPRポイントをご記入ください"
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                  {...register('section2.projectPR', {
                    maxLength: { value: 500, message: '500文字以内で入力してください' },
                  })}
                />
                <div className="flex justify-end mt-1">
                  <p className="text-sm text-slate-400">
                    {watch('section2.projectPR')?.length ?? 0} / 500
                  </p>
                </div>
                {errors.section2?.projectPR && (
                  <p className="text-red-500 text-sm mt-1">{errors.section2.projectPR.message}</p>
                )}
              </td>
            </tr>

          </tbody>
        </table>
      </section>

      {/* 共催・後援・協賛 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">共催・後援・協賛</h3>
        </div>

        <table className="block md:table w-full border-collapse">
          <tbody className="block md:table-row-group">
            <tr className="block md:table-row">
              <td className="block md:table-cell p-5 align-top md:w-70">
                <label className="block font-bold">団体名等</label>
                <p className="mt-2 text-sm text-slate-500">共催・後援・協賛団体がある場合のみ</p>
              </td>
              <td className="block md:table-cell p-5">
                <textarea
                  placeholder="団体名などをご記入ください"
                  className="w-full min-h-40 p-4 border border-slate-300 rounded-xl bg-white"
                  {...register('section2.coOrganizers')}
                />
              </td>
            </tr>
          </tbody>
        </table>
      </section>

    </section>
  )
}

export default Section2
