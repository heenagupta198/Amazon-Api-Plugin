export const SITE_URL = 'https://yogeshwebdeveloper.com'

export const COMPANY = {
  name: 'Yogesh Web Developer',
  brand: 'yogeshwebdeveloper',
  domain: 'yogeshwebdeveloper.com',
  tagline: 'Web Design & Development Company',
  email: 'contact@yogeshwebdeveloper.com',
  ownerEmail: 'ygupta13@gmail.com',
  phone: '+91 83779 56442',
  phoneHref: 'tel:+918377956442',
  whatsapp: 'https://wa.me/918377956442',
  address: 'Saket, New Delhi, India',
  foundedYear: 2010,
  experience: '14+',
  defaultDescription:
    'Yogesh Web Developer (yogeshwebdeveloper.com) is a leading web design and development company in Delhi NCR. We build fast, SEO-friendly websites, React apps, WordPress sites, Laravel applications and e-commerce stores for businesses across India and worldwide.',
  defaultKeywords:
    'yogesh web developer, yogeshwebdeveloper, web design company delhi, web development company saket, website development delhi ncr, react development company, wordpress development delhi, seo company delhi',
}

export const SOCIAL_LINKS = [
  { name: 'google', label: 'Google Business', href: '#' },
  { name: 'facebook', label: 'Facebook', href: '#' },
  { name: 'instagram', label: 'Instagram', href: '#' },
  { name: 'twitter', label: 'Twitter / X', href: '#' },
  { name: 'linkedin', label: 'LinkedIn', href: '#' },
  { name: 'youtube', label: 'YouTube', href: '#' },
  { name: 'whatsapp', label: 'WhatsApp', href: COMPANY.whatsapp },
  { name: 'upwork', label: 'Upwork', href: 'https://www.upwork.com/freelancers/~01bba1b5cc95c508c4?mp_source=share' },
]

export const UPWORK_URL = SOCIAL_LINKS.find((s) => s.name === 'upwork').href
