const TABS = ['5m', '15m', '1h', '1d']

export default function VwapModule({ data }) {
  const byTf = data?.vwap_above ?? {}

  return (
    <div className="grid gap-4 md:grid-cols-2">
      {TABS.map((tf) => (
        <div key={tf} className="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
          <h4 className="text-sm font-semibold text-emerald-300">Above VWAP · {tf}</h4>
          <ul className="mt-3 space-y-2 text-sm">
            {(byTf[tf] ?? []).map((r) => (
              <li key={`${tf}-${r.symbol}`} className="flex justify-between border-b border-slate-800/80 pb-2">
                <span>
                  {r.symbol} <span className="text-slate-500">LTP {r.ltp}</span>
                </span>
                <span className="text-emerald-400">+{r.distance_pct}% vs VWAP</span>
              </li>
            ))}
            {!byTf[tf]?.length ? (
              <li className="text-slate-500">No names — refresh modules with live API.</li>
            ) : null}
          </ul>
        </div>
      ))}
    </div>
  )
}