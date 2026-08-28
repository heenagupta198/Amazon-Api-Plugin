import { Link } from 'react-router-dom'
import { useState } from 'react'
import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { CheckIcon, SendIcon } from '../components/Icons'
import { UPWORK_URL, inquiryTypes, COMPANY } from '../data/siteData'

const contactInfo = [
  { icon: '📞', label: 'Phone', value: COMPANY.phone, href: COMPANY.phoneHref },
  { icon: '✉️', label: 'Email', value: COMPANY.email, href: `mailto:${COMPANY.email}` },
  { icon: '📍', label: 'Location', value: COMPANY.address, href: '/web-design-development-services-in-saket', internal: true },
  { icon: '💬', label: 'WhatsApp', value: 'Chat on WhatsApp', href: COMPANY.whatsapp },
  { icon: '💼', label: 'Upwork', value: 'View on Upwork', href: UPWORK_URL, external: true },
]

export default function Contact() {
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    inquiryType: '',
    message: '',
  })
  const [submitted, setSubmitted] = useState(false)

  const handleSubmit = (e) => {
    e.preventDefault()
    setSubmitted(true)
    setForm({ name: '', email: '', phone: '', inquiryType: '', message: '' })
    setTimeout(() => setSubmitted(false), 5000)
  }

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  return (
    <>
      <SEO
        title="Contact Us"
        description={`Contact ${COMPANY.name} for web design and development services in Delhi NCR. Get a free consultation and project quote today.`}
        path="/contact"
      />

      <PageBanner
        title="Contact Us"
        subtitle="Have a project in mind? Let's discuss how we can help your business grow."
        breadcrumbs={[{ label: 'Contact' }]}
      />

      <section className="py-14 sm:py-18 lg:py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-5 gap-10 lg:gap-12">
            <div className="lg:col-span-2 space-y-5">
              <div>
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-3">Get In Touch</h2>
                <p className="text-gray-600 text-sm leading-relaxed">
                  We're available for projects worldwide. Fill out the form or reach out directly —
                  we typically respond within 24 hours.
                </p>
              </div>

              {contactInfo.map((item) => (
                item.internal ? (
                  <Link
                    key={item.label}
                    to={item.href}
                    className="flex items-center gap-4 bg-gray-50 hover:bg-brand/5 border border-gray-100 hover:border-brand/20 rounded-xl p-4 transition-all group"
                  >
                    <span className="text-2xl">{item.icon}</span>
                    <div>
                      <p className="text-xs text-gray-500 font-medium">{item.label}</p>
                      <p className="text-sm font-semibold text-gray-900 group-hover:text-brand transition-colors">
                        {item.value}
                      </p>
                    </div>
                  </Link>
                ) : (
                <a
                  key={item.label}
                  href={item.href}
                  target={item.external ? '_blank' : undefined}
                  rel={item.external ? 'noopener noreferrer' : undefined}
                  className="flex items-center gap-4 bg-gray-50 hover:bg-brand/5 border border-gray-100 hover:border-brand/20 rounded-xl p-4 transition-all group"
                >
                  <span className="text-2xl">{item.icon}</span>
                  <div>
                    <p className="text-xs text-gray-500 font-medium">{item.label}</p>
                    <p className="text-sm font-semibold text-gray-900 group-hover:text-brand transition-colors">
                      {item.value}
                    </p>
                  </div>
                </a>
                )
              ))}

              <div className="bg-brand/5 border border-brand/15 rounded-xl p-5">
                <p className="font-bold text-brand text-sm mb-3">Available for new projects</p>
                <ul className="space-y-2">
                  {['Free Consultation', 'Quick Response', 'Flexible Plans'].map((item) => (
                    <li key={item} className="flex items-center gap-2 text-sm text-gray-700">
                      <CheckIcon className="w-4 h-4 text-brand" />
                      {item}
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            <div className="lg:col-span-3">
              <form onSubmit={handleSubmit} className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
                <h3 className="font-bold text-gray-900 text-lg mb-6">Send Us a Message</h3>

                {submitted && (
                  <div className="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6 text-sm">
                    Thank you! Your message has been sent. We'll get back to you within 24 hours.
                  </div>
                )}

                <div className="grid sm:grid-cols-2 gap-4 mb-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Your Name *</label>
                    <input
                      type="text"
                      name="name"
                      required
                      value={form.name}
                      onChange={handleChange}
                      className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/30 transition-colors"
                      placeholder="John Doe"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Email Address *</label>
                    <input
                      type="email"
                      name="email"
                      required
                      value={form.email}
                      onChange={handleChange}
                      className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/30 transition-colors"
                      placeholder="john@example.com"
                    />
                  </div>
                </div>

                <div className="grid sm:grid-cols-2 gap-4 mb-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                    <input
                      type="tel"
                      name="phone"
                      value={form.phone}
                      onChange={handleChange}
                      className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/30 transition-colors"
                      placeholder="+91 98765 43210"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                      What are you looking for? *
                    </label>
                    <select
                      name="inquiryType"
                      required
                      value={form.inquiryType}
                      onChange={handleChange}
                      className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/30 transition-colors bg-white text-gray-900"
                    >
                      <option value="" disabled>Select a service</option>
                      {inquiryTypes.map((type) => (
                        <option key={type} value={type}>{type}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="mb-6">
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">Your Message *</label>
                  <textarea
                    name="message"
                    required
                    rows={5}
                    value={form.message}
                    onChange={handleChange}
                    className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/30 transition-colors resize-none"
                    placeholder="Tell us about your project requirements..."
                  />
                </div>

                <button
                  type="submit"
                  className="btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white font-semibold px-8 py-3.5 rounded-md"
                >
                  Send Message
                  <SendIcon className="w-4 h-4" />
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
