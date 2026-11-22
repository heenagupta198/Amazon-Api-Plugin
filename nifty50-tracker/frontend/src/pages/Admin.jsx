import { useEffect, useState } from 'react'
import { api } from '../services/api'

export default function Admin() {
  const [status, setStatus] = useState(null)
  const [dhan, setDhan] = useState(null)

  useEffect(() => {
    api.adminStatus().then(setStatus)
    api.dhanTest().then(setDhan)
  }, [])

  return (
    <div className="space-y-6">
      <h2 className="text-lg font-semibold">Admin panel</h2>
      <div className="grid gap-4 md:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 text-sm">
          <h3 className="font-medium text-slate-200">Data inventory</h3>
          <ul className="mt-3 space-y-1 text-slate-400">
            <li>Active stocks: {status?.stocks ?? '—'}</li>
            <li>Mapped security IDs: {status?.with_security_id ?? '—'}</li>
            <li>Dhan .env configured: {status?.env?.dhan_configured ? 'Yes' : 'No'}</li>
          </ul>
        </div>
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 text-sm">
          <h3 className="font-medium text-slate-200">Dhan connection test</h3>
          <pre className="mt-3 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-300">
            {JSON.stringify(dhan, null, 2)}
          </pre>
        </div>
      </div>
      <p className="text-sm text-slate-500">
        Run <code className="text-slate-300">php artisan nifty:import-constituents</code> and set{' '}
        <code className="text-slate-300">DHAN_ACCESS_TOKEN</code> /{' '}
        <code className="text-slate-300">DHAN_CLIENT_ID</code> in <code>backend/.env</code>.
      </p>
    </div>
  )
}