import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';

export interface ConfirmDeleteModalProps {
  open: boolean;
  onClose: () => void;
  onConfirm: () => Promise<void>;
  title: string;
  /** What will be deleted, in plain language. */
  body: string;
  /** Optional override for the destructive button label. */
  confirmLabel?: string;
}

/**
 * Generic two-step delete confirmation. Used for cost / harvest / season
 * destructive actions. The async `onConfirm` is awaited inside the modal
 * so the button shows a loading state until the parent's mutation
 * resolves; the modal closes automatically on success or surfaces the
 * error inline.
 */
export function ConfirmDeleteModal({
  open,
  onClose,
  onConfirm,
  title,
  body,
  confirmLabel,
}: ConfirmDeleteModalProps) {
  const { t } = useTranslation();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handle = async () => {
    setBusy(true);
    setError(null);
    try {
      await onConfirm();
      onClose();
    } catch (err) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        (err instanceof Error ? err.message : 'Could not delete.');
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal open={open} onClose={busy ? () => undefined : onClose} title={title}>
      <div className="space-y-4">
        <p className="text-sm text-gray-700">{body}</p>
        {error && <p className="text-sm text-danger-600" role="alert">{error}</p>}
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="secondary" onClick={onClose} disabled={busy}>
            {t('common.cancel')}
          </Button>
          <Button type="button" variant="danger" onClick={handle} loading={busy}>
            {confirmLabel ?? t('common.delete')}
          </Button>
        </div>
      </div>
    </Modal>
  );
}
