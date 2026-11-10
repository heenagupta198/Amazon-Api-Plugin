import { Link } from 'react-router-dom'
import { globalRegions } from '../data/siteData'
import { CheckIcon, ArrowRightIcon } from './Icons'

export default function GlobalHire() {
  return (
    <section className="relative overflow-hidden world-map-bg py-14 sm:py-18 lg:py-24">
      <div className="absolute inset-0 opacity-[0.03] pointer-events-none"
        style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23006838' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
        }}
      />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div className="grid lg:grid-cols-5 gap-10 lg:gap-12 items-start">
          <div className="lg:col-span-2 lg:sticky lg:top-24">
            <div className="inline-flex items-center gap-2 bg-brand/10 text-brand text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full mb-5">
              <span className="text-lg">🌍</span>
              Global Web Development
            </div>

            <h2 className="text-2xl sm:text-3xl lg:text-4xl xl:text-[2.5rem] font-bold text-gray-900 mb-5 leading-tight">
              Web Development Services{' '}
              <span className="text-brand">Worldwide</span>
            </h2>

            <p className="text-gray-600 mb-4 leading-relaxed text-base sm:text-lg">
              We work with clients globally — USA, Canada, Australia, Europe and beyond.
              Seamless remote collaboration with clear communication and on-time delivery.
            </p>

            <ul className="space-y-2.5 mb-8">
              {['Flexible Time Zones', 'Video Calls & Daily Updates', 'Secure Payment Methods'].map((item) => (
                <li key={item} className="flex items-center gap-2.5 text-sm text-gray-700">
                  <span className="w-5 h-5 rounded-full bg-brand text-white flex items-center justify-center flex-shrink-0">
                    <CheckIcon className="w-3 h-3" />
                  </span>
                  {item}
                </li>
              ))}
            </ul>

            <Link
              to="/contact"
              className="btn-primary inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-semibold px-7 py-3.5 rounded-md text-base"
            >
              Start Your Project
              <ArrowRightIcon className="btn-arrow w-4 h-4" />
            </Link>
          </div>

          <div className="lg:col-span-3">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
              {globalRegions.map((region, i) => (
                <div
                  key={region.name}
                  className="global-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100 shadow-sm hover:border-brand/30 group"
                  style={{ animationDelay: `${i * 0.1}s` }}
                >
                  <div className="flex items-center gap-3 mb-4 pb-3 border-b border-gray-50">
                    <span
                      className="text-4xl group-hover:scale-110 transition-transform duration-300"
                      role="img"
                      aria-label={region.name}
                    >
                      {region.flag}
                    </span>
                    <div>
                      <h3 className="font-bold text-gray-900 text-lg">{region.name}</h3>
                      <p className="text-brand text-xs font-medium">Available for Projects</p>
                    </div>
                  </div>

                  <ul className="space-y-2">
                    {region.points.map((point) => (
                      <li key={point} className="flex items-center gap-2.5 text-sm text-gray-600 group-hover:text-gray-800 transition-colors">
                        <span className="text-brand flex-shrink-0">
                          <CheckIcon className="w-3.5 h-3.5" />
                        </span>
                        {point}
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>

            <div className="mt-5 bg-brand/5 border border-brand/15 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-3">
              <p className="text-gray-700 text-sm sm:text-base font-medium text-center sm:text-left">
                🚀 Working with clients across <span className="text-brand font-bold">15+ countries</span> worldwide
              </p>
              <Link to="/contact" className="text-brand font-semibold text-sm hover:underline whitespace-nowrap">
                Start Your Project →
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
