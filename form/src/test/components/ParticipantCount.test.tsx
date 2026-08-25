import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useForm } from 'react-hook-form'
import ParticipantCount from '../../components/ParticipantCount'
import type { FormData } from '../../types/form'

function ParticipantCountWrapper({ sectionTitle = '参加人数' }: { sectionTitle?: string }) {
  const { register, watch, formState: { errors } } = useForm<FormData>({
    defaultValues: {
      section2: {
        organizer:    { count: 0, days: 0, total: 0 },
        participants: { count: 0, days: 0, total: 0 },
      },
    },
  })

  return (
    <ParticipantCount
      register={register}
      errors={errors}
      watch={watch}
      organizer={{
        countField: 'section2.organizer.count',
        daysField:  'section2.organizer.days',
      }}
      participant={{
        countField: 'section2.participants.count',
        daysField:  'section2.participants.days',
      }}
      sectionTitle={sectionTitle}
    />
  )
}

describe('ParticipantCount', () => {

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('セクションタイトルが表示される', () => {
      render(<ParticipantCountWrapper sectionTitle="参加人数（実績）" />)
      expect(screen.getByText('参加人数（実績）')).toBeInTheDocument()
    })

    it('申請団体側人数が表示される', () => {
      render(<ParticipantCountWrapper />)
      expect(screen.getByText('申請団体側人数')).toBeInTheDocument()
    })

    it('参加側人数が表示される', () => {
      render(<ParticipantCountWrapper />)
      expect(screen.getByText('参加側人数')).toBeInTheDocument()
    })

    it('延べ人数フィールドが表示される', () => {
      render(<ParticipantCountWrapper />)
      const labels = screen.getAllByText('延べ人数')
      expect(labels.length).toBe(2)
    })
  })

  // ============================================================
  // 自動計算
  // ============================================================

  describe('自動計算', () => {
    it('両方0のとき延べ人数は0', () => {
      render(<ParticipantCountWrapper />)
      const readOnlyInputs = document.querySelectorAll('input[readonly]')
      readOnlyInputs.forEach((input) => {
        expect((input as HTMLInputElement).value).toBe('0')
      })
    })

    it('申請団体側の人数×日数が延べ人数に反映される', () => {
      function TestWrapper() {
        const { register, watch, formState: { errors } } = useForm<FormData>({
          defaultValues: {
            section2: {
              organizer:    { count: 5, days: 3, total: 0 },
              participants: { count: 0, days: 0, total: 0 },
            },
          },
        })

        return (
          <ParticipantCount
            register={register}
            errors={errors}
            watch={watch}
            organizer={{
              countField: 'section2.organizer.count',
              daysField:  'section2.organizer.days',
            }}
            participant={{
              countField: 'section2.participants.count',
              daysField:  'section2.participants.days',
            }}
          />
        )
      }

      render(<TestWrapper />)

      const readOnlyInputs = document.querySelectorAll('input[readonly]')
      expect((readOnlyInputs[0] as HTMLInputElement).value).toBe('15')
    })

    it('参加側の人数×日数が延べ人数に反映される', () => {
      function TestWrapper() {
        const { register, watch, formState: { errors } } = useForm<FormData>({
          defaultValues: {
            section2: {
              organizer:    { count: 0, days: 0, total: 0 },
              participants: { count: 10, days: 2, total: 0 },
            },
          },
        })

        return (
          <ParticipantCount
            register={register}
            errors={errors}
            watch={watch}
            organizer={{
              countField: 'section2.organizer.count',
              daysField:  'section2.organizer.days',
            }}
            participant={{
              countField: 'section2.participants.count',
              daysField:  'section2.participants.days',
            }}
          />
        )
      }

      render(<TestWrapper />)

      const readOnlyInputs = document.querySelectorAll('input[readonly]')
      expect((readOnlyInputs[1] as HTMLInputElement).value).toBe('20')
    })
  })

  // ============================================================
  // 数値入力（フォーカス維持の回帰テスト）
  // ============================================================
  // 「人数」「日数」の行コンポーネント（CountRow）が以前はParticipantCountの
  // 関数本体内で定義されていたため、watch()の値が変わって再レンダーされる
  // （＝1文字入力するたび）ごとに毎回新しい関数として扱われ、既存の<input>が
  // アンマウント・再マウントされてフォーカスが外れていた。そのため実際の
  // ブラウザでは1文字入力するとフォーカスが外れ、1桁しか入力できなかった。

  describe('数値入力（フォーカス維持）', () => {
    it('人数欄に複数桁の数値を続けて入力できる', async () => {
      render(<ParticipantCountWrapper />)

      const countInputs = document.querySelectorAll(
        'input[type="number"]:not([readonly])',
      ) as NodeListOf<HTMLInputElement>
      const organizerCountInput = countInputs[0]

      await userEvent.clear(organizerCountInput)
      await userEvent.type(organizerCountInput, '123')

      expect(organizerCountInput.value).toBe('123')
    })

    it('日数欄に複数桁の数値を続けて入力できる', async () => {
      render(<ParticipantCountWrapper />)

      const dayInputs = document.querySelectorAll(
        'input[type="number"]:not([readonly])',
      ) as NodeListOf<HTMLInputElement>
      const organizerDaysInput = dayInputs[1]

      await userEvent.clear(organizerDaysInput)
      await userEvent.type(organizerDaysInput, '45')

      expect(organizerDaysInput.value).toBe('45')
    })
  })

})
