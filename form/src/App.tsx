// src/App.tsx

import { Outlet } from 'react-router-dom'

function App() {
  return (
    <div className="relative min-h-screen pb-36 bg-gray-100">
      <header className="flex flex-wrap items-center justify-between max-w-3xl mx-auto">
        <h1 className="w-1/2 p-3 mix-blend-multiply">
          <img src="/images/m-zaidan_logo.png" alt="三谷市民文化振興財団" />
        </h1>
        <div className="w-1/2 max-w-2xs p-3 font-bold text-[min(2.7vw,16px)]">
          <span className="block">〒910-0857</span>
          <span className="block">福井県福井市豊島1-3-1三谷ビル</span>
          <span className="block">
            <span className="inline-block mr-1">TEL0776-20-3188</span>
            <span className="inline-block">FAX0776-20-3306</span></span>
        </div>
      </header>
      <main className="max-w-3xl mx-auto p-3">
        <Outlet />
      </main>
      <footer className="absolute bottom-0 w-full py-12 bg-sky-300 text-center">
        <small>&copy; { new Date().getFullYear() } 三谷市民文化振興財団</small>
      </footer>
    </div>
  )
}

export default App
