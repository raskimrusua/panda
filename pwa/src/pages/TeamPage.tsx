import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Trash2, X, Plus, ShieldCheck } from 'lucide-react';
import { teamApi } from '@/api/team';
import { useAuth } from '@/auth/useAuth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { ConfirmDeleteModal } from '@/components/ui/ConfirmDeleteModal';
import { formatDate } from '@/lib/utils';
import { inviteMemberSchema, type InviteMemberValues } from '@/lib/zodSchemas';

export function TeamPage() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const qc = useQueryClient();
  const isOwner = user?.role === 'owner';

  const [submitError, setSubmitError] = useState<string | null>(null);
  const [memberToRemove, setMemberToRemove] = useState<string | null>(null);
  const [invitationToRevoke, setInvitationToRevoke] = useState<string | null>(null);

  const teamQuery = useQuery({
    queryKey: ['team'],
    queryFn: () => teamApi.list(),
  });

  const inviteForm = useForm<InviteMemberValues>({
    resolver: zodResolver(inviteMemberSchema),
    defaultValues: { email: '', name: '' },
  });

  const refresh = () => qc.invalidateQueries({ queryKey: ['team'] });

  const onInvite = async (values: InviteMemberValues) => {
    setSubmitError(null);
    try {
      await teamApi.invite(values);
      inviteForm.reset();
      refresh();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: { email?: string[] }; message?: string } } };
      setSubmitError(e?.response?.data?.errors?.email?.[0] ?? e?.response?.data?.message ?? t('team.invite_failed'));
    }
  };

  const revoke = async () => {
    if (!invitationToRevoke) return;
    await teamApi.revokeInvitation(invitationToRevoke);
    refresh();
  };

  const remove = async () => {
    if (!memberToRemove) return;
    await teamApi.removeMember(memberToRemove);
    refresh();
  };

  return (
    <div className="space-y-6 max-w-2xl">
      <header>
        <h1 className="text-2xl font-semibold">{t('team.title')}</h1>
        <p className="text-gray-600 text-sm">{t('team.subtitle')}</p>
      </header>

      {isOwner && (
        <Card>
          <CardHeader>
            <h2 className="font-semibold">{t('team.invite_section')}</h2>
          </CardHeader>
          <CardBody>
            <form onSubmit={inviteForm.handleSubmit(onInvite)} className="space-y-3">
              <div>
                <Label htmlFor="invite_email">{t('team.invite_email')}</Label>
                <Input id="invite_email" type="email" autoComplete="email" {...inviteForm.register('email')} />
                <FieldError message={inviteForm.formState.errors.email?.message} />
              </div>
              <div>
                <Label htmlFor="invite_name">{t('team.invite_name_optional')}</Label>
                <Input id="invite_name" {...inviteForm.register('name')} />
                <FieldError message={inviteForm.formState.errors.name?.message} />
              </div>
              {submitError && <p className="text-sm text-danger-600">{submitError}</p>}
              <Button type="submit" loading={inviteForm.formState.isSubmitting}>
                <Plus className="h-4 w-4 mr-1" /> {t('team.send_invite')}
              </Button>
            </form>
          </CardBody>
        </Card>
      )}

      <Card>
        <CardHeader>
          <h2 className="font-semibold">{t('team.members_section')}</h2>
        </CardHeader>
        <CardBody className="p-0">
          {teamQuery.isLoading && <p className="p-4 text-gray-500">{t('team.loading')}</p>}
          {teamQuery.data?.members.data.map((m) => (
            <div key={m.id} className="flex items-center justify-between border-t border-gray-100 px-4 py-3 first:border-t-0">
              <div className="min-w-0">
                <div className="font-medium truncate">
                  {m.name}
                  {m.role === 'owner' && (
                    <span className="ml-2 inline-flex items-center text-xs bg-brand-50 text-brand-700 px-1.5 py-0.5 rounded">
                      <ShieldCheck className="h-3 w-3 mr-0.5" /> {t('team.role_owner')}
                    </span>
                  )}
                </div>
                <div className="text-xs text-gray-500 truncate">{m.email}</div>
              </div>
              {isOwner && m.role !== 'owner' && m.id !== user?.id && (
                <Button size="sm" variant="ghost" onClick={() => setMemberToRemove(m.id)} aria-label={t('team.remove')}>
                  <Trash2 className="h-4 w-4 text-danger-600" />
                </Button>
              )}
            </div>
          ))}
        </CardBody>
      </Card>

      {teamQuery.data && teamQuery.data.invitations.data.length > 0 && (
        <Card>
          <CardHeader>
            <h2 className="font-semibold">{t('team.invitations_section')}</h2>
          </CardHeader>
          <CardBody className="p-0">
            {teamQuery.data.invitations.data.map((inv) => (
              <div key={inv.id} className="flex items-center justify-between border-t border-gray-100 px-4 py-3 first:border-t-0">
                <div className="min-w-0">
                  <div className="font-medium truncate">{inv.email}</div>
                  <div className="text-xs text-gray-500">
                    {t(`team.status_${inv.status}`)}
                    {inv.status === 'pending' && ` · ${t('team.expires_on', { date: formatDate(inv.expires_at) })}`}
                  </div>
                </div>
                {isOwner && inv.status === 'pending' && (
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => setInvitationToRevoke(inv.id)}
                    aria-label={t('team.revoke')}
                  >
                    <X className="h-4 w-4 text-gray-500" />
                  </Button>
                )}
              </div>
            ))}
          </CardBody>
        </Card>
      )}

      <ConfirmDeleteModal
        open={!!memberToRemove}
        onClose={() => setMemberToRemove(null)}
        title={t('team.remove_member_title')}
        body={t('team.remove_member_body')}
        onConfirm={remove}
      />
      <ConfirmDeleteModal
        open={!!invitationToRevoke}
        onClose={() => setInvitationToRevoke(null)}
        title={t('team.revoke_invite_title')}
        body={t('team.revoke_invite_body')}
        onConfirm={revoke}
      />
    </div>
  );
}
