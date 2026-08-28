import { Link, useLocation } from 'react-router-dom'
import { useState } from 'react'
import { navLinks, COMPANY } from '../data/siteData'
import { MenuIcon, CloseIcon, SendIcon } from './Icons'

export default function Header() {
  const [menuOpen, setMenuOpen] = useState(false)
  const { pathname } = useLocation()

  const isActive = (href) => {
    if (href === '/') return pathname === '/'
    return pathname.startsWith(href)
  }

  return (
    <header className="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16 lg:h-20">
          <Link to="/" className="flex-shrink-0">
            <div className="text-brand font-bold text-lg sm:text-xl leading-tight">
              {COMPANY.name}
            </div>
            <div className="text-gray-500 text-xs sm:text-sm">{COMPANY.domain}</div>
          </Link>

          <nav className="hidden lg:flex items-center gap-6 xl:gap-8">
            {navLinks.map((link) => (
              <Link
                key={link.label}
                to={link.href}
                className={`text-sm font-medium transition-colors hover:text-brand ${
                  isActive(link.href)
                    ? 'text-brand border-b-2 border-brand pb-0.5'
                    : 'text-gray-700'
                }`}
              >
                {link.label}
              </Link>
            ))}
          </nav>

          <Link
            to="/contact"
            className="hidden lg:inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-5 py-2.5 rounded transition-colors"
          >
            Get Free Quote
            <SendIcon className="w-3.5 h-3.5" />
          </Link>

          <button
            type="button"
            className="lg:hidden p-2 text-gray-700 hover:text-brand"
            onClick={() => setMenuOpen(!menuOpen)}
            aria-label={menuOpen ? 'Close menu' : 'Open menu'}
          >
            {menuOpen ? <CloseIcon /> : <MenuIcon />}
          </button>
        </div>
      </div>

      {menuOpen && (
        <div className="lg:hidden border-t border-gray-100 bg-white">
          <nav className="flex flex-col px-4 py-4 gap-1">
            {navLinks.map((link) => (
              <Link
                key={link.label}
                to={link.href}
                className={`font-medium py-3 px-3 rounded-lg transition-colors ${
                  isActive(link.href)
                    ? 'text-brand bg-green-50'
                    : 'text-gray-700 hover:text-brand hover:bg-green-50'
                }`}
                onClick={() => setMenuOpen(false)}
              >
                {link.label}
              </Link>
            ))}
            <Link
              to="/contact"
              className="mt-2 inline-flex items-center justify-center gap-2 bg-brand text-white font-semibold py-3 px-4 rounded-lg"
              onClick={() => setMenuOpen(false)}
            >
              Get Free Quote
              <SendIcon className="w-3.5 h-3.5" />
            </Link>
          </nav>
        </div>
      )}
    </header>
  )
}
