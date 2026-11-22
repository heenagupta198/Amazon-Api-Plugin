const API_BASE = import.meta.env.VITE_API_BASE ?? '/api'

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    headers: {
      Accept: 'application/json',
      ...(options.headers ?? {}),
    },
    ...options,
  })

  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new Error(data.message ?? `Request failed: ${response.status}`)
  }
  return data
}

export const api = {
  dashboard: () => request('/market/dashboard'),
  refresh: () => request('/market/refresh', { method: 'POST' }),
  dhanTest: () => request('/dhan/connection-test'),
  stocks: () => request('/market/stocks'),
  gainers: () => request('/market/gainers'),
  losers: () => request('/market/losers'),
  breadth: () => request('/market/breadth'),
  adminStatus: () => request('/admin/status'),
}