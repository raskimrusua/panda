import { useTranslation } from 'react-i18next';
import { CloudOff, Cloud, RefreshCw } from 'lucide-react';
import { useOfflineQueue } from '@/offline/useOfflineQueue';
import { cn } from '@/lib/utils';

export function OnlineIndicator() {
  const { online, syncing, pending, syncNow } = useOfflineQueue();
  const { t } = useTranslation();

  const status = !online ? 'offline' : syncing ? 'syncing' : pending > 0 ? 'pending' : 'online';

  return (
    <button
      type="button"
      onClick={() => void syncNow()}
      disabled={!online || syncing || pending === 0}
      className={cn(
        'flex items-center gap-1.5 text-xs px-2 py-1 rounded-md',
        status === 'online' && 'text-gray-500',
        status === 'syncing' && 'text-warn-600 animate-pulse',
        status === 'pending' && 'text-warn-600 hover:bg-warn-500/10 cursor-pointer',
        status === 'offline' && 'text-danger-600 cursor-not-allowed',
      )}
      title={
        status === 'pending'
          ? t('common.sync_now')
          : status === 'offline'
            ? t('common.offline')
            : t('common.online')
      }
    >
      {!online ? (
        <CloudOff className="h-3.5 w-3.5" />
      ) : syncing ? (
        <RefreshCw className="h-3.5 w-3.5 animate-spin" />
      ) : (
        <Cloud className="h-3.5 w-3.5" />
      )}
      <span>
        {!online && t('common.offline')}
        {online && syncing && t('common.syncing')}
        {online && !syncing && pending > 0 && t('common.pending_sync', { count: pending })}
        {online && !syncing && pending === 0 && t('common.online')}
      </span>
    </button>
  );
}
