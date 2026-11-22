const API_BASE = import.meta.env.VITE_API_BASE ?? '/api'
const TOKEN_KEY = 'traderdomina_token'

export function setAuthToken(token) {
  sessionStorage.setItem(TOKEN_KEY, token)
}

export function clearAuthToken() {
  sessionStorage.removeItem(TOKEN_KEY)
}

function getAuthToken() {
  return sessionStorage.getItem(TOKEN_KEY)
}

async function request(path, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.headers ?? {}),
  }

  const token = getAuthToken()
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  })

  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    if (response.status === 401) {
      clearAuthToken()
    }
    throw new Error(data.message ?? `Request failed: ${response.status}`)
  }
  return data
}

export const api = {
  login: (email, password) =>
    request('/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password, device_name: 'web' }),
    }),
  logout: () => request('/auth/logout', { method: 'POST' }),
  me: () => request('/auth/me'),
  dashboard: () => request('/market/dashboard'),
  refresh: () => request('/market/refresh', { method: 'POST' }),
  dhanTest: () => request('/dhan/connection-test'),
  stocks: () => request('/market/stocks'),
  gainers: () => request('/market/gainers'),
  losers: () => request('/market/losers'),
  breadth: () => request('/market/breadth'),
  adminStatus: () => request('/admin/status'),
  listSubscribers: () => request('/subscribers'),
  createSubscriber: (body) =>
    request('/subscribers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }),
}