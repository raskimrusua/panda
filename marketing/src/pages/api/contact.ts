import type { APIRoute } from 'astro'
import { z } from 'zod'

export const prerender = false

const Body = z.object({
  name: z.string().min(2).max(120),
  email: z.string().email(),
  phone: z.string().max(40).optional(),
  message: z.string().min(10).max(2000),
})

/**
 * Minimal contact-form sink. Logs to the CF Worker console and (TODO once
 * Resend is wired) emails hello@panda.shira.farm. Intentionally no DB write
 * yet — Phase-6 marketing scope is "ship a brochure site"; CRM lands later
 * (mirror Shira's `admin_leads` D1 table when we add the marketing-admin
 * dashboard).
 */
export const POST: APIRoute = async ({ request }) => {
  let payload: unknown
  try {
    payload = await request.json()
  } catch {
    return Response.json({ error: 'invalid json' }, { status: 400 })
  }

  const parsed = Body.safeParse(payload)
  if (!parsed.success) {
    return Response.json(
      { error: 'validation_failed', issues: parsed.error.flatten() },
      { status: 422 },
    )
  }

  // eslint-disable-next-line no-console
  console.log('[panda-marketing] contact submission', {
    name: parsed.data.name,
    email: parsed.data.email,
    phone: parsed.data.phone ?? null,
    message_length: parsed.data.message.length,
    received_at: new Date().toISOString(),
  })

  return Response.json({ status: 'ok' }, { status: 202 })
}
