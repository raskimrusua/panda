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
  })
  .refine((d) => d.password === d.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match',
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

export type LoginValues = z.infer<typeof loginSchema>;
export type RegisterValues = z.infer<typeof registerSchema>;
export type NewSeasonValues = z.infer<typeof newSeasonSchema>;
