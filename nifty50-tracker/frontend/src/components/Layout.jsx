import { NavLink, Outlet } from 'react-router-dom'

const links = [
  { to: '/', label: 'Dashboard' },
  { to: '/gainers', label: 'Top Gainers' },
  { to: '/losers', label: 'Top Losers' },
  { to: '/stocks', label: 'All 50 Stocks' },
  { to: '/breadth', label: 'Market Breadth' },
  { to: '/historical', label: 'Historical' },
  { to: '/admin', label: 'Admin' },
]

export default function Layout() {
  return (
    <div className="mx-auto max-w-6xl px-4 py-6">
      <header className="mb-8 flex flex-col gap-4 border-b border-slate-800 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-sm font-medium uppercase tracking-widest text-emerald-400">
            Nifty 50 Contribution Tracker
          </p>
          <h1 className="text-2xl font-semibold text-white sm:text-3xl">
            Live index contribution analytics
          </h1>
        </div>
        <nav className="flex flex-wrap gap-2">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.to === '/'}
              className={({ isActive }) =>
                [
                  'rounded-full px-3 py-1.5 text-sm transition',
                  isActive
                    ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/40'
                    : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200',
                ].join(' ')
              }
            >
              {link.label}
            </NavLink>
          ))}
        </nav>
      </header>
      <Outlet />
    </div>
  )
}