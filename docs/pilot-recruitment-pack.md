# Panda Pilot Recruitment Pack — Meru + Kirinyaga, 200 farmers

Prepared 2026-10-20 (panda-launch-track). Owner: Josh. This pack turns the
live waitlist (`panda_waitlist`, D1 `panda-marketing-db`) into a tracked funnel.

## 1. The funnel

1. Farmer clicks a **channel-specific link** (section 2) → lands on
   `panda.shira.farm/#waitlist` → fills the form.
2. The form now captures UTM params into `panda_waitlist.source` (pipe-joined,
   e.g. `extension_officer|whatsapp|pilot-2026|meru`) — patch 017 in this pack.
3. Resend confirmation email fires (already live, verified end-to-end).
4. Phase-8 CRM (see waitlist-crm spec) moves rows through
   `new → contacted → enrolled | declined | lost`.
5. Weekly metric: signups per channel, county mix, crop mix — all queryable
   straight from D1 since `source`, `county`, `crop` are columns.

## 2. Signup links (UTM'd) — copy these per channel

Campaign: `pilot-2026`. Source names are the channel. `utm_content` is the region.

| Channel | Meru link |
|---|---|
| Extension officers | `https://panda.shira.farm/?utm_source=extension_officer&utm_medium=whatsapp&utm_campaign=pilot-2026&utm_content=meru#waitlist` |
| Agronomist network (Silas) | `https://panda.shira.farm/?utm_source=agronomist&utm_medium=whatsapp&utm_campaign=pilot-2026&utm_content=meru#waitlist` |
| Farmers' groups / co-ops | `https://panda.shira.farm/?utm_source=farmers_group&utm_medium=whatsapp&utm_campaign=pilot-2026&utm_content=meru#waitlist` |
| Agrovet referral | `https://panda.shira.farm/?utm_source=agrovet&utm_medium=in_person&utm_campaign=pilot-2026&utm_content=meru#waitlist` |
| Email | `https://panda.shira.farm/?utm_source=email&utm_medium=email&utm_campaign=pilot-2026&utm_content=meru#waitlist` |

| Channel | Kirinyaga link |
|---|---|
| Extension officers | `https://panda.shira.farm/?utm_source=extension_officer&utm_medium=whatsapp&utm_campaign=pilot-2026&utm_content=kirinyaga#waitlist` |
| Agronomist network (Silas) | `https://panda.shira.farm/?utm_source=agronomist&utm_medium=whatsapp&utm_campaign=pilot-2026&utm_content=kirinyaga#waitlist` |
| Farmers' groups / co-ops | `https://panda.shira.farm/?utm_source=farmers_group&utm_medium=whatsapp&utm_campaign=pilot-2026&utm_content=kirinyaga#waitlist` |
| Agrovet referral | `https://panda.shira.farm/?utm_source=agrovet&utm_medium=in_person&utm_campaign=pilot-2026&utm_content=kirinyaga#waitlist` |
| Email | `https://panda.shira.farm/?utm_source=email&utm_medium=email&utm_campaign=pilot-2026&utm_content=kirinyaga#waitlist` |

Add `utm_source=radio&utm_content=meru` if a radio slot happens — tell the
presenter to read the URL aloud; it's short enough.

## 3. Outreach drafts — Josh sends

### 3a. Email — county extension officers (Meru/Kirinyaga agricultural offices)

Subject: `Free pilot for 100 smallholder farmers in {Meru|Kirinyaga} — farm planning + disease scan app`

```
Habari {Name},

I'm Joshua from Panda, a farm-planning app built in Nairobi for Kenyan
smallholder horticulture farmers (inspired by the JICA SHEP PLUS approach).

We're recruiting 200 farmers for a pilot in Meru and Kirinyaga — 100 per
county — and we'd like your extension network to know about it. Farmers in
the pilot get:

- Step-by-step season plans for 17 horticultural crops (kale, tomato,
  onion, French beans, mango and more), tuned for Kenyan conditions
- A disease scanner: photograph a leaf, get the diagnosis and PCPB-
  registered treatment advice
- Market-price tracking and a dealer directory for their county

There is no cost to farmers during the pilot. In exchange we ask for honest
feedback so we can tune the tool for Kenyan farms.

Would you share this link with your farmer groups and field officers?
{UTM LINK for extension_officer + county}

If a demo would help, I can visit your office or join a field day.

Asante sana,
Joshua Mukui
Upstate Web Co / Panda
hello@panda.shira.farm
```

