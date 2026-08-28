import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import Section5 from '../../../pages/Application/Section5'
import type { FormData } from '../../../types/form'

// isEditMode が正しく PhotoSlots / PdfUploader へ伝播しているかを確認するテスト。
// 過去に <PhotoSlots isEditMode /> のように値を直書きしてしまい、常に
// isEditMode=true 扱いになる（＝写真の必須チェックが常に無効化される）
// バグがあったため、実際の isEditMode の値ごとに挙動が変わることを検証する。

function Section5Wrapper({ isEditMode }: { isEditMode: boolean }) {
  const { register, control, watch, trigger, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section5: {
        photos: [],
        docs: {
          regulations: null,
          activityReport: null,
          financialReport: null,
          activityPlan: null,
          financialPlan: null,
        },
        confirmed: false,
      },
    } as any,
  })

  return (
    <>
      <Section5 register={register} errors={errors} watch={watch} control={control} isEditMode={isEditMode} />
      <button
        type="button"
        onClick={() => trigger(['section5.photos'] as any)}
      >
        検証
      </button>
    </>
  )
}

describe('Section5（要望申請フォーム・添付資料の isEditMode 伝播）', () => {
  it('isEditMode=false（新規申請）では、写真未添付だとバリデーションエラーになる', async () => {
    render(<Section5Wrapper isEditMode={false} />)

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(await screen.findByText('写真を1枚以上アップロードしてください')).toBeInTheDocument()
  })

  it('isEditMode=true（編集）では、写真未添付でもバリデーションエラーにならない', async () => {
    render(<Section5Wrapper isEditMode={true} />)

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(screen.queryByText('写真を1枚以上アップロードしてください')).not.toBeInTheDocument()
  })
})
