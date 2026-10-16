import { WHY_US } from '../data/services'

export default function WhyUs() {
  return (
    <section className="section why-us">
      <div className="container">
        <div className="section-head section-head--light">
          <p className="eyebrow eyebrow--light">Why choose us</p>
          <h2>Superior support, real-world expertise</h2>
          <p>
            We combine field experience with modern equipment to keep your systems running smoothly
            — before, during, and after installation.
          </p>
        </div>

        <div className="why-grid">
          {WHY_US.map((item, i) => (
            <article key={item.title} className="why-card">
              <span className="why-card__index">{String(i + 1).padStart(2, '0')}</span>
              <h3>{item.title}</h3>
              <p>{item.text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
