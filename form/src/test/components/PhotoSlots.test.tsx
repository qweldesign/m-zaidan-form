import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import PhotoSlots from '../../components/PhotoSlots'
import type { FormData } from '../../types/form'

function PhotoSlotsWrapper({ maxSlots = 3, isEditMode }: { maxSlots?: number; isEditMode?: boolean }) {
  const { control, trigger, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section5: { photos: [] },
    },
  })

  return (
    <>
      <PhotoSlots
        control={control}
        errors={errors}
        name="section5.photos"
        maxSlots={maxSlots}
        isEditMode={isEditMode}
      />
      {/* 送信時相当のバリデーションを発火させるための検証ボタン（テスト用） */}
      <button type="button" onClick={() => trigger(['section5.photos'] as any)}>検証</button>
    </>
  )
}

describe('PhotoSlots', () => {

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('maxSlotsの数だけスロットが表示される', () => {
      render(<PhotoSlotsWrapper maxSlots={3} />)
      const inputs = document.querySelectorAll('input[type="file"]')
      expect(inputs.length).toBe(3)
    })

    it('各スロットに「N枚目」ラベルが表示される', () => {
      render(<PhotoSlotsWrapper maxSlots={3} />)
      expect(screen.getByText('1 枚目')).toBeInTheDocument()
      expect(screen.getByText('2 枚目')).toBeInTheDocument()
      expect(screen.getByText('3 枚目')).toBeInTheDocument()
    })
  })

  // ============================================================
  // ファイル操作
  // ============================================================

  describe('ファイル操作', () => {
    it('ファイルを選択するとファイル名が表示される', async () => {
      render(<PhotoSlotsWrapper />)

      const file  = new File(['dummy'], 'test.jpg', { type: 'image/jpeg' })
      const input = document.querySelectorAll('input[type="file"]')[0] as HTMLInputElement

      await userEvent.upload(input, file)

      expect(screen.getByText('test.jpg')).toBeInTheDocument()
    })

    it('削除ボタンはファイル選択前は表示されない', () => {
      render(<PhotoSlotsWrapper />)
      expect(screen.queryByRole('button', { name: '削除' })).not.toBeInTheDocument()
    })

    it('ファイルを選択すると削除ボタンが表示される', async () => {
      render(<PhotoSlotsWrapper />)

      const file  = new File(['dummy'], 'test.jpg', { type: 'image/jpeg' })
      const input = document.querySelectorAll('input[type="file"]')[0] as HTMLInputElement

      await userEvent.upload(input, file)

      expect(screen.getByRole('button', { name: '削除' })).toBeInTheDocument()
    })

    it('削除ボタンをクリックするとファイル名が消える', async () => {
      render(<PhotoSlotsWrapper />)

      const file  = new File(['dummy'], 'test.jpg', { type: 'image/jpeg' })
      const input = document.querySelectorAll('input[type="file"]')[0] as HTMLInputElement

      await userEvent.upload(input, file)
      expect(screen.getByText('test.jpg')).toBeInTheDocument()

      await userEvent.click(screen.getByRole('button', { name: '削除' }))
      expect(screen.queryByText('test.jpg')).not.toBeInTheDocument()
    })
  })

  // ============================================================
  // 規定枚数のバリデーション
  // ============================================================
  // 以前は「1枚以上」であればよかったが、規定枚数（maxSlots）ちょうどを
  // 必須にする仕様変更を行った（要望申請は3枚、完了報告は2枚）。

  describe('規定枚数のバリデーション', () => {
    it('1枚も選択せずに検証すると、規定枚数のエラーになる', async () => {
      render(<PhotoSlotsWrapper maxSlots={3} />)

      await userEvent.click(screen.getByRole('button', { name: '検証' }))

      expect(await screen.findByText('写真は3枚必須です')).toBeInTheDocument()
    })

    it('規定枚数より少ない状態で検証すると、規定枚数のエラーになる', async () => {
      render(<PhotoSlotsWrapper maxSlots={3} />)

      const input = document.querySelectorAll('input[type="file"]')[0] as HTMLInputElement
      await userEvent.upload(input, new File(['1'], 'p1.jpg', { type: 'image/jpeg' }))

      await userEvent.click(screen.getByRole('button', { name: '検証' }))

      expect(await screen.findByText('写真は3枚必須です')).toBeInTheDocument()
    })

    it('規定枚数ちょうど選択して検証すると、エラーにならない', async () => {
      render(<PhotoSlotsWrapper maxSlots={3} />)

      const inputs = document.querySelectorAll('input[type="file"]')
      await userEvent.upload(inputs[0] as HTMLInputElement, new File(['1'], 'p1.jpg', { type: 'image/jpeg' }))
      await userEvent.upload(inputs[1] as HTMLInputElement, new File(['2'], 'p2.jpg', { type: 'image/jpeg' }))
      await userEvent.upload(inputs[2] as HTMLInputElement, new File(['3'], 'p3.jpg', { type: 'image/jpeg' }))

      await userEvent.click(screen.getByRole('button', { name: '検証' }))

      expect(screen.queryByText('写真は3枚必須です')).not.toBeInTheDocument()
    })

    it('編集モード（isEditMode）では、1枚も選択していなくても検証エラーにならない', async () => {
      render(<PhotoSlotsWrapper maxSlots={3} isEditMode />)

      await userEvent.click(screen.getByRole('button', { name: '検証' }))

      expect(screen.queryByText('写真は3枚必須です')).not.toBeInTheDocument()
    })
  })

})
