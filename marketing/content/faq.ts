export interface QA {
  q: string
  a: string
}

export const faq: QA[] = [
  {
    q: 'Is Panda affiliated with JICA or KALRO?',
    a: 'Not formally. Panda\'s agronomic content is *inspired by* JICA SHEP PLUS — a methodology JICA published with Kenya\'s Ministry of Agriculture and which is freely reproducible for non-commercial use. We pursue a formal KALRO partnership only when pilot traction justifies the relationship cost.',
  },
  {
    q: 'Which crops does Panda support?',
    a: 'Tomato is the launch crop with full content (timeline, inputs, diseases, varieties). 16 more crops from the SHEP PLUS catalogue are scheduled — kale, cabbage, onion, French beans, capsicum, chili, eggplant, potato, watermelon, amaranthus, black nightshade, cowpea leaves, avocado, banana, mango, passion fruit. Each crop\'s content needs sign-off from a KALRO-credentialed agronomist before it ships.',
  },
  {
    q: 'Does Panda work without internet?',
    a: 'Yes. Form writes (mark activity done, log a cost, log a harvest) save offline and sync when you\'re back online. The app installs to your phone home screen like a native app. We test on 3G + intermittent fibre.',
  },
  {
    q: 'How does the disease scan work?',
    a: 'You photograph an affected leaf. Today the diagnosis comes from a deterministic mock that returns one of six common tomato diseases (early blight, late blight, bacterial wilt, Tuta absoluta, TYLCV, fusarium wilt). At pilot launch we flip a config flag and the same flow calls Crop.health (Kindwise) for real ML diagnosis, with a monthly KES ceiling so we never get a surprise bill.',
  },
  {
    q: 'What\'s the pilot timeline?',
    a: '200 farmers in Meru and Kirinyaga, recruited through extension officers and a paid agronomist (named pre-launch). Phase-8 success criteria are explicit: ≥ 65% daily active, ≥ 85% season completion, ≥ 80% disease accuracy on Kenyan field photos, ≥ 40 NPS, ≥ 4 activities logged per farmer per week, ≥ 40% willingness to pay KES 200/mo. Below thresholds → we redesign before scaling.',
  },
  {
    q: 'How is Panda different from Shira?',
    a: 'Shira is for livestock — dairy, poultry, swine. Panda is for horticulture — tomato + 16 other crops. Same operating discipline (Kenyan-built, offline-first, bilingual, lender-grade reports), different domain. Sister products under the same Upstate Web Co umbrella; share infrastructure but not data.',
  },
  {
    q: 'Where is my data stored?',
    a: 'PostgreSQL on a Hetzner CX22 server in Frankfurt, behind Cloudflare. Daily encrypted backups to Cloudflare R2. Disease photos in object storage; farmers can opt out of having photos used for model training. Compliant with the Kenya Data Protection Act 2019.',
  },
  {
    q: 'Can I export my data?',
    a: 'Yes — PDF reports work today; CSV exports of cost, harvest, and disease history are on the Phase-8 must-fix list. If you offboard, we provide a JSON dump of your tenant within 7 days, per the Data Protection Act\'s data portability right.',
  },
  {
    q: 'Is there a phone app?',
    a: 'Panda is a Progressive Web App — install it from your phone browser to your home screen and it behaves like a native app. No App Store / Play Store yet (intentional — gets us to pilot faster). A native shell may follow if pilot demand justifies it.',
  },
  {
    q: 'Who built Panda?',
    a: 'Upstate Web Co (Nairobi) with engineering by Joshua Mukui. Agronomic content by a paid KALRO-credentialed contractor (named pre-launch). Translation by a native Kiswahili speaker.',
  },
]
