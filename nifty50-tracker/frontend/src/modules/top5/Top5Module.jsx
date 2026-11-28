import ContributionTable from '../../components/ContributionTable'

export default function Top5Module({ data }) {
  const top5 = data?.top5 ?? {}

  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <ContributionTable title="Top 5 positive" rows={top5.positive} emptyLabel="Refresh market" />
      <ContributionTable title="Top 5 negative" rows={top5.negative} emptyLabel="Refresh market" />
      <div className="lg:col-span-2">
        <ContributionTable title="Top 5 volume" rows={top5.volume} emptyLabel="Refresh market" />
      </div>
    </div>
  )
}