export interface Feature {
  emoji: string
  title: string
  body: string
}

export const features: Feature[] = [
  {
    emoji: '📅',
    title: 'Season planner',
    body: 'Pick a crop + your acreage + a planting date. Panda lays out the full activity timeline + scaled input list (seed, fertiliser, sprays). Inspired by JICA SHEP PLUS.',
  },
  {
    emoji: '💸',
    title: 'Costs + harvests log',
    body: 'Tap once after every spend or pick. End of season: a lender-grade PDF with every shilling in and every kilo out. Paper notebooks gone.',
  },
  {
    emoji: '📷',
    title: 'Disease scan (mock today, AI tomorrow)',
    body: 'Snap a leaf, get a diagnosis + a list of PCPB-registered treatments. Currently a deterministic mock; flips to Crop.health AI when the pilot starts.',
  },
  {
    emoji: '🗺️',
    title: 'Dealer map',
    body: '30 verified Kenyan agro-dealers across 14 counties (Elgon Kenya, ETG, Juanco, Twiga). GPS-sorted by your farm location.',
  },
  {
    emoji: '📈',
    title: '12-month price intelligence',
    body: 'Local market prices for the 5 MVP crops, updated weekly. See seasonal swings, plan your harvest week, and stop selling at the wrong price.',
  },
  {
    emoji: '📶',
    title: 'Works without internet',
    body: 'Forms save offline. Sync when you reconnect. Built for the realities of fibre cuts and 3G zones in rural Meru and Kirinyaga.',
  },
  {
    emoji: '🇰🇪',
    title: 'Bilingual (EN + SW)',
    body: 'Every farmer-facing screen ships in English and Kiswahili. Native-speaker reviewed. No machine translation.',
  },
  {
    emoji: '🤝',
    title: 'Sister to Shira',
    body: 'Shares Shira\'s identity, infrastructure, and operational discipline. Your livestock farmer can use Shira; your tomato farmer can use Panda.',
  },
]
