import { Link, useParams, Navigate } from 'react-router-dom'
import PageBanner from '../components/PageBanner'
import SEO, { localBusinessSchema, breadcrumbSchema } from '../components/SEO'
import RelatedPages from '../components/RelatedPages'
import { getLocationBySlug, locationSlug } from '../data/locations'
import { COMPANY } from '../data/company'
import { CheckIcon, ArrowRightIcon } from '../components/Icons'

export default function LocationPage() {
  const { slug } = useParams()
  const page = getLocationBySlug(slug)

  if (!page) {
    return <Navigate to="/" replace />
  }

  const path = `/${page.slug}`
  const relatedPages = page.relatedLocations.map((loc) => ({
    label: `Web Design & Development Services in ${loc}`,
    href: `/${locationSlug(loc)}`,
    description: `Professional website development company serving ${loc}.`,
  }))

  const relatedServicePages = [
    { label: 'Our Services', href: '/services', description: 'Explore all web design and development services.' },
    { label: 'Contact Us', href: '/contact', description: 'Get a free quote for your project.' },
    { label: 'About Our Company', href: '/about', description: `Learn about ${COMPANY.name}.` },
  ]

  return (
    <>
      <SEO
        title={page.title}
        description={page.description}
        keywords={page.keywords}
        path={path}
        image={page.heroImage}
        schema={[
          localBusinessSchema(page.location, path),
          breadcrumbSchema([
            { name: 'Home', url: '/' },
            { name: page.title, url: path },
          ]),
        ]}
      />

      <PageBanner
        title={page.title}
        subtitle={page.intro}
        breadcrumbs={[{ label: page.title }]}
      />

      <section className="py-10 sm:py-14">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-3 gap-10">
            <div className="lg:col-span-2 space-y-10">
              <div className="rounded-2xl overflow-hidden shadow-lg">
                <img
                  src={page.heroImage}
                  alt={`${page.title} - ${COMPANY.name}`}
                  className="w-full h-56 sm:h-72 object-cover"
                  loading="lazy"
                />
              </div>

              {page.sections.map((section) => (
                <article key={section.heading}>
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-3">{section.heading}</h2>
                  <p className="text-gray-600 leading-relaxed mb-4">{section.content}</p>
                  <ul className="space-y-2">
                    {section.bullets.map((bullet) => (
                      <li key={bullet} className="flex items-start gap-2 text-sm text-gray-700">
                        <CheckIcon className="w-4 h-4 text-brand mt-0.5 flex-shrink-0" />
                        {bullet}
                      </li>
                    ))}
                  </ul>
                </article>
              ))}
            </div>

            <aside className="space-y-6">
              <div className="bg-brand/5 border border-brand/15 rounded-2xl p-6">
                <h3 className="font-bold text-gray-900 mb-4">Services in {page.location}</h3>
                <ul className="space-y-2">
                  {page.services.map((service) => (
                    <li key={service} className="flex items-center gap-2 text-sm text-gray-700">
                      <CheckIcon className="w-3.5 h-3.5 text-brand" />
                      {service}
                    </li>
                  ))}
                </ul>
              </div>

              <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                <h3 className="font-bold text-gray-900 mb-2">Get Started Today</h3>
                <p className="text-gray-600 text-sm mb-4">{page.cta}</p>
                <Link
                  to="/contact"
                  className="btn-primary inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-semibold px-5 py-3 rounded-md w-full justify-center text-sm"
                >
                  Request Free Quote
                  <ArrowRightIcon className="w-4 h-4" />
                </Link>
              </div>

              <div className="bg-gray-50 rounded-2xl p-6">
                <h3 className="font-bold text-gray-900 mb-3">Contact {COMPANY.name}</h3>
                <ul className="space-y-2 text-sm text-gray-700">
                  <li>
                    <a href={COMPANY.phoneHref} className="hover:text-brand">{COMPANY.phone}</a>
                  </li>
                  <li>
                    <a href={`mailto:${COMPANY.email}`} className="hover:text-brand">{COMPANY.email}</a>
                  </li>
                  <li>{COMPANY.address}</li>
                </ul>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <RelatedPages title={`Web Design Services in Nearby Areas`} pages={relatedPages} />
      <RelatedPages title="Explore More" pages={relatedServicePages} />
    </>
  )
}
