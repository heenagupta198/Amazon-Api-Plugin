export default function LoadingSpinner({ text = 'Loading...' }) {
  return (
    <div className="flex flex-col items-center justify-center py-20 gap-3">
      <div className="w-10 h-10 border-4 border-brand/20 border-t-brand rounded-full animate-spin" />
      <p className="text-gray-500 text-sm">{text}</p>
    </div>
  )
}
