import { describe, it, expect } from 'vitest';
import { cn, confidenceBand, formatDate, formatKes } from '@/lib/utils';

describe('cn', () => {
  it('merges Tailwind classes with twMerge precedence', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
    expect(cn('text-red-500', null, undefined, 'font-bold')).toBe('text-red-500 font-bold');
    expect(cn({ 'text-blue-500': true, hidden: false })).toBe('text-blue-500');
  });
});

describe('formatKes', () => {
  // Intl en-KE renders the currency symbol as 'Ksh' (the local symbol),
  // not the ISO 'KES' code — match what farmers see.
  it('formats positive numbers with thousands separator + a KES symbol', () => {
    expect(formatKes(4500)).toContain('4,500');
    expect(formatKes(4500)).toMatch(/Ksh|KES/);
  });

  it('formats decimal strings', () => {
    expect(formatKes('1234.56')).toContain('1,234.56');
  });

  it('returns dash for null / undefined / empty / NaN', () => {
    expect(formatKes(null)).toBe('—');
    expect(formatKes(undefined)).toBe('—');
    expect(formatKes('')).toBe('—');
    expect(formatKes('abc')).toBe('—');
  });
});

describe('formatDate', () => {
  // happy-dom's default TZ may shift the date by ±1 day vs UTC midnight.
  // Asserting month + year only avoids a global TZ pin in tests.
  it('formats ISO date as a locale-readable string', () => {
    expect(formatDate('2026-06-15')).toMatch(/Jun 2026/);
  });

  it('returns dash for null / empty', () => {
    expect(formatDate(null)).toBe('—');
    expect(formatDate(undefined)).toBe('—');
    expect(formatDate('')).toBe('—');
  });
});

describe('confidenceBand', () => {
  it('bands high / medium / low at the documented thresholds', () => {
    expect(confidenceBand(0.92)).toBe('high');
    expect(confidenceBand(0.85)).toBe('high');
    expect(confidenceBand(0.84)).toBe('medium');
    expect(confidenceBand(0.7)).toBe('medium');
    expect(confidenceBand(0.69)).toBe('low');
    expect(confidenceBand(0.3)).toBe('low');
  });

  it('returns null for missing or NaN confidence', () => {
    expect(confidenceBand(null)).toBeNull();
    expect(confidenceBand(undefined)).toBeNull();
    expect(confidenceBand(Number.NaN)).toBeNull();
  });
});
