import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Mail } from 'lucide-react';
import { authApi } from '@/api/auth';
import { useAuth } from '@/auth/useAuth';
import { Button } from '@/components/ui/Button';

/**
 * Dismissible banner shown at the top of AppShell for users whose email
 * is not yet verified. Non-blocking — the farmer can still use the app.
 * The "Resend" button calls POST /auth/email/verification-notification.
 */
export function EmailVerificationBanner() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const [dismissed, setDismissed] = useState(false);
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  if (!user || user.email_verified_at || dismissed) return null;

  const handleResend = async () => {
    setSending(true);
    try {
      await authApi.sendVerification();
      setSent(true);
    } catch {
      // Already-verified users get 204 (handled silently); other errors
      // are non-critical — the banner stays visible and they can retry.
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="bg-yellow-50 border-b border-yellow-200 px-4 py-2">
      <div className="max-w-5xl mx-auto flex items-center justify-between gap-3 text-sm">
        <div className="flex items-center gap-2 text-yellow-900 min-w-0">
          <Mail className="h-4 w-4 shrink-0" />
          <span className="truncate">
            {sent ? t('auth.verify_sent') : t('auth.verify_pending')}
          </span>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          {!sent && (
            <Button size="sm" variant="secondary" loading={sending} onClick={handleResend}>
              {t('auth.resend_email')}
            </Button>
          )}
          <button
            type="button"
            onClick={() => setDismissed(true)}
            className="text-yellow-900 hover:text-yellow-700"
          >
            ×
          </button>
        </div>
      </div>
    </div>
  );
}
