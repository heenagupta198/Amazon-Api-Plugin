import Navbar from './components/Navbar'
import Hero from './components/Hero'
import Partners from './components/Partners'
import About from './components/About'
import WhyUs from './components/WhyUs'
import Services from './components/Services'
import Contact from './components/Contact'
import Footer from './components/Footer'
import './App.css'

export default function App() {
  return (
    <>
      <Navbar />
      <main>
        <Hero />
        <Partners />
        <About />
        <WhyUs />
        <Services />
        <Contact />
      </main>
      <Footer />
    </>
  )
}
