import { useCallback, useEffect, useState } from 'react'
import { NIFTY_MODULES } from '../modules/registry'
import { api } from '../services/api'

export default function NiftyPulse() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [refreshing, setRefreshing] = useState(false)
  const [active, setActive] = useState(NIFTY_MODULES[0].id)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const payload = await api.modulesNifty50()
      setData(payload)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  async function refreshModules() {
    setRefreshing(true)
    try {
      const payload = await api.modulesRefresh()
      setData(payload)
    } finally {
      setRefreshing(false)
    }
  }

  const activeModule = NIFTY_MODULES.find((m) => m.id === active)
  const ModuleView = activeModule?.Component

  return (
    <div className="space-y-6">
      <header className="rounded-2xl border border-amber-500/25 bg-slate-900/50 p-5">
        <h2 className="text-xl font-semibold text-white">
          NiftyPulse · {data?.scope ?? 'Nifty 50 only'}
        </h2>
        <p className="mt-1 text-sm text-slate-400">
          {data?.compliance_note}
        </p>
        <button
          type="button"
          onClick={refreshModules}
          disabled={refreshing}
          className="mt-4 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-60"
        >
          {refreshing ? 'Computing… (uses Dhan historical)' : 'Refresh modules'}
        </button>
      </header>

      <nav className="flex flex-wrap gap-2">
        {NIFTY_MODULES.map((m) => (
          <button
            key={m.id}
            type="button"
            onClick={() => setActive(m.id)}
            className={`rounded-full px-3 py-1.5 text-sm ${
              active === m.id
                ? 'bg-amber-500/20 text-amber-200 ring-1 ring-amber-500/40'
                : 'bg-slate-800 text-slate-400'
            }`}
          >
            {m.title}
          </button>
        ))}
      </nav>

      {loading ? (
        <p className="text-slate-400">Loading modules…</p>
      ) : ModuleView ? (
        <section>
          <h3 className="mb-2 text-lg font-medium text-slate-200">{activeModule.title}</h3>
          <p className="mb-4 text-sm text-slate-500">{activeModule.description}</p>
          <ModuleView data={data} />
        </section>
      ) : null}
    </div>
  )
}