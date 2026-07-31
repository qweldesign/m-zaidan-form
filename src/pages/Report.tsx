// src/pages/Report.tsx

import { useForm } from 'react-hook-form'
import type { ReportFormData } from '../types/form'
import { useStepForm } from '../hooks/useStepForm'
import ResumeDialog from '../components/ResumeDialog'
import SaveToast from '../components/SaveToast'
import ReportSection1 from './Report/Section1'
import ReportSection2 from './Report/Section2'
import ReportSection3 from './Report/Section3'

function Report() {
  const {
    register, watch, getValues,
    trigger, control, reset,
    formState: { errors },
  } = useForm<ReportFormData>({
    defaultValues: {
      reportSection1: {
        teamName: '',
        contactName: '',
        contactPhone: '',
        contactEmail: '',
      },
      reportSection2: {
        projectName: '',
        activityCategory: 'ボランティア活動' as const,
        actualStartDate: '',
        actualEndDate: '',
        actualVenue: '',
        organizerCount: 0,
        organizerDays: 0,
        participantCount: 0,
        participantDays: 0,
        actualDetail: '',
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
      reportSection3: {
        photos: [],
        receipts: [],
        confirmed: false,
      },
    },
  })

  const {
    step,
    showResumeDialog, saveMessage,
    handleResume, handleStartOver,
    handleSave, handleNext, handleBack,
  } = useStepForm<ReportFormData>({
    totalSteps: 3,
    storageKey: 'zaidan_report_draft',
    stepStorageKey: 'zaidan_report_draft_step',
    stepFields: {
      1: ['reportSection1'],
      2: ['reportSection2'],
      3: ['reportSection3'],
    },
    getValues,
    reset,
    trigger,
  })

  return (
    <div className="p-3 leading-relaxed">
      {showResumeDialog && (
        <ResumeDialog
          onResume={handleResume}
          onStartOver={handleStartOver}
          note="※ 添付ファイルは再選択が必要です。"
        />
      )}
      {saveMessage && <SaveToast />}

      <h2 className="mt-3 mb-6 font-bold text-2xl">完了報告フォーム</h2>

      <form>
        {step === 1 && <ReportSection1 register={register} errors={errors} watch={watch} />}
        {step === 2 && <ReportSection2 register={register} errors={errors} watch={watch} control={control} />}
        {step === 3 && <ReportSection3 register={register} errors={errors} watch={watch} control={control} />}
        {step === 4 && (
          <div className="text-center py-16 space-y-6">
            <div className="text-5xl">✅</div>
            <h2 className="text-2xl font-bold text-slate-800">
              完了報告を受け付けました
            </h2>
            <p className="text-slate-600 leading-7">
              ご登録のメールアドレスに受付完了メールをお送りしました。\n内容を確認のうえ、担当者よりご連絡いたします。
            </p>
          </div>
        )}

        {step < 4 && (
          <div className="flex justify-center gap-4 mt-6">
            {step !== 1 && (
              <button type="button" onClick={handleBack}
                className="block w-3xs my-6 py-3 rounded bg-orange-500 hover:bg-orange-200 text-white hover:text-black text-center transition-colors duration-300">
                戻る
              </button>
            )}
            <button type="button" onClick={handleSave}
              className="block w-3xs my-6 py-3 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-center transition-colors duration-300">
              一時保存
            </button>
            {step < 3 ? (
              <button type="button" onClick={handleNext}
                className="block w-3xs my-6 py-3 rounded bg-sky-500 hover:bg-sky-200 text-white hover:text-black text-center transition-colors duration-300">
                次へ
              </button>
            ) : (
              <>
                <button type="submit"
                  className="block w-3xs my-6 py-3 rounded bg-green-500 hover:bg-green-200 text-white hover:text-black text-center transition-colors duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  送信する
                </button>
              </>
            )}
          </div>
        )}
      </form>
    </div>
  )
}

export default Report
