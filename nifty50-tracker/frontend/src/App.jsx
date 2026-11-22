import { BrowserRouter, Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import Admin from './pages/Admin'
import AllStocks from './pages/AllStocks'
import Dashboard from './pages/Dashboard'
import PlaceholderPage from './pages/PlaceholderPage'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<Dashboard />} />
          <Route
            path="/gainers"
            element={
              <PlaceholderPage
                title="Top gainers"
                description="Phase 2: dedicated gainers view with LTP, weightage, and point contribution (API route /market/gainers is ready)."
              />
            }
          />
          <Route
            path="/losers"
            element={
              <PlaceholderPage
                title="Top losers"
                description="Phase 2: dedicated losers view (API route /market/losers is ready)."
              />
            }
          />
          <Route path="/stocks" element={<AllStocks />} />
          <Route
            path="/breadth"
            element={
              <PlaceholderPage
                title="Market breadth"
                description="Phase 3: sector breakdown and advance/decline charts using /market/breadth."
              />
            }
          />
          <Route
            path="/historical"
            element={
              <PlaceholderPage
                title="Historical analysis"
                description="Phase 4: date-wise snapshots, CSV export, and intraday contribution timeline."
              />
            }
          />
          <Route path="/admin" element={<Admin />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}