// src/pages/Report.tsx

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import type { ReportFormData } from '../types/form'
import { useStepForm } from '../hooks/useStepForm'
import ResumeDialog from '../components/ResumeDialog'
import SaveToast from '../components/SaveToast'
import ReportSection1 from './Report/Section1'
import ReportSection2 from './Report/Section2'
import ReportSection3 from './Report/Section3'

function Report() {
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)

  const {
    register, handleSubmit, watch, getValues,
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
    step, setStep,
    showResumeDialog, saveMessage,
    handleResume, handleStartOver,
    handleSave, handleNext, handleBack,
    clearStorage,
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

  const onSubmit = async (data: ReportFormData) => {
    setIsSubmitting(true)
    setSubmitError(null)
    try {
      const formData = new FormData()
      const { reportSection3, ...rest } = data

      formData.append('report_section1_json', JSON.stringify(rest.reportSection1))
      formData.append('report_section2_json', JSON.stringify(rest.reportSection2))

      reportSection3.photos.forEach((file) => {
        formData.append('photos[]', file)
      })
      reportSection3.receipts.forEach((file) => {
        formData.append('receipts[]', file)
      })

      const res = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/reports`, {
        method: 'POST',
        body: formData,
        // Content-Typeは指定しない（ブラウザが自動でboundaryを付ける）
      })

      const json = await res.json()
      if (!res.ok) throw new Error(json.error ?? '送信に失敗しました')

      // 送信成功
      clearStorage()
      setStep(4) // 完了画面へ

    } catch (err) {
      setSubmitError(err instanceof Error ? err.message : '送信中にエラーが発生しました')
    } finally {
      setIsSubmitting(false)
    }
  }

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

      <form onSubmit={handleSubmit(onSubmit)}>
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
            <button type="button" onClick={handleSave} disabled={isSubmitting}
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
                {submitError && (
                  <div className="w-full text-center mb-4 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm">
                    {submitError}
                  </div>
                )}
                <button type="submit" disabled={isSubmitting}
                  className="block w-3xs my-6 py-3 rounded bg-green-500 hover:bg-green-200 text-white hover:text-black text-center transition-colors duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  {isSubmitting ? '送信中...' : '送信する'}
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
