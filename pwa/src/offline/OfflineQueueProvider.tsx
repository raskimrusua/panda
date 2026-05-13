import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { offlineQueue } from './queue';
import { OfflineQueueContext, type OfflineQueueContextValue } from './OfflineQueueContext';

/**
 * Polls the queue (cheap — pure IDB scan) on mount + on `online` event +
 * on a 30-sec interval. Replays whenever online with pending items, with
 * a debounce so a flaky connection doesn't fire repeated waves.
 */
export function OfflineQueueProvider({ children }: { children: ReactNode }) {
  const qc = useQueryClient();
  const [pending, setPending] = useState(0);
  const [online, setOnline] = useState(typeof navigator === 'undefined' ? true : navigator.onLine);
  const [syncing, setSyncing] = useState(false);
  const [lastError, setLastError] = useState<string | null>(null);
  const inFlight = useRef(false);

  const refresh = useCallback(async () => {
    const items = await offlineQueue.list();
    setPending(items.length);
  }, []);

  const syncNow = useCallback(async () => {
    if (inFlight.current) return;
    if (typeof navigator !== 'undefined' && !navigator.onLine) return;

    inFlight.current = true;
    setSyncing(true);
    setLastError(null);
    try {
      const result = await offlineQueue.replay();
      // Invalidate every distinct query key referenced by replayed items.
      // Cheap, and catches any UI that's still showing stale "offline" copy.
      const items = await offlineQueue.list();
      // We invalidate the whole 'seasons' surface — every nested key falls
      // under it (timeline, costs, harvests, inputs, list).
      qc.invalidateQueries({ queryKey: ['seasons'] });
      qc.invalidateQueries({ queryKey: ['disease', 'history'] });
      setPending(items.length);
      if (result.failed.length > 0) {
        const first = result.failed[0];
        if (first) setLastError(first.error);
      }
    } catch (err) {
      setLastError((err as Error)?.message ?? 'Sync failed');
    } finally {
      setSyncing(false);
      inFlight.current = false;
    }
  }, [qc]);

  // Online/offline listeners
  useEffect(() => {
    const onOnline = () => {
      setOnline(true);
      // Small debounce so a flapping connection doesn't fire a wave.
      window.setTimeout(() => {
        void syncNow();
      }, 500);
    };
    const onOffline = () => setOnline(false);
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
    return () => {
      window.removeEventListener('online', onOnline);
      window.removeEventListener('offline', onOffline);
    };
  }, [syncNow]);

  // Initial scan + periodic refresh while there are pending items.
  useEffect(() => {
    void refresh();
    void syncNow();
    const i = window.setInterval(() => {
      void refresh();
      if (online) void syncNow();
    }, 30_000);
    return () => window.clearInterval(i);
  }, [refresh, syncNow, online]);

  const value: OfflineQueueContextValue = useMemo(
    () => ({ pending, online, syncing, lastError, refresh, syncNow }),
    [pending, online, syncing, lastError, refresh, syncNow],
  );

  return <OfflineQueueContext.Provider value={value}>{children}</OfflineQueueContext.Provider>;
}
