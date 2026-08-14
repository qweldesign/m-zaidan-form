import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import Application from '../../pages/Application'

// fetchのモック
const mockFetch = vi.fn()
vi.stubGlobal('fetch', mockFetch)

// scrollToのモック
vi.stubGlobal('scrollTo', vi.fn())

describe('Application', () => {

  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()
  })

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('フォームタイトルが表示される', () => {
      render(<Application />)
      expect(screen.getByText('要望申請フォーム')).toBeInTheDocument()
    })

    it('初期状態でSTEP1が表示される', () => {
      render(<Application />)
      expect(screen.getByText('STEP 1 / 5')).toBeInTheDocument()
    })

    it('「次へ」ボタンが表示される', () => {
      render(<Application />)
      expect(screen.getByRole('button', { name: '次へ' })).toBeInTheDocument()
    })

    it('STEP1では「戻る」ボタンが表示されない', () => {
      render(<Application />)
      expect(screen.queryByRole('button', { name: '戻る' })).not.toBeInTheDocument()
    })

    it('「一時保存」ボタンが表示される', () => {
      render(<Application />)
      expect(screen.getByRole('button', { name: '一時保存' })).toBeInTheDocument()
    })
  })

  // ============================================================
  // 再開ダイアログ
  // ============================================================

  describe('再開ダイアログ', () => {
    it('LocalStorageにデータがある場合ダイアログが表示される', () => {
      localStorage.setItem('zaidan_draft', JSON.stringify({ section1: {} }))
      render(<Application />)
      expect(screen.getByText('入力途中のデータがあります')).toBeInTheDocument()
    })

    it('LocalStorageにデータがない場合ダイアログが表示されない', () => {
      render(<Application />)
      expect(screen.queryByText('入力途中のデータがあります')).not.toBeInTheDocument()
    })

    it('「最初から入力する」をクリックするとダイアログが閉じる', async () => {
      localStorage.setItem('zaidan_draft', JSON.stringify({ section1: {} }))
      render(<Application />)

      await userEvent.click(screen.getByRole('button', { name: '最初から入力する' }))

      expect(screen.queryByText('入力途中のデータがあります')).not.toBeInTheDocument()
    })

    it('「再開する」をクリックするとダイアログが閉じる', async () => {
      localStorage.setItem('zaidan_draft', JSON.stringify({ section1: {} }))
      render(<Application />)

      await userEvent.click(screen.getByRole('button', { name: '再開する' }))

      expect(screen.queryByText('入力途中のデータがあります')).not.toBeInTheDocument()
    })
  })

  // ============================================================
  // STEP遷移
  // ============================================================

  describe('STEP遷移', () => {
    it('バリデーション失敗時はSTEPが進まない', async () => {
      render(<Application />)

      // 何も入力せずに次へ
      await userEvent.click(screen.getByRole('button', { name: '次へ' }))

      // まだSTEP1のまま
      expect(screen.getByText('STEP 1 / 5')).toBeInTheDocument()
    })
  })

  // ============================================================
  // 一時保存
  // ============================================================

  describe('一時保存', () => {
    it('「一時保存」をクリックするとLocalStorageに保存される', async () => {
      render(<Application />)

      await userEvent.click(screen.getByRole('button', { name: '一時保存' }))

      expect(localStorage.getItem('zaidan_draft')).not.toBeNull()
    })

    it('「一時保存」をクリックすると「保存しました」が表示される', async () => {
      render(<Application />)

      await userEvent.click(screen.getByRole('button', { name: '一時保存' }))

      await waitFor(() => {
        expect(screen.getByText('保存しました')).toBeInTheDocument()
      })
    })
  })

})
