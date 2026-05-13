import { useTranslation } from 'react-i18next';
import { Globe } from 'lucide-react';

export function LanguageSwitcher() {
  const { i18n, t } = useTranslation();

  const lang = i18n.resolvedLanguage ?? 'en';

  const onChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    void i18n.changeLanguage(e.target.value);
  };

  return (
    <div className="flex items-center gap-2">
      <Globe className="h-4 w-4 text-gray-500" aria-hidden />
      <label htmlFor="lang-switcher" className="sr-only">{t('common.language')}</label>
      <select
        id="lang-switcher"
        value={lang}
        onChange={onChange}
        className="text-sm bg-transparent border-0 focus:outline-none focus:ring-0 cursor-pointer"
      >
        <option value="en">English</option>
        <option value="sw">Kiswahili</option>
      </select>
    </div>
  );
}
