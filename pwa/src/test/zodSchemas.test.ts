import { describe, it, expect } from 'vitest';
import { loginSchema, registerSchema, newSeasonSchema } from '@/lib/zodSchemas';

describe('loginSchema', () => {
  it('accepts a valid email + password', () => {
    expect(loginSchema.safeParse({ email: 'a@b.com', password: 'x' }).success).toBe(true);
  });
  it('rejects bad email', () => {
    expect(loginSchema.safeParse({ email: 'nope', password: 'x' }).success).toBe(false);
  });
  it('rejects empty password', () => {
    expect(loginSchema.safeParse({ email: 'a@b.com', password: '' }).success).toBe(false);
  });
});

describe('registerSchema', () => {
  const baseValid = {
    farm_name: 'Mwea',
    county: 'Kirinyaga',
    name: 'Jane',
    email: 'jane@example.com',
    password: 'kale-acres-1',
    password_confirmation: 'kale-acres-1',
  };

  it('accepts a valid payload', () => {
    expect(registerSchema.safeParse(baseValid).success).toBe(true);
  });

  it('rejects mismatched passwords', () => {
    const out = registerSchema.safeParse({ ...baseValid, password_confirmation: 'different' });
    expect(out.success).toBe(false);
  });

  it('rejects weak password (no number)', () => {
    const weak = { ...baseValid, password: 'noNumberHere', password_confirmation: 'noNumberHere' };
    expect(registerSchema.safeParse(weak).success).toBe(false);
  });

  it('rejects short farm name', () => {
    expect(registerSchema.safeParse({ ...baseValid, farm_name: 'A' }).success).toBe(false);
  });
});

describe('newSeasonSchema', () => {
  const baseValid = {
    crop_id: '01ABCDEFGHJKMNPQRSTVWXYZ00',
    acreage: 1.5,
    planting_date: '2026-06-15',
    irrigation_type: 'rainfed' as const,
    status: 'planning' as const,
  };

  it('accepts a valid payload', () => {
    expect(newSeasonSchema.safeParse(baseValid).success).toBe(true);
  });

  it('coerces a string acreage from a form', () => {
    const out = newSeasonSchema.safeParse({ ...baseValid, acreage: '2.5' });
    expect(out.success).toBe(true);
    if (out.success) expect(out.data.acreage).toBe(2.5);
  });

  it('rejects negative acreage', () => {
    expect(newSeasonSchema.safeParse({ ...baseValid, acreage: -1 }).success).toBe(false);
  });

  it('rejects invalid irrigation type', () => {
    const bad = { ...baseValid, irrigation_type: 'wrong' as 'rainfed' };
    expect(newSeasonSchema.safeParse(bad).success).toBe(false);
  });
});