### 3b. Email — agronomist channel (Silas's network, co-ops, SACCO agriculture desks)

Subject: `200-farmer pilot: SHEP PLUS-style season plans + crop disease scanner (free)`

```
Habari {Name},

Silas {Surname} works with us reviewing the agronomic content for Panda, a
farm-planning app for Kenyan smallholders. We're opening a 200-farmer pilot
in Meru and Kirinyaga and would love your network to join.

Each farmer gets:
- Season-by-season plans for 17 crops with timelines, inputs and spraying
  schedules reviewed by a credentialed agronomist
- A photo-based disease scanner with treatment guidance
- Market-price tracking and a dealer directory

Farmers join free during the pilot and give feedback in return. Signup is
one minute: {UTM LINK for agronomist + county}

I'm happy to run a short demo for your group, in person or on a call.

Asante,
Joshua Mukui — Panda, hello@panda.shira.farm
```

### 3c. WhatsApp — short forward (farmer groups)

```
🌱 PANDA — mpango wa shamba kwa simu yako (bure kwa pilot)

Kwa wakulima wa {Meru|Kirinyaga}:
- Mipango ya msimu kwa mazao 17 (sukuma wiki, nyanya, vitunguu, maharagwe ya france, maembe…)
- Piga picha ya jani → tambua ugonjwa → pata matibabu
- Bei za soko + wauzaji karibu nawe

Jiandikishe hapa (dakika 1): {UTM LINK}
Pilot ni bure. Tunasikiliza maoni yako.
```

### 3d. WhatsApp — direct to an extension officer / agronomist

```
Habari {Name}, huyu Joshua (Panda — app ya mashamba, inspired by SHEP PLUS).
Tunatafuta wakulima 200 wa pilot Meru + Kirinyaga (bure kwa wakulima).
Unaweza kushare link hii kwa vikundi vyako? {UTM LINK}
Ninaweza pia kufanya demo kwa ofisi au field day yako. Asante!
```

## 4. Copy rules (compliance)

- **"Inspired by JICA SHEP PLUS"** only — never claim a JICA partnership
  (per project framing; no formal partnership exists).
- **Pricing honesty:** say "free during the pilot" only if Josh confirms the
  pilot is free. The pricing page hypothesis is KES 200/mo with a 40%
  willingness-to-pay gate — if the pilot charges from day one, replace the
  free lines with "KES 200/month after a free trial week" (Josh decides).
- **No yield guarantees, no cure promises.** Disease advice is advisory and
  carries the KEPHIS/PCPB disclaimer pattern (Rule L5) — drafts above avoid
  specific claims.
- **Data:** farmers consent at registration (DPA 2019 consent gate is live
  on the app). Don't promise anything the consent flow doesn't deliver.

## 5. Pilot gates (definition of done for recruitment)

- [ ] Josh approves the pricing/free-during-pilot wording (section 4)
- [ ] 200 signups across the two counties (or the target Josh sets)
- [ ] Silas sign-off: all 17 crops reviewed (see `docs/crop-signoff-checklist.md`)
- [ ] Disease scan flipped to Kindwise OR explicitly deferred (see
      `docs/runbooks/disease-scan-ml-flip.md`)
- [ ] 40% willingness-to-pay validated at KES 200/mo → pricing decision
- [ ] Phase-8 waitlist CRM live (see waitlist-crm spec) before outreach —
      otherwise signups can't be worked

## 6. Channel strategy (Kenyan horticulture)

- **Primary: extension officers + farmer groups on WhatsApp** — the channel
  the market already uses; links are forward-friendly.
- **Secondary: agronomist network (Silas)** — credibility multiplier; each
  agronomist touches 10-50 farms.
- **Tertiary: agrovets** — poster/QR at the counter; short link above.
- **If budget allows: one community-radio slot** in each county (Meru FM /
  Inooro FM regionals) reading the short link aloud.
- **Social:** launch the channels specced in `docs/social-channels.md` once
  the blog is live — posts repurpose the crop content, links point at the
  UTM'd signup.
