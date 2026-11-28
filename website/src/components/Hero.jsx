import { Link } from 'react-router-dom'
import {
  heroTrustItems,
  whyChooseUs,
  COMPANY,
} from '../data/siteData'
import { CheckIcon, ArrowRightIcon, EyeIcon } from './Icons'

const hero = {
  badgeText: `${COMPANY.experience} Years of Excellence`,
  headline: 'Best Web Design & Development Company',
  headlineHighlight: 'in Delhi NCR',
  subtitle: 'We build fast, secure and SEO-friendly websites that help businesses grow online. Trusted by 150+ clients across India and worldwide.',
  ctaPrimary: 'Get Free Consultation',
  ctaSecondary: 'View Our Work',
  yearsBadge: `${COMPANY.experience} Years Experience`,
  projectsCount: '250+',
  clientsCount: '150+',
  image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=500&h=500&fit=crop',
}

export default function Hero() {
  return (
    <section id="home" className="relative overflow-hidden bg-white py-10 sm:py-12 lg:py-16">
      <div className="absolute top-0 right-0 w-96 h-96 bg-brand/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-72 h-72 bg-brand-light/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/3 pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-6 xl:gap-8 items-center">
          <div className="lg:col-span-5 xl:col-span-5 order-1">
            <span className="animate-fade-up inline-flex items-center gap-2 bg-gray-100 text-gray-600 text-xs sm:text-sm font-bold tracking-widest uppercase px-4 py-2 rounded-full mb-5 border border-gray-200">
              <span className="w-2 h-2 rounded-full bg-brand-light animate-pulse" />
              {hero.badgeText}
            </span>

            <h1 className="animate-fade-up animate-delay-100 text-3xl sm:text-4xl lg:text-[2.6rem] xl:text-5xl font-bold text-gray-900 leading-[1.15] mb-4">
              {hero.headline}{' '}
              <span className="text-brand">{hero.headlineHighlight}</span>
            </h1>

            <p className="animate-fade-up animate-delay-200 text-gray-600 text-base sm:text-lg leading-relaxed mb-7 max-w-lg">
              {hero.subtitle}
            </p>

            <div className="animate-fade-up animate-delay-300 flex flex-col sm:flex-row gap-3 sm:gap-4 mb-8">
              <Link
                to="/contact"
                className="btn-primary inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white font-semibold px-6 py-3.5 rounded-md"
              >
                {hero.ctaPrimary}
                <ArrowRightIcon className="btn-arrow w-4 h-4" />
              </Link>
              <Link
                to="/work"
                className="inline-flex items-center justify-center gap-2 border-2 border-gray-800 text-gray-900 hover:bg-gray-900 hover:text-white font-semibold px-6 py-3.5 rounded-md transition-all duration-300 hover:-translate-y-0.5"
              >
                <EyeIcon className="w-4 h-4" />
                {hero.ctaSecondary}
              </Link>
            </div>

            <div className="animate-fade-up animate-delay-400 flex flex-wrap gap-x-5 gap-y-3">
              {heroTrustItems.map((item) => (
                <div key={item} className="flex items-center gap-2 group">
                  <span className="flex-shrink-0 w-5 h-5 rounded-full bg-brand text-white flex items-center justify-center group-hover:scale-110 transition-transform">
                    <CheckIcon className="w-3 h-3" />
                  </span>
                  <span className="text-xs sm:text-sm text-gray-700 font-medium">{item}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="lg:col-span-4 xl:col-span-4 order-2 flex justify-center">
            <div className="animate-fade-in animate-delay-300 relative">
              <div className="absolute -top-4 -left-4 w-20 h-20 bg-brand-light/20 rounded-full blur-sm" />
              <div className="absolute -bottom-6 -right-6 w-28 h-28 bg-brand/10 rounded-full blur-md" />

              <div className="hero-ring relative animate-float">
                <div className="relative w-56 h-56 sm:w-64 sm:h-64 lg:w-72 lg:h-72 xl:w-80 xl:h-80 rounded-full overflow-hidden border-[5px] border-brand shadow-2xl shadow-brand/20">
                  <img
                    src={hero.image}
                    alt={`${COMPANY.name} - Web Design & Development Company`}
                    className="w-full h-full object-cover"
                  />
                </div>
                <div className="absolute inset-0 rounded-full border-2 border-brand-light/40 animate-ping opacity-20" style={{ animationDuration: '3s' }} />
              </div>

              <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-brand text-white text-sm font-bold px-5 py-2 rounded-full shadow-lg whitespace-nowrap">
                {hero.yearsBadge}
              </div>

              <div className="absolute top-8 -right-2 bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-md animate-fade-up animate-delay-500 hidden sm:block">
                <div className="text-brand font-bold text-lg leading-none">{hero.projectsCount}</div>
                <div className="text-gray-500 text-[10px] font-medium">Projects Done</div>
              </div>

              <div className="absolute bottom-16 -left-4 bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-md animate-fade-up animate-delay-500 hidden sm:block">
                <div className="text-brand font-bold text-lg leading-none">{hero.clientsCount}</div>
                <div className="text-gray-500 text-[10px] font-medium">Happy Clients</div>
              </div>
            </div>
          </div>

          <div className="lg:col-span-3 xl:col-span-3 order-3">
            <div className="animate-fade-up animate-delay-400 bg-white rounded-2xl p-5 sm:p-6 border-2 border-brand/15 shadow-lg shadow-brand/5 hover:shadow-xl hover:border-brand/25 transition-all duration-300">
              <h3 className="font-bold text-gray-900 text-base sm:text-lg mb-1 pb-3 border-b border-gray-100">
                Why Clients Choose Us?
              </h3>
              <ul className="space-y-3 mt-4">
                {whyChooseUs.map((item) => (
                  <li key={item} className="flex items-start gap-2.5 text-sm text-gray-700 group">
                    <span className="flex-shrink-0 w-5 h-5 rounded-full bg-brand text-white flex items-center justify-center mt-0.5 group-hover:scale-110 group-hover:bg-brand-light group-hover:text-brand-dark transition-all">
                      <CheckIcon className="w-3 h-3" />
                    </span>
                    <span className="group-hover:text-brand transition-colors">{item}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
