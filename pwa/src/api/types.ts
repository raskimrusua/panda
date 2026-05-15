// Shared API types — match the Laravel resource shapes exactly.

export interface Tenant {
  id: string;
  name: string;
  slug: string;
  county: string;
  sub_county: string | null;
  ward: string | null;
  gps_lat: number | null;
  gps_lng: number | null;
}

export interface User {
  id: string;
  name: string;
  email: string;
  email_verified_at: string | null;
  tenant_id: string;
  tenant?: Tenant;
}

export interface Crop {
  id: string;
  slug: string;
  name_en: string;
  name_sw: string;
  category: string;
  harvest_type: 'single' | 'multi';
  image_url: string | null;
  jica_manual_ref: string | null;
  phase_added: number;
  is_active: boolean;
}

export type SeasonStatus = 'planning' | 'active' | 'harvesting' | 'complete' | 'abandoned';
export type IrrigationType = 'rainfed' | 'drip' | 'furrow' | 'greenhouse';

export interface Season {
  id: string;
  crop_id: string;
  crop_name?: string;
  crop?: Crop;
  acreage: string;
  planting_date: string;
  status: SeasonStatus;
  irrigation_type: IrrigationType;
  engine_metadata?: Record<string, unknown>;
  client_id: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface SeasonActivity {
  id: string;
  season_id: string;
  activity_type: string;
  phase: string;
  ideal_date: string;
  week_from_planting: number;
  day_window: number;
  description_en: string;
  description_sw: string;
  tip_en: string | null;
  tip_sw: string | null;
  is_critical: boolean;
  status: 'pending' | 'done' | 'skipped' | 'overdue';
  completed_at: string | null;
  completion_notes: string | null;
}

export interface InputListItem {
  id: string;
  season_id: string;
  input_type: string;
  product_name: string;
  quantity_per_acre: string;
  quantity_scaled: string;
  unit: string;
  week_from_planting: number;
  benchmark_price_kes: string | null;
  cost_estimate_kes: string | null;
  pcpb_registered: boolean;
  alternatives: string[] | null;
  procured_quantity: string | null;
  procured_at: string | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  links?: Record<string, string | null>;
  meta?: { current_page: number; last_page: number; total: number; per_page: number };
}
