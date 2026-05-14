import { useState } from 'react'

const COUNTIES = [
  'Meru',
  'Kirinyaga',
  'Nyeri',
  'Embu',
  'Tharaka-Nithi',
  'Murang’a',
  'Other',
]

const CROPS = [
  'Tomato',
  'Kale (Sukuma Wiki)',
  'Cabbage',
  'Bulb Onion',
  'French Beans',
  'Other',
]

type Status = 'idle' | 'sending' | 'sent' | 'error'

export default function WaitlistForm() {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [county, setCounty] = useState('')
  const [acreage, setAcreage] = useState('')
  const [crop, setCrop] = useState('')
  const [status, setStatus] = useState<Status>('idle')
  const [errorMsg, setErrorMsg] = useState<string | null>(null)

  const onSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setStatus('sending')
    setErrorMsg(null)
    try {
      const res = await fetch('/api/waitlist', {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify({
          name,
          email,
          phone: phone || undefined,
          county: county || undefined,
          acreage: acreage ? Number(acreage) : undefined,
          crop: crop || undefined,
        }),
      })
      if (!res.ok) {
        const body = (await res.json().catch(() => ({}))) as { error?: string }
        throw new Error(body.error ?? `bad_response_${res.status}`)
      }
      setStatus('sent')
      setName(''); setEmail(''); setPhone(''); setCounty(''); setAcreage(''); setCrop('')
    } catch (err) {
      setStatus('error')
      setErrorMsg(err instanceof Error ? err.message : 'Unknown error')
    }
  }

  if (status === 'sent') {
    return (
      <div className="rounded-lg border border-soft-green/40 bg-sky-wash p-6 text-center">
        <p className="text-lg font-semibold text-highland-green">You're on the list 🌱</p>
        <p className="mt-2 text-sm text-rich-earth/75">
          We'll email you when onboarding opens for your county. Asante.
        </p>
      </div>
    )
  }

  return (
    <form onSubmit={onSubmit} className="space-y-3 rounded-lg border border-warm-cream/60 bg-bone-white p-5 shadow-sm">
      <div>
        <label htmlFor="wl-name" className="block text-sm font-medium text-rich-earth">
          Your name
        </label>
        <input
          id="wl-name"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
        />
      </div>

      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
          <label htmlFor="wl-email" className="block text-sm font-medium text-rich-earth">
            Email
          </label>
          <input
            id="wl-email"
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          />
        </div>
        <div>
          <label htmlFor="wl-phone" className="block text-sm font-medium text-rich-earth">
            Phone (optional)
          </label>
          <input
            id="wl-phone"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="+254 7..."
            className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
        <div>
          <label htmlFor="wl-county" className="block text-sm font-medium text-rich-earth">
            County
          </label>
          <select
            id="wl-county"
            value={county}
            onChange={(e) => setCounty(e.target.value)}
            className="mt-1 block h-[38px] w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          >
            <option value="">Choose…</option>
            {COUNTIES.map((c) => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label htmlFor="wl-acreage" className="block text-sm font-medium text-rich-earth">
            Acres
          </label>
          <input
            id="wl-acreage"
            type="number"
            step="0.25"
            min="0"
            value={acreage}
            onChange={(e) => setAcreage(e.target.value)}
            className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          />
        </div>
        <div>
          <label htmlFor="wl-crop" className="block text-sm font-medium text-rich-earth">
            Main crop
          </label>
          <select
            id="wl-crop"
            value={crop}
            onChange={(e) => setCrop(e.target.value)}
            className="mt-1 block h-[38px] w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          >
            <option value="">Choose…</option>
            {CROPS.map((c) => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </select>
        </div>
      </div>

      {errorMsg && (
        <div className="rounded-md border border-terracotta/40 bg-terracotta/10 p-2 text-sm text-terracotta" role="alert">
          Something went wrong ({errorMsg}). Please try again or email{' '}
          <a href="mailto:hello@panda.shira.farm" className="underline">hello@panda.shira.farm</a>.
        </div>
      )}

      <button
        type="submit"
        disabled={status === 'sending'}
        className="rounded-md bg-savanna-sun px-5 py-2.5 text-sm font-semibold text-bone-white transition-colors hover:bg-terracotta disabled:opacity-60"
      >
        {status === 'sending' ? 'Sending…' : 'Join the pilot waitlist'}
      </button>
    </form>
  )
}
