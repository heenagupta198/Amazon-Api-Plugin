import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { testimonials } from '../data/pagesData'
import { COMPANY } from '../data/siteData'
import { CheckIcon } from '../components/Icons'

function StarRating({ rating }) {
  return (
    <div className="flex gap-0.5">
      {Array.from({ length: rating }).map((_, i) => (
        <span key={i} className="text-yellow-400 text-sm">★</span>
      ))}
    </div>
  )
}

export default function Testimonials() {
  return (
    <>
      <SEO
        title="Client Testimonials"
        description={`Read what our clients say about ${COMPANY.name}. Trusted by 150+ businesses across India, USA, UK, Canada and Australia.`}
        path="/testimonials"
      />

      <PageBanner
        title="Client Testimonials"
        subtitle="What our clients say about working with us."
        breadcrumbs={[{ label: 'Testimonials' }]}
      />

      <section className="py-14 sm:py-18 lg:py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            {testimonials.map((review) => (
              <article
                key={review.id}
                className="bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 shadow-sm hover:shadow-lg transition-shadow"
              >
                <StarRating rating={review.rating} />
                <p className="text-gray-600 text-sm leading-relaxed my-4 italic">"{review.text}"</p>
                <div className="flex items-center gap-3 pt-4 border-t border-gray-100">
                  <img
                    src={review.image}
                    alt={review.name}
                    className="w-12 h-12 rounded-full object-cover"
                    loading="lazy"
                  />
                  <div>
                    <p className="font-bold text-gray-900 text-sm">{review.name}</p>
                    <p className="text-gray-500 text-xs">{review.role}</p>
                    <p className="text-brand text-xs font-medium flex items-center gap-1 mt-0.5">
                      <CheckIcon className="w-3 h-3" />
                      {review.country}
                    </p>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </>
  )
}
