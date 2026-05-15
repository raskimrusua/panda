import { useEffect, useRef, useState } from 'react';
import { MoreVertical, Pencil, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export interface RowActionsProps {
  onEdit: () => void;
  onDelete: () => void;
  /** Optional override for accessibility label on the trigger. */
  label?: string;
}

/**
 * Tiny 3-dot menu button used at the right edge of a list row.
 * Two actions: Edit (pencil) + Delete (trash). Closes on Escape, on click
 * outside, and after either action fires.
 *
 * Pattern mirrors HelpTooltip's outside-click handler — no external
 * dropdown library, ~50 LOC.
 */
export function RowActions({ onEdit, onDelete, label }: RowActionsProps) {
  const { t } = useTranslation();
  const [open, setOpen] = useState(false);
  const wrapRef = useRef<HTMLSpanElement | null>(null);

  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (!wrapRef.current?.contains(e.target as Node)) setOpen(false);
    };
    const onEsc = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onEsc);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onEsc);
    };
  }, [open]);

  const fire = (fn: () => void) => () => {
    setOpen(false);
    fn();
  };

  return (
    <span ref={wrapRef} className="relative inline-flex">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
        aria-label={label ?? t('common.row_actions')}
        aria-expanded={open}
      >
        <MoreVertical className="h-4 w-4" />
      </button>
      {open && (
        <div
          role="menu"
          className="absolute right-0 top-7 z-20 w-36 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg"
        >
          <button
            type="button"
            role="menuitem"
            onClick={fire(onEdit)}
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
          >
            <Pencil className="h-4 w-4" /> {t('common.edit')}
          </button>
          <button
            type="button"
            role="menuitem"
            onClick={fire(onDelete)}
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-danger-600 hover:bg-danger-50"
          >
            <Trash2 className="h-4 w-4" /> {t('common.delete')}
          </button>
        </div>
      )}
    </span>
  );
}
