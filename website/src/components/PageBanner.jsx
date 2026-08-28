import { Link } from 'react-router-dom'

export default function PageBanner({ title, subtitle, breadcrumbs = [] }) {
  return (
    <section className="relative bg-gradient-to-br from-brand via-brand-dark to-brand-footer text-white py-14 sm:py-18 lg:py-20 overflow-hidden">
      <div className="absolute inset-0 opacity-10"
        style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
        }}
      />
      <div className="absolute top-0 right-0 w-80 h-80 bg-brand-light/10 rounded-full blur-3xl translate-x-1/3 -translate-y-1/2" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        {breadcrumbs.length > 0 && (
          <nav className="flex items-center gap-2 text-sm text-white/70 mb-4">
            <Link to="/" className="hover:text-white transition-colors">Home</Link>
            {breadcrumbs.map((crumb) => (
              <span key={crumb.label} className="flex items-center gap-2">
                <span>/</span>
                {crumb.href ? (
                  <Link to={crumb.href} className="hover:text-white transition-colors">{crumb.label}</Link>
                ) : (
                  <span className="text-white">{crumb.label}</span>
                )}
              </span>
            ))}
          </nav>
        )}

        <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 animate-fade-up">{title}</h1>
        {subtitle && (
          <p className="text-white/80 text-base sm:text-lg max-w-2xl animate-fade-up animate-delay-100">{subtitle}</p>
        )}
      </div>
    </section>
  )
}
