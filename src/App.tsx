import { Outlet } from 'react-router-dom'

function App() {
  return (
    <>
      <header className="header">
        <h1 className="sitebrand">
          <img src="/images/m-zaidan_logo.png" alt="三谷市民文化振興財団" />
        </h1>
      </header>
      <main className="main">
        <Outlet />
      </main>
      <footer className="footer">
        <small>&copy; { new Date().getFullYear() } 三谷市民文化振興財団</small>
      </footer>
    </>
  )
}

export default App
