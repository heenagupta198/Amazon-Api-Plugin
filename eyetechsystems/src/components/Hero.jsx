import InquiryForm from './InquiryForm'

export default function Hero() {
  return (
    <section id="home" className="hero">
      <div className="hero__bg" aria-hidden="true">
        <div className="hero__orb hero__orb--1" />
        <div className="hero__orb hero__orb--2" />
        <div className="hero__grid" />
      </div>

      <div className="container hero__layout">
        <div className="hero__content">
          <p className="eyebrow">IT solutions · Delhi NCR</p>
          <h1>
            Smart technology services for{' '}
            <span className="text-gradient">homes &amp; businesses</span>
          </h1>
          <p className="hero__lead">
            From CCTV and networking to printers, UPS, access control, and AMC — Eye Tech Systems
            delivers reliable installation, maintenance, and support you can count on.
          </p>
          <ul className="hero__stats">
            <li>
              <strong>12+</strong>
              <span>Service lines</span>
            </li>
            <li>
              <strong>AMC</strong>
              <span>Annual contracts</span>
            </li>
            <li>
              <strong>24×7</strong>
              <span>Support mindset</span>
            </li>
          </ul>
          <div className="hero__contact-strip">
            <a href="tel:+919650239071" className="hero__phone">+91 96502 39071</a>
            <span className="hero__divider" />
            <span>Mandawali, East Delhi</span>
          </div>
        </div>

        <div className="hero__form-panel">
          <InquiryForm
            id="hero-inquiry"
            compact
            title="Request a free consultation"
          />
          <p className="hero__form-note">
            Share your requirement — we respond quickly on phone and email.
          </p>
        </div>
      </div>
    </section>
  )
}
