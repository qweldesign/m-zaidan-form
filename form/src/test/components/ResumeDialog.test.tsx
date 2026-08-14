import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import ResumeDialog from '../../components/ResumeDialog'

describe('ResumeDialog', () => {

  const defaultProps = {
    onResume:    vi.fn(),
    onStartOver: vi.fn(),
  }

  // ============================================================
  // 表示内容
  // ============================================================

  describe('表示内容', () => {
    it('タイトルが表示される', () => {
      render(<ResumeDialog {...defaultProps} />)
      expect(screen.getByText('入力途中のデータがあります')).toBeInTheDocument()
    })

    it('「再開する」ボタンが表示される', () => {
      render(<ResumeDialog {...defaultProps} />)
      expect(screen.getByRole('button', { name: '再開する' })).toBeInTheDocument()
    })

    it('「最初から入力する」ボタンが表示される', () => {
      render(<ResumeDialog {...defaultProps} />)
      expect(screen.getByRole('button', { name: '最初から入力する' })).toBeInTheDocument()
    })

    it('noteが渡された場合に表示される', () => {
      render(<ResumeDialog {...defaultProps} note="※ 添付ファイルは再選択が必要です。" />)
      expect(screen.getByText('※ 添付ファイルは再選択が必要です。')).toBeInTheDocument()
    })

    it('noteが渡されない場合は表示されない', () => {
      render(<ResumeDialog {...defaultProps} />)
      expect(screen.queryByText('※ 添付ファイルは再選択が必要です。')).not.toBeInTheDocument()
    })
  })

  // ============================================================
  // ボタン操作
  // ============================================================

  describe('ボタン操作', () => {
    it('「再開する」クリックでonResumeが呼ばれる', async () => {
      const onResume = vi.fn()
      render(<ResumeDialog {...defaultProps} onResume={onResume} />)

      await userEvent.click(screen.getByRole('button', { name: '再開する' }))

      expect(onResume).toHaveBeenCalledTimes(1)
    })

    it('「最初から入力する」クリックでonStartOverが呼ばれる', async () => {
      const onStartOver = vi.fn()
      render(<ResumeDialog {...defaultProps} onStartOver={onStartOver} />)

      await userEvent.click(screen.getByRole('button', { name: '最初から入力する' }))

      expect(onStartOver).toHaveBeenCalledTimes(1)
    })
  })

})
