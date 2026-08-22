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
  // 編集トークン使用時のLocalStorageキー分離
  // ============================================================
  // トークン無しの新規申請と、トークン付きURLでの再編集とで一時保存の
  // LocalStorageキーを分ける前は、一方の一時保存データがもう一方の
  // 再開ダイアログに誤って表示されてしまう問題があった。

  describe('編集トークン使用時のLocalStorageキー分離', () => {
    const editingResponse = {
      ok: true,
      json: async () => ({
        data: {
          status: '審査前',
          section1_json: {}, section2_json: {}, section3_json: {}, section4_json: {},
        },
      }),
    }

    it('トークン無しの一時保存データがあっても、編集トークン付きURLではダイアログが表示されない', async () => {
      localStorage.setItem('zaidan_draft', JSON.stringify({ section1: {} }))
      mockFetch.mockResolvedValue(editingResponse)

      render(<Application editToken="tok123" />)

      await waitFor(() => expect(mockFetch).toHaveBeenCalled())
      expect(screen.queryByText('入力途中のデータがあります')).not.toBeInTheDocument()
    })

    it('編集トークンごとに一時保存データが別キーで保存され、トークン無しのキーとは混ざらない', async () => {
      mockFetch.mockResolvedValue(editingResponse)

      render(<Application editToken="tok123" />)
      await waitFor(() => expect(mockFetch).toHaveBeenCalled())

      await userEvent.click(screen.getByRole('button', { name: '一時保存' }))

      expect(localStorage.getItem('zaidan_draft')).toBeNull()
      expect(localStorage.getItem('zaidan_draft_edit_tok123')).not.toBeNull()
    })

    it('トークンの取得が完了するまで再開ダイアログの判定を待つ（サーバーデータ取得中に古い一時保存で上書きされない）', async () => {
      // このトークン専用のキーに、以前中断した再編集の一時保存データがあるケース
      localStorage.setItem('zaidan_draft_edit_tok123', JSON.stringify({ section1: {} }))

      let resolveFetch: (value: unknown) => void = () => {}
      mockFetch.mockReturnValue(new Promise((resolve) => { resolveFetch = resolve }))

      render(<Application editToken="tok123" />)

      // サーバーからの取得が完了するまではダイアログを出さない
      expect(screen.queryByText('入力途中のデータがあります')).not.toBeInTheDocument()

      resolveFetch(editingResponse)

      // 取得完了後にあらためて判定され、ダイアログが表示される
      await waitFor(() => {
        expect(screen.getByText('入力途中のデータがあります')).toBeInTheDocument()
      })
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
