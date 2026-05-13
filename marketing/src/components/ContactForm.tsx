import { useState } from 'react'

export default function ContactForm() {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [message, setMessage] = useState('')
  const [status, setStatus] = useState<'idle' | 'sending' | 'sent' | 'error'>('idle')

  const onSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setStatus('sending')
    try {
      const res = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify({ name, email, phone, message }),
      })
      if (!res.ok) throw new Error('bad response')
      setStatus('sent')
      setName('')
      setEmail('')
      setPhone('')
      setMessage('')
    } catch {
      setStatus('error')
    }
  }

  if (status === 'sent') {
    return (
      <div className="rounded-md border border-soft-green/40 bg-sky-wash p-4 text-sm text-highland-green">
        Thanks — we'll get back to you within 1 working day.
      </div>
    )
  }

  return (
    <form onSubmit={onSubmit} className="space-y-3">
      <div>
        <label htmlFor="name" className="block text-sm font-medium text-rich-earth">
          Your name
        </label>
        <input
          id="name"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
        />
      </div>

      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
          <label htmlFor="email" className="block text-sm font-medium text-rich-earth">
            Email
          </label>
          <input
            id="email"
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          />
        </div>
        <div>
          <label htmlFor="phone" className="block text-sm font-medium text-rich-earth">
            Phone (optional)
          </label>
          <input
            id="phone"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="+254 7..."
            className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
          />
        </div>
      </div>

      <div>
        <label htmlFor="message" className="block text-sm font-medium text-rich-earth">
          What would you like Panda to do for your farm?
        </label>
        <textarea
          id="message"
          required
          rows={4}
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          className="mt-1 block w-full rounded-md border border-warm-cream bg-bone-white px-3 py-2 text-sm focus:border-highland-green focus:outline-none focus:ring-2 focus:ring-soft-green/40"
        />
      </div>

      {status === 'error' && (
        <div className="rounded-md border border-terracotta/40 bg-terracotta/10 p-2 text-sm text-terracotta">
          Could not send. Please try again or email <a href="mailto:hello@panda.shira.farm" className="underline">hello@panda.shira.farm</a>.
        </div>
      )}

      <button
        type="submit"
        disabled={status === 'sending'}
        className="rounded-md bg-highland-green px-5 py-2.5 text-sm font-semibold text-bone-white transition-colors hover:bg-deep-forest disabled:opacity-60"
      >
        {status === 'sending' ? 'Sending…' : 'Send'}
      </button>
    </form>
  )
}
