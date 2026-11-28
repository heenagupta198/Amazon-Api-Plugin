const TABS = ['5m', '15m', '1h', '1d']

export default function SuperTrendModule({ data }) {
  const byTf = data?.supertrend ?? {}

  return (
    <div className="grid gap-4 md:grid-cols-2">
      {TABS.map((tf) => (
        <div key={tf} className="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
          <h4 className="text-sm font-semibold text-sky-300">SuperTrend · {tf}</h4>
          <ul className="mt-3 space-y-2 text-sm">
            {(byTf[tf] ?? []).map((r) => (
              <li key={`${tf}-${r.symbol}`} className="flex justify-between">
                <span>{r.symbol}</span>
                <span
                  className={
                    r.signal === 'bullish_breakout' ? 'text-emerald-400' : 'text-rose-400'
                  }
                >
                  {r.signal === 'bullish_breakout' ? 'Bull breakout' : 'Bear breakdown'}
                </span>
              </li>
            ))}
            {!byTf[tf]?.length ? (
              <li className="text-slate-500">No signals yet.</li>
            ) : null}
          </ul>
        </div>
      ))}
    </div>
  )
}