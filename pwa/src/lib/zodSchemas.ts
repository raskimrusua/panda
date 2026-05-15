import { z } from 'zod';

// All schemas keep field names matching the DRF/Laravel resource shapes
// exactly so a typo never silently re-keys a payload.

export const loginSchema = z.object({
  email: z.string().email('Enter a valid email'),
  password: z.string().min(1, 'Password is required'),
});

export const registerSchema = z
  .object({
    farm_name: z.string().min(2, 'Farm name is too short'),
    county: z.string().min(2, 'Choose a county'),
    sub_county: z.string().optional(),
    name: z.string().min(2, 'Your name is too short'),
    email: z.string().email('Enter a valid email'),
    password: z
      .string()
      .min(8, 'At least 8 characters')
      .regex(/[A-Za-z]/, 'Must include a letter')
      .regex(/[0-9]/, 'Must include a number'),
    password_confirmation: z.string(),
    // Kenya DPA 2019 §30 — informed + specific consent. Schema mirrors
    // the wire shape so a false value blocks submit before the network
    // call. Booleans (not z.literal(true)) so defaultValues can start
    // unchecked without a type cast; the .refine() below enforces true.
    terms_accepted: z.boolean(),
    privacy_accepted: z.boolean(),
  })
  .refine((d) => d.password === d.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match',
  })
  .refine((d) => d.terms_accepted === true, {
    path: ['terms_accepted'],
    message: 'You must accept the Terms of Service to continue.',
  })
  .refine((d) => d.privacy_accepted === true, {
    path: ['privacy_accepted'],
    message: 'You must accept the Privacy Policy to continue.',
  });

export const newSeasonSchema = z.object({
  crop_id: z.string().min(1, 'Choose a crop'),
  acreage: z.coerce
    .number({ invalid_type_error: 'Enter a number' })
    .positive('Must be positive')
    .max(1000, 'Too large'),
  planting_date: z.string().min(1, 'Pick a date'),
  irrigation_type: z.enum(['rainfed', 'drip', 'furrow', 'greenhouse']),
  status: z.enum(['planning', 'active']).default('planning'),
});

export const logActivityDoneSchema = z.object({
  completion_notes: z.string().max(2000).optional(),
  completed_at: z.string().optional(),
});

export const newCostSchema = z.object({
  category: z.enum([
    'seed',
    'fertiliser',
    'chemical',
    'labour',
    'equipment',
    'transport',
    'other',
  ]),
  description: z.string().min(1, 'Describe the cost').max(200),
  amount_kes: z.coerce
    .number({ invalid_type_error: 'Enter a number' })
    .positive('Must be positive')
    .max(9_999_999.99, 'Too large'),
  incurred_at: z.string().min(1, 'Pick a date'),
  supplier_name: z.string().max(120).optional(),
});

export const newHarvestSchema = z
  .object({
    harvested_at: z.string().min(1, 'Pick a date'),
    quantity_kg: z.coerce
      .number({ invalid_type_error: 'Enter a number' })
      .positive('Must be positive')
      .max(9_999_999.99, 'Too large'),
    sold_quantity_kg: z.coerce
      .number({ invalid_type_error: 'Enter a number' })
      .min(0, 'Cannot be negative')
      .optional(),
    unit_price_kes: z.coerce
      .number({ invalid_type_error: 'Enter a number' })
      .min(0, 'Cannot be negative')
      .optional(),
    buyer_name: z.string().max(120).optional(),
    notes: z.string().max(2000).optional(),
  })
  .refine(
    (d) => (d.sold_quantity_kg ?? 0) <= d.quantity_kg,
    { path: ['sold_quantity_kg'], message: 'Cannot exceed total picked' },
  )
  .refine(
    (d) => !d.sold_quantity_kg || d.unit_price_kes !== undefined,
    { path: ['unit_price_kes'], message: 'Price is required when you sold any' },
  );

export type LoginValues = z.infer<typeof loginSchema>;
export type RegisterValues = z.infer<typeof registerSchema>;
export type NewSeasonValues = z.infer<typeof newSeasonSchema>;
export type LogActivityDoneValues = z.infer<typeof logActivityDoneSchema>;
export type NewCostValues = z.infer<typeof newCostSchema>;
export type NewHarvestValues = z.infer<typeof newHarvestSchema>;
