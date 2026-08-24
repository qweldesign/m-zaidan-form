import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import Section2 from '../../../pages/Application/Section2'
import type { FormData } from '../../../types/form'

function Section2Wrapper() {
  const { register, watch, trigger, formState: { errors } } = useForm<FormData>({
    defaultValues: {
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
    } as any,
  })

  return (
    <>
      <Section2 register={register} errors={errors} watch={watch} />
      {/* 送信時相当のバリデーションを発火させるための検証ボタン（テスト用） */}
      <button
        type="button"
        onClick={() => trigger(['section2.startDate', 'section2.endDate'] as any)}
      >
        検証
      </button>
    </>
  )
}

function getDateInputs(): HTMLInputElement[] {
  return Array.from(document.querySelectorAll('input[type="date"]'))
}

describe('Section2（要望申請フォーム・実施時期のカレンダー表示範囲）', () => {
  const thisYear = new Date().getFullYear()

  it('開始日・終了日の入力欄に「現在年〜+2年」のmin/maxが設定される', () => {
    render(<Section2Wrapper />)

    const [startInput, endInput] = getDateInputs()
    expect(startInput.min).toBe(`${thisYear}-01-01`)
    expect(startInput.max).toBe(`${thisYear + 2}-12-31`)
    expect(endInput.min).toBe(`${thisYear}-01-01`)
    expect(endInput.max).toBe(`${thisYear + 2}-12-31`)
  })

  it('範囲より後（+5年）の日付を入力して検証すると、実施時期のバリデーションエラーになる', async () => {
    render(<Section2Wrapper />)

    const [startInput] = getDateInputs()
    fireEvent.change(startInput, { target: { value: `${thisYear + 5}-01-01` } })

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(
      await screen.findByText(`実施時期は${thisYear}年〜${thisYear + 2}年の範囲で入力してください`),
    ).toBeInTheDocument()
  })

  it('範囲より前（前年）の日付を入力して検証すると、実施時期のバリデーションエラーになる', async () => {
    render(<Section2Wrapper />)

    const [startInput] = getDateInputs()
    fireEvent.change(startInput, { target: { value: `${thisYear - 1}-12-31` } })

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(
      await screen.findByText(`実施時期は${thisYear}年〜${thisYear + 2}年の範囲で入力してください`),
    ).toBeInTheDocument()
  })

  it('範囲内の日付を入力して検証してもエラーにならない', async () => {
    render(<Section2Wrapper />)

    const [startInput, endInput] = getDateInputs()
    fireEvent.change(startInput, { target: { value: `${thisYear}-04-01` } })
    fireEvent.change(endInput,   { target: { value: `${thisYear}-04-02` } })

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(screen.queryByText(/の範囲で入力してください/)).not.toBeInTheDocument()
  })
})
