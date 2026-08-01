// src/hooks/useStepForm.ts

import { useState, useEffect } from 'react'
import type { UseFormGetValues, UseFormReset, UseFormTrigger, FieldValues, Path } from 'react-hook-form'

type Options<T extends FieldValues> = {
  totalSteps: number
  storageKey: string
  stepStorageKey: string
  stepFields: Record<number, (keyof T)[]>
  getValues: UseFormGetValues<T>
  reset: UseFormReset<T>
  trigger: UseFormTrigger<T>
}

export function useStepForm<T extends FieldValues>({
  totalSteps,
  storageKey,
  stepStorageKey,
  stepFields,
  getValues,
  reset,
  trigger,
}: Options<T>) {
  const [step, setStep] = useState(1)
  const [showResumeDialog, setShowResumeDialog] = useState(false)
  const [saveMessage, setSaveMessage] = useState(false)

  // マウント時にLocalStorageを確認
  useEffect(() => {
    const saved = localStorage.getItem(storageKey)
    if (saved) setShowResumeDialog(true)
  }, [storageKey])

  // 再開する
  const handleResume = () => {
    try {
      const saved     = localStorage.getItem(storageKey)
      const savedStep = localStorage.getItem(stepStorageKey)
      if (saved) reset(JSON.parse(saved))
      if (savedStep) setStep(Number(savedStep))
    } catch {
      localStorage.removeItem(storageKey)
      localStorage.removeItem(stepStorageKey)
    }
    setShowResumeDialog(false)
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
    handleResume,
    handleStartOver,
    handleSave,
    handleNext,
    handleBack,
    clearStorage,
  }
}
