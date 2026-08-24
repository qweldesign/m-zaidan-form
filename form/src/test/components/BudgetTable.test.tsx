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
  initialGrantRequest = 0,
}: {
  removableFrom?: number
  initialExpenses?: FormData['section3']['expenses']
  initialGrantRequest?: number
}) {
  const { register, watch, control, trigger, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section3: {
        income: {
          grantRequest: initialGrantRequest,
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
    <>
      <BudgetTable
        register={register}
        errors={errors}
        watch={watch}
        control={control}
        prefix="section3"
        removableFrom={removableFrom}
      />
      {/* 送信時相当のバリデーションを発火させるための検証ボタン（テスト用） */}
      <button type="button" onClick={() => trigger('section3.income.grantRequest' as any)}>
        検証
      </button>
    </>
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

  // ============================================================
  // 検算（助成金要望額 と 助成金使用額合計 の一致チェック）
  // ============================================================
  // 「助成金要望額」が入力されているのに支出行の「助成金使用額」が
  // すべて0（＝合計が0）の場合も、金額の不一致として検出されるべき。
  // 修正前は「助成金使用額合計 > 0」の場合しか判定していなかったため、
  // 支出行が未入力のまま要望額だけ入力してもエラーにならなかった。

  describe('検算（助成金要望額と助成金使用額合計の一致チェック）', () => {
    it('助成金使用額合計が0のままでも、助成金要望額が入力されていれば不一致の案内が表示される', () => {
      const initialExpenses = [
        { id: '1', subject: '会場費', amount: 0, grantUsage: 0, memo: '' },
      ]
      render(<BudgetTableWrapper initialGrantRequest={100000} initialExpenses={initialExpenses} />)

      expect(screen.getByText(/一致していません/)).toBeInTheDocument()
    })

    it('助成金要望額と助成金使用額合計が一致していれば案内が表示されない', () => {
      const initialExpenses = [
        { id: '1', subject: '会場費', amount: 100000, grantUsage: 100000, memo: '' },
      ]
      render(<BudgetTableWrapper initialGrantRequest={100000} initialExpenses={initialExpenses} />)

      expect(screen.queryByText(/一致していません/)).not.toBeInTheDocument()
    })

    it('助成金使用額合計が0のまま検証すると、助成金要望額の項目がバリデーションエラーになる（従来はエラーにならなかった不具合の修正）', async () => {
      const initialExpenses = [
        { id: '1', subject: '会場費', amount: 0, grantUsage: 0, memo: '' },
      ]
      render(<BudgetTableWrapper initialGrantRequest={100000} initialExpenses={initialExpenses} />)

      await userEvent.click(screen.getByRole('button', { name: '検証' }))

      expect(
        await screen.findByText('助成金要望額と支出の部の助成金使用額合計（0円）が一致していません'),
      ).toBeInTheDocument()
    })

    it('金額が一致していれば検証してもバリデーションエラーにならない', async () => {
      const initialExpenses = [
        { id: '1', subject: '会場費', amount: 100000, grantUsage: 100000, memo: '' },
      ]
      render(<BudgetTableWrapper initialGrantRequest={100000} initialExpenses={initialExpenses} />)

      await userEvent.click(screen.getByRole('button', { name: '検証' }))

      expect(screen.queryByText(/一致していません/)).not.toBeInTheDocument()
    })
  })

})
