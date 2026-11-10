import { Link } from 'react-router-dom'
import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'

export default function NotFound() {
  return (
    <>
      <SEO title="Page Not Found" description="The page you are looking for does not exist." path="/404" noindex />
      <PageBanner title="404 - Page Not Found" subtitle="Sorry, the page you requested could not be found." />
      <section className="py-16 text-center">
        <Link to="/" className="text-brand font-semibold hover:underline">Go back to Home</Link>
      </section>
    </>
  )
}
