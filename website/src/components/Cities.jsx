import { useState } from 'react'
import { Link } from 'react-router-dom'
import { cities } from '../data/siteData'
import { locationSlug } from '../data/locations'
import { LocationIcon, ArrowRightIcon } from './Icons'

export default function Cities() {
  const [showAll, setShowAll] = useState(false)
  const visibleCities = showAll ? cities : cities.slice(0, 20)

  return (
    <section className="bg-white py-12 sm:py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-2xl sm:text-3xl font-bold text-center text-gray-900 mb-8 sm:mb-10">
          Web Design & Development Services in Major Cities
        </h2>

        <div className="flex flex-wrap justify-center gap-2 sm:gap-3">
          {visibleCities.map((city) => (
            <Link
              key={city}
              to={`/${locationSlug(city)}`}
              className="inline-flex items-center gap-1.5 bg-gray-50 hover:bg-brand/10 hover:text-brand border border-gray-200 text-gray-700 text-xs sm:text-sm font-medium px-3 py-1.5 sm:px-4 sm:py-2 rounded-full transition-colors"
            >
              <LocationIcon className="text-brand" />
              {city}
            </Link>
          ))}
        </div>

        {!showAll && cities.length > 20 && (
          <div className="text-center mt-6">
            <button
              type="button"
              onClick={() => setShowAll(true)}
              className="inline-flex items-center gap-2 text-brand hover:text-brand-dark font-semibold text-sm transition-colors"
            >
              View All Cities
              <ArrowRightIcon />
            </button>
          </div>
        )}
      </div>
    </section>
  )
}
