// src/pages/Application.tsx

import { useForm } from 'react-hook-form'
import type { FormData as FormSchema } from '../types/form'
import { useStepForm } from '../hooks/useStepForm'
import Section1 from './Application/Section1'
import Section2 from './Application/Section2'
import Section3 from './Application/Section3'
import Section4 from './Application/Section4'
import Section5 from './Application/Section5'

function Application() {
  const {
    control,
  } = useForm<FormSchema>({
    defaultValues: {
      section1: {
        teamName: '',
        teamNameKana: '',
        teamPostalCode: '',
        teamAddress: '',
        establishedYear: String(new Date().getFullYear()),
        members: { under20: 0, age21to40: 0, age41to60: 0, over61: 0 },
        activityCategory: 'ボランティア活動',
        grantHistory: {
          thisFoundationCount: 0,
          thisFoundationLatestYear: String(new Date().getFullYear()),
          otherFoundationCount: 0,
          otherFoundationLatestYear: String(new Date().getFullYear()),
        },
        applicationHistory: {
          count: 0,
          latestYear: String(new Date().getFullYear()),
        },
        applicationRoute: [],
        applicationRouteOther: '',
        representativeName: '',
        representativeNameKana: '',
        representativePhone: '',
        representativeEmail: '',
        sameAsRepresentative: false,
        contactName: '',
        contactNameKana: '',
        contactPhone: '',
        contactEmail: '',
      },
      section2: {
        projectName: '',
        startDate: '',
        endDate: '',
        venue: '',
        recruitmentArea: '',
        organizer: { count: 0, days: 0, total: 0 },
        participants: { count: 0, days: 0, total: 0 },
        projectDetail: '',
        projectPurpose: '',
        projectPR: '',
        coOrganizers: '',
      },
      section3: {
        income: {
          grantRequest: 0,
          memberFees: 0,
          donations: 0,
          tickets: 0,
          incomeMemo: {
            grantRequest: '',
            memberFees: '',
            donations: '',
            tickets: '',
          },
        },
        expenses: [
          { id: '1', subject: '',    amount: 0, grantUsage: 0, memo: '' },
        ],
        budgetNote: '',
      },
      section4: {
        establishmentPurpose: [],
        establishmentBackground: '',
        activityFrequency: '毎週' as const,
        activityContent: '',
        hasAward: 'あり' as const,
        awardDetail: '',
        hasCommunityInvolvement: 'あり' as const,
        communityInvolvementDetail: '',
        prNote: '',
      },
      section5: {
        photos: [],
        docs: {
          regulations: null,
          activityReport: null,
          financialReport: null,
          activityPlan: null,
          financialPlan: null,
        },
        confirmed: false,
      },
    },
  })

  const {
    step, 
    handleNext, handleBack,
  } = useStepForm({
    totalSteps: 5,
  })

  return (
    <div className="p-3 leading-relaxed">
      <h2 className="mt-3 mb-6 font-bold text-2xl">要望申請フォーム</h2>

      <form>
        {step === 1 && <Section1 />}
        {step === 2 && <Section2 />}
        {step === 3 && <Section3 control={control} />}
        {step === 4 && <Section4 />}
        {step === 5 && <Section5 control={control} />}
        {step === 6 && (
          <div className="text-center py-16 space-y-6">
            <div className="text-5xl">✅</div>
            <h2 className="text-2xl font-bold text-slate-800">
              申請を受け付けました
            </h2>
            <p className="text-slate-600 leading-7">
              ご登録のメールアドレスに受付完了メールをお送りしました。\n内容を確認のうえ、担当者よりご連絡いたします。
            </p>
          </div>
        )}

        <div className="flex justify-center gap-4 mt-6">
          {step !== 1 && step !== 6 && (
            <button type="button" onClick={handleBack}
              className="block w-3xs my-6 py-3 rounded bg-orange-500 hover:bg-orange-200 text-white hover:text-black text-center transition-colors duration-300">
              戻る
            </button>
          )}
          {step < 5 && (
            <>
              <button type="button" onClick={handleNext}
                className="block w-3xs my-6 py-3 rounded bg-sky-500 hover:bg-sky-200 text-white hover:text-black text-center transition-colors duration-300">
                次へ
              </button>
            </>
          )}
          {step === 5 && (
            <>
              <button type="submit"
                className="block w-3xs my-6 py-3 rounded bg-green-500 hover:bg-green-200 text-white hover:text-black text-center transition-colors duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                送信する
              </button>
            </>
          )}
        </div>
      </form>
    </div>
  )
}

export default Application
