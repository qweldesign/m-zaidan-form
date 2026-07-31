// src/router.tsx

import { createBrowserRouter } from 'react-router'
import App from './App'
import Entrance from './pages/Entrance'
import Application from './pages/Application'
import Report from './pages/Report'

// トークンをpropsで渡すためのラッパー
function ApplicationWrapper() {
  const searchParams = new URLSearchParams(window.location.search)
  const editToken    = searchParams.get('token') ?? undefined
  return <Application editToken={editToken} />
}

function ReportWrapper() {
  const searchParams = new URLSearchParams(window.location.search)
  const editToken    = searchParams.get('token') ?? undefined
  return <Report editToken={editToken} />
}

export const router = createBrowserRouter([
  {
    path: '/',
    element: <App />,
    children: [
      { index: true, element: <Entrance /> },
      { path: 'application', children: [
        { index: true, element: <ApplicationWrapper /> },
      ]},
      { path: 'report', children: [
        { index: true, element: <ReportWrapper /> },
      ]},
    ]
  }
])
