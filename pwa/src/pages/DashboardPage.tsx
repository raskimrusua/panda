import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Sprout, Plus } from 'lucide-react';
import { useAuth } from '@/auth/useAuth';
import { buttonClasses } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { seasonsApi } from '@/api/seasons';

export function DashboardPage() {
  const { user } = useAuth();

  const { data, isLoading } = useQuery({
    queryKey: ['seasons'],
    queryFn: () => seasonsApi.list(),
  });

  const seasons = data?.data ?? [];
  const activeCount = seasons.filter((s) => s.status === 'active' || s.status === 'harvesting').length;

  return (
    <div className="space-y-6">
      <header className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Karibu, {user?.name?.split(' ')[0]}.</h1>
          <p className="text-gray-600">Here&rsquo;s what&rsquo;s happening on the farm.</p>
        </div>
        <Link to="/seasons/new" className={`${buttonClasses('primary', 'md')} gap-1`}>
          <Plus className="h-4 w-4" /> New season
        </Link>
      </header>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <Sprout className="h-8 w-8 text-brand-600" />
              <div>
                <div className="text-2xl font-semibold">{isLoading ? '…' : seasons.length}</div>
                <div className="text-sm text-gray-600">Total seasons</div>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="text-2xl font-semibold">{isLoading ? '…' : activeCount}</div>
            <div className="text-sm text-gray-600">Active right now</div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="text-2xl font-semibold">{user?.tenant?.county ?? '—'}</div>
            <div className="text-sm text-gray-600">County</div>
          </CardBody>
        </Card>
      </div>

      {!isLoading && seasons.length === 0 && (
        <Card>
          <CardBody className="text-center py-12">
            <Sprout className="h-12 w-12 text-brand-500 mx-auto mb-3" />
            <h2 className="text-lg font-medium">No seasons yet</h2>
            <p className="text-gray-600 mt-1 mb-4">
              Plan your first one &mdash; pick a crop, your acreage, and a planting date.
              Panda will draft the timeline + input list for you.
            </p>
            <Link to="/seasons/new" className={`${buttonClasses('primary', 'md')} gap-1`}>
              <Plus className="h-4 w-4" /> Plan a season
            </Link>
          </CardBody>
        </Card>
      )}
    </div>
  );
}
