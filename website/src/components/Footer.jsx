import { Link } from 'react-router-dom'
import { seoLocationLinks } from '../data/locations'
import {
  footerServices,
  footerServiceAreas,
  footerUsefulLinks,
  UPWORK_URL,
  COMPANY,
} from '../data/siteData'
import { CheckIcon, SocialIcon } from './Icons'

const socialLinks = [
  { name: 'upwork', href: UPWORK_URL },
  { name: 'linkedin', href: '#' },
  { name: 'twitter', href: '#' },
  { name: 'instagram', href: '#' },
  { name: 'whatsapp', href: COMPANY.whatsapp },
]

export default function Footer() {
  return (
    <footer className="bg-brand-footer text-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-10">
          <div className="sm:col-span-2 lg:col-span-1">
            <h3 className="font-bold text-lg mb-4">{COMPANY.name}</h3>
            <p className="text-white/75 text-sm leading-relaxed mb-5">
              {COMPANY.name} is a professional web design and development company with {COMPANY.experience} years
              of experience building websites, WordPress solutions, Laravel apps and modern React applications
              for businesses across India and worldwide.
            </p>
            <div className="flex gap-2">
              {socialLinks.map((social) => (
                <a
                  key={social.name}
                  href={social.href}
                  target={social.href.startsWith('http') ? '_blank' : undefined}
                  rel={social.href.startsWith('http') ? 'noopener noreferrer' : undefined}
                  className="w-9 h-9 rounded-full bg-white/10 hover:bg-brand-light hover:text-brand-dark flex items-center justify-center transition-colors"
                  aria-label={social.name}
                >
                  <SocialIcon name={social.name} />
                </a>
              ))}
            </div>
          </div>

          <div>
            <h3 className="font-bold text-base mb-4">Services</h3>
            <ul className="space-y-2">
              {footerServices.map((item) => (
                <li key={item.label}>
                  <Link to={item.href} className="text-white/70 hover:text-brand-light text-sm transition-colors">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-base mb-4">Service Areas</h3>
            <ul className="space-y-2">
              {footerServiceAreas.map((item) => (
                <li key={item.label}>
                  <Link to={item.href} className="text-white/70 hover:text-brand-light text-sm transition-colors">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-base mb-4">Useful Links</h3>
            <ul className="space-y-2">
              {footerUsefulLinks.map((item) => (
                <li key={item.label}>
                  <Link to={item.href} className="text-white/70 hover:text-brand-light text-sm transition-colors">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-base mb-4">Contact Info</h3>
            <ul className="space-y-3 text-sm text-white/75">
              <li>
                <a href={COMPANY.phoneHref} className="hover:text-brand-light transition-colors">
                  {COMPANY.phone}
                </a>
              </li>
              <li>
                <a href={`mailto:${COMPANY.email}`} className="hover:text-brand-light transition-colors">
                  {COMPANY.email}
                </a>
              </li>
              <li>
                <a
                  href={UPWORK_URL}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-brand-light transition-colors"
                >
                  View on Upwork
                </a>
              </li>
              <li>{COMPANY.address}</li>
            </ul>

            <div className="mt-5 bg-brand-light text-brand-dark rounded-lg p-4">
              <p className="font-bold text-sm mb-2">Available for new projects</p>
              <ul className="space-y-1">
                {['Quick Response', 'Free Consultation', 'Flexible Plans'].map((item) => (
                  <li key={item} className="flex items-center gap-2 text-xs font-medium">
                    <CheckIcon className="w-3.5 h-3.5" />
                    {item}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        <div className="mt-12 pt-8 border-t border-white/10">
          <h3 className="font-bold text-base mb-5 text-center sm:text-left">
            Web Design & Development Services Near You
          </h3>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-2">
            {seoLocationLinks.map((link) => (
              <Link
                key={link.href}
                to={link.href}
                className="text-white/50 hover:text-brand-light text-xs transition-colors"
              >
                {link.label}
              </Link>
            ))}
          </div>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-white/60">
          <p>© {new Date().getFullYear()} {COMPANY.name}. All Rights Reserved.</p>
          <div className="flex gap-4">
            <Link to="/privacy-policy" className="hover:text-white transition-colors">Privacy Policy</Link>
            <span>|</span>
            <Link to="/terms-and-conditions" className="hover:text-white transition-colors">Terms & Conditions</Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
