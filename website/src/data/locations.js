import { cities } from './siteData'

export function locationSlug(location) {
  return `web-design-development-services-in-${location
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')}`
}

function buildLocationContent(location) {
  const slug = locationSlug(location)
  const title = `Web Design & Development Services in ${location}`
  const description = `Looking for a trusted web design and development company in ${location}? Yogesh Web Developer (yogeshwebdeveloper.com) delivers responsive websites, e-commerce stores, WordPress, React and Laravel applications with SEO optimization.`
  const nearby = cities.filter((c) => c !== location).slice(0, 5)

  return {
    slug,
    location,
    title,
    metaTitle: `${title} | Yogesh Web Developer`,
    description,
    keywords: `yogesh web developer ${location.toLowerCase()}, yogeshwebdeveloper, web design ${location.toLowerCase()}, web development ${location.toLowerCase()}, website company ${location.toLowerCase()}, seo services ${location.toLowerCase()}`,
    heroImage: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=600&fit=crop',
    intro: `Yogesh Web Developer (yogeshwebdeveloper.com) is a professional web design and development company serving businesses in ${location} and across Delhi NCR. With 14+ years of experience, we help startups, SMEs and enterprises build high-performance websites that rank on Google and convert visitors into customers.`,
    sections: [
      {
        heading: `Why Choose Us for Web Development in ${location}?`,
        content: `Businesses in ${location} need websites that load fast, look professional on every device and appear in local Google searches. Our team combines modern React and WordPress development with proven SEO strategies so your brand stands out in ${location}'s competitive market.`,
        bullets: [
          'Custom website design tailored to your brand',
          'Mobile-responsive and fast-loading pages',
          'Local SEO optimization for Google Maps & search',
          'E-commerce, WordPress, React & Laravel expertise',
          'Dedicated support and maintenance plans',
        ],
      },
      {
        heading: `Our Web Services in ${location}`,
        content: `From corporate websites to online stores and custom web applications, we offer end-to-end web design and development services for clients in ${location}. Every project includes clean code, security best practices and performance optimization.`,
        bullets: [
          'Business & corporate website development',
          'WordPress & WooCommerce e-commerce stores',
          'React.js & Next.js web applications',
          'Laravel backend & API development',
          'SEO, speed optimization & Google indexing',
        ],
      },
      {
        heading: `Serving ${location} & Nearby Areas`,
        content: `We work with clients across ${location} and surrounding neighbourhoods in Delhi NCR. Whether you run a local shop, coaching institute, real estate agency or tech startup, Yogesh Web Developer delivers websites that grow your business online.`,
        bullets: nearby.map((area) => `Also serving ${area}`),
      },
    ],
    services: [
      'Custom Website Development',
      'WordPress Development',
      'E-commerce Development',
      'React.js Development',
      'SEO Optimization',
      'Website Maintenance',
    ],
    relatedLocations: nearby,
    cta: `Get a free consultation for your ${location} business website today.`,
  }
}

export const locationPages = cities.map(buildLocationContent)

export function getLocationBySlug(slug) {
  return locationPages.find((page) => page.slug === slug) || null
}

export const seoLocationLinks = locationPages.map((page) => ({
  label: page.title,
  href: `/${page.slug}`,
  location: page.location,
}))
