import { apiClient } from './client';
import type {
  InputListItem,
  PaginatedResponse,
  Season,
  SeasonActivity,
} from './types';

export interface CreateSeasonPayload {
  crop_id: string;
  acreage: number;
  planting_date: string;
  status?: Season['status'];
  irrigation_type?: Season['irrigation_type'];
  client_id?: string;
}

export const seasonsApi = {
  async list(): Promise<PaginatedResponse<Season>> {
    const { data } = await apiClient.get<PaginatedResponse<Season>>('/seasons');
    return data;
  },

  async get(id: string): Promise<{ data: Season }> {
    const { data } = await apiClient.get<{ data: Season }>(`/seasons/${id}`);
    return data;
  },

  async create(payload: CreateSeasonPayload): Promise<{ data: Season }> {
    const { data } = await apiClient.post<{ data: Season }>('/seasons', payload);
    return data;
  },

  async timeline(id: string): Promise<{ data: SeasonActivity[] }> {
    const { data } = await apiClient.get<{ data: SeasonActivity[] }>(
      `/seasons/${id}/timeline`,
    );
    return data;
  },

  async inputList(id: string): Promise<{ data: InputListItem[] }> {
    const { data } = await apiClient.get<{ data: InputListItem[] }>(
      `/seasons/${id}/input-list`,
    );
    return data;
  },
};
