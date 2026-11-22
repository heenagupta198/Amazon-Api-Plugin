import { useCallback, useEffect, useState } from 'react'
import ContributionTable from '../components/ContributionTable'
import StatCard from '../components/StatCard'
import TopFivePremium from '../components/TopFivePremium'
import { api } from '../services/api'

function fmt(n, digits = 2) {
  if (n == null || Number.isNaN(Number(n))) return '—'
  const sign = Number(n) > 0 ? '+' : ''
  return `${sign}${Number(n).toFixed(digits)}`
}

export default function Dashboard() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [refreshing, setRefreshing] = useState(false)
  const [error, setError] = useState(null)

  const load = useCallback(async () => {
    setError(null)
    try {
      const payload = await api.dashboard()
      setData(payload)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
    const id = setInterval(load, 30_000)
    return () => clearInterval(id)
  }, [load])

  async function handleRefresh() {
    setRefreshing(true)
    try {
      await api.refresh()
      await load()
    } catch (e) {
      setError(e.message)
    } finally {
      setRefreshing(false)
    }
  }

  const summary = data?.summary

  return (
    <div className="space-y-6">
      <section className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-xl font-semibold text-white">
            {data?.brand ?? 'Yogesh Traderdomina'} · {data?.title ?? 'NIFTY 50 LIVE'}
          </h2>
          <p className="mt-1 text-sm text-slate-400">
            {summary?.is_live
              ? 'Live DhanHQ feed'
              : 'Connect Dhan API for live quotes + volume'}
            {summary?.as_of ? ` · Updated ${new Date(summary.as_of).toLocaleString()}` : ''}
          </p>
        </div>
        <button
          type="button"
          onClick={handleRefresh}
          disabled={refreshing}
          className="rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:from-amber-400 disabled:opacity-60"
        >
          {refreshing ? 'Refreshing…' : 'Refresh quotes'}
        </button>
      </section>

      {error ? (
        <div className="rounded-xl border border-rose-500/30 bg-rose-950/30 px-4 py-3 text-sm text-rose-200">
          {error}
        </div>
      ) : null}

      {loading ? (
        <p className="text-slate-400">Loading dashboard…</p>
      ) : (
        <>
          <TopFivePremium
            niftyValue={summary?.nifty_value}
            niftyChange={summary?.nifty_change}
            totalVolume={summary?.total_volume}
            topVolume={data?.top_volume}
          />

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
              label="Total positive"
              value={`${fmt(summary?.total_positive)} pts`}
              tone="positive"
            />
            <StatCard
              label="Total negative"
              value={`${fmt(summary?.total_negative)} pts`}
              tone="negative"
            />
            <StatCard
              label="Net contribution"
              value={`${fmt(summary?.net_contribution)} pts`}
              tone={
                Number(summary?.net_contribution) >= 0 ? 'positive' : 'negative'
              }
            />
            <StatCard
              label="Net Nifty move"
              value={`${fmt(summary?.nifty_change)} pts`}
              tone={Number(summary?.nifty_change) >= 0 ? 'positive' : 'negative'}
            />
          </div>

          <div className="grid gap-4 lg:grid-cols-2">
            <ContributionTable
              title="Top 5 positive contributors"
              rows={data?.top_positive}
              emptyLabel="Refresh after Dhan import"
            />
            <ContributionTable
              title="Top 5 negative contributors"
              rows={data?.top_negative}
              emptyLabel="Refresh after Dhan import"
            />
          </div>

          <div className="rounded-2xl border border-slate-800 bg-slate-900/40 px-5 py-4 text-sm text-slate-400">
            <span className="text-slate-300">Advancers / decliners:</span>{' '}
            <span className="text-emerald-400">{summary?.advancers ?? 0}</span> /{' '}
            <span className="text-rose-400">{summary?.decliners ?? 0}</span>
          </div>
        </>
      )}
    </div>
  )
}