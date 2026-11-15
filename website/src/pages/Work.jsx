import { useState } from 'react'
import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { projects } from '../data/pagesData'
import { COMPANY } from '../data/siteData'

const categories = ['All', ...new Set(projects.map((p) => p.category))]

export default function Work() {
  const [activeCategory, setActiveCategory] = useState('All')
  const filtered = activeCategory === 'All'
    ? projects
    : projects.filter((p) => p.category === activeCategory)

  return (
    <>
      <SEO
        title="Our Work"
        description={`View our portfolio of web design and development projects — WordPress, React, Laravel and e-commerce by ${COMPANY.name}.`}
        path="/work"
      />

      <PageBanner
        title="Our Work"
        subtitle="A showcase of websites and applications we've built for clients worldwide."
        breadcrumbs={[{ label: 'Work' }]}
      />

      <section className="py-14 sm:py-18 lg:py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-wrap justify-center gap-2 mb-10">
            {categories.map((cat) => (
              <button
                key={cat}
                type="button"
                onClick={() => setActiveCategory(cat)}
                className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                  activeCategory === cat
                    ? 'bg-brand text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-brand/10 hover:text-brand'
                }`}
              >
                {cat}
              </button>
            ))}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            {filtered.map((project) => (
              <article
                key={project.id}
                className="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-2"
              >
                <div className="relative overflow-hidden aspect-[16/10]">
                  <img
                    src={project.image}
                    alt={project.title}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    loading="lazy"
                  />
                  <span className="absolute top-3 left-3 bg-brand text-white text-xs font-bold px-3 py-1 rounded-full">
                    {project.category}
                  </span>
                </div>

                <div className="p-5 sm:p-6">
                  <h3 className="font-bold text-gray-900 text-lg mb-2 group-hover:text-brand transition-colors">
                    {project.title}
                  </h3>
                  <p className="text-gray-600 text-sm leading-relaxed mb-3">{project.description}</p>
                  <div className="flex flex-wrap gap-1.5">
                    {project.tech.map((t) => (
                      <span key={t} className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                        {t}
                      </span>
                    ))}
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
