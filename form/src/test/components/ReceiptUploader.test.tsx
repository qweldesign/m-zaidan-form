import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import ReceiptUploader from '../../components/ReceiptUploader'
import type { ReportFormData } from '../../types/form'

function ReceiptUploaderWrapper() {
  const { control, formState: { errors } } = useForm<ReportFormData>({
    defaultValues: {
      reportSection3: { receipts: [] },
    },
  })

  return (
    <ReceiptUploader
      control={control}
      errors={errors}
      name="reportSection3.receipts"
    />
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

})
