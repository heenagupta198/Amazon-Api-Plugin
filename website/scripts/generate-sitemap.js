import { writeFileSync } from 'fs'
import { SITE_URL } from '../src/data/company.js'

const cities = [
  'Delhi NCR', 'Noida', 'Gurgaon', 'Faridabad', 'Ghaziabad', 'Greater Noida',
  'Saket', 'Dwarka', 'Rohini', 'Janakpuri', 'Pitampura', 'Laxmi Nagar',
  'Connaught Place', 'Karol Bagh', 'South Delhi', 'East Delhi', 'West Delhi',
  'North Delhi', 'Nehru Place', 'Okhla', 'Vasant Kunj', 'Hauz Khas',
  'Defence Colony', 'Rajouri Garden', 'Mayur Vihar', 'Indirapuram',
  'Vaishali', 'Kaushambi', 'Noida Sector 62', 'Noida Sector 18',
]

const blogPosts = [
  'why-wordpress-best-cms-2026',
  'react-vs-wordpress-comparison',
  'seo-tips-web-development',
  'building-ai-chatbots-rag',
  'laravel-best-practices',
  'hire-web-development-company-delhi',
]

function locationSlug(location) {
  return `web-design-development-services-in-${location
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')}`
}

const staticPages = [
  { path: '/', priority: '1.0', changefreq: 'weekly' },
  { path: '/about', priority: '0.8', changefreq: 'monthly' },
  { path: '/services', priority: '0.9', changefreq: 'monthly' },
  { path: '/work', priority: '0.8', changefreq: 'monthly' },
  { path: '/testimonials', priority: '0.7', changefreq: 'monthly' },
  { path: '/blog', priority: '0.8', changefreq: 'weekly' },
  { path: '/contact', priority: '0.9', changefreq: 'monthly' },
  { path: '/privacy-policy', priority: '0.3', changefreq: 'yearly' },
  { path: '/terms-and-conditions', priority: '0.3', changefreq: 'yearly' },
  { path: '/sitemap', priority: '0.4', changefreq: 'monthly' },
]

const today = new Date().toISOString().split('T')[0]

const urls = [
  ...staticPages,
  ...blogPosts.map((slug) => ({ path: `/blog/${slug}`, priority: '0.7', changefreq: 'monthly' })),
  ...cities.map((city) => ({
    path: `/${locationSlug(city)}`,
    priority: '0.8',
    changefreq: 'monthly',
  })),
]

const images = [
  'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&h=600&fit=crop',
  'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=600&fit=crop',
  'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=500&fit=crop&crop=face',
]

const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
${urls.map(({ path, priority, changefreq }) => `  <url>
    <loc>${SITE_URL}${path}</loc>
    <lastmod>${today}</lastmod>
    <changefreq>${changefreq}</changefreq>
    <priority>${priority}</priority>
${path === '/' ? images.map((img) => `    <image:image>
      <image:loc>${img}</image:loc>
      <image:title>Yogesh Web Solutions - Web Design Company</image:title>
    </image:image>`).join('\n') : ''}
  </url>`).join('\n')}
</urlset>
`

writeFileSync('public/sitemap.xml', xml)
console.log(`Generated sitemap.xml with ${urls.length} URLs`)
