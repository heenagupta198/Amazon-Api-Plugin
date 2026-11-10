import Hero from '../components/Hero'
import Services from '../components/Services'
import Stats from '../components/Stats'
import Cities from '../components/Cities'
import GlobalHire from '../components/GlobalHire'
import Engagement from '../components/Engagement'
import SEO, { organizationSchema } from '../components/SEO'
import { COMPANY } from '../data/siteData'

export default function Home() {
  return (
    <>
      <SEO
        title="Web Design & Development Company in Delhi NCR"
        description={COMPANY.defaultDescription}
        path="/"
        schema={organizationSchema()}
      />
      <Hero />
      <Services />
      <Stats />
      <Cities />
      <GlobalHire />
      <Engagement />
    </>
  )
}
