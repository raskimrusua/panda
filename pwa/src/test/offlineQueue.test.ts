import { describe, it, expect, beforeEach, vi } from 'vitest';
import { offlineDb, makeUlid } from '@/offline/db';
import { offlineQueue } from '@/offline/queue';

// Stub the apiClient so replay() can simulate success / network error / 4xx
vi.mock('@/api/client', () => ({
  apiClient: {
    request: vi.fn(),
  },
  tokenStore: { get: vi.fn(), set: vi.fn(), clear: vi.fn() },
}));

import { apiClient } from '@/api/client';
const mockedRequest = apiClient.request as unknown as ReturnType<typeof vi.fn>;

beforeEach(async () => {
  await offlineDb.clear();
  mockedRequest.mockReset();
});

describe('makeUlid', () => {
  it('returns a 26-char string', () => {
    const id = makeUlid();
    expect(id).toHaveLength(26);
  });
  it('returns different ids on consecutive calls', () => {
    const a = makeUlid();
    const b = makeUlid();
    expect(a).not.toBe(b);
  });
});

describe('offlineQueue.enqueue', () => {
  it('persists a mutation with a generated client_id', async () => {
    const m = await offlineQueue.enqueue({
      type: 'log_cost',
      url: '/costs',
      method: 'POST',
      payload: { season_id: 'X', amount_kes: 100 },
      invalidate_keys: [['seasons', 'X', 'costs']],
    });
    expect(m.client_id).toHaveLength(26);
    expect((m.payload as { client_id: string }).client_id).toBe(m.client_id);

    const all = await offlineDb.list();
    expect(all).toHaveLength(1);
    expect(all[0]?.type).toBe('log_cost');
  });

  it('preserves a caller-supplied client_id', async () => {
    const m = await offlineQueue.enqueue({
      type: 'log_harvest',
      url: '/harvests',
      method: 'POST',
      payload: {},
      invalidate_keys: [],
      client_id: 'CALLER-ULID-26-CHARS-123456',
    });
    expect(m.client_id).toBe('CALLER-ULID-26-CHARS-123456');
  });
});

describe('offlineQueue.replay', () => {
  it('removes successful mutations from the queue', async () => {
    await offlineQueue.enqueue({
      type: 'log_cost',
      url: '/costs',
      method: 'POST',
      payload: { x: 1 },
      invalidate_keys: [],
    });
    mockedRequest.mockResolvedValue({ data: {} });

    const result = await offlineQueue.replay();
    expect(result.succeeded).toHaveLength(1);
    expect(result.failed).toHaveLength(0);
    const remaining = await offlineDb.list();
    expect(remaining).toHaveLength(0);
  });

  it('keeps mutations on transient (5xx) failure with attempt + last_error stamped', async () => {
    await offlineQueue.enqueue({
      type: 'log_cost',
      url: '/costs',
      method: 'POST',
      payload: {},
      invalidate_keys: [],
    });
    mockedRequest.mockRejectedValue({ response: { status: 500, data: { message: 'Server down' } } });

    const result = await offlineQueue.replay();
    expect(result.failed).toHaveLength(1);
    const remaining = await offlineDb.list();
    expect(remaining).toHaveLength(1);
    expect(remaining[0]?.attempts).toBe(1);
    expect(remaining[0]?.last_error).toContain('Server down');
  });

  it('drops mutations on permanent (422) failure', async () => {
    await offlineQueue.enqueue({
      type: 'log_cost',
      url: '/costs',
      method: 'POST',
      payload: {},
      invalidate_keys: [],
    });
    mockedRequest.mockRejectedValue({ response: { status: 422, data: { message: 'Validation' } } });

    await offlineQueue.replay();
    const remaining = await offlineDb.list();
    expect(remaining).toHaveLength(0);
  });

  it('keeps mutations on 401 (token may refresh) and on 408/429 (transient)', async () => {
    await offlineQueue.enqueue({
      type: 'log_cost', url: '/costs', method: 'POST', payload: {}, invalidate_keys: [],
    });
    await offlineQueue.enqueue({
      type: 'log_cost', url: '/costs', method: 'POST', payload: {}, invalidate_keys: [],
    });

    mockedRequest
      .mockRejectedValueOnce({ response: { status: 401, data: {} } })
      .mockRejectedValueOnce({ response: { status: 429, data: {} } });

    await offlineQueue.replay();
    const remaining = await offlineDb.list();
    expect(remaining).toHaveLength(2);
  });

  it('replays in enqueue order', async () => {
    const a = await offlineQueue.enqueue({
      type: 'log_cost', url: '/a', method: 'POST', payload: {}, invalidate_keys: [],
    });
    // brief delay so enqueued_at differs
    await new Promise((r) => setTimeout(r, 5));
    const b = await offlineQueue.enqueue({
      type: 'log_cost', url: '/b', method: 'POST', payload: {}, invalidate_keys: [],
    });
    mockedRequest.mockResolvedValue({ data: {} });

    await offlineQueue.replay();
    const calls = mockedRequest.mock.calls.map((c) => (c[0] as { url: string }).url);
    expect(calls).toEqual(['/a', '/b']);
    void a; void b;
  });
});
