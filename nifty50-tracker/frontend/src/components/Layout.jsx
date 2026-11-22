import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const links = [
  { to: '/', label: 'Dashboard' },
  { to: '/stocks', label: 'All 50 Stocks' },
  { to: '/admin', label: 'Admin', adminOnly: true },
]

export default function Layout() {
  const { user, logout, isAdmin } = useAuth()

  return (
    <div className="mx-auto max-w-6xl px-4 py-6">
      <header className="mb-8 flex flex-col gap-4 border-b border-amber-500/20 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-sm font-medium uppercase tracking-widest text-amber-400">
            Yogesh Traderdomina
          </p>
          <h1 className="text-2xl font-semibold text-white sm:text-3xl">
            Premium Nifty 50 intelligence
          </h1>
          <p className="mt-1 text-xs text-slate-500">
            {user?.is_admin ? 'Admin — full access' : `Subscriber · expires ${user?.subscription_expires_at ?? '—'}`}
          </p>
        </div>
        <div className="flex flex-col items-stretch gap-2 sm:items-end">
          <nav className="flex flex-wrap gap-2">
            {links
              .filter((l) => !l.adminOnly || isAdmin)
              .map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  end={link.to === '/'}
                  className={({ isActive }) =>
                    [
                      'rounded-full px-3 py-1.5 text-sm transition',
                      isActive
                        ? 'bg-amber-500/20 text-amber-200 ring-1 ring-amber-500/40'
                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200',
                    ].join(' ')
                  }
                >
                  {link.label}
                </NavLink>
              ))}
          </nav>
          <button
            type="button"
            onClick={() => logout()}
            className="self-end text-xs text-slate-500 underline hover:text-slate-300"
          >
            Sign out
          </button>
        </div>
      </header>
      <Outlet />
    </div>
  )
}