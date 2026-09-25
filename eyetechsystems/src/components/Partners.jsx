const BRANDS = ['HP', 'Dell', 'Lenovo', 'Acer', 'ASUS', 'Vertiv', 'Hikvision', 'CP Plus']

export default function Partners() {
  return (
    <section className="partners" aria-label="Technology partners">
      <div className="container">
        <p className="partners__label">Brands &amp; ecosystems we work with</p>
        <ul className="partners__list">
          {BRANDS.map((name) => (
            <li key={name}>{name}</li>
          ))}
        </ul>
      </div>
    </section>
  )
}
