import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Plus } from 'lucide-react';
import { buttonClasses } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { seasonsApi } from '@/api/seasons';
import { formatDate } from '@/lib/utils';
import type { Season } from '@/api/types';

const STATUS_BADGE: Record<Season['status'], string> = {
  planning: 'bg-gray-100 text-gray-700',
  active: 'bg-brand-100 text-brand-800',
  harvesting: 'bg-warn-500/10 text-warn-600',
  complete: 'bg-blue-50 text-blue-700',
  abandoned: 'bg-danger-500/10 text-danger-600',
};

export function SeasonListPage() {
  const { t } = useTranslation();
  const { data, isLoading, error } = useQuery({
    queryKey: ['seasons'],
    queryFn: () => seasonsApi.list(),
  });

  return (
    <div className="space-y-4">
      <header className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">{t('seasons.title')}</h1>
        <Link to="/seasons/new" className={`${buttonClasses('primary', 'md')} gap-1`}>
          <Plus className="h-4 w-4" /> {t('dashboard.new_season')}
        </Link>
      </header>

      {isLoading && <p className="text-gray-500">{t('common.loading')}</p>}
      {error && <p className="text-danger-600">{t('seasons.could_not_load')}</p>}

      {data && data.data.length === 0 && (
        <Card>
          <CardBody className="text-center py-12">
            <p className="text-gray-600">{t('seasons.none_yet')}</p>
          </CardBody>
        </Card>
      )}

      {data && data.data.length > 0 && (
        <div className="grid grid-cols-1 gap-3">
          {data.data.map((season) => (
            <Link
              key={season.id}
              to={`/seasons/${season.id}`}
              className="block transition-shadow hover:shadow-md"
            >
              <Card>
                <CardBody className="flex items-center justify-between">
                  <div>
                    <div className="font-medium">
                      {season.crop_name ?? season.crop?.name_en ?? t('seasons.crop')}
                    </div>
                    <div className="text-sm text-gray-600">
                      {Number(season.acreage)} {t('seasons.acreage').toLowerCase()} ·{' '}
                      {formatDate(season.planting_date)}
                    </div>
                  </div>
                  <span
                    className={`text-xs px-2 py-1 rounded-full font-medium ${STATUS_BADGE[season.status]}`}
                  >
                    {season.status}
                  </span>
                </CardBody>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
