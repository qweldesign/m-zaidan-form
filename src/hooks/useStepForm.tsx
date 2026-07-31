// src/hooks/useStepForm.ts

import { useState } from 'react'

type Options = {
  totalSteps: number
}

export function useStepForm({
  totalSteps,
}: Options) {
  const [step, setStep] = useState(1)

  // 次へ
  const handleNext = async () => {
    setStep((prev) => Math.min(prev + 1, totalSteps))
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  // 戻る
  const handleBack = () => {
    setStep((prev) => Math.max(prev - 1, 1))
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  return {
    step,
    setStep,
    handleNext,
    handleBack,
  }
}
