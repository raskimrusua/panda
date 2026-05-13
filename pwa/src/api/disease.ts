import { apiClient } from './client';
import type { PaginatedResponse } from './types';

export interface DiseaseTreatment {
  generic: string;
  pcpb: string | null;
  application_notes?: string;
}

export interface DiseaseDetection {
  id: string;
  season_id: string | null;
  crop_id: string | null;
  image_url: string;
  provider: 'mock' | 'crop_health' | 'offline_decision_tree';
  top_diagnosis: string | null;
  confidence: number | null;
  treatments: DiseaseTreatment[] | null;
  opt_in_for_training: boolean;
  captured_at: string | null;
}

export interface DetectPayload {
  image: File;
  crop_id?: string;
  season_id?: string;
  opt_in_for_training?: boolean;
}

export const diseaseApi = {
  async detect(payload: DetectPayload): Promise<{ data: DiseaseDetection }> {
    const fd = new FormData();
    fd.append('image', payload.image);
    if (payload.crop_id) fd.append('crop_id', payload.crop_id);
    if (payload.season_id) fd.append('season_id', payload.season_id);
    if (payload.opt_in_for_training) fd.append('opt_in_for_training', '1');

    const { data } = await apiClient.post<{ data: DiseaseDetection }>(
      '/disease/detect',
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );
    return data;
  },

  async history(): Promise<PaginatedResponse<DiseaseDetection>> {
    const { data } = await apiClient.get<PaginatedResponse<DiseaseDetection>>(
      '/disease/history',
    );
    return data;
  },
};
