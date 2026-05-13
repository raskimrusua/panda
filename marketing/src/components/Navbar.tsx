import { useState } from 'react'
import { Menu, X } from 'lucide-react'

const links = [
  { href: '/features', label: 'Features' },
  { href: '/pricing', label: 'Pricing' },
  { href: '/faq', label: 'FAQ' },
  { href: '/contact', label: 'Contact' },
]

export default function Navbar() {
  const [open, setOpen] = useState(false)

  return (
    <nav
      className="sticky top-0 z-30 border-b border-warm-cream/60 bg-bone-white/90 backdrop-blur"
      aria-label="Primary"
    >
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 md:px-6">
        <a href="/" className="text-xl font-semibold text-highland-green">
          Panda<span className="text-panda-leaf">.</span>
        </a>

        <div className="hidden items-center gap-6 md:flex">
          {links.map((l) => (
            <a
              key={l.href}
              href={l.href}
              className="text-sm font-medium text-rich-earth/80 transition-colors hover:text-highland-green"
            >
              {l.label}
            </a>
          ))}
          <a
            href="https://app.panda.shira.farm"
            className="rounded-md bg-highland-green px-4 py-2 text-sm font-semibold text-bone-white transition-colors hover:bg-deep-forest"
          >
            Open the app
          </a>
        </div>

        <button
          type="button"
          onClick={() => setOpen((o) => !o)}
          className="md:hidden text-rich-earth"
          aria-label={open ? 'Close menu' : 'Open menu'}
          aria-expanded={open}
        >
          {open ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {open && (
        <div className="border-t border-warm-cream/60 bg-bone-white md:hidden">
          <div className="space-y-1 px-4 py-3">
            {links.map((l) => (
              <a
                key={l.href}
                href={l.href}
                className="block rounded-md px-3 py-2 text-base font-medium text-rich-earth/80 hover:bg-warm-cream/40"
              >
                {l.label}
              </a>
            ))}
            <a
              href="https://app.panda.shira.farm"
              className="mt-2 block rounded-md bg-highland-green px-3 py-2 text-center text-base font-semibold text-bone-white"
            >
              Open the app
            </a>
          </div>
        </div>
      )}
    </nav>
  )
}
