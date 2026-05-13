import { get, set, del, keys } from 'idb-keyval';
import type { QueuedMutation } from './types';

// All queued mutations live under one key prefix so we can list them by
// scanning IDB keys without a separate index. Pilot volume is fine —
// a farmer's offline session creates dozens, not thousands.
const PREFIX = 'panda-offline-';

export const offlineDb = {
  async push(m: QueuedMutation): Promise<void> {
    await set(PREFIX + m.id, m);
  },

  async update(m: QueuedMutation): Promise<void> {
    await set(PREFIX + m.id, m);
  },

  async remove(id: string): Promise<void> {
    await del(PREFIX + id);
  },

  async list(): Promise<QueuedMutation[]> {
    const allKeys = await keys();
    const ours = allKeys.filter(
      (k): k is string => typeof k === 'string' && k.startsWith(PREFIX),
    );
    const items = await Promise.all(ours.map((k) => get<QueuedMutation>(k)));
    return items.filter((x): x is QueuedMutation => !!x).sort((a, b) => a.enqueued_at - b.enqueued_at);
  },

  async clear(): Promise<void> {
    const all = await this.list();
    await Promise.all(all.map((m) => this.remove(m.id)));
  },
};

export function makeUlid(): string {
  // Browser-safe ULID-ish id: timestamp + random. Real ULID would be
  // crockford-base32; the backend just needs a stable unique string.
  const t = Date.now().toString(36);
  const r = Math.random().toString(36).slice(2, 12);
  return `${t}${r}`.padEnd(26, '0').slice(0, 26).toUpperCase();
}
