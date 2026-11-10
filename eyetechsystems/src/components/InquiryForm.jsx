import { useState } from 'react'
import { SERVICE_OPTIONS } from '../data/services'

const INITIAL = {
  name: '',
  email: '',
  phone: '',
  service: '',
  message: '',
}

const FORM_ENDPOINT = 'https://formsubmit.co/ajax/ygupta13@gmail.com'

export default function InquiryForm({ id = 'inquiry-form', compact = false, title }) {
  const [form, setForm] = useState(INITIAL)
  const [status, setStatus] = useState('idle')
  const [error, setError] = useState('')

  const onChange = (e) => {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  const onSubmit = async (e) => {
    e.preventDefault()
    setStatus('loading')
    setError('')

    try {
      const res = await fetch(FORM_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          name: form.name,
          email: form.email,
          phone: form.phone,
          service: form.service,
          message: form.message,
          _subject: `Eye Tech Systems — ${form.service || 'New inquiry'}`,
          _template: 'table',
          _captcha: 'false',
        }),
      })

      if (!res.ok) {
        throw new Error('Unable to send message')
      }

      setStatus('success')
      setForm(INITIAL)
    } catch {
      setStatus('error')
      setError('Something went wrong. Please call us or try again in a moment.')
    }
  }

  const onReset = () => {
    setForm(INITIAL)
    setStatus('idle')
    setError('')
  }

  return (
    <div className={`inquiry-form-wrap ${compact ? 'inquiry-form-wrap--compact' : ''}`}>
      {title && <h3 className="inquiry-form__title">{title}</h3>}
      <form id={id} className="inquiry-form" onSubmit={onSubmit} onReset={onReset}>
        <div className="inquiry-form__grid">
          <label className="field">
            <span>Name</span>
            <input
              name="name"
              type="text"
              placeholder="Your name"
              value={form.name}
              onChange={onChange}
              required
              autoComplete="name"
            />
          </label>
          <label className="field">
            <span>Email</span>
            <input
              name="email"
              type="email"
              placeholder="you@email.com"
              value={form.email}
              onChange={onChange}
              required
              autoComplete="email"
            />
          </label>
          <label className="field">
            <span>Phone</span>
            <input
              name="phone"
              type="tel"
              placeholder="10-digit mobile"
              value={form.phone}
              onChange={onChange}
              required
              pattern="[0-9+\s-]{10,15}"
              autoComplete="tel"
            />
          </label>
          <label className="field field--full">
            <span>Service</span>
            <select name="service" value={form.service} onChange={onChange} required>
              <option value="" disabled>Select a service</option>
              {SERVICE_OPTIONS.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </label>
          <label className="field field--full">
            <span>Message</span>
            <textarea
              name="message"
              rows={compact ? 3 : 4}
              placeholder="Tell us about your requirement..."
              value={form.message}
              onChange={onChange}
            />
          </label>
        </div>

        {status === 'success' && (
          <p className="form-feedback form-feedback--success" role="status">
            Thank you! Your request has been sent. We will contact you shortly.
          </p>
        )}
        {status === 'error' && (
          <p className="form-feedback form-feedback--error" role="alert">{error}</p>
        )}

        <div className="inquiry-form__actions">
          <button type="submit" className="btn btn--primary" disabled={status === 'loading'}>
            {status === 'loading' ? 'Sending…' : 'Send inquiry'}
          </button>
          <button type="reset" className="btn btn--ghost">Reset</button>
        </div>
      </form>
    </div>
  )
}
