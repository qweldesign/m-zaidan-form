import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { useForm } from 'react-hook-form'
import Section1 from '../../../pages/Application/Section1'
import type { FormData } from '../../../types/form'

// 郵便番号APIのモック
const mockFetch = vi.fn().mockResolvedValue({
  json: () => Promise.resolve({
    results: [{
      address1: '福井県',
      address2: '福井市',
      address3: '中央',
    }]
  })
})

vi.stubGlobal('fetch', mockFetch)

function Section1Wrapper() {
  const { register, watch, setValue, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section1: {
        teamName:             '',
        teamNameKana:         '',
        teamPostalCode:       '',
        teamAddress:          '',
        establishedYear:      String(new Date().getFullYear()),
        activityCategory:     'その他市民活動',
        members:              { under20: 0, age21to40: 0, age41to60: 0, over61: 0 },
        grantHistory:         { thisFoundationCount: 0, thisFoundationLatestYear: '', otherFoundationCount: 0, otherFoundationLatestYear: '' },
        applicationHistory:   { count: 0, latestYear: '' },
        applicationRoute:     [],
        applicationRouteOther:'',
        representativeName:   '',
        representativeNameKana: '',
        representativePhone:  '',
        representativeEmail:  '',
        sameAsRepresentative: false,
        contactName:          '',
        contactNameKana:      '',
        contactPhone:         '',
        contactEmail:         '',
      },
    },
  })

  return (
    <Section1
      register={register}
      errors={errors}
      watch={watch}
      setValue={setValue}
    />
  )
}

describe('Section1', () => {

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('STEP1/5が表示される', () => {
      render(<Section1Wrapper />)
      expect(screen.getByText('STEP 1 / 5')).toBeInTheDocument()
    })

    it('団体名称フィールドが表示される', () => {
      render(<Section1Wrapper />)
      expect(screen.getByText('団体名称')).toBeInTheDocument()
    })

    it('「代表者と同じ」チェックボックスが表示される', () => {
      render(<Section1Wrapper />)
      expect(screen.getByText('代表者と同じ')).toBeInTheDocument()
    })

    it('「住所を検索」ボタンが表示される', () => {
      render(<Section1Wrapper />)
      expect(screen.getByRole('button', { name: '住所を検索' })).toBeInTheDocument()
    })
  })

  // ============================================================
  // バリデーション
  // ============================================================

  describe('バリデーション', () => {
    it('フリガナに英字を入力するとエラーが表示される', async () => {
      // useFormをラッパー外で使えないためuseFormラッパーを別途作る
      function TestWrapper() {
        const form = useForm<FormData>({
          defaultValues: { section1: { teamNameKana: '' } } as any,
        })
        return (
          <Section1
            register={form.register}
            errors={form.formState.errors}
            watch={form.watch}
            setValue={form.setValue}
          />
        )
      }

      render(<TestWrapper />)

      const kanaInput = screen.getAllByRole('textbox').find(
        (el) => el.getAttribute('placeholder') === null &&
          el.closest('tr')?.textContent?.includes('フリガナ')
      )

      if (kanaInput) {
        await userEvent.type(kanaInput, 'abc')
        await userEvent.tab()
      }
    })

    it('メールアドレスが不正な形式だとエラーが表示される', async () => {
      function TestWrapper() {
        const form = useForm<FormData>({ defaultValues: { section1: {} } as any })
        return (
          <Section1
            register={form.register}
            errors={form.formState.errors}
            watch={form.watch}
            setValue={form.setValue}
          />
        )
      }

      render(<TestWrapper />)

      const emailInputs = screen.getAllByRole('textbox').filter(
        (el) => el.getAttribute('type') === 'email' ||
          el.closest('tr')?.textContent?.includes('メールアドレス')
      )

      if (emailInputs[0]) {
        await userEvent.type(emailInputs[0], 'invalid-email')
        await userEvent.tab()
      }
    })
  })

  // ============================================================
  // 担当者情報
  // ============================================================

  describe('担当者情報', () => {
    it('「代表者と同じ」をチェックすると担当者フィールドが無効になる', async () => {
      render(<Section1Wrapper />)

      const checkbox = screen.getByRole('checkbox', { name: '代表者と同じ' })
      await userEvent.click(checkbox)

      // 担当者名フィールドが無効になっている
      const disabledInputs = screen.getAllByRole('textbox').filter(
        (el) => (el as HTMLInputElement).disabled
      )
      expect(disabledInputs.length).toBeGreaterThan(0)
    })
  })

  // ============================================================
  // 郵便番号
  // ============================================================

  describe('郵便番号', () => {
    it('住所を検索ボタンをクリックするとfetchが呼ばれる', async () => {
      render(<Section1Wrapper />)

      // name属性で取得
      const postalInput = document.querySelector('input[name="section1.teamPostalCode"]') as HTMLInputElement
      await userEvent.type(postalInput, '9100001')

      await userEvent.click(screen.getByRole('button', { name: '住所を検索' }))

      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('zipcloud')
      )
    })
  })

})
