import { renderHook, act } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useStepForm } from '../../hooks/useStepForm'

// window.scrollTo のモック
vi.stubGlobal('scrollTo', vi.fn())

// useStepForm に渡すモック関数
const createMocks = () => ({
  getValues: vi.fn().mockReturnValue({ section1: { teamName: 'テスト' } }),
  reset:     vi.fn(),
  trigger:   vi.fn().mockResolvedValue(true), // デフォルトはバリデーション通過
})

const defaultOptions = (mocks: ReturnType<typeof createMocks>) => ({
  totalSteps:    5,
  storageKey:    'test_draft',
  stepStorageKey:'test_draft_step',
  stepFields:    { 1: ['section1'], 2: ['section2'] } as Record<number, string[]>,
  getValues:     mocks.getValues,
  reset:         mocks.reset,
  trigger:       mocks.trigger,
})

describe('useStepForm', () => {

  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()
  })

  // ============================================================
  // LocalStorage確認（マウント時）
  // ============================================================

  describe('マウント時のLocalStorage確認', () => {
    it('保存データがあればshowResumeDialogがtrueになる', () => {
      localStorage.setItem('test_draft', JSON.stringify({ section1: {} }))

      const mocks = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      expect(result.current.showResumeDialog).toBe(true)
    })

    it('保存データがなければshowResumeDialogはfalseのまま', () => {
      const mocks = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      expect(result.current.showResumeDialog).toBe(false)
    })
  })

  // ============================================================
  // handleResume
  // ============================================================

  describe('handleResume', () => {
    it('LocalStorageのデータでresetが呼ばれる', () => {
      const savedData = { section1: { teamName: '保存済みデータ' } }
      localStorage.setItem('test_draft', JSON.stringify(savedData))

      const mocks  = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleResume() })

      expect(mocks.reset).toHaveBeenCalledWith(savedData)
    })

    it('保存されたSTEPに戻る', () => {
      localStorage.setItem('test_draft',      JSON.stringify({}))
      localStorage.setItem('test_draft_step', '3')

      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleResume() })

      expect(result.current.step).toBe(3)
    })

    it('showResumeDialogがfalseになる', () => {
      localStorage.setItem('test_draft', JSON.stringify({}))

      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleResume() })

      expect(result.current.showResumeDialog).toBe(false)
    })
  })

  // ============================================================
  // handleStartOver
  // ============================================================

  describe('handleStartOver', () => {
    it('LocalStorageがクリアされる', () => {
      localStorage.setItem('test_draft',      JSON.stringify({}))
      localStorage.setItem('test_draft_step', '2')

      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleStartOver() })

      expect(localStorage.getItem('test_draft')).toBeNull()
      expect(localStorage.getItem('test_draft_step')).toBeNull()
    })

    it('showResumeDialogがfalseになる', () => {
      localStorage.setItem('test_draft', JSON.stringify({}))

      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleStartOver() })

      expect(result.current.showResumeDialog).toBe(false)
    })
  })

  // ============================================================
  // handleSave
  // ============================================================

  describe('handleSave', () => {
    it('LocalStorageに保存される', () => {
      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleSave() })

      const saved = localStorage.getItem('test_draft')
      expect(saved).not.toBeNull()
      expect(JSON.parse(saved!)).toEqual({ section1: { teamName: 'テスト' } })
    })

    it('saveMessageがtrueになる', () => {
      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleSave() })

      expect(result.current.saveMessage).toBe(true)
    })
  })

  // ============================================================
  // handleNext
  // ============================================================

  describe('handleNext', () => {
    it('バリデーション通過でstepが進む', async () => {
      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      await act(async () => { await result.current.handleNext() })

      expect(result.current.step).toBe(2)
    })

    it('バリデーション失敗でstepが進まない', async () => {
      const mocks  = createMocks()
      mocks.trigger = vi.fn().mockResolvedValue(false) // バリデーション失敗

      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      await act(async () => { await result.current.handleNext() })

      expect(result.current.step).toBe(1)
    })

    it('totalStepsを超えない', async () => {
      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm({
        ...defaultOptions(mocks),
        totalSteps: 1,
      }))

      await act(async () => { await result.current.handleNext() })

      expect(result.current.step).toBe(1)
    })
  })

  // ============================================================
  // handleBack
  // ============================================================

  describe('handleBack', () => {
    it('stepが1つ戻る', async () => {
      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      // まず2に進める
      await act(async () => { await result.current.handleNext() })
      expect(result.current.step).toBe(2)

      // 戻る
      act(() => { result.current.handleBack() })
      expect(result.current.step).toBe(1)
    })

    it('step1では1より小さくならない', () => {
      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.handleBack() })

      expect(result.current.step).toBe(1)
    })
  })

  // ============================================================
  // clearStorage
  // ============================================================

  describe('clearStorage', () => {
    it('LocalStorageがクリアされる', () => {
      localStorage.setItem('test_draft',      JSON.stringify({}))
      localStorage.setItem('test_draft_step', '3')

      const mocks      = createMocks()
      const { result } = renderHook(() => useStepForm(defaultOptions(mocks)))

      act(() => { result.current.clearStorage() })

      expect(localStorage.getItem('test_draft')).toBeNull()
      expect(localStorage.getItem('test_draft_step')).toBeNull()
    })
  })

})
