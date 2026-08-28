import { Link } from 'react-router-dom'
import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { blogPosts } from '../data/pagesData'
import { COMPANY } from '../data/siteData'
import { ArrowRightIcon } from '../components/Icons'

export default function Blog() {
  return (
    <>
      <SEO
        title="Blog"
        description={`Web development tips, tutorials and insights from ${COMPANY.name} — WordPress, React, SEO, Laravel and more.`}
        path="/blog"
      />

      <PageBanner
        title="Blog"
        subtitle="Tips, tutorials and insights on web development, WordPress, React and more."
        breadcrumbs={[{ label: 'Blog' }]}
      />

      <section className="py-14 sm:py-18 lg:py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            {blogPosts.map((post) => (
              <article
                key={post.id}
                className="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-2"
              >
                <div className="relative overflow-hidden aspect-[16/10]">
                  <img
                    src={post.image}
                    alt={post.title}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    loading="lazy"
                  />
                  <span className="absolute top-3 left-3 bg-brand text-white text-xs font-bold px-3 py-1 rounded-full">
                    {post.category}
                  </span>
                </div>

                <div className="p-5 sm:p-6">
                  <div className="flex items-center gap-3 text-xs text-gray-500 mb-3">
                    <span>{post.date}</span>
                    <span>•</span>
                    <span>{post.readTime}</span>
                  </div>

                  <h3 className="font-bold text-gray-900 text-lg mb-2 leading-snug group-hover:text-brand transition-colors">
                    {post.title}
                  </h3>

                  <p className="text-gray-600 text-sm leading-relaxed mb-4">{post.excerpt}</p>

                  <Link
                    to={`/blog/${post.slug}`}
                    className="inline-flex items-center gap-1 text-brand text-sm font-semibold group-hover:gap-2 transition-all"
                  >
                    Read More
                    <ArrowRightIcon className="w-3.5 h-3.5" />
                  </Link>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </>
  )
}
