import { fmtVolume } from '../utils/format'

function Row({ rank, row }) {
  return (
    <li className="flex items-center justify-between gap-3 border-t border-amber-500/10 py-2.5 first:border-t-0">
      <div className="flex items-center gap-3">
        <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/15 text-xs font-bold text-amber-300">
          {rank}
        </span>
        <div>
          <div className="font-medium text-slate-100">{row.name}</div>
          <div className="text-xs text-slate-500">{row.symbol}</div>
        </div>
      </div>
      <div className="text-right text-xs">
        <div
          className={`font-semibold tabular-nums ${
            row.points >= 0 ? 'text-emerald-400' : 'text-rose-400'
          }`}
        >
          {row.points >= 0 ? '+' : ''}
          {Number(row.points).toFixed(2)} pts
        </div>
        <div className="mt-0.5 text-slate-500">Vol {fmtVolume(row.volume)}</div>
      </div>
    </li>
  )
}

export default function TopFivePremium({ niftyValue, niftyChange, totalVolume, topVolume }) {
  return (
    <section className="rounded-3xl border border-amber-500/25 bg-gradient-to-br from-slate-900 via-slate-950 to-amber-950/30 p-6 shadow-xl">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-widest text-amber-400">
            Nifty 50 numbers
          </p>
          <p className="mt-1 text-4xl font-bold tabular-nums text-white">
            {niftyValue != null ? Number(niftyValue).toFixed(2) : '—'}
          </p>
          <p className="mt-1 text-sm text-slate-400">
            Day change{' '}
            <span
              className={
                Number(niftyChange) >= 0 ? 'text-emerald-400' : 'text-rose-400'
              }
            >
              {niftyChange != null
                ? `${Number(niftyChange) >= 0 ? '+' : ''}${Number(niftyChange).toFixed(2)}`
                : '—'}{' '}
              pts
            </span>
            {' · '}
            Basket volume{' '}
            <span className="text-amber-200">{fmtVolume(totalVolume)}</span>
          </p>
        </div>
        <div className="rounded-2xl border border-amber-500/20 bg-black/30 px-4 py-3 text-xs text-amber-100/90">
          Top 5 layout (premium) — contribution points + traded volume
        </div>
      </div>

      <div className="mt-6">
        <h3 className="text-sm font-semibold text-amber-200">Top 5 by volume today</h3>
        <ol className="mt-2">
          {(topVolume ?? []).map((row, i) => (
            <Row key={row.symbol} rank={i + 1} row={row} />
          ))}
        </ol>
        {!topVolume?.length ? (
          <p className="mt-3 text-sm text-slate-500">
            Refresh quotes after Dhan Data API is connected to load volume.
          </p>
        ) : null}
      </div>
    </section>
  )
}