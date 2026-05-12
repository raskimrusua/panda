import { apiClient } from './client';

export interface HarvestLog {
  id: string;
  season_id: string;
  harvested_at: string;
  quantity_kg: string;
  sold_quantity_kg: string;
  unit_price_kes: string | null;
  revenue_kes: number;
  buyer_name: string | null;
  notes: string | null;
  photo_url: string | null;
  created_at: string;
}

export interface CreateHarvestPayload {
  season_id: string;
  harvested_at: string;
  quantity_kg: number;
  sold_quantity_kg?: number;
  unit_price_kes?: number;
  buyer_name?: string;
  notes?: string;
  client_id?: string;
}

export interface SeasonHarvestsResponse {
  data: HarvestLog[];
  totals: {
    quantity_kg: number;
    sold_quantity_kg: number;
    revenue_kes: number;
  };
}

export const harvestsApi = {
  async forSeason(seasonId: string): Promise<SeasonHarvestsResponse> {
    const { data } = await apiClient.get<SeasonHarvestsResponse>(
      `/seasons/${seasonId}/harvests`,
    );
    return data;
  },

  async create(payload: CreateHarvestPayload): Promise<{ data: HarvestLog }> {
    const { data } = await apiClient.post<{ data: HarvestLog }>('/harvests', payload);
    return data;
  },

  async destroy(id: string): Promise<void> {
    await apiClient.delete(`/harvests/${id}`);
  },
};
