import { useState } from 'react'
import { ChevronDown } from 'lucide-react'

export interface QA {
  q: string
  a: string
}

export default function FAQAccordion({ items }: { items: QA[] }) {
  const [open, setOpen] = useState<number | null>(0)

  return (
    <div className="divide-y divide-warm-cream/60 rounded-lg border border-warm-cream/60 bg-bone-white">
      {items.map((item, i) => {
        const isOpen = open === i
        return (
          <div key={item.q}>
            <button
              type="button"
              onClick={() => setOpen(isOpen ? null : i)}
              className="flex w-full items-center justify-between px-4 py-4 text-left"
              aria-expanded={isOpen}
            >
              <span className="text-base font-medium text-rich-earth">
                {item.q}
              </span>
              <ChevronDown
                className={`h-4 w-4 text-rich-earth/60 transition-transform ${isOpen ? 'rotate-180' : ''}`}
              />
            </button>
            {isOpen && (
              <div className="px-4 pb-4 text-sm leading-relaxed text-rich-earth/75">
                {item.a}
              </div>
            )}
          </div>
        )
      })}
    </div>
  )
}
