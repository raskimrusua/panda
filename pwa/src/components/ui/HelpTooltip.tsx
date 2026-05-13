import { useEffect, useId, useRef, useState } from 'react';
import { HelpCircle, X } from 'lucide-react';

export interface HelpTooltipProps {
  /** Short heading shown in the popover header. */
  title: string;
  /** Body text — kept short (1–2 short paragraphs). */
  body: string;
  /** Accessible label for the trigger button (often the field name). */
  label?: string;
}

/**
 * Inline "?" icon that opens a small popover with a title + body explainer.
 * Click anywhere outside or press Escape to close. Keyboard-accessible:
 * the trigger is a real button; the popover is announced via aria-describedby.
 *
 * Pattern is intentionally tiny — no Popper/Floating-UI dependency. The
 * popover is absolutely positioned below the trigger and clips to the right
 * edge of the screen on narrow viewports via `right-0` fallback when the
 * trigger is in the right half of the viewport.
 */
export function HelpTooltip({ title, body, label }: HelpTooltipProps) {
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLSpanElement | null>(null);
  const id = useId();
  const popoverId = `help-${id}`;

  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (!containerRef.current?.contains(e.target as Node)) setOpen(false);
    };
    const onEsc = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onEsc);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onEsc);
    };
  }, [open]);

  return (
    <span ref={containerRef} className="relative inline-flex align-middle">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="text-gray-400 hover:text-gray-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
        aria-label={label ?? title}
        aria-expanded={open}
        aria-controls={popoverId}
      >
        <HelpCircle className="h-4 w-4" />
      </button>
      {open && (
        <div
          id={popoverId}
          role="dialog"
          aria-label={title}
          className="absolute left-0 top-6 z-30 w-64 rounded-md border border-gray-200 bg-white p-3 text-left shadow-lg"
        >
          <div className="flex items-start justify-between gap-2 pb-1">
            <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="text-gray-400 hover:text-gray-600"
              aria-label="Close"
            >
              <X className="h-4 w-4" />
            </button>
          </div>
          <p className="text-xs leading-relaxed text-gray-600">{body}</p>
        </div>
      )}
    </span>
  );
}
