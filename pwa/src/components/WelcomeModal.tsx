import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';

const DISMISS_KEY = 'panda.welcome.dismissed.v1';

/**
 * First-run onboarding modal — shown once per device.
 *
 * Three short steps explaining what a season is, what activity logging gets
 * you, and that the app works offline. Dismissed via a single button which
 * sets `panda.welcome.dismissed.v1` in localStorage. Bumping the version
 * suffix on a future change re-shows the modal for everyone.
 *
 * Mounted in AppShell so it appears on the first authenticated page load
 * (Dashboard or Seasons list, depending on landing route).
 */
export function WelcomeModal() {
  const { t } = useTranslation();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (typeof window === 'undefined') return;
    const dismissed = window.localStorage.getItem(DISMISS_KEY);
    if (dismissed !== '1') {
      // Defer one tick so we don't fight the auth-redirect on cold load.
      const timer = window.setTimeout(() => setOpen(true), 250);
      return () => window.clearTimeout(timer);
    }
  }, []);

  const handleClose = () => {
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(DISMISS_KEY, '1');
    }
    setOpen(false);
  };

  return (
    <Modal open={open} onClose={handleClose} title={t('onboarding.welcome_title')}>
      <div className="space-y-4">
        <p className="text-sm text-gray-600">{t('onboarding.welcome_subtitle')}</p>

        <div className="space-y-3">
          <Step title={t('onboarding.step1_title')} body={t('onboarding.step1_body')} />
          <Step title={t('onboarding.step2_title')} body={t('onboarding.step2_body')} />
          <Step title={t('onboarding.step3_title')} body={t('onboarding.step3_body')} />
        </div>

        <div className="flex justify-end pt-2">
          <Button onClick={handleClose}>{t('onboarding.got_it')}</Button>
        </div>
      </div>
    </Modal>
  );
}

function Step({ title, body }: { title: string; body: string }) {
  return (
    <div>
      <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
      <p className="text-sm text-gray-600 mt-0.5">{body}</p>
    </div>
  );
}
