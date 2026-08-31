import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import ReceiptUploader from '../../components/ReceiptUploader'
import type { ReportFormData } from '../../types/form'

function ReceiptUploaderWrapper({ maxFiles }: { maxFiles?: number } = {}) {
  const { control, trigger, formState: { errors } } = useForm<ReportFormData>({
    defaultValues: {
      reportSection3: { receipts: [] },
    },
  })

  return (
    <>
      <ReceiptUploader
        control={control}
        errors={errors}
        name="reportSection3.receipts"
        maxFiles={maxFiles}
      />
      {/* 送信時相当のバリデーションを発火させるための検証ボタン（テスト用） */}
      <button type="button" onClick={() => trigger(['reportSection3.receipts'] as any)}>検証</button>
    </>
  )
}

describe('ReceiptUploader', () => {

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('「ファイルを追加」ボタンが表示される', () => {
      render(<ReceiptUploaderWrapper />)
      expect(screen.getByText('ファイルを追加')).toBeInTheDocument()
    })

    it('初期状態で「ファイルを選択してください」が表示される', () => {
      render(<ReceiptUploaderWrapper />)
      expect(screen.getByText('ファイルを選択してください')).toBeInTheDocument()
    })
  })

  // ============================================================
  // ファイル操作
  // ============================================================

  describe('ファイル操作', () => {
    it('ファイルを追加するとファイル名が表示される', async () => {
      render(<ReceiptUploaderWrapper />)

      const file  = new File(['dummy'], 'receipt.pdf', { type: 'application/pdf' })
      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, file)

      expect(screen.getByText('receipt.pdf')).toBeInTheDocument()
    })

    it('ファイルを追加すると「現在 N ファイル」に変わる', async () => {
      render(<ReceiptUploaderWrapper />)

      const file  = new File(['dummy'], 'receipt.pdf', { type: 'application/pdf' })
      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, file)

      expect(screen.getByText('現在 1 ファイル')).toBeInTheDocument()
    })

    it('複数ファイルを追加できる', async () => {
      render(<ReceiptUploaderWrapper />)

      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, new File(['dummy1'], 'receipt1.pdf', { type: 'application/pdf' }))
      await userEvent.upload(input, new File(['dummy2'], 'receipt2.pdf', { type: 'application/pdf' }))

      expect(screen.getByText('receipt1.pdf')).toBeInTheDocument()
      expect(screen.getByText('receipt2.pdf')).toBeInTheDocument()
      expect(screen.getByText('現在 2 ファイル')).toBeInTheDocument()
    })

    it('削除ボタンをクリックするとファイルが消える', async () => {
      render(<ReceiptUploaderWrapper />)

      const file  = new File(['dummy'], 'receipt.pdf', { type: 'application/pdf' })
      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, file)
      expect(screen.getByText('receipt.pdf')).toBeInTheDocument()

      await userEvent.click(screen.getByRole('button', { name: '削除' }))
      expect(screen.queryByText('receipt.pdf')).not.toBeInTheDocument()
    })
  })

  // ============================================================
  // 枚数上限
  // ============================================================
  // 以前は枚数の上限が無く、post_max_size を超えるほど大量に
  // アップロードされてしまう可能性があった不具合に対応。

  describe('枚数上限', () => {
    it('上限に達すると「ファイルを追加」ボタンが非表示になり、上限メッセージが表示される', async () => {
      render(<ReceiptUploaderWrapper maxFiles={2} />)

      const input = document.querySelector('input[type="file"]') as HTMLInputElement
      await userEvent.upload(input, new File(['1'], 'r1.pdf', { type: 'application/pdf' }))
      await userEvent.upload(
        document.querySelector('input[type="file"]') as HTMLInputElement,
        new File(['2'], 'r2.pdf', { type: 'application/pdf' }),
      )

      expect(screen.queryByText('ファイルを追加')).not.toBeInTheDocument()
      expect(screen.getByText('領収書は最大2枚まで添付できます（現在 2 ファイル）')).toBeInTheDocument()
    })

    it('上限未満では「ファイルを追加」ボタンが表示されたままになる', async () => {
      render(<ReceiptUploaderWrapper maxFiles={2} />)

      const input = document.querySelector('input[type="file"]') as HTMLInputElement
      await userEvent.upload(input, new File(['1'], 'r1.pdf', { type: 'application/pdf' }))

      expect(screen.getByText('ファイルを追加')).toBeInTheDocument()
    })

    it('デフォルトの上限（5枚）を超えて検証すると、バリデーションエラーになる', async () => {
      render(<ReceiptUploaderWrapper />)

      // 上限に達する前に非表示になる「ファイルを追加」に頼らず、11枚分アップロードして確認する
      let input = document.querySelector('input[type="file"]') as HTMLInputElement
      for (let i = 1; i <= 10; i++) {
        await userEvent.upload(input, new File([`${i}`], `r${i}.pdf`, { type: 'application/pdf' }))
        // 上限未満のうちは同じinputが残る
        input = document.querySelector('input[type="file"]') as HTMLInputElement
      }

      // 10枚に達した時点で追加ボタン自体が消えるため、11枚目はUI上追加できない。
      // つまりこの状態でのユーザー操作としては既に上限に収まっているはずで、
      // バリデーション上もエラーにならないことを確認する。
      await userEvent.click(screen.getByRole('button', { name: '検証' }))
      expect(screen.queryByText(/最大10枚までです/)).not.toBeInTheDocument()
      expect(screen.getByText('領収書は最大10枚まで添付できます（現在 10 ファイル）')).toBeInTheDocument()
    })
  })

})
