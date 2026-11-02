import type { APIRoute } from 'astro'
import { z } from 'zod'

export const prerender = false

const Body = z.object({
  name: z.string().min(2).max(120),
  email: z.string().email(),
  phone: z.string().max(40).optional(),
  message: z.string().min(10).max(2000),
})

interface Env {
  RESEND_API_KEY?: string
  SITE_URL?: string
}

interface RuntimeLocals {
  runtime: { env: Env }
}

/**
 * panda.shira.farm contact-form sink.
 *
 * Flow:
 *   1. Validate the form body via Zod.
 *   2. Log to the CF Worker console (audit trail).
 *   3. When RESEND_API_KEY is bound on the Pages project, fire-and-forget:
 *      - an inbox notify to hello@panda.shira.farm, and
 *      - an auto-reply to the enquirer.
 *      If the key is unbound (or Resend errors), the response is still 202 —
 *      the form never fails a submitter.
 *
 * No DB write yet — CRM lands with the marketing-admin dashboard (mirror
 * Shira's `admin_leads` D1 table when that work lands).
 *
 * Returns 202 on success. Returns 422 on validation failure. Returns 400 on
 * bad JSON.
 */
export const POST: APIRoute = async ({ request, locals }) => {
  let payload: unknown
  try {
    payload = await request.json()
  } catch {
    return Response.json({ error: 'invalid_json' }, { status: 400 })
  }

  const parsed = Body.safeParse(payload)
  if (!parsed.success) {
    return Response.json(
      { error: 'validation_failed', issues: parsed.error.flatten() },
      { status: 422 },
    )
  }

  const data = parsed.data

  // eslint-disable-next-line no-console
  console.log('[panda-marketing] contact submission', {
    name: data.name,
    email: data.email,
    phone: data.phone ?? null,
    message_length: data.message.length,
    received_at: new Date().toISOString(),
  })

  // Fire-and-forget emails. Don't await — they're allowed to fail.
  const env = (locals as unknown as RuntimeLocals).runtime?.env
  if (env?.RESEND_API_KEY) {
    void notifyInbox(env.RESEND_API_KEY, data, env.SITE_URL ?? 'https://panda.shira.farm')
    void sendAutoReply(env.RESEND_API_KEY, data)
  }

  return Response.json({ status: 'ok' }, { status: 202 })
}

async function notifyInbox(
  apiKey: string,
  data: z.infer<typeof Body>,
  siteUrl: string,
): Promise<void> {
  const html = `<!doctype html>
<html><body style="font-family: 'DM Sans', system-ui, sans-serif; color:#2D2D2D; max-width:560px; margin:24px auto; line-height:1.5;">
  <h2 style="color:#1B4332; margin:0 0 12px;">New Panda contact-form enquiry</h2>
  <ul>
    <li><strong>Name:</strong> ${escapeHtml(data.name)}</li>
    <li><strong>Email:</strong> ${escapeHtml(data.email)}</li>
    ${data.phone ? `<li><strong>Phone:</strong> ${escapeHtml(data.phone)}</li>` : ''}
  </ul>
  <p style="white-space:pre-wrap;">${escapeHtml(data.message)}</p>
  <p style="color:#9ca3af; font-size:12px; margin-top:24px;">Submitted from ${siteUrl}</p>
</body></html>`

  try {
    const res = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        from: 'Panda Website <noreply@panda.shira.farm>',
        to: ['hello@panda.shira.farm'],
        subject: `New contact-form enquiry from ${data.name}`,
        html,
      }),
    })
    if (!res.ok) {
      // eslint-disable-next-line no-console
      console.error('[panda-contact] Resend notify non-2xx', res.status, await res.text().catch(() => '?'))
    }
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[panda-contact] Resend notify threw', err)
  }
}

async function sendAutoReply(apiKey: string, data: z.infer<typeof Body>): Promise<void> {
  const html = `<!doctype html>
<html><body style="font-family: 'DM Sans', system-ui, sans-serif; color:#2D2D2D; max-width:560px; margin:24px auto; line-height:1.5;">
  <h2 style="color:#1B4332; margin:0 0 12px;">We received your message 🌱</h2>
  <p>Karibu, ${escapeHtml(data.name)}.</p>
  <p>Thanks for reaching out to Panda. Our team reads every enquiry and will reply within two business days.</p>
  <p>While you wait, you can explore the crop planning tools on the marketing site or join the pilot waitlist at <a style="color:#1B4332;" href="https://panda.shira.farm">panda.shira.farm</a>.</p>
  <p style="color:#9ca3af; font-size:12px; margin-top:24px;">Sister product to <a style="color:#9ca3af;" href="https://shira.farm">Shira</a>. Built in Nairobi.</p>
</body></html>`

  try {
    const res = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        from: 'Panda <hello@panda.shira.farm>',
        reply_to: 'hello@panda.shira.farm',
        to: [data.email],
        subject: 'We received your message',
        html,
      }),
    })
    if (!res.ok) {
      // eslint-disable-next-line no-console
      console.error('[panda-contact] Resend auto-reply non-2xx', res.status, await res.text().catch(() => '?'))
    }
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[panda-contact] Resend auto-reply threw', err)
  }
}

function escapeHtml(s: string): string {
  return s.replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] ?? c,
  )
}
