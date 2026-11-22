import { useEffect, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { api } from '../services/api'

export default function Admin() {
  const { isAdmin } = useAuth()
  const [status, setStatus] = useState(null)
  const [dhan, setDhan] = useState(null)
  const [subscribers, setSubscribers] = useState([])
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    subscription_days: 30,
  })
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!isAdmin) return
    api.adminStatus().then(setStatus)
    api.dhanTest().then(setDhan)
    api.listSubscribers().then((r) => setSubscribers(r.data ?? []))
  }, [isAdmin])

  if (!isAdmin) {
    return <Navigate to="/" replace />
  }

  async function createSubscriber(e) {
    e.preventDefault()
    setMessage('')
    try {
      await api.createSubscriber(form)
      setMessage('Subscriber created.')
      const list = await api.listSubscribers()
      setSubscribers(list.data ?? [])
      setForm({ name: '', email: '', password: '', subscription_days: 30 })
    } catch (err) {
      setMessage(err.message)
    }
  }

  return (
    <div className="space-y-6">
      <h2 className="text-lg font-semibold text-amber-200">Admin · full control</h2>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 text-sm">
          <h3 className="font-medium text-slate-200">Platform status</h3>
          <ul className="mt-3 space-y-1 text-slate-400">
            <li>Active stocks: {status?.stocks ?? '—'}</li>
            <li>Security IDs: {status?.with_security_id ?? '—'}</li>
            <li>Dhan configured: {status?.env?.dhan_configured ? 'Yes' : 'No'}</li>
          </ul>
        </div>
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 text-sm">
          <h3 className="font-medium text-slate-200">Dhan connection</h3>
          <pre className="mt-3 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-300">
            {JSON.stringify(dhan, null, 2)}
          </pre>
        </div>
      </div>

      <div className="rounded-2xl border border-amber-500/25 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-amber-200">Create subscriber (no public signup)</h3>
        <form onSubmit={createSubscriber} className="mt-4 grid gap-3 sm:grid-cols-2">
          <input
            placeholder="Name"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            className="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
          />
          <input
            type="email"
            placeholder="Email"
            required
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            className="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
          />
          <input
            type="password"
            placeholder="Password (min 12)"
            required
            minLength={12}
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
            className="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm sm:col-span-2"
          />
          <input
            type="number"
            min={1}
            value={form.subscription_days}
            onChange={(e) =>
              setForm({ ...form, subscription_days: Number(e.target.value) })
            }
            className="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
          />
          <button
            type="submit"
            className="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950"
          >
            Add subscriber
          </button>
        </form>
        {message ? <p className="mt-2 text-xs text-slate-400">{message}</p> : null}
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-800">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-900 text-left text-xs uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">User</th>
              <th className="px-4 py-3">Role</th>
              <th className="px-4 py-3">Subscription</th>
              <th className="px-4 py-3">Active</th>
            </tr>
          </thead>
          <tbody>
            {subscribers.map((u) => (
              <tr key={u.id} className="border-t border-slate-800">
                <td className="px-4 py-2">
                  {u.name}
                  <div className="text-xs text-slate-500">{u.email}</div>
                </td>
                <td className="px-4 py-2">{u.is_admin ? 'Admin' : 'Subscriber'}</td>
                <td className="px-4 py-2 text-xs">{u.subscription_expires_at ?? '—'}</td>
                <td className="px-4 py-2">{u.is_active ? 'Yes' : 'No'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}