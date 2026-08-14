import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import SaveToast from '../../components/SaveToast'

describe('SaveToast', () => {

  it('「保存しました」が表示される', () => {
    render(<SaveToast />)
    expect(screen.getByText('保存しました')).toBeInTheDocument()
  })

})
