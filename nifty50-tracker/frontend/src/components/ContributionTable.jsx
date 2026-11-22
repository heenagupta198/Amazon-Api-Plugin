function formatPoints(value) {
  const n = Number(value)
  const sign = n > 0 ? '+' : ''
  return `${sign}${n.toFixed(2)}`
}

export default function ContributionTable({ title, rows, emptyLabel }) {
  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
      <div className="border-b border-slate-800 px-5 py-3">
        <h3 className="text-sm font-semibold text-slate-200">{title}</h3>
      </div>
      <table className="w-full text-sm">
        <thead className="bg-slate-950/60 text-left text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th className="px-5 py-2 font-medium">Stock</th>
            <th className="px-5 py-2 font-medium text-right">Points</th>
          </tr>
        </thead>
        <tbody>
          {rows?.length ? (
            rows.map((row) => (
              <tr key={row.symbol} className="border-t border-slate-800/80">
                <td className="px-5 py-2.5">
                  <div className="font-medium text-slate-100">{row.name}</div>
                  <div className="text-xs text-slate-500">{row.symbol}</div>
                </td>
                <td
                  className={`px-5 py-2.5 text-right font-semibold tabular-nums ${
                    row.points >= 0 ? 'text-emerald-400' : 'text-rose-400'
                  }`}
                >
                  {formatPoints(row.points)}
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan={2} className="px-5 py-6 text-center text-slate-500">
                {emptyLabel}
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  )
}