/**
 * One queued mutation. Stored in IndexedDB until the device is online
 * AND the request succeeds. The backend is idempotent on `client_id`
 * (offline-sync key on Season + HarvestLog) so a re-send after a flaky
 * network never duplicates.
 */
export interface QueuedMutation {
  id: string;
  client_id: string;
  type: 'log_activity_done' | 'log_cost' | 'log_harvest';
  url: string;
  method: 'POST' | 'PATCH';
  payload: Record<string, unknown>;
  invalidate_keys: string[][];
  enqueued_at: number;
  attempts: number;
  last_error: string | null;
}

export type SyncStatus = 'idle' | 'syncing' | 'error';

export interface OfflineQueueState {
  pending: number;
  online: boolean;
  syncing: boolean;
  lastError: string | null;
}
