const RATE_LIMIT_KEY = 'ywd_contact_last_submit'
const RATE_LIMIT_MS = 60_000

export function canSubmitForm() {
  const last = Number(localStorage.getItem(RATE_LIMIT_KEY) || 0)
  if (Date.now() - last < RATE_LIMIT_MS) {
    const wait = Math.ceil((RATE_LIMIT_MS - (Date.now() - last)) / 1000)
    return { allowed: false, waitSeconds: wait }
  }
  return { allowed: true, waitSeconds: 0 }
}

export function markFormSubmitted() {
  localStorage.setItem(RATE_LIMIT_KEY, String(Date.now()))
}

export async function submitContactForm(formData) {
  const rateCheck = canSubmitForm()
  if (!rateCheck.allowed) {
    throw new Error(`Please wait ${rateCheck.waitSeconds} seconds before submitting again.`)
  }

  if (formData.website) {
    throw new Error('Spam detected.')
  }

  const response = await fetch('/api/contact.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      name: formData.name,
      email: formData.email,
      phone: formData.phone,
      inquiryType: formData.inquiryType,
      message: formData.message,
      website: formData.website || '',
      timestamp: Date.now(),
    }),
  })

  const data = await response.json().catch(() => ({}))

  if (!response.ok || !data.success) {
    throw new Error(data.message || 'Failed to send message. Please try again or email us directly.')
  }

  markFormSubmitted()
  return data
}
