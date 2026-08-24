import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import ReportSection2 from '../../../pages/Report/Section2'
import type { ReportFormData } from '../../../types/form'

function Section2Wrapper() {
  const { register, watch, control, trigger, formState: { errors } } = useForm<ReportFormData>({
    defaultValues: {
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
          incomeMemo: { grantRequest: '', memberFees: '', donations: '', tickets: '' },
        },
        expenses: [],
        budgetNote: '',
      },
    } as any,
  })

  return (
    <>
      <ReportSection2 register={register} errors={errors} watch={watch} control={control} />
      {/* 送信時相当のバリデーションを発火させるための検証ボタン（テスト用） */}
      <button
        type="button"
        onClick={() => trigger(['reportSection2.actualStartDate', 'reportSection2.actualEndDate'] as any)}
      >
        検証
      </button>
    </>
  )
}

function getDateInputs(): HTMLInputElement[] {
  return Array.from(document.querySelectorAll('input[type="date"]'))
}

describe('ReportSection2（完了報告フォーム・実施時期のカレンダー表示範囲）', () => {
  const thisYear = new Date().getFullYear()
  const minYear  = thisYear - 1
  const maxYear  = thisYear + 1

  it('開始日・終了日の入力欄に「去年〜+1年」（去年から3年間）のmin/maxが設定される', () => {
    render(<Section2Wrapper />)

    const [startInput, endInput] = getDateInputs()
    expect(startInput.min).toBe(`${minYear}-01-01`)
    expect(startInput.max).toBe(`${maxYear}-12-31`)
    expect(endInput.min).toBe(`${minYear}-01-01`)
    expect(endInput.max).toBe(`${maxYear}-12-31`)
  })

  it('範囲より後（+5年）の日付を入力して検証すると、実施時期のバリデーションエラーになる', async () => {
    render(<Section2Wrapper />)

    const [startInput] = getDateInputs()
    fireEvent.change(startInput, { target: { value: `${thisYear + 5}-01-01` } })

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(
      await screen.findByText(`実施時期は${minYear}年〜${maxYear}年の範囲で入力してください`),
    ).toBeInTheDocument()
  })

  it('範囲より前（一昨年）の日付を入力して検証すると、実施時期のバリデーションエラーになる', async () => {
    render(<Section2Wrapper />)

    const [startInput] = getDateInputs()
    fireEvent.change(startInput, { target: { value: `${thisYear - 2}-12-31` } })

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(
      await screen.findByText(`実施時期は${minYear}年〜${maxYear}年の範囲で入力してください`),
    ).toBeInTheDocument()
  })

  it('範囲内（去年）の日付を入力して検証してもエラーにならない', async () => {
    render(<Section2Wrapper />)

    const [startInput, endInput] = getDateInputs()
    fireEvent.change(startInput, { target: { value: `${minYear}-04-01` } })
    fireEvent.change(endInput,   { target: { value: `${minYear}-04-02` } })

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(screen.queryByText(/の範囲で入力してください/)).not.toBeInTheDocument()
  })
})
