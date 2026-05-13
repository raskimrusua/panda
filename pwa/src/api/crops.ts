import { apiClient } from './client';
import type { Crop, PaginatedResponse } from './types';

export const cropsApi = {
  async list(): Promise<PaginatedResponse<Crop>> {
    const { data } = await apiClient.get<PaginatedResponse<Crop>>('/crops', {
      params: { active_only: 1, per_page: 50 },
    });
    return data;
  },
};
