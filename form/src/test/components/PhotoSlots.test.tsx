import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import PhotoSlots from '../../components/PhotoSlots'
import type { FormData } from '../../types/form'

function PhotoSlotsWrapper({ maxSlots = 3 }: { maxSlots?: number }) {
  const { control, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section5: { photos: [] },
    },
  })

  return (
    <PhotoSlots
      control={control}
      errors={errors}
      name="section5.photos"
      maxSlots={maxSlots}
    />
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

})
