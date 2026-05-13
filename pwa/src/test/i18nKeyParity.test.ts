import { describe, it, expect } from 'vitest';
import en from '@/i18n/locales/en.json';
import sw from '@/i18n/locales/sw.json';

/**
 * Walks both locale trees in parallel and emits the dotted key paths for
 * any leaf that exists in one and not the other. A future translator
 * can run `npm test` and immediately see which keys still need work.
 */
function flatten(obj: unknown, prefix = ''): string[] {
  if (obj === null || typeof obj !== 'object') return [];
  const out: string[] = [];
  for (const [k, v] of Object.entries(obj as Record<string, unknown>)) {
    const path = prefix ? `${prefix}.${k}` : k;
    if (typeof v === 'string') out.push(path);
    else out.push(...flatten(v, path));
  }
  return out.sort();
}

describe('i18n key parity', () => {
  it('en and sw locales have identical key sets', () => {
    const enKeys = flatten(en);
    const swKeys = flatten(sw);
    const onlyInEn = enKeys.filter((k) => !swKeys.includes(k));
    const onlyInSw = swKeys.filter((k) => !enKeys.includes(k));
    expect(onlyInEn).toEqual([]);
    expect(onlyInSw).toEqual([]);
  });

  it('every value is a non-empty string', () => {
    for (const locale of [en, sw]) {
      const keys = flatten(locale);
      for (const k of keys) {
        const v = k.split('.').reduce<unknown>(
          (acc, part) => (acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[part] : undefined),
          locale,
        );
        expect(typeof v).toBe('string');
        expect((v as string).length).toBeGreaterThan(0);
      }
    }
  });
});
