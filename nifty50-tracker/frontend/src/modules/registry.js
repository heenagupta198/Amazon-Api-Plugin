import OpenInterestModule from './open-interest/OpenInterestModule'
import SuperTrendModule from './supertrend/SuperTrendModule'
import Top5Module from './top5/Top5Module'
import UnusualVolumeModule from './unusual-volume/UnusualVolumeModule'
import VwapModule from './vwap/VwapModule'

export const NIFTY_MODULES = [
  {
    id: 'top5',
    title: 'Top 5 + Volume',
    description: 'Leaders in points and traded volume (Nifty 50 only).',
    Component: Top5Module,
  },
  {
    id: 'unusual-volume',
    title: 'Unusual volume',
    description: 'Today vs 20-day average — spikes subscribers pay for.',
    Component: UnusualVolumeModule,
  },
  {
    id: 'vwap',
    title: 'Above VWAP',
    description: 'Price trading above VWAP on 5m · 15m · 1h · 1d.',
    Component: VwapModule,
  },
  {
    id: 'supertrend',
    title: 'SuperTrend',
    description: 'Breakout / breakdown on 5m · 15m · 1h · 1d.',
    Component: SuperTrendModule,
  },
  {
    id: 'open-interest',
    title: 'F&O open interest',
    description: 'OI change digits + chart (Nifty 50 underlying stocks).',
    Component: OpenInterestModule,
  },
]