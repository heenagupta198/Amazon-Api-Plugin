import { SERVICES } from '../data/services'

export default function Services() {
  return (
    <section id="services" className="section services">
      <div className="container">
        <div className="section-head">
          <p className="eyebrow">Products &amp; services</p>
          <h2>Complete IT &amp; infrastructure solutions</h2>
          <p>
            Professional sales, installation, and maintenance — tailored for offices, shops,
            schools, and residential projects across Delhi NCR.
          </p>
        </div>

        <div className="services-grid">
          {SERVICES.map((service) => (
            <article key={service.title} className="service-card">
              <div className="service-card__media">
                <img src={service.image} alt="" loading="lazy" />
              </div>
              <div className="service-card__body">
                <h3>{service.title}</h3>
                <p>{service.summary}</p>
                <a href="#contact" className="service-card__link">Enquire now →</a>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
