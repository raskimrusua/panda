import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { CheckCircle2, XCircle } from 'lucide-react';

/**
 * Landing page the API redirects to after the email-verification link
 * is clicked. The API hits this URL with no query params on success, or
 * with `?error=invalid` (expired / wrong hash / unknown user). The page
 * just renders the appropriate state — no API calls of its own.
 */
export function VerifiedPage() {
  const { t } = useTranslation();
  const [params] = useSearchParams();
  const error = params.get('error');

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-md text-center space-y-4">
        {error ? (
          <>
            <XCircle className="h-16 w-16 text-danger-600 mx-auto" />
            <h1 className="text-2xl font-semibold text-danger-700">{t('auth.verify_failed_title')}</h1>
            <p className="text-gray-600">{t('auth.verify_failed_body')}</p>
          </>
        ) : (
          <>
            <CheckCircle2 className="h-16 w-16 text-brand-700 mx-auto" />
            <h1 className="text-2xl font-semibold text-brand-700">{t('auth.verify_ok_title')}</h1>
            <p className="text-gray-600">{t('auth.verify_ok_body')}</p>
          </>
        )}
        <Link to="/login" className="inline-block text-brand-700 hover:underline font-medium">
          {t('auth.continue_to_signin')}
        </Link>
      </div>
    </div>
  );
}
