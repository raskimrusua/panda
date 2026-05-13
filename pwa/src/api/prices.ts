import { apiClient } from './client';

export interface MarketPrice {
  id: string;
  crop_id: string;
  market_name: string;
  county: string;
  observed_at: string;
  grade: string;
  price_per_kg_kes: number;
  source: string;
}

export interface ForecastPoint {
  month: string;
  projected_price_per_kg_kes: number | null;
  method: string;
}

export const pricesApi = {
  async latest(slug: string): Promise<{ data: MarketPrice[]; meta: { crop_slug: string; market_count: number } }> {
    const { data } = await apiClient.get(`/prices/${slug}/latest`);
    return data;
  },

  async history(slug: string): Promise<{ data: MarketPrice[]; meta: { crop_slug: string; observation_count: number; date_range: { start: string | null; end: string | null } } }> {
    const { data } = await apiClient.get(`/prices/${slug}/history`);
    return data;
  },

  async forecast(slug: string): Promise<{ data: ForecastPoint[]; meta: { crop_slug?: string; method: string; history_observations?: number; note: string } }> {
    const { data } = await apiClient.get(`/prices/${slug}/forecast`);
    return data;
  },
};
