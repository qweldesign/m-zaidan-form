// src/hooks/useStepForm.ts

import { useState, useEffect } from 'react'
import type {
  FieldErrors,
  FieldValues,
  Path,
  UseFormGetValues,
  UseFormReset,
  UseFormTrigger,
} from 'react-hook-form'

type Options<T extends FieldValues> = {
  totalSteps: number
  storageKey: string
  stepStorageKey: string
  stepFields: Record<number, (keyof T)[]>
  getValues: UseFormGetValues<T>
  reset: UseFormReset<T>
  trigger: UseFormTrigger<T>
  // 編集トークンでの再編集時、サーバーからの初期データ取得（reset）が完了するまでは
  // マウント時のLocalStorage確認（再開ダイアログの表示判定）を保留するためのフラグ。
  // 省略時はtrue（トークンを使わない画面はこれまで通り即座に判定する）。
  enabled?: boolean
}

// errors オブジェクトを見て、エラーを含む最初のステップ番号を返す（無ければ null）
// ウィザードの最終送信時、非表示のステップにあるエラーへユーザーを誘導するために使う
export function getStepWithError<T extends FieldValues>(
  stepFields: Record<number, (keyof T)[]>,
  errors: FieldErrors<T>,
): number | null {
  const steps = Object.keys(stepFields)
    .map(Number)
    .sort((a, b) => a - b)

  for (const s of steps) {
    const fields = stepFields[s]
    const hasError = fields.some((field) => {
      return Boolean((errors as Record<string, unknown>)[field as string])
    })
    if (hasError) return s
  }

  return null
}

export function useStepForm<T extends FieldValues>({
  totalSteps,
  storageKey,
  stepStorageKey,
  stepFields,
  getValues,
  reset,
  trigger,
  enabled = true,
}: Options<T>) {
  const [step, setStep] = useState(1)
  const [showResumeDialog, setShowResumeDialog] = useState(false)
  const [saveMessage, setSaveMessage] = useState(false)
  const [resumeWarning, setResumeWarning] = useState(false)

  // マウント時にLocalStorageを確認
  // enabledがfalseの間（編集トークンでのサーバーデータ取得が完了する前）は判定しない。
  // これを待たないと、トークン取得の完了（reset）より先にダイアログが表示され、
  // ユーザーが「再開する」を押した際に取得済みのサーバーデータを
  // 古いLocalStorageの内容で上書きしてしまうことがある。
  useEffect(() => {
    if (!enabled) return
    const saved = localStorage.getItem(storageKey)
    if (saved) setShowResumeDialog(true)
  }, [storageKey, enabled])

  // 先頭のステップから順に検証し、最初に無効だったステップ番号を返す（すべて有効なら null）
  // 「一時保存」はバリデーションを行わずに保存されるため、再開時に不備が残っていないか確認する
  const findFirstInvalidStep = async (): Promise<number | null> => {
    for (let s = 1; s <= totalSteps; s++) {
      const fields = stepFields[s] as Path<T>[] | undefined
      if (!fields) continue
      const ok = await trigger(fields)
      if (!ok) return s
    }
    return null
  }

  // 再開する
  const handleResume = async () => {
    let saved: string | null = null
    try {
      saved = localStorage.getItem(storageKey)
      const savedStep = localStorage.getItem(stepStorageKey)
      if (saved) reset(JSON.parse(saved))
      setShowResumeDialog(false)

      if (!saved) return

      // 保存内容に不備がないか確認し、あれば該当ステップへ誘導する
      const invalidStep = await findFirstInvalidStep()
      if (invalidStep) {
        setStep(invalidStep)
        setResumeWarning(true)
      } else if (savedStep) {
        setStep(Number(savedStep))
      }
    } catch {
      localStorage.removeItem(storageKey)
      localStorage.removeItem(stepStorageKey)
      setShowResumeDialog(false)
    }
  }

  // 最初から始める
  const handleStartOver = () => {
    localStorage.removeItem(storageKey)
    localStorage.removeItem(stepStorageKey)
    setShowResumeDialog(false)
  }

  // 明示的な保存
  const handleSave = () => {
    const data = getValues()
    localStorage.setItem(storageKey, JSON.stringify(data))
    localStorage.setItem(stepStorageKey, String(step))
    setSaveMessage(true)
    setResumeWarning(false)
    setTimeout(() => setSaveMessage(false), 3000)
  }

  // 次へ
  const handleNext = async () => {
    const fields = stepFields[step] as Path<T>[]
    const valid  = await trigger(fields)
    if (!valid) return
    const data = getValues()
    localStorage.setItem(storageKey, JSON.stringify(data))
    localStorage.setItem(stepStorageKey, String(step))
    setResumeWarning(false)
    setStep((prev) => Math.min(prev + 1, totalSteps))
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  // 戻る
  const handleBack = () => {
    const data = getValues()
    localStorage.setItem(storageKey, JSON.stringify(data))
    setStep((prev) => Math.max(prev - 1, 1))
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  // 送信完了後のクリア
  const clearStorage = () => {
    localStorage.removeItem(storageKey)
    localStorage.removeItem(stepStorageKey)
  }

  return {
    step,
    setStep,
    showResumeDialog,
    saveMessage,
    resumeWarning,
    handleResume,
    handleStartOver,
    handleSave,
    handleNext,
    handleBack,
    clearStorage,
  }
}
