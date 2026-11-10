import PageBanner from '../components/PageBanner'
import SEO from '../components/SEO'
import { COMPANY } from '../data/company'

export default function PrivacyPolicy() {
  return (
    <>
      <SEO
        title="Privacy Policy"
        description={`Privacy Policy for ${COMPANY.name}. Learn how we collect, use and protect your personal information.`}
        path="/privacy-policy"
      />

      <PageBanner
        title="Privacy Policy"
        subtitle="How we handle and protect your personal information."
        breadcrumbs={[{ label: 'Privacy Policy' }]}
      />

      <section className="py-14 sm:py-18">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-gray max-w-none">
          <p className="text-gray-600 leading-relaxed mb-6">
            Last updated: August 28, 2026. {COMPANY.name} ("we", "our", "us") is committed to protecting
            your privacy. This Privacy Policy explains how we collect, use, disclose and safeguard your
            information when you visit our website or use our services.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Information We Collect</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            We may collect personal information that you voluntarily provide when you fill out our contact
            form, request a quote or communicate with us. This may include your name, email address, phone
            number, company name and project details.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">How We Use Your Information</h2>
          <ul className="list-disc pl-5 space-y-2 text-gray-600 mb-4">
            <li>To respond to your inquiries and provide web development services</li>
            <li>To send project updates, proposals and service-related communications</li>
            <li>To improve our website, services and user experience</li>
            <li>To comply with legal obligations</li>
          </ul>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Cookies & Analytics</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            Our website may use cookies and analytics tools such as Google Analytics to understand visitor
            behaviour and improve our services. You can control cookie preferences through your browser settings.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Data Security</h2>
          <p className="text-gray-600 leading-relaxed mb-4">
            We implement appropriate technical and organizational measures to protect your personal data against
            unauthorized access, alteration, disclosure or destruction.
          </p>

          <h2 className="text-xl font-bold text-gray-900 mt-8 mb-3">Contact Us</h2>
          <p className="text-gray-600 leading-relaxed">
            If you have questions about this Privacy Policy, contact us at{' '}
            <a href={`mailto:${COMPANY.email}`} className="text-brand hover:underline">{COMPANY.email}</a>{' '}
            or call <a href={COMPANY.phoneHref} className="text-brand hover:underline">{COMPANY.phone}</a>.
          </p>
        </div>
      </section>
    </>
  )
}
