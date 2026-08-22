// src/pages/Application.tsx

import { useState, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import type { FieldErrors } from 'react-hook-form'
import type { FormData as FormSchema } from '../types/form'
import { useStepForm, getStepWithError } from '../hooks/useStepForm'
import ResumeDialog from '../components/ResumeDialog'
import SaveToast from '../components/SaveToast'
import Section1 from './Application/Section1'
import Section2 from './Application/Section2'
import Section3 from './Application/Section3'
import Section4 from './Application/Section4'
import Section5 from './Application/Section5'

type Props = {
  editToken?: string
}

function Application({ editToken }: Props) {
  const [isEditMode, setIsEditMode] = useState(false)
  const [editBlocked, setEditBlocked] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [validationNotice, setValidationNotice] = useState<string | null>(null)
  // 編集トークンでの再編集時、サーバーからの初期データ取得（下のuseEffect）が
  // 完了するまでtrueにしない。トークン無し（新規申請）の場合は最初から判定して良い。
  const [tokenResolved, setTokenResolved] = useState(!editToken)

  // 一時保存のLocalStorageキー。編集トークンがある場合はトークンごとに分ける。
  // 共通のキーのままだと、「トークン無しで新規入力中の一時保存データ」が
  // 「トークン付きURLでの再編集中」に誤って再開候補として表示されてしまったり、
  // 逆に複数の申請をトークンで編集した際にデータが混ざったりする。
  const storageKey     = editToken ? `zaidan_draft_edit_${editToken}` : 'zaidan_draft'
  const stepStorageKey = editToken ? `zaidan_draft_edit_step_${editToken}` : 'zaidan_draft_step'

  // ステップとフォームの各セクションの対応（送信時のエラー誘導・一時保存の再検証で共有する）
  const stepFieldsMap: Record<number, (keyof FormSchema)[]> = {
    1: ['section1'],
    2: ['section2'],
    3: ['section3'],
    4: ['section4'],
    5: ['section5'],
  }

  const {
    register, handleSubmit, watch, getValues, setValue,
    trigger, control, reset,
    formState: { errors },
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
    step, setStep,
    showResumeDialog, saveMessage, resumeWarning,
    handleResume, handleStartOver,
    handleSave, handleNext, handleBack,
    clearStorage,
  } = useStepForm<FormSchema>({
    totalSteps: 5,
    storageKey,
    stepStorageKey,
    stepFields: stepFieldsMap,
    getValues,
    reset,
    trigger,
    enabled: tokenResolved,
  })

  // フォーム全体のバリデーションに失敗した場合（非表示のステップに不備がある場合を含む）、
  // 該当ステップへ移動してエラーを可視化する。これが無いと送信ボタンが無反応に見えてしまう。
  const onInvalid = (formErrors: FieldErrors<FormSchema>) => {
    const invalidStep = getStepWithError(stepFieldsMap, formErrors)
    if (invalidStep && invalidStep !== step) {
      setStep(invalidStep)
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
    setValidationNotice('入力内容に不備がある項目があります。ステップの内容をご確認のうえ、再度送信してください。')
  }

  const onSubmit = async (data: FormSchema) => {
    setIsSubmitting(true)
    setSubmitError(null)
    setValidationNotice(null)
    try {
      // ブラウザのFormDataを使ってmultipart/form-dataで送信
      const formData = new FormData()

      // セクション1〜4はJSONとして一括で渡す
      // section5のファイルは別途appendする
      const { section5, ...rest } = data

      formData.append('section1_json', JSON.stringify(rest.section1))
      formData.append('section2_json', JSON.stringify(rest.section2))
      formData.append('section3_json', JSON.stringify(rest.section3))
      formData.append('section4_json', JSON.stringify(rest.section4))

      // 写真（複数）
      if (section5.photos && section5.photos.length > 0) {
        section5.photos.forEach((file) => {
          formData.append('photos[]', file)
        })
      }

      // PDF各種（単体）
      const docFields = [
        'regulations',
        'activityReport',
        'financialReport',
        'activityPlan',
        'financialPlan',
      ] as const

      docFields.forEach((field) => {
        const file = section5.docs[field]
        if (file) {
          formData.append(field, file)
        }
      })

      // 編集モードはPUT・新規はPOST
      const url    = isEditMode
        ? `${import.meta.env.VITE_API_BASE_URL}/api/submissions/edit/${editToken}`
        : `${import.meta.env.VITE_API_BASE_URL}/api/submissions`
      const method = isEditMode ? 'PUT' : 'POST'

      const res  = await fetch(url, { method, body: formData })
      const json = await res.json()
      if (!res.ok) throw new Error(json.error ?? '送信に失敗しました')

      // 送信成功
      clearStorage()
      setStep(6) // 完了画面へ

    } catch (err) {
      setSubmitError(err instanceof Error ? err.message : '送信中にエラーが発生しました')
    } finally {
      setIsSubmitting(false)
    }
  }

  // トークンがある場合はAPIからデータ取得して復元
  useEffect(() => {
    if (!editToken) return

    const fetchDraft = async () => {
      try {
        const res  = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/submissions/edit/${editToken}`)
        const json = await res.json()
        if (!res.ok) {
          alert('申請データの取得に失敗しました。URLを確認してください。')
          return
        }

        // 審査前以外は編集不可
        if (json.data.status !== '審査前') {
          setEditBlocked(true)
          return
        }

        // フォームにデータを復元
        const d = json.data
        reset({
          section1: d.section1_json,
          section2: d.section2_json,
          section3: d.section3_json,
          section4: d.section4_json,
          section5: {
            photos: [],      // ファイルは再選択
            docs: {
              regulations:   null,
              activityReport: null,
              financialReport: null,
              activityPlan:  null,
              financialPlan: null,
            },
            confirmed: false,
          },
        })
        setIsEditMode(true)
      } catch {
        alert('申請データの取得中にエラーが発生しました。')
      } finally {
        // サーバーデータの反映（reset）が終わってから、このトークン専用の
        // 一時保存キーでの再開ダイアログ判定を許可する（成功・失敗いずれの場合も）。
        setTokenResolved(true)
      }
    }

    fetchDraft()
  }, [editToken])

  // editBlocked の場合はフォームを表示せず案内メッセージを表示
  if (editBlocked) {
    return (
      <div className="text-center py-16 space-y-4">
        <div className="text-5xl">🔒</div>
        <h2 className="text-2xl font-bold text-slate-800">申請内容を変更できません</h2>
        <p className="text-slate-600 leading-7">
          この申請はすでに受理されているため、内容の変更はできません。<br />
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
        />
      )}
      {saveMessage && <SaveToast />}

      <h2 className="mt-3 mb-6 font-bold text-2xl">要望申請フォーム</h2>

      {resumeWarning && (
        <div className="mx-auto max-w-lg mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm text-center">
          保存されていた内容に未入力・不備の項目がありました。このステップの内容をご確認ください。
        </div>
      )}

      <form onSubmit={handleSubmit(onSubmit, onInvalid)}>
        {step === 1 && <Section1 register={register} errors={errors} watch={watch} setValue={setValue} />}
        {step === 2 && <Section2 register={register} errors={errors} watch={watch} />}
        {step === 3 && <Section3 register={register} errors={errors} watch={watch} control={control} />}
        {step === 4 && <Section4 register={register} errors={errors} watch={watch} />}
        {step === 5 && <Section5 register={register} errors={errors} watch={watch} control={control} isEditMode={isEditMode} />}
        {step === 6 && (
          <div className="text-center py-16 space-y-6">
            <div className="text-5xl">✅</div>
            <h2 className="text-2xl font-bold text-slate-800">
              {isEditMode ? '申請内容を更新しました' : '申請を受け付けました'}
            </h2>
            <p className="text-slate-600 leading-7">
              {isEditMode
                ? '申請内容が更新されました。担当者よりご連絡いたします。'
                : 'ご登録のメールアドレスに受付完了メールをお送りしました。\n内容を確認のうえ、担当者よりご連絡いたします。'
              }
            </p>
          </div>
        )}

        {validationNotice && (
          <div className="mx-auto max-w-lg mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm text-center">
            {validationNotice}
          </div>
        )}

        {submitError && (
          <div className="mx-auto max-w-lg mb-4 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm text-center">
            {submitError}
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
              <button type="button" onClick={handleSave}
                className="block w-3xs my-6 py-3 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-center transition-colors duration-300">
                一時保存
              </button>
              <button type="button" onClick={handleNext}
                className="block w-3xs my-6 py-3 rounded bg-sky-500 hover:bg-sky-200 text-white hover:text-black text-center transition-colors duration-300">
                次へ
              </button>
            </>
          )}
          {step === 5 && (
            <>
              <button type="button" onClick={handleSave} disabled={isSubmitting}
                className="block w-3xs my-6 py-3 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-center transition-colors duration-300">
                一時保存
              </button>
              <button type="submit" disabled={isSubmitting}
                className="block w-3xs my-6 py-3 rounded bg-green-500 hover:bg-green-200 text-white hover:text-black text-center transition-colors duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                {isSubmitting ? '送信中...' : '送信する'}
              </button>
            </>
          )}
        </div>
      </form>
    </div>
  )
}

export default Application
