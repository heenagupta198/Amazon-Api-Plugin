import { useEffect, useState } from 'react'
import { api } from '../services/api'

export default function AllStocks() {
  const [rows, setRows] = useState([])
  const [sort, setSort] = useState('contribution')

  useEffect(() => {
    api.stocks().then((res) => setRows(res.data ?? []))
  }, [])

  const sorted = [...rows].sort((a, b) => {
    if (sort === 'weight') return Number(b.weightage) - Number(a.weightage)
    if (sort === 'pct') return Number(b.change_percent) - Number(a.change_percent)
    return Number(b.net_contribution) - Number(a.net_contribution)
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-3">
        <h2 className="text-lg font-semibold">All Nifty 50 stocks</h2>
        <select
          value={sort}
          onChange={(e) => setSort(e.target.value)}
          className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm"
        >
          <option value="contribution">Sort by contribution</option>
          <option value="weight">Sort by weightage</option>
          <option value="pct">Sort by % change</option>
        </select>
      </div>
      <div className="overflow-x-auto rounded-2xl border border-slate-800">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-900 text-left text-xs uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">Symbol</th>
              <th className="px-4 py-3">LTP</th>
              <th className="px-4 py-3">% Chg</th>
              <th className="px-4 py-3">Weight %</th>
              <th className="px-4 py-3 text-right">Net pts</th>
            </tr>
          </thead>
          <tbody>
            {sorted.map((s) => (
              <tr key={s.id ?? s.symbol} className="border-t border-slate-800">
                <td className="px-4 py-2">{s.symbol}</td>
                <td className="px-4 py-2 tabular-nums">{s.ltp ?? '—'}</td>
                <td className="px-4 py-2 tabular-nums">{s.change_percent ?? '—'}</td>
                <td className="px-4 py-2 tabular-nums">{s.weightage ?? '—'}</td>
                <td className="px-4 py-2 text-right tabular-nums">{s.net_contribution ?? '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}