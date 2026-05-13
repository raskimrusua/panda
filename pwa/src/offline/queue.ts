import { apiClient } from '@/api/client';
import { offlineDb, makeUlid } from './db';
import type { QueuedMutation } from './types';

export interface EnqueueInput {
  type: QueuedMutation['type'];
  url: string;
  method: QueuedMutation['method'];
  payload: Record<string, unknown>;
  invalidate_keys: string[][];
  client_id?: string;
}

/**
 * Enqueue a mutation for later sync.
 *
 * The caller decides whether to enqueue (we're offline / first send
 * failed with a network error) or to pass through. This module just
 * persists + replays.
 */
export async function enqueue(input: EnqueueInput): Promise<QueuedMutation> {
  const m: QueuedMutation = {
    id: makeUlid(),
    client_id: input.client_id ?? makeUlid(),
    type: input.type,
    url: input.url,
    method: input.method,
    payload: { ...input.payload, client_id: input.client_id ?? makeUlid() },
    invalidate_keys: input.invalidate_keys,
    enqueued_at: Date.now(),
    attempts: 0,
    last_error: null,
  };
  // Make sure payload.client_id matches the queue's client_id.
  m.payload.client_id = m.client_id;
  await offlineDb.push(m);
  return m;
}

export interface ReplayResult {
  succeeded: string[];
  failed: { id: string; error: string }[];
}

/**
 * Try to send every queued mutation in enqueue order. Successes are
 * removed from the queue; failures stay (with attempt count + last error)
 * so the next replay tries again.
 *
 * Returns ids per outcome so the caller can invalidate the right
 * react-query keys.
 */
export async function replay(): Promise<ReplayResult> {
  const items = await offlineDb.list();
  const succeeded: string[] = [];
  const failed: ReplayResult['failed'] = [];

  for (const m of items) {
    try {
      await apiClient.request({
        url: m.url,
        method: m.method,
        data: m.payload,
      });
      await offlineDb.remove(m.id);
      succeeded.push(m.id);
    } catch (err: unknown) {
      const status = (err as { response?: { status?: number } })?.response?.status;
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        (err as Error)?.message ??
        'Unknown sync error';

      // 4xx (except 401/408/429) is a permanent failure — backend rejected
      // the payload. Drop from queue so the user isn't stuck retrying a
      // bad row forever; surface the error so the UI can show a banner.
      if (status && status >= 400 && status < 500 && status !== 401 && status !== 408 && status !== 429) {
        await offlineDb.remove(m.id);
        failed.push({ id: m.id, error: `${status}: ${message}` });
        continue;
      }

      // Otherwise: transient (network blip, 5xx, 429). Keep in queue.
      m.attempts += 1;
      m.last_error = message;
      await offlineDb.update(m);
      failed.push({ id: m.id, error: message });
    }
  }

  return { succeeded, failed };
}

export const offlineQueue = {
  enqueue,
  replay,
  list: () => offlineDb.list(),
  clear: () => offlineDb.clear(),
};
