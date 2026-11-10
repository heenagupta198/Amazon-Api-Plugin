import InquiryForm from './InquiryForm'

const MAP_QUERY = encodeURIComponent(
  'D-485 West Vinod Nagar Mandawali Delhi 110092',
)

export default function Contact() {
  return (
    <section id="contact" className="section contact">
      <div className="container contact__grid">
        <div className="contact__info">
          <p className="eyebrow">Contact</p>
          <h2>Visit us or send a message</h2>
          <p>
            Call for urgent support or use the form for quotations, AMC, and new installations.
          </p>

          <ul className="contact-cards">
            <li>
              <strong>Address</strong>
              <p>D-485 West Vinod Nagar, Mandawali, Delhi-110092</p>
            </li>
            <li>
              <strong>Phone</strong>
              <p>
                <a href="tel:+919650239071">+91 96502 39071</a>
              </p>
            </li>
            <li>
              <strong>Website</strong>
              <p>
                <a href="https://eyetechsystems.com" rel="noopener noreferrer">
                  eyetechsystems.com
                </a>
              </p>
            </li>
          </ul>

          <div className="map-wrap">
            <iframe
              title="Eye Tech Systems location"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              src={`https://www.google.com/maps?q=${MAP_QUERY}&output=embed`}
            />
          </div>
        </div>

        <div className="contact__form">
          <InquiryForm id="contact-inquiry" title="Contact form" />
        </div>
      </div>
    </section>
  )
}
