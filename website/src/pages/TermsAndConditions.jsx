import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { COMPANY } from '../data/company'

export default function TermsAndConditions() {
  return (
    <>
      <SEO
        title="Terms & Conditions"
        description={`Terms and Conditions for using ${COMPANY.name} website and web development services.`}
        path="/terms-and-conditions"
      />

      <PageBanner
        title="Terms & Conditions"
        subtitle="Please read these terms carefully before using our website or services."
        breadcrumbs={[{ label: 'Terms & Conditions' }]}
      />

      <section className="py-14 sm:py-18">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <p className="text-gray-600 leading-relaxed mb-6">
            Last updated: August 28, 2026. By accessing and using the {COMPANY.name} website and services,
            you agree to be bound by these Terms and Conditions.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Services</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            {COMPANY.name} provides web design, web development, SEO, e-commerce and related digital
            services. Specific deliverables, timelines and pricing are defined in individual project
            agreements or proposals.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Payment Terms</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            Payment schedules are outlined in project proposals. Unless otherwise agreed, a deposit is
            required before work begins. Final deliverables are released upon full payment.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Intellectual Property</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            Upon full payment, clients receive ownership of custom designs and code developed specifically
            for their project. Third-party themes, plugins and stock assets remain subject to their
            respective licenses.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Limitation of Liability</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            {COMPANY.name} shall not be liable for any indirect, incidental or consequential damages
            arising from the use of our website or services. Our total liability is limited to the amount
            paid for the specific service in question.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Contact</h2>
          <p className="text-gray-600 leading-relaxed">
            For questions about these terms, contact{' '}
            <a href={`mailto:${COMPANY.email}`} className="text-brand hover:underline">{COMPANY.email}</a>.
          </p>
        </div>
      </section>
    </>
  )
}
