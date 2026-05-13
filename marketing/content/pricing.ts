export interface PlanFeature {
  text: string
  included: boolean
}

export interface Plan {
  name: string
  swahili?: string
  priceKES: number | 'Custom'
  pricePeriod: string
  audience: string
  features: PlanFeature[]
  cta: { href: string; label: string }
  highlight?: boolean
}

/*
 * Pricing is provisional — final tiers land after the Phase-8 pilot.
 * Numbers below are the working assumption used in the JAICA build doc;
 * displayed publicly only when the pilot validates willingness-to-pay
 * (target: 40% of pilot farmers say yes at KES 200/mo).
 */
export const plans: Plan[] = [
  {
    name: 'Mkulima',
    swahili: 'Free',
    priceKES: 0,
    pricePeriod: 'forever',
    audience: 'A single smallholder testing the waters.',
    features: [
      { text: '1 farm, 1 user', included: true },
      { text: 'Up to 2 active seasons', included: true },
      { text: 'Activity timeline + cost / harvest log', included: true },
      { text: 'Disease scan — 5 photos / month', included: true },
      { text: 'In-app alerts only', included: true },
      { text: 'PDF reports', included: false },
      { text: 'SMS alerts', included: false },
    ],
    cta: { href: 'https://app.panda.shira.farm', label: 'Open the app' },
  },
  {
    name: 'Shamba',
    swahili: 'Plot',
    priceKES: 200,
    pricePeriod: '/ month',
    audience: 'A growing farm with a couple of plots and one helper.',
    features: [
      { text: '1 farm, 2 users', included: true },
      { text: 'Unlimited active seasons', included: true },
      { text: 'Disease scan — 30 photos / month', included: true },
      { text: 'PDF + CSV reports', included: true },
      { text: 'SMS alerts (50 / month)', included: true },
      { text: 'M-Pesa receipt parsing (beta)', included: true },
      { text: 'Crop benchmarks vs your county', included: true },
    ],
    cta: { href: '/contact', label: 'Talk to us' },
    highlight: true,
  },
  {
    name: 'Boma',
    swahili: 'Compound',
    priceKES: 600,
    pricePeriod: '/ month',
    audience: 'A multi-plot farm with paid labour and a manager.',
    features: [
      { text: '1 farm, 5 users with role permissions', included: true },
      { text: 'Everything in Shamba', included: true },
      { text: 'Lender-ready financial reports', included: true },
      { text: 'SMS alerts (200 / month)', included: true },
      { text: 'Per-plot P&L', included: true },
      { text: 'Priority WhatsApp support', included: true },
    ],
    cta: { href: '/contact', label: 'Talk to us' },
  },
  {
    name: 'Cooperative',
    swahili: 'Ushirika',
    priceKES: 'Custom',
    pricePeriod: 'pricing',
    audience: 'A SACCO, cooperative, or extension network.',
    features: [
      { text: 'Multi-tenant deployment', included: true },
      { text: 'Bulk farmer onboarding', included: true },
      { text: 'Custom data exports + API', included: true },
      { text: 'Dedicated account manager', included: true },
      { text: 'Co-branded mobile experience', included: true },
    ],
    cta: { href: '/contact', label: 'Talk to us' },
  },
]
