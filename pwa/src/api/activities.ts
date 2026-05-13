import { apiClient } from './client';
import type { SeasonActivity } from './types';

export interface LogDonePayload {
  completion_notes?: string;
  completed_at?: string;
}

export const activitiesApi = {
  async logDone(id: string, payload: LogDonePayload = {}): Promise<{ data: SeasonActivity }> {
    const { data } = await apiClient.post<{ data: SeasonActivity }>(
      `/activities/${id}/log-done`,
      payload,
    );
    return data;
  },
};
