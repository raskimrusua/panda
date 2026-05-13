import { apiClient } from './client';
import type { PaginatedResponse } from './types';

export interface Dealer {
  id: string;
  name: string;
  slug: string;
  county: string;
  sub_county: string | null;
  town: string | null;
  gps_lat: number;
  gps_lng: number;
  phone: string | null;
  whatsapp: string | null;
  website: string | null;
  stocks: string[];
  is_pcpb_certified: boolean;
  distance_km: number | null;
}

export interface DealerSearchParams {
  lat?: number;
  lng?: number;
  radius_km?: number;
  county?: string;
  stocks?: 'seed' | 'fertiliser' | 'chemical' | 'equipment';
  pcpb_only?: boolean;
  per_page?: number;
}

export const dealersApi = {
  async search(params: DealerSearchParams = {}): Promise<PaginatedResponse<Dealer>> {
    const { data } = await apiClient.get<PaginatedResponse<Dealer>>('/dealers', { params });
    return data;
  },
};
