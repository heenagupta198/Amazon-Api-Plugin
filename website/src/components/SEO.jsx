import { useEffect } from 'react'
import { COMPANY, SITE_URL } from '../data/company'

export default function SEO({
  title,
  description,
  keywords,
  path = '',
  image,
  type = 'website',
  schema,
  noindex = false,
}) {
  const pageTitle = title ? `${title} | ${COMPANY.name}` : `${COMPANY.name} | ${COMPANY.tagline}`
  const pageDescription = description || COMPANY.defaultDescription
  const pageKeywords = keywords || COMPANY.defaultKeywords
  const canonical = `${SITE_URL}${path.startsWith('/') ? path : `/${path}`}`
  const ogImage = image || `${SITE_URL}/og-image.jpg`

  useEffect(() => {
    document.title = pageTitle

    const setMeta = (name, content, isProperty = false) => {
      const attr = isProperty ? 'property' : 'name'
      let el = document.querySelector(`meta[${attr}="${name}"]`)
      if (!el) {
        el = document.createElement('meta')
        el.setAttribute(attr, name)
        document.head.appendChild(el)
      }
      el.setAttribute('content', content)
    }

    setMeta('description', pageDescription)
    setMeta('keywords', pageKeywords)
    setMeta('robots', noindex ? 'noindex, nofollow' : 'index, follow')
    setMeta('author', COMPANY.name)
    setMeta('og:title', pageTitle, true)
    setMeta('og:description', pageDescription, true)
    setMeta('og:type', type, true)
    setMeta('og:url', canonical, true)
    setMeta('og:image', ogImage, true)
    setMeta('og:site_name', COMPANY.name, true)
    setMeta('twitter:card', 'summary_large_image')
    setMeta('twitter:title', pageTitle)
    setMeta('twitter:description', pageDescription)
    setMeta('twitter:image', ogImage)

    let link = document.querySelector('link[rel="canonical"]')
    if (!link) {
      link = document.createElement('link')
      link.setAttribute('rel', 'canonical')
      document.head.appendChild(link)
    }
    link.setAttribute('href', canonical)

    let schemaEl = document.getElementById('page-schema')
    if (schema) {
      if (!schemaEl) {
        schemaEl = document.createElement('script')
        schemaEl.id = 'page-schema'
        schemaEl.type = 'application/ld+json'
        document.head.appendChild(schemaEl)
      }
      const schemaData = Array.isArray(schema) ? schema : [schema]
      schemaEl.textContent = JSON.stringify(schemaData.length === 1 ? schemaData[0] : schemaData)
    } else if (schemaEl) {
      schemaEl.remove()
    }
  }, [pageTitle, pageDescription, pageKeywords, canonical, ogImage, type, schema, noindex])

  return null
}

export function localBusinessSchema(location, path) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ProfessionalService',
    name: `${COMPANY.name} - ${location}`,
    description: `Web design and development services in ${location}`,
    url: `${SITE_URL}${path}`,
    telephone: COMPANY.phone,
    email: COMPANY.email,
    address: {
      '@type': 'PostalAddress',
      addressLocality: location,
      addressRegion: 'Delhi',
      addressCountry: 'IN',
    },
    areaServed: location,
    priceRange: '₹₹',
    image: `${SITE_URL}/og-image.jpg`,
    sameAs: [COMPANY.whatsapp],
  }
}

export function organizationSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: COMPANY.name,
    url: SITE_URL,
    logo: `${SITE_URL}/favicon.svg`,
    description: COMPANY.defaultDescription,
    email: COMPANY.email,
    telephone: COMPANY.phone,
    address: {
      '@type': 'PostalAddress',
      addressLocality: 'Saket',
      addressRegion: 'Delhi',
      addressCountry: 'IN',
    },
    foundingDate: String(COMPANY.foundedYear),
    sameAs: [COMPANY.whatsapp],
  }
}

export function breadcrumbSchema(items) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url ? `${SITE_URL}${item.url}` : undefined,
    })),
  }
}
