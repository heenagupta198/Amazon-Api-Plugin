export default function About() {
  return (
    <section id="about" className="section about">
      <div className="container about__grid">
        <div className="about__visual">
          <div className="about__image-frame">
            <img src="/images/founder.png" alt="Eye Tech Systems team" />
          </div>
          <div className="about__badge">
            <span>Trusted local partner</span>
            <strong>Delhi · NCR</strong>
          </div>
        </div>

        <div className="about__copy">
          <p className="eyebrow">About us</p>
          <h2>Technology that works for you</h2>
          <p>
            Eye Tech Systems helps clients stay secure, connected, and productive with end-to-end
            services — from consultation and supply to installation and annual maintenance.
          </p>

          <div className="vision-mission">
            <article>
              <h3>Our vision</h3>
              <p>
                To be a leading provider of innovative, reliable IT and infrastructure solutions
                that empower businesses and communities in the digital era.
              </p>
            </article>
            <article>
              <h3>Our mission</h3>
              <p>
                Deliver customized technology with skilled execution, transparent communication,
                and support that exceeds expectations.
              </p>
            </article>
          </div>
        </div>
      </div>
    </section>
  )
}
