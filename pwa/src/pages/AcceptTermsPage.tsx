import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { authApi, type ActivePolicies } from '@/api/auth';

/**
 * Reconsent landing — reached when ConsentGate emits 409
 * TERMS_VERSION_OUTDATED on a non-policy endpoint. The user must
 * re-acknowledge the current Terms + Privacy before any other action
 * succeeds. Kenya DPA 2019 §31 — consent must be re-collected when
 * the lawful-basis text changes.
 *
 * The page is reachable while logged in (the user has a valid token,
 * but their stored versions are stale). It is NOT wrapped in the
 * normal AppShell because the surrounding nav may itself call gated
 * endpoints and trigger redirect loops.
 */
export function AcceptTermsPage() {
  const [policies, setPolicies] = useState<ActivePolicies | null>(null);
  const [termsChecked, setTermsChecked] = useState(false);
  const [privacyChecked, setPrivacyChecked] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    authApi.activePolicies()
      .then(setPolicies)
      .catch(() => setError('Could not load the current policies. Please refresh.'));
  }, []);

  const onAccept = async () => {
    if (!policies || !termsChecked || !privacyChecked) return;
    setSubmitting(true);
    setError(null);
    try {
      await authApi.acceptPolicies({
        terms_version: policies.terms.version,
        privacy_version: policies.privacy.version,
        terms_accepted: true,
        privacy_accepted: true,
      });
      const params = new URLSearchParams(window.location.search);
      const next = params.get('next') || '/';
      window.location.href = next;
    } catch (e: unknown) {
      const msg =
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message
        ?? 'Could not record your acceptance. Please try again.';
      setError(msg);
      setSubmitting(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-8">
      <div className="w-full max-w-lg rounded-lg bg-white p-6 shadow-sm border border-gray-200">
        <h1 className="text-2xl font-semibold text-brand-700">We updated our policies</h1>
        <p className="mt-2 text-gray-700">
          We have updated our Terms of Service and Privacy Policy. Please review
          and accept the new versions to continue using Panda.
        </p>

        {!policies && !error && (
          <p className="mt-4 text-sm text-gray-500">Loading current policies…</p>
        )}

        {policies && (
          <div className="mt-5 space-y-3">
            <label className="flex items-start gap-2 text-sm text-gray-800">
              <input
                type="checkbox"
                className="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                checked={termsChecked}
                onChange={(e) => setTermsChecked(e.target.checked)}
              />
              <span>
                I accept the{' '}
                <a
                  href={policies.terms.url}
                  target="_blank"
                  rel="noreferrer"
                  className="text-brand-700 font-medium underline"
                >
                  Terms of Service ({policies.terms.version})
                </a>
                .
              </span>
            </label>
            <label className="flex items-start gap-2 text-sm text-gray-800">
              <input
                type="checkbox"
                className="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                checked={privacyChecked}
                onChange={(e) => setPrivacyChecked(e.target.checked)}
              />
              <span>
                I accept the{' '}
                <a
                  href={policies.privacy.url}
                  target="_blank"
                  rel="noreferrer"
                  className="text-brand-700 font-medium underline"
                >
                  Privacy Policy ({policies.privacy.version})
                </a>{' '}
                (Kenya DPA 2019).
              </span>
            </label>
          </div>
        )}

        {error && (
          <p className="mt-4 text-sm text-danger-600" role="alert">
            {error}
          </p>
        )}

        <Button
          type="button"
          className="mt-6 w-full"
          loading={submitting}
          disabled={!policies || !termsChecked || !privacyChecked}
          onClick={onAccept}
        >
          Accept and continue
        </Button>
      </div>
    </div>
  );
}
