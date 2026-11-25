import { fmtVolume } from '../../utils/format'

export default function UnusualVolumeModule({ data }) {
  const rows = data?.unusual_volume ?? []

  return (
    <div className="overflow-hidden rounded-2xl border border-amber-500/20 bg-slate-900/60">
      <p className="border-b border-amber-500/10 px-5 py-3 text-xs text-amber-200/90">
        Flag: today volume ≥ 1.5× 20-day average (premium signal).
      </p>
      <table className="w-full text-sm">
        <thead className="bg-slate-950/50 text-left text-xs uppercase text-slate-500">
          <tr>
            <th className="px-5 py-2">Stock</th>
            <th className="px-5 py-2 text-right">Volume</th>
            <th className="px-5 py-2 text-right">20d avg</th>
            <th className="px-5 py-2 text-right">Ratio</th>
            <th className="px-5 py-2 text-right">Pts</th>
          </tr>
        </thead>
        <tbody>
          {rows.length ? (
            rows.map((r) => (
              <tr key={r.symbol} className="border-t border-slate-800">
                <td className="px-5 py-2">
                  <div className="font-medium">{r.name}</div>
                  <div className="text-xs text-slate-500">{r.symbol}</div>
                </td>
                <td className="px-5 py-2 text-right tabular-nums">{fmtVolume(r.volume)}</td>
                <td className="px-5 py-2 text-right tabular-nums">{fmtVolume(r.avg_volume_20d)}</td>
                <td className="px-5 py-2 text-right font-semibold text-amber-300">{r.ratio}×</td>
                <td className="px-5 py-2 text-right tabular-nums">{Number(r.points).toFixed(2)}</td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan={5} className="px-5 py-6 text-center text-slate-500">
                Hit &quot;Refresh modules&quot; after Dhan Data API is active.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  )
}