import { describe, it, expect } from 'vitest';
import {
  logActivityDoneSchema,
  newCostSchema,
  newHarvestSchema,
} from '@/lib/zodSchemas';

describe('logActivityDoneSchema', () => {
  it('accepts empty object (notes optional)', () => {
    expect(logActivityDoneSchema.safeParse({}).success).toBe(true);
  });
  it('accepts a notes string', () => {
    expect(logActivityDoneSchema.safeParse({ completion_notes: 'Did it' }).success).toBe(true);
  });
  it('rejects notes over 2000 chars', () => {
    expect(
      logActivityDoneSchema.safeParse({ completion_notes: 'x'.repeat(2001) }).success,
    ).toBe(false);
  });
});

describe('newCostSchema', () => {
  const valid = {
    category: 'seed' as const,
    description: 'Tylka F1',
    amount_kes: 4500,
    incurred_at: '2026-05-10',
    supplier_name: 'Elgon Kenya',
  };

  it('accepts valid', () => {
    expect(newCostSchema.safeParse(valid).success).toBe(true);
  });

  it('coerces a string amount', () => {
    const out = newCostSchema.safeParse({ ...valid, amount_kes: '4500' });
    expect(out.success).toBe(true);
    if (out.success) expect(out.data.amount_kes).toBe(4500);
  });

  it('rejects negative amount', () => {
    expect(newCostSchema.safeParse({ ...valid, amount_kes: -10 }).success).toBe(false);
  });

  it('rejects bad category', () => {
    const bad = { ...valid, category: 'random' as 'seed' };
    expect(newCostSchema.safeParse(bad).success).toBe(false);
  });

  it('rejects empty description', () => {
    expect(newCostSchema.safeParse({ ...valid, description: '' }).success).toBe(false);
  });
});

describe('newHarvestSchema', () => {
  const valid = {
    harvested_at: '2026-04-15',
    quantity_kg: 100,
    sold_quantity_kg: 80,
    unit_price_kes: 60,
    buyer_name: 'Marikiti',
    notes: 'Good pick',
  };

  it('accepts valid', () => {
    expect(newHarvestSchema.safeParse(valid).success).toBe(true);
  });

  it('rejects sold > picked', () => {
    const out = newHarvestSchema.safeParse({ ...valid, sold_quantity_kg: 200 });
    expect(out.success).toBe(false);
    if (!out.success) {
      expect(out.error.issues.some((i) => i.path.includes('sold_quantity_kg'))).toBe(true);
    }
  });

  it('rejects sold without unit_price', () => {
    const { unit_price_kes: _omit, ...rest } = valid;
    void _omit;
    const out = newHarvestSchema.safeParse(rest);
    expect(out.success).toBe(false);
    if (!out.success) {
      expect(out.error.issues.some((i) => i.path.includes('unit_price_kes'))).toBe(true);
    }
  });

  it('accepts a picked-only entry (own consumption, no sale)', () => {
    expect(newHarvestSchema.safeParse({ harvested_at: '2026-04-15', quantity_kg: 50 }).success).toBe(true);
  });

  it('rejects negative picked', () => {
    expect(newHarvestSchema.safeParse({ ...valid, quantity_kg: -1 }).success).toBe(false);
  });
});
