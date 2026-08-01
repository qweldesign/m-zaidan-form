// src/pages/Report.tsx

import { useState, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import type { ReportFormData } from '../types/form'
import { useStepForm } from '../hooks/useStepForm'
import ResumeDialog from '../components/ResumeDialog'
import SaveToast from '../components/SaveToast'
import ReportSection1 from './Report/Section1'
import ReportSection2 from './Report/Section2'
import ReportSection3 from './Report/Section3'

type Props = {
  editToken?: string
}

function Report({ editToken }: Props) {
  const [isEditMode, setIsEditMode] = useState(false)
  const [editBlocked, setEditBlocked] = useState(false)
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
      if (editToken) {
        formData.append('submission_token', editToken)
      }

      reportSection3.photos.forEach((file) => formData.append('photos[]', file))
      reportSection3.receipts.forEach((file) => formData.append('receipts[]', file))

      const url    = isEditMode
        ? `${import.meta.env.VITE_API_BASE_URL}/api/reports/edit/${editToken}`
        : `${import.meta.env.VITE_API_BASE_URL}/api/reports`
      const method = isEditMode ? 'PUT' : 'POST'

      const res  = await fetch(url, { method, body: formData })
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

  // トークンがある場合はAPIからデータ取得して復元
  useEffect(() => {
    if (!editToken) return

    const fetchData = async () => {
      try {
        // まず完了報告データの取得を試みる
        const reportRes = await fetch(
          `${import.meta.env.VITE_API_BASE_URL}/api/reports/edit/${editToken}`
        )

        if (reportRes.ok) {
          // 完了報告データが存在する → 再編集モード
          const json = await reportRes.json()

          if (json.data.status !== '確認前') {
            setEditBlocked(true)
            return
          }

          const d = json.data
          reset({
            reportSection1: d.report_section1_json,
            reportSection2: {
              ...d.report_section2_json,
            },
            reportSection3: {
              photos: [],
              receipts: [],
              confirmed: false,
            },
          })
          setIsEditMode(true)
          return
        }

        // 完了報告データが存在しない → 申請データからコピーして新規作成
        const submissionRes = await fetch(
          `${import.meta.env.VITE_API_BASE_URL}/api/submissions/edit/${editToken}`
        )

        if (!submissionRes.ok) {
          alert('データの取得に失敗しました。URLを確認してください。')
          return
        }

        const json = await submissionRes.json()
        const d    = json.data
        const s2   = d.section2_json ?? {}
        const s3   = d.section3_json ?? {}

        // 申請データを完了報告の初期値としてセット
        reset({
          reportSection1: {
            teamName:     d.team_name     ?? '',
            contactName:  d.contact_name  ?? '',
            contactPhone: d.contact_phone ?? '',
            contactEmail: d.contact_email ?? '',
          },
          reportSection2: {
            projectName:       d.project_name ?? '',
            activityCategory:  d.activity_category ?? '',
            actualStartDate:   d.start_date  ?? '',
            actualEndDate:     d.end_date    ?? '',
            actualVenue:       d.venue       ?? '',
            organizerCount:    s2.organizer?.count        ?? 0,
            organizerDays:     s2.organizer?.days         ?? 0,
            participantCount:  s2.participants?.count     ?? 0,
            participantDays:   s2.participants?.days      ?? 0,
            actualDetail:      '',
            income:            s3.income   ?? {
              grantRequest: 0, memberFees: 0, donations: 0, tickets: 0,
              incomeMemo: { grantRequest: '', memberFees: '', donations: '', tickets: '' },
            },
            expenses:    s3.expenses  ?? [],
            budgetNote:  '',
          },
          reportSection3: {
            photos:    [],
            receipts:  [],
            confirmed: false,
          },
        })

      } catch {
        alert('データの取得中にエラーが発生しました。')
      }
    }

    fetchData()
  }, [editToken])

  // editBlocked の表示
  if (editBlocked) {
    return (
      <div className="text-center py-16 space-y-4">
        <div className="text-5xl">🔒</div>
        <h2 className="text-2xl font-bold text-slate-800">完了報告内容を変更できません</h2>
        <p className="text-slate-600 leading-7">
          この完了報告はすでに確認済みのため、内容の変更はできません。<br />
          ご不明な点は財団までお問い合わせください。
        </p>
      </div>
    )
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
              {isEditMode ? '完了報告内容を更新しました' : '完了報告を受け付けました'}
            </h2>
            <p className="text-slate-600 leading-7">
              {isEditMode
                ? '完了報告内容が更新されました。担当者よりご連絡いたします。'
                : 'ご登録のメールアドレスに受付完了メールをお送りしました。\n内容を確認のうえ、担当者よりご連絡いたします。'
              }
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
