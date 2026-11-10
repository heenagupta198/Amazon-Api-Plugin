import { Link, useParams, Navigate } from 'react-router-dom'
import PageBanner from '../components/PageBanner'
import SEO, { breadcrumbSchema } from '../components/SEO'
import RelatedPages from '../components/RelatedPages'
import { blogPosts } from '../data/pagesData'
import { ArrowRightIcon } from '../components/Icons'

const blogContent = {
  'why-wordpress-best-cms-2026': {
    content: `WordPress continues to dominate the CMS market in 2026, powering over 43% of all websites globally. For businesses in Delhi NCR and worldwide, WordPress offers the perfect balance of flexibility, ease of use and SEO capability.

At Yogesh Web Developer, we build custom WordPress websites that are fast, secure and optimized for Google. Whether you need a corporate site, blog or WooCommerce store, WordPress provides a robust foundation with thousands of plugins and themes.

Key advantages include easy content management, strong SEO plugins like Yoast, mobile-responsive themes and a massive developer community. For most small to medium businesses, WordPress remains the smartest choice for web development.`,
  },
  'react-vs-wordpress-comparison': {
    content: `Choosing between React and WordPress depends on your project goals. WordPress excels at content-heavy sites, blogs and e-commerce with quick deployment. React shines for interactive web applications, dashboards and headless CMS frontends.

Our team at Yogesh Web Developer often recommends a hybrid approach: WordPress as a headless CMS with a React frontend for maximum performance and flexibility. For simple business websites, WordPress alone is often sufficient and more cost-effective.

Consider React when you need complex user interactions, real-time data or a single-page application experience. Consider WordPress when content editors need easy access and you want faster time-to-market.`,
  },
  'seo-tips-web-development': {
    content: `SEO is not optional — it's essential for every website we build. Here are key practices our development team follows: optimize page speed with lazy loading and compressed images, use semantic HTML with proper heading hierarchy, implement structured data (Schema.org), create unique meta titles and descriptions for every page, and build mobile-first responsive designs.

Local businesses in Delhi NCR should also optimize Google Business Profile, include location-specific landing pages and earn local backlinks. Technical SEO including XML sitemaps, robots.txt and canonical URLs ensures Google indexes all your pages and images correctly.`,
  },
  'building-ai-chatbots-rag': {
    content: `RAG (Retrieval-Augmented Generation) chatbots are transforming customer support for businesses. Unlike basic chatbots, RAG systems pull answers from your company's knowledge base, product docs and FAQs to provide accurate, contextual responses.

Yogesh Web Developer builds custom AI chatbots integrated with your website, CRM and support systems. Benefits include 24/7 customer support, reduced support costs, faster response times and improved customer satisfaction. We use modern AI APIs with secure data handling and custom training on your business content.`,
  },
  'laravel-best-practices': {
    content: `Laravel remains one of the best PHP frameworks for building scalable web applications. Our development team follows key best practices: use Eloquent ORM with proper relationships, implement form requests for validation, follow repository patterns for complex apps, use Laravel queues for background jobs and always write tests for critical business logic.

Security is paramount — we use Laravel's built-in CSRF protection, authentication scaffolding, and environment-based configuration. For API development, Laravel Sanctum or Passport provides robust token-based authentication.`,
  },
  'hire-web-development-company-delhi': {
    content: `Hiring the right web development company in Delhi can make or break your online presence. Look for a company with proven portfolio, clear communication, SEO expertise and post-launch support. Yogesh Web Developer brings 14+ years of experience serving businesses across Delhi NCR.

Ask potential partners about their development process, timeline estimates, maintenance plans and SEO approach. Avoid companies that promise instant results or use outdated templates without customization. A good web development partner understands your business goals and builds solutions that drive real growth.`,
  },
}

export default function BlogPost() {
  const { slug } = useParams()
  const post = blogPosts.find((p) => p.slug === slug)

  if (!post) return <Navigate to="/blog" replace />

  const content = blogContent[slug] || post.excerpt
  const relatedPosts = blogPosts
    .filter((p) => p.slug !== slug)
    .slice(0, 3)
    .map((p) => ({
      label: p.title,
      href: `/blog/${p.slug}`,
      description: p.excerpt,
    }))

  return (
    <>
      <SEO
        title={post.title}
        description={post.excerpt}
        path={`/blog/${post.slug}`}
        image={post.image}
        type="article"
        schema={breadcrumbSchema([
          { name: 'Home', url: '/' },
          { name: 'Blog', url: '/blog' },
          { name: post.title, url: `/blog/${post.slug}` },
        ])}
      />

      <PageBanner
        title={post.title}
        subtitle={`${post.date} • ${post.readTime}`}
        breadcrumbs={[{ label: 'Blog', href: '/blog' }, { label: post.title }]}
      />

      <article className="py-14 sm:py-18">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="rounded-2xl overflow-hidden mb-8">
            <img src={post.image} alt={post.title} className="w-full h-64 sm:h-80 object-cover" />
          </div>
          <span className="inline-block bg-brand/10 text-brand text-xs font-bold px-3 py-1 rounded-full mb-4">
            {post.category}
          </span>
          <div className="prose prose-gray max-w-none">
            {content.split('\n\n').map((para) => (
              <p key={para.slice(0, 40)} className="text-gray-600 leading-relaxed mb-4">{para}</p>
            ))}
          </div>
          <div className="mt-8 pt-6 border-t border-gray-100">
            <Link to="/contact" className="inline-flex items-center gap-2 text-brand font-semibold hover:gap-3 transition-all">
              Need help with your project? Contact us
              <ArrowRightIcon className="w-4 h-4" />
            </Link>
          </div>
        </div>
      </article>

      <RelatedPages title="Related Articles" pages={relatedPosts} />
    </>
  )
}
