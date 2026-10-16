import { Link } from 'react-router-dom'
import { useState } from 'react'
import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { CheckIcon, SendIcon } from '../components/Icons'
import { UPWORK_URL, inquiryTypes, COMPANY } from '../data/siteData'
import { submitContactForm } from '../lib/contactForm'
import {
  validateEmail,
  validatePhone,
  validateName,
  validateMessage,
  sanitizeInput,
  normalizePhone,
} from '../utils/validation'

const contactInfo = [
  { icon: '📞', label: 'Phone', value: COMPANY.phone, href: COMPANY.phoneHref },
  { icon: '✉️', label: 'Email', value: COMPANY.email, href: `mailto:${COMPANY.email}` },
  { icon: '📍', label: 'Location', value: COMPANY.address, href: '/web-design-development-services-in-saket', internal: true },
  { icon: '💬', label: 'WhatsApp', value: 'Chat on WhatsApp', href: COMPANY.whatsapp },
  { icon: '💼', label: 'Upwork', value: 'View on Upwork', href: UPWORK_URL, external: true },
]

const emptyForm = { name: '', email: '', phone: '', inquiryType: '', message: '', website: '' }

export default function Contact() {
  const [form, setForm] = useState(emptyForm)
  const [errors, setErrors] = useState({})
  const [submitted, setSubmitted] = useState(false)
  const [successMessage, setSuccessMessage] = useState('')
  const [loading, setLoading] = useState(false)
  const [submitError, setSubmitError] = useState('')

  const validateForm = () => {
    const next = {
      name: validateName(form.name),
      email: validateEmail(form.email),
      phone: validatePhone(form.phone, true),
      message: validateMessage(form.message),
      inquiryType: form.inquiryType ? '' : 'Please select a service.',
    }
    setErrors(next)
    return !Object.values(next).some(Boolean)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitError('')
    if (!validateForm()) return

    setLoading(true)
    try {
      const payload = {
        name: sanitizeInput(form.name, 80),
        email: sanitizeInput(form.email, 120),
        phone: normalizePhone(form.phone),
        inquiryType: sanitizeInput(form.inquiryType, 100),
        message: sanitizeInput(form.message, 2000),
        website: form.website,
      }
      const result = await submitContactForm(payload)
      setSubmitted(true)
      setSuccessMessage(result.message)
      setForm(emptyForm)
      setErrors({})
      setTimeout(() => {
        setSubmitted(false)
        setSuccessMessage('')
      }, 12000)
    } catch (err) {
      setSubmitError(err.message || 'Something went wrong. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (e) => {
    const { name, value } = e.target
    setForm({ ...form, [name]: value })
    if (errors[name]) setErrors({ ...errors, [name]: '' })
    if (submitError) setSubmitError('')
  }

  const handleBlur = (e) => {
    const { name, value } = e.target
    let error = ''
    if (name === 'name') error = validateName(value)
    if (name === 'email') error = validateEmail(value)
    if (name === 'phone') error = validatePhone(value, true)
    if (name === 'message') error = validateMessage(value)
    setErrors({ ...errors, [name]: error })
  }

  const inputClass = (field) =>
    `w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-1 transition-colors ${
      errors[field]
        ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
        : 'border-gray-200 focus:border-brand focus:ring-brand/30'
    }`

  return (
    <>
      <SEO
        title="Contact Us"
        description={`Contact Yogesh Web Developer (yogeshwebdeveloper.com) for web design and development services in Delhi NCR. Call ${COMPANY.phone} or email ${COMPANY.email}`}
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
              <form
                onSubmit={handleSubmit}
                noValidate
                className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8"
              >
                <h3 className="font-bold text-gray-900 text-lg mb-6">Send Us a Message</h3>

                {submitted && (
                  <div className="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6 text-sm leading-relaxed" role="status">
                    <p className="font-semibold mb-1">✅ Thank You!</p>
                    <p>{successMessage}</p>
                  </div>
                )}

                {submitError && (
                  <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm" role="alert">
                    {submitError}
                  </div>
                )}

                {/* Honeypot - hidden from users, bots fill this */}
                <div className="absolute -left-[9999px] opacity-0 h-0 w-0 overflow-hidden" aria-hidden="true">
                  <label htmlFor="website">Website</label>
                  <input
                    type="text"
                    id="website"
                    name="website"
                    tabIndex={-1}
                    autoComplete="off"
                    value={form.website}
                    onChange={handleChange}
                  />
                </div>

                <div className="grid sm:grid-cols-2 gap-4 mb-4">
                  <div>
                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1.5">Your Name *</label>
                    <input
                      type="text"
                      id="name"
                      name="name"
                      required
                      maxLength={80}
                      value={form.name}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className={inputClass('name')}
                      placeholder="Your full name"
                      autoComplete="name"
                    />
                    {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                  </div>
                  <div>
                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1.5">Email Address *</label>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      required
                      maxLength={120}
                      value={form.email}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className={inputClass('email')}
                      placeholder="you@example.com"
                      autoComplete="email"
                    />
                    {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                  </div>
                </div>

                <div className="grid sm:grid-cols-2 gap-4 mb-4">
                  <div>
                    <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1.5">Phone Number *</label>
                    <input
                      type="tel"
                      id="phone"
                      name="phone"
                      required
                      maxLength={15}
                      value={form.phone}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className={inputClass('phone')}
                      placeholder="+91 83779 56442"
                      autoComplete="tel"
                    />
                    {errors.phone && <p className="text-red-500 text-xs mt-1">{errors.phone}</p>}
                  </div>
                  <div>
                    <label htmlFor="inquiryType" className="block text-sm font-medium text-gray-700 mb-1.5">
                      What are you looking for? *
                    </label>
                    <select
                      id="inquiryType"
                      name="inquiryType"
                      required
                      value={form.inquiryType}
                      onChange={handleChange}
                      className={`${inputClass('inquiryType')} bg-white text-gray-900`}
                    >
                      <option value="" disabled>Select a service</option>
                      {inquiryTypes.map((type) => (
                        <option key={type} value={type}>{type}</option>
                      ))}
                    </select>
                    {errors.inquiryType && <p className="text-red-500 text-xs mt-1">{errors.inquiryType}</p>}
                  </div>
                </div>

                <div className="mb-6">
                  <label htmlFor="message" className="block text-sm font-medium text-gray-700 mb-1.5">Your Message *</label>
                  <textarea
                    id="message"
                    name="message"
                    required
                    rows={5}
                    maxLength={2000}
                    value={form.message}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    className={`${inputClass('message')} resize-none`}
                    placeholder="Tell us about your project requirements..."
                  />
                  {errors.message && <p className="text-red-500 text-xs mt-1">{errors.message}</p>}
                  <p className="text-gray-400 text-xs mt-1 text-right">{form.message.length}/2000</p>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold px-8 py-3.5 rounded-md"
                >
                  {loading ? 'Sending...' : 'Send Message'}
                  {!loading && <SendIcon className="w-4 h-4" />}
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
