import { apiClient } from './client';

export interface TeamMember {
  id: string;
  name: string;
  email: string;
  role: 'owner' | 'member';
  email_verified_at: string | null;
  joined_at: string | null;
}

export interface TeamInvitation {
  id: string;
  email: string;
  name: string | null;
  status: 'pending' | 'accepted' | 'expired' | 'revoked';
  expires_at: string;
  accepted_at: string | null;
  invited_by_name?: string | null;
}

export interface TeamListResponse {
  members: { data: TeamMember[] };
  invitations: { data: TeamInvitation[] };
}

export const teamApi = {
  async list(): Promise<TeamListResponse> {
    const { data } = await apiClient.get<TeamListResponse>('/team');
    return data;
  },

  async invite(payload: { email: string; name?: string }): Promise<{ data: TeamInvitation }> {
    const { data } = await apiClient.post<{ data: TeamInvitation }>('/team/invite', payload);
    return data;
  },

  async revokeInvitation(invitationId: string): Promise<void> {
    await apiClient.delete(`/team/invitations/${invitationId}`);
  },

  async removeMember(userId: string): Promise<void> {
    await apiClient.delete(`/team/${userId}`);
  },

  async accept(
    token: string,
    payload: { name: string; password: string; password_confirmation: string },
  ): Promise<{ user: TeamMember; token: string }> {
    const { data } = await apiClient.post<{ user: TeamMember; token: string }>(
      `/team/accept/${token}`,
      payload,
    );
    return data;
  },
};
