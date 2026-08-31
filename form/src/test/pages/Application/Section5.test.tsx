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
          other: null,
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
        onClick={() => trigger(['section5.photos', 'section5.docs'] as any)}
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

    expect(await screen.findByText('写真は3枚必須です')).toBeInTheDocument()
  })

  it('isEditMode=true（編集）では、写真未添付でもバリデーションエラーにならない', async () => {
    render(<Section5Wrapper isEditMode={true} />)

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    expect(screen.queryByText('写真は3枚必須です')).not.toBeInTheDocument()
  })
})

// 「その他」資料（機関誌・新聞記事等の補足資料・見積書・カタログなどを想定）は
// 必須の PDF 5種とは異なり任意の添付欄として追加した。ラベル表示・バリデーション
// の両面で「必須ではない」ことを確認する。
describe('Section5（PDF資料「その他」は任意）', () => {
  it('「その他」ラベルには必須マーク（*）ではなく（任意）と表示される', () => {
    render(<Section5Wrapper isEditMode={false} />)

    const label = screen.getByText('その他').closest('label')
    expect(label).toHaveTextContent('その他（任意）')
    expect(label).not.toHaveTextContent('その他*')
  })

  it('isEditMode=false（新規申請）でも、「その他」が未添付だとバリデーションエラーにはならない', async () => {
    render(<Section5Wrapper isEditMode={false} />)

    await userEvent.click(screen.getByRole('button', { name: '検証' }))

    // 他の必須PDFのエラーは出るが、「その他」のエラーだけは出ないことを確認する
    expect(await screen.findByText('団体規約をアップロードしてください')).toBeInTheDocument()
    expect(screen.queryByText('その他をアップロードしてください')).not.toBeInTheDocument()
  })
})
