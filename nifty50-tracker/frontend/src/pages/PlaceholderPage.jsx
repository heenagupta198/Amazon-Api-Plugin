export default function PlaceholderPage({ title, description }) {
  return (
    <div className="rounded-2xl border border-dashed border-slate-700 bg-slate-900/30 p-8">
      <h2 className="text-lg font-semibold text-white">{title}</h2>
      <p className="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">
        {description}
      </p>
    </div>
  )
}