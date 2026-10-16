const EMAIL_REGEX = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
const PHONE_REGEX = /^(\+91[\s-]?)?[6-9]\d{9}$/

export function validateEmail(email) {
  const trimmed = email.trim()
  if (!trimmed) return 'Email address is required.'
  if (!EMAIL_REGEX.test(trimmed)) return 'Please enter a valid email address.'
  if (trimmed.length > 120) return 'Email address is too long.'
  return ''
}

export function validatePhone(phone, required = false) {
  const cleaned = phone.replace(/[\s-]/g, '').trim()
  if (!cleaned) return required ? 'Phone number is required.' : ''
  if (!PHONE_REGEX.test(cleaned)) {
    return 'Please enter a valid 10-digit Indian mobile number (e.g. +91 8377956442).'
  }
  return ''
}

export function validateName(name) {
  const trimmed = name.trim()
  if (!trimmed) return 'Name is required.'
  if (trimmed.length < 2) return 'Name must be at least 2 characters.'
  if (trimmed.length > 80) return 'Name is too long.'
  if (!/^[a-zA-Z\s.'-]+$/.test(trimmed)) return 'Name contains invalid characters.'
  return ''
}

export function validateMessage(message) {
  const trimmed = message.trim()
  if (!trimmed) return 'Message is required.'
  if (trimmed.length < 10) return 'Message must be at least 10 characters.'
  if (trimmed.length > 2000) return 'Message is too long (max 2000 characters).'
  return ''
}

export function sanitizeInput(value, maxLength = 500) {
  return String(value || '')
    .replace(/<[^>]*>/g, '')
    .replace(/[<>"'`;(){}[\]\\]/g, '')
    .trim()
    .slice(0, maxLength)
}

export function normalizePhone(phone) {
  const digits = phone.replace(/\D/g, '')
  if (digits.length === 10) return `+91${digits}`
  if (digits.length === 12 && digits.startsWith('91')) return `+${digits}`
  return phone.trim()
}
