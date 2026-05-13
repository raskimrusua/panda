import { useContext } from 'react';
import { OfflineQueueContext } from './OfflineQueueContext';

export function useOfflineQueue() {
  const ctx = useContext(OfflineQueueContext);
  if (!ctx) throw new Error('useOfflineQueue must be inside <OfflineQueueProvider>');
  return ctx;
}
