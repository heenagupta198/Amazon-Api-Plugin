export default function Footer() {
  const year = new Date().getFullYear()

  return (
    <footer className="site-footer">
      <div className="container site-footer__top">
        <div className="footer-brand">
          <img src="/images/founder.png" alt="" className="footer-brand__icon" />
          <div>
            <strong>Eye Tech Systems</strong>
            <p>IT hardware · Security · Networking · AMC</p>
          </div>
        </div>

        <div className="footer-col">
          <h4>Quick links</h4>
          <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>

        <div className="footer-col">
          <h4>Services</h4>
          <ul>
            <li><a href="#services">CCTV &amp; Security</a></li>
            <li><a href="#services">UPS &amp; Power</a></li>
            <li><a href="#services">Printers &amp; AMC</a></li>
            <li><a href="#services">LAN &amp; Telephone</a></li>
          </ul>
        </div>

        <div className="footer-col">
          <h4>Reach us</h4>
          <ul className="footer-contact">
            <li>D-485 West Vinod Nagar, Mandawali, Delhi-110092</li>
            <li><a href="tel:+919650239071">+91 96502 39071</a></li>
            <li><a href="mailto:ygupta13@gmail.com">ygupta13@gmail.com</a></li>
          </ul>
        </div>
      </div>

      <div className="container site-footer__bottom">
        <p>© {year} Eye Tech Systems. All rights reserved.</p>
        <div className="footer-legal">
          <a href="#home">Privacy</a>
          <a href="#home">Terms</a>
        </div>
      </div>
    </footer>
  )
}
