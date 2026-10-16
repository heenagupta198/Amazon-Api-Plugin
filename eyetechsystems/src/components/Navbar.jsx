import { useEffect, useState } from 'react'

const LINKS = [
  { href: '#home', label: 'Home' },
  { href: '#about', label: 'About' },
  { href: '#services', label: 'Services' },
  { href: '#contact', label: 'Contact' },
]

export default function Navbar() {
  const [open, setOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
  }, [open])

  return (
    <header className={`site-header ${scrolled ? 'site-header--scrolled' : ''}`}>
      <div className="container site-header__inner">
        <a href="#home" className="brand" onClick={() => setOpen(false)}>
          <img src="/images/founder.png" alt="Eye Tech Systems" className="brand__mark" />
          <span className="brand__text">
            <strong>Eye Tech Systems</strong>
            <small>eyetechsystems.com</small>
          </span>
        </a>

        <button
          type="button"
          className="nav-toggle"
          aria-expanded={open}
          aria-label="Toggle menu"
          onClick={() => setOpen((v) => !v)}
        >
          <span />
          <span />
          <span />
        </button>

        <nav className={`site-nav ${open ? 'site-nav--open' : ''}`} aria-label="Main">
          <ul>
            {LINKS.map((link) => (
              <li key={link.href}>
                <a href={link.href} onClick={() => setOpen(false)}>{link.label}</a>
              </li>
            ))}
          </ul>
          <a href="#contact" className="btn btn--primary btn--sm nav-cta" onClick={() => setOpen(false)}>
            Get a quote
          </a>
        </nav>
      </div>
    </header>
  )
}
