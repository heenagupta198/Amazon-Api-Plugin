import { services } from '../data/siteData'
import { ServiceIcon } from './Icons'

export default function Services() {
  return (
    <section className="bg-gray-50 py-12 sm:py-16 lg:py-20">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-10 sm:mb-12">
          <span className="text-brand font-semibold text-sm uppercase tracking-wider">What We Do</span>
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">Our Web Development Services</h2>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-6">
          {services.map((service) => (
            <article
              key={service.title}
              className="service-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100 group"
            >
              <div className="w-12 h-12 rounded-xl bg-brand/5 flex items-center justify-center mb-4 group-hover:bg-brand/10 group-hover:scale-110 transition-all">
                <ServiceIcon name={service.icon} />
              </div>
              <h3 className="font-bold text-gray-900 text-base mb-2 group-hover:text-brand transition-colors">
                {service.title}
              </h3>
              <p className="text-gray-600 text-sm leading-relaxed">{service.description}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
