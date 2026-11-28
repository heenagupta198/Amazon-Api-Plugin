import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { api, clearAuthToken, setAuthToken } from '../services/api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [booting, setBooting] = useState(true)

  const refresh = useCallback(async () => {
    try {
      const { user: me } = await api.me()
      setUser(me)
    } catch {
      clearAuthToken()
      setUser(null)
    } finally {
      setBooting(false)
    }
  }, [])

  useEffect(() => {
    refresh()
  }, [refresh])

  const login = useCallback(async (email, password) => {
    const payload = await api.login(email, password)
    setAuthToken(payload.token)
    setUser(payload.user)
    return payload.user
  }, [])

  const logout = useCallback(async () => {
    try {
      await api.logout()
    } catch {
      /* ignore */
    }
    clearAuthToken()
    setUser(null)
  }, [])

  const value = useMemo(
    () => ({
      user,
      booting,
      isAuthenticated: Boolean(user),
      isAdmin: Boolean(user?.is_admin),
      login,
      logout,
      refresh,
    }),
    [user, booting, login, logout, refresh],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return ctx
}