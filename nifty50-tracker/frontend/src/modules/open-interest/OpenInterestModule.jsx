export default function OpenInterestModule({ data }) {
  const rows = data?.open_interest ?? []

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
      <p className="text-xs text-slate-400">
        OI change (digits) + mini chart — F&O contract mapping arrives next; structure is ready.
      </p>
      <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {rows.map((r) => (
          <div key={r.symbol} className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
            <div className="font-medium text-slate-100">{r.symbol}</div>
            <div className="mt-2 text-2xl font-semibold tabular-nums text-amber-300">
              {r.oi_change_pct != null ? `${r.oi_change_pct}%` : '—'}
            </div>
            <div className="mt-1 text-xs text-slate-500">{r.note}</div>
            <div className="mt-3 h-8 rounded bg-gradient-to-r from-slate-800 via-amber-900/40 to-slate-800" />
          </div>
        ))}
      </div>
    </div>
  )
}