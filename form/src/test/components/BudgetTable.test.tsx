import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import BudgetTable from '../../components/BudgetTable'
import type { FormData } from '../../types/form'

// テスト用ラッパーコンポーネント
function BudgetTableWrapper({
  removableFrom = 0,
  initialExpenses = [],
}: {
  removableFrom?: number
  initialExpenses?: FormData['section3']['expenses']
}) {
  const { register, watch, control, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section3: {
        income: {
          grantRequest: 0,
          memberFees:   0,
          donations:    0,
          tickets:      0,
          incomeMemo: {
            grantRequest: '',
            memberFees:   '',
            donations:    '',
            tickets:      '',
          },
        },
        expenses:   initialExpenses,
        budgetNote: '',
      },
    },
  })

  return (
    <BudgetTable
      register={register}
      errors={errors}
      watch={watch}
      control={control}
      prefix="section3"
      removableFrom={removableFrom}
    />
  )
}

describe('BudgetTable', () => {

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('収入の部が表示される', () => {
      render(<BudgetTableWrapper />)
      expect(screen.getByText('収入の部')).toBeInTheDocument()
    })

    it('支出の部が表示される', () => {
      render(<BudgetTableWrapper />)
      expect(screen.getByText('支出の部')).toBeInTheDocument()
    })

    it('例示行が表示される', () => {
      render(<BudgetTableWrapper />)
      expect(screen.getByText('例')).toBeInTheDocument()
      expect(screen.getByDisplayValue('会場費')).toBeInTheDocument()
    })

    it('「行を追加」ボタンが表示される', () => {
      render(<BudgetTableWrapper />)
      expect(screen.getByRole('button', { name: '行を追加' })).toBeInTheDocument()
    })
  })

  // ============================================================
  // 行操作
  // ============================================================

  describe('行操作', () => {
    it('「行を追加」で行が増える', async () => {
      render(<BudgetTableWrapper />)

      const beforeCount = screen.queryAllByRole('button', { name: '行を削除' }).length
      await userEvent.click(screen.getByRole('button', { name: '行を追加' }))
      const afterCount = screen.queryAllByRole('button', { name: '行を削除' }).length

      expect(afterCount).toBe(beforeCount + 1)
    })

    it('removableFrom以降の行は削除ボタンが表示される', () => {
      const initialExpenses = [
        { id: '1', subject: '会場費', amount: 0, grantUsage: 0, memo: '' },
        { id: '2', subject: '印刷費', amount: 0, grantUsage: 0, memo: '' },
      ]
      render(<BudgetTableWrapper removableFrom={0} initialExpenses={initialExpenses} />)

      const deleteButtons = screen.queryAllByRole('button', { name: '行を削除' })
      expect(deleteButtons.length).toBe(2)
    })

    it('removableFrom未満の行は削除ボタンが表示されない', () => {
      const initialExpenses = [
        { id: '1', subject: '会場費', amount: 0, grantUsage: 0, memo: '' },
        { id: '2', subject: '印刷費', amount: 0, grantUsage: 0, memo: '' },
      ]
      // removableFrom=2 なので全行削除不可
      render(<BudgetTableWrapper removableFrom={2} initialExpenses={initialExpenses} />)

      const deleteButtons = screen.queryAllByRole('button', { name: '行を削除' })
      expect(deleteButtons.length).toBe(0)
    })
  })

})
