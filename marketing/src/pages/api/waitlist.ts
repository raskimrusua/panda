import type { APIRoute } from 'astro'
import { z } from 'zod'

export const prerender = false

const Body = z.object({
  name: z.string().min(2).max(120),
  email: z.string().email().max(180),
  phone: z.string().max(40).optional(),
  county: z.string().max(80).optional(),
  acreage: z.coerce.number().min(0).max(1000).optional(),
  crop: z.string().max(80).optional(),
})

interface Env {
  DB: D1Database
  RESEND_API_KEY?: string
  SITE_URL?: string
}

interface RuntimeLocals {
  runtime: { env: Env }
}

/**
 * panda.shira.farm waitlist sink.
 *
 * Flow:
 *   1. Validate the form body via Zod (loose — county/acreage/crop optional).
 *   2. UPSERT into D1's `panda_waitlist` table on the unique email column.
 *      Re-submitting the same email refreshes the row + bumps updated_at;
 *      no duplicates. Status defaults to 'new'; admin moves to
 *      'contacted'/'enrolled'/etc. via the (Phase-8) marketing-admin UI.
 *   3. Fire-and-forget a Resend confirmation email if RESEND_API_KEY is
 *      bound. If sending fails, the response is still 202 — the row is
 *      already saved, the team can re-contact later.
 *
 * Returns 202 on success (signal: accepted, will follow up).
 * Returns 422 on validation failure with the Zod issues.
 * Returns 400 on bad JSON.
 * Returns 500 only if D1 write fails (rare; logs the error).
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

  const env = (locals as unknown as RuntimeLocals).runtime?.env
  if (!env?.DB) {
    // eslint-disable-next-line no-console
    console.error('[panda-waitlist] DB binding missing — submission lost', parsed.data.email)
    return Response.json({ error: 'storage_unavailable' }, { status: 503 })
  }

  const data = parsed.data

  try {
    await env.DB.prepare(
      `INSERT INTO panda_waitlist (name, email, phone, county, acreage, crop)
       VALUES (?1, ?2, ?3, ?4, ?5, ?6)
       ON CONFLICT (email) DO UPDATE SET
         name = excluded.name,
         phone = excluded.phone,
         county = excluded.county,
         acreage = excluded.acreage,
         crop = excluded.crop,
         updated_at = CURRENT_TIMESTAMP`,
    )
      .bind(
        data.name,
        data.email,
        data.phone ?? null,
        data.county ?? null,
        data.acreage ?? null,
        data.crop ?? null,
      )
      .run()
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[panda-waitlist] D1 write failed', err)
    return Response.json({ error: 'storage_failed' }, { status: 500 })
  }

  // Fire-and-forget confirmation. Don't await — it's allowed to fail.
  if (env.RESEND_API_KEY) {
    void sendConfirmation(env.RESEND_API_KEY, data, env.SITE_URL ?? 'https://panda.shira.farm')
  }

  return Response.json({ status: 'ok' }, { status: 202 })
}

async function sendConfirmation(
  apiKey: string,
  data: z.infer<typeof Body>,
  siteUrl: string,
): Promise<void> {
  const html = `<!doctype html>
<html><body style="font-family: 'DM Sans', system-ui, sans-serif; color:#2D2D2D; max-width:560px; margin:24px auto; line-height:1.5;">
  <h2 style="color:#1B4332; margin:0 0 12px;">You're on the Panda waitlist 🌱</h2>
  <p>Karibu, ${escapeHtml(data.name)}.</p>
  <p>Thanks for signing up for Panda — the JAICA SHEP PLUS-inspired farm planning tool we're building for Kenyan smallholders.</p>
  <p>Our 200-farmer pilot launches in <strong>Meru &amp; Kirinyaga</strong> in 2026. We'll reach out the week onboarding opens for your area.</p>
  <p>What you signed up with:</p>
  <ul>
    <li>Email: ${escapeHtml(data.email)}</li>
    ${data.phone ? `<li>Phone: ${escapeHtml(data.phone)}</li>` : ''}
    ${data.county ? `<li>County: ${escapeHtml(data.county)}</li>` : ''}
    ${data.acreage ? `<li>Acreage: ${data.acreage}</li>` : ''}
    ${data.crop ? `<li>Crop: ${escapeHtml(data.crop)}</li>` : ''}
  </ul>
  <p>In the meantime, the marketing site is at <a style="color:#1B4332;" href="${siteUrl}">${siteUrl}</a>.</p>
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
        from: 'Panda <noreply@panda.shira.farm>',
        to: [data.email],
        subject: "You're on the Panda waitlist",
        html,
      }),
    })
    if (!res.ok) {
      // eslint-disable-next-line no-console
      console.error('[panda-waitlist] Resend non-2xx', res.status, await res.text().catch(() => '?'))
    }
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[panda-waitlist] Resend threw', err)
  }
}

function escapeHtml(s: string): string {
  return s.replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] ?? c,
  )
}
