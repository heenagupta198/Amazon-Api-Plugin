export default function StatCard({ label, value, tone = 'neutral', hint }) {
  const toneClass =
    tone === 'positive'
      ? 'text-emerald-400'
      : tone === 'negative'
        ? 'text-rose-400'
        : 'text-white'

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-black/20">
      <p className="text-xs font-medium uppercase tracking-wider text-slate-500">
        {label}
      </p>
      <p className={`mt-2 text-3xl font-semibold tabular-nums ${toneClass}`}>
        {value}
      </p>
      {hint ? <p className="mt-1 text-xs text-slate-500">{hint}</p> : null}
    </div>
  )
}