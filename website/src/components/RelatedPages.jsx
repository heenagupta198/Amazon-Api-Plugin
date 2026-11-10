import { Link } from 'react-router-dom'

export default function RelatedPages({ title, pages }) {
  if (!pages?.length) return null

  return (
    <section className="py-10 bg-gray-50 border-t border-gray-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-6">{title}</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {pages.map((page) => (
            <Link
              key={page.href}
              to={page.href}
              className="bg-white border border-gray-100 rounded-xl p-4 hover:border-brand/30 hover:shadow-md transition-all group"
            >
              <h3 className="font-semibold text-gray-900 group-hover:text-brand transition-colors text-sm sm:text-base">
                {page.label}
              </h3>
              {page.description && (
                <p className="text-gray-600 text-xs sm:text-sm mt-1 line-clamp-2">{page.description}</p>
              )}
            </Link>
          ))}
        </div>
      </div>
    </section>
  )
}
