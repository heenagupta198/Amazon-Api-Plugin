import { engagementModels } from '../data/siteData'

const iconEmoji = { clock: '⏱️', user: '👥', briefcase: '💼' }

export default function Engagement() {
  return (
    <section className="bg-white py-12 sm:py-16 lg:py-20">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-10">
          <span className="text-brand font-semibold text-sm uppercase tracking-wider">Engagement Models</span>
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">Flexible Ways to Work With Us</h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {engagementModels.map((model) => (
              <div
                key={model.title}
                className={`relative rounded-2xl p-6 sm:p-8 border transition-all hover:shadow-lg ${
                  model.popular
                    ? 'border-brand bg-brand/5 shadow-md'
                    : 'border-gray-100 bg-white'
                }`}
              >
                {model.popular && (
                  <span className="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand text-white text-xs font-bold px-3 py-1 rounded-full">
                    Most Popular
                  </span>
                )}
                <div className="w-12 h-12 rounded-xl bg-brand/10 flex items-center justify-center mb-4 text-2xl">
                  {iconEmoji[model.icon] || '💼'}
                </div>
                <h3 className="font-bold text-gray-900 text-lg mb-2">{model.title}</h3>
                <p className="text-gray-600 text-sm leading-relaxed">{model.description}</p>
              </div>
            ))}
        </div>
      </div>
    </section>
  )
}
