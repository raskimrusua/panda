import { apiClient } from './client';
import type { PaginatedResponse } from './types';

export type CostCategory =
  | 'seed'
  | 'fertiliser'
  | 'chemical'
  | 'labour'
  | 'equipment'
  | 'transport'
  | 'other';

export interface CostEntry {
  id: string;
  season_id: string;
  input_list_item_id: string | null;
  category: CostCategory;
  description: string;
  amount_kes: string;
  incurred_at: string;
  supplier_name: string | null;
  receipt_url: string | null;
  created_at: string;
}

export interface CreateCostPayload {
  season_id: string;
  input_list_item_id?: string;
  category: CostCategory;
  description: string;
  amount_kes: number;
  incurred_at: string;
  supplier_name?: string;
}

export interface SeasonCostsResponse {
  data: CostEntry[];
  totals: {
    by_category: Record<string, number>;
    all_kes: number;
  };
}

export const costsApi = {
  async list(): Promise<PaginatedResponse<CostEntry>> {
    const { data } = await apiClient.get<PaginatedResponse<CostEntry>>('/costs');
    return data;
  },

  async forSeason(seasonId: string): Promise<SeasonCostsResponse> {
    const { data } = await apiClient.get<SeasonCostsResponse>(`/seasons/${seasonId}/costs`);
    return data;
  },

  async create(payload: CreateCostPayload): Promise<{ data: CostEntry }> {
    const { data } = await apiClient.post<{ data: CostEntry }>('/costs', payload);
    return data;
  },

  async destroy(id: string): Promise<void> {
    await apiClient.delete(`/costs/${id}`);
  },
};
