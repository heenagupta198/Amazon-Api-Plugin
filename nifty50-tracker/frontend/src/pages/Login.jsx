import { useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Login() {
  const { login, isAuthenticated, booting } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  if (!booting && isAuthenticated) {
    return <Navigate to="/" replace />
  }

  async function onSubmit(e) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(email.trim(), password)
    } catch (err) {
      setError(err.message ?? 'Login failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950/40 px-4">
      <div className="w-full max-w-md rounded-3xl border border-amber-500/30 bg-slate-950/90 p-8 shadow-2xl shadow-amber-900/20">
        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-amber-400">
          Premium access only
        </p>
        <h1 className="mt-2 text-2xl font-bold text-white">Yogesh Traderdomina</h1>
        <p className="mt-2 text-sm text-slate-400">
          Subscription required. Public sign-up is disabled — contact admin for access.
        </p>

        <form onSubmit={onSubmit} className="mt-8 space-y-4">
          <label className="block text-sm text-slate-300">
            Email
            <input
              type="email"
              autoComplete="username"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none ring-amber-500/0 focus:ring-2 focus:ring-amber-500/40"
            />
          </label>
          <label className="block text-sm text-slate-300">
            Password
            <input
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none focus:ring-2 focus:ring-amber-500/40"
            />
          </label>

          {error ? (
            <p className="rounded-lg border border-rose-500/30 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">
              {error}
            </p>
          ) : null}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 py-2.5 text-sm font-semibold text-slate-950 hover:from-amber-400 hover:to-amber-500 disabled:opacity-60"
          >
            {loading ? 'Signing in…' : 'Enter premium workspace'}
          </button>
        </form>
      </div>
    </div>
  )
}