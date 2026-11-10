import { Link } from 'react-router-dom'
import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { seoLocationLinks } from '../data/locations'
import { blogPosts } from '../data/pagesData'
import { COMPANY, SITE_URL } from '../data/company'

const staticPages = [
  { label: 'Home', href: '/' },
  { label: 'About Us', href: '/about' },
  { label: 'Services', href: '/services' },
  { label: 'Work / Portfolio', href: '/work' },
  { label: 'Testimonials', href: '/testimonials' },
  { label: 'Blog', href: '/blog' },
  { label: 'Contact', href: '/contact' },
  { label: 'Privacy Policy', href: '/privacy-policy' },
  { label: 'Terms & Conditions', href: '/terms-and-conditions' },
]

export default function SitemapPage() {
  return (
    <>
      <SEO
        title="Sitemap"
        description={`Complete sitemap of ${COMPANY.name} — all pages, services, blog posts and location-wise web design pages.`}
        path="/sitemap"
      />

      <PageBanner
        title="Sitemap"
        subtitle="Browse all pages on our website for easy navigation and Google indexing."
        breadcrumbs={[{ label: 'Sitemap' }]}
      />

      <section className="py-14 sm:py-18">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div>
            <h2 className="text-xl font-bold text-gray-900 mb-4">Main Pages</h2>
            <ul className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
              {staticPages.map((page) => (
                <li key={page.href}>
                  <Link to={page.href} className="text-brand hover:underline text-sm">
                    {page.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h2 className="text-xl font-bold text-gray-900 mb-4">Blog Posts</h2>
            <ul className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
              {blogPosts.map((post) => (
                <li key={post.slug}>
                  <Link to={`/blog/${post.slug}`} className="text-brand hover:underline text-sm">
                    {post.title}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              Web Design & Development Services by Location
            </h2>
            <ul className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
              {seoLocationLinks.map((link) => (
                <li key={link.href}>
                  <Link to={link.href} className="text-brand hover:underline text-sm">
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div className="bg-gray-50 rounded-xl p-6">
            <p className="text-sm text-gray-600">
              XML Sitemap for search engines:{' '}
              <a href={`${SITE_URL}/sitemap.xml`} className="text-brand hover:underline">
                {SITE_URL}/sitemap.xml
              </a>
            </p>
          </div>
        </div>
      </section>
    </>
  )
}
