/** Panda — site configuration. */
export const SITE = {
  name: 'Panda',
  tagline: 'Plan, log, and grow your horticulture farm.',
  description: 'A Kenyan-built planning + logging tool for tomato, kale, and 15 other crops. Inspired by SHEP PLUS. Works offline. Bilingual EN / SW.',
  url: 'https://panda-farm.com',
  email: 'hello@panda-farm.com',
  phone: '',
  postalAddress: {
    streetAddress: '',
    addressLocality: 'Nairobi',
    addressRegion: '',
    postalCode: '',
    addressCountry: 'KE',
  },
  geo: { latitude: '-1.2921', longitude: '36.8219' },
  openingHours: [] as { dayOfWeek: string[]; opens: string; closes: string }[],
  sameAs: [] as string[],
  priceRange: '$$',
  paymentAccepted: ['M-Pesa', 'Credit Card'] as string[],
  currenciesAccepted: 'KES',
  indexable: true,
} as const
