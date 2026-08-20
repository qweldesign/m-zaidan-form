import { useState } from 'react'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import PdfUploader from '../../components/PdfUploader'
import type { FormData } from '../../types/form'

function PdfUploaderWrapper({ isEditMode = false }: { isEditMode?: boolean }) {
  const { control, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section5: { docs: { regulations: null } as any },
    },
  })

  return (
    <PdfUploader
      control={control}
      errors={errors}
      name="section5.docs.regulations"
      label="団体規約"
      isEditMode={isEditMode}
    />
  )
}

describe('PdfUploader', () => {

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('「ファイルを選択」ボタンが表示される', () => {
      render(<PdfUploaderWrapper />)
      expect(screen.getByText('ファイルを選択')).toBeInTheDocument()
    })

    it('初期状態で「ファイルが選択されていません」が表示される', () => {
      render(<PdfUploaderWrapper />)
      expect(screen.getByText('ファイルが選択されていません')).toBeInTheDocument()
    })
  })

  // ============================================================
  // ファイル操作
  // ============================================================

  describe('ファイル操作', () => {
    it('ファイルを選択するとファイル名が表示される', async () => {
      render(<PdfUploaderWrapper />)

      const file  = new File(['dummy'], 'kiyaku.pdf', { type: 'application/pdf' })
      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, file)

      expect(screen.getByText('kiyaku.pdf')).toBeInTheDocument()
      expect(screen.getByText('ファイルを変更')).toBeInTheDocument()
    })

    it('ファイルを選択し直すと新しいファイル名に置き換わる', async () => {
      render(<PdfUploaderWrapper />)

      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, new File(['dummy1'], 'old.pdf', { type: 'application/pdf' }))
      expect(screen.getByText('old.pdf')).toBeInTheDocument()

      await userEvent.upload(input, new File(['dummy2'], 'new.pdf', { type: 'application/pdf' }))
      expect(screen.queryByText('old.pdf')).not.toBeInTheDocument()
      expect(screen.getByText('new.pdf')).toBeInTheDocument()
    })

    it('削除ボタンをクリックするとファイル名が消える', async () => {
      render(<PdfUploaderWrapper />)

      const file  = new File(['dummy'], 'kiyaku.pdf', { type: 'application/pdf' })
      const input = document.querySelector('input[type="file"]') as HTMLInputElement

      await userEvent.upload(input, file)
      expect(screen.getByText('kiyaku.pdf')).toBeInTheDocument()

      await userEvent.click(screen.getByRole('button', { name: '削除' }))
      expect(screen.queryByText('kiyaku.pdf')).not.toBeInTheDocument()
      expect(screen.getByText('ファイルが選択されていません')).toBeInTheDocument()
    })
  })

  // ============================================================
  // 回帰テスト：STEPの行き来（アンマウント・再マウント）
  // ============================================================

  describe('アンマウント・再マウント', () => {
    // Application.tsx の STEP 切り替えは {step === 5 && <Section5 .../>} のように
    // 条件付きレンダリングになっており、STEP 5から離れると PdfUploader を含む
    // Section5 コンポーネントごとアンマウントされる。旧実装（register()を直接
    // <input type="file"> にスプレッドする非制御な形）では、選択済みファイルが
    // DOM要素（そのブラウザ上の<input>ノード自体）にしか保持されておらず、
    // アンマウントと同時に消えてしまっていた。Controller経由でreact-hook-formの
    // 中央state（useFormを呼んでいる親コンポーネント側で保持される）に保存する
    // ことで、PdfUploaderがアンマウント・再マウントされても選択済みファイルが
    // 保持されることを確認する。
    it('コンポーネントがアンマウント・再マウントされても選択済みファイルが保持される', async () => {
      function ToggleWrapper() {
        const { control, formState: { errors } } = useForm<FormData>({
          defaultValues: {
            section5: { docs: { regulations: null } as any },
          },
        })
        const [show, setShow] = useState(true)

        return (
          <>
            <button type="button" onClick={() => setShow((v) => !v)}>
              STEP切り替え
            </button>
            {show && (
              <PdfUploader
                control={control}
                errors={errors}
                name="section5.docs.regulations"
                label="団体規約"
              />
            )}
          </>
        )
      }

      render(<ToggleWrapper />)

      const file  = new File(['dummy'], 'kiyaku.pdf', { type: 'application/pdf' })
      const input = document.querySelector('input[type="file"]') as HTMLInputElement
      await userEvent.upload(input, file)
      expect(screen.getByText('kiyaku.pdf')).toBeInTheDocument()

      // 他のSTEPへ移動（アンマウント）
      await userEvent.click(screen.getByRole('button', { name: 'STEP切り替え' }))
      expect(screen.queryByText('kiyaku.pdf')).not.toBeInTheDocument()

      // STEP 5に戻る（再マウント）
      await userEvent.click(screen.getByRole('button', { name: 'STEP切り替え' }))

      // 選択済みファイルがそのまま復元されている
      expect(screen.getByText('kiyaku.pdf')).toBeInTheDocument()
    })
  })

})
