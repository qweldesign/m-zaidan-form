import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import ReportSection3 from '../../../pages/Report/Section3'
import type { ReportFormData } from '../../../types/form'

// isEditMode が正しく PhotoSlots / ReceiptUploader へ伝播しているかを確認するテスト。
// 過去に isEditMode を props で受け取らず（さらに <PhotoSlots isEditMode /> /
// <ReceiptUploader isEditMode /> のように値を直書きしてしまい）、常に
// isEditMode=true 扱いになる（＝写真・領収書の必須チェックが常に無効化される）
// バグがあったため、実際の isEditMode の値ごとに挙動が変わることを検証する。

function Section3Wrapper({ isEditMode }: { isEditMode: boolean }) {
  const { register, control, watch, trigger, formState: { errors } } = useForm<ReportFormData>({
    defaultValues: {
      reportSection3: {
        photos: [],
        receipts: [],
        confirmed: false,
      },
    } as any,
  })

  return (
    <>
      <ReportSection3 register={register} errors={errors} watch={watch} control={control} isEditMode={isEditMode} />
      <button
        type="button"
        onClick={() => trigger(['reportSection3.photos', 'reportSection3.receipts'] as any)}
      >
        検証
      </button>
    </>
  )
}

describe('ReportSection3（完了報告フォーム・添付資料の isEditMode 伝播）', () => {
  it('isEditMode=false（新規提出）では、写真・領収書とも未添付だとバリデーションエラーになる', async () => {
    render(<Section3Wrapper isEditMode={false} />)

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(await screen.findByText('写真を1枚以上アップロードしてください')).toBeInTheDocument()
    expect(await screen.findByText('領収書をアップロードしてください')).toBeInTheDocument()
  })

  it('isEditMode=true（編集）では、写真・領収書とも未添付でもバリデーションエラーにならない', async () => {
    render(<Section3Wrapper isEditMode={true} />)

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(screen.queryByText('写真を1枚以上アップロードしてください')).not.toBeInTheDocument()
    expect(screen.queryByText('領収書をアップロードしてください')).not.toBeInTheDocument()
  })
})
