import type { APIRoute } from 'astro'
import { z } from 'zod'
import { adminCookieValid } from '../../../../../lib/admin-auth'

export const prerender = false

interface Env {
  DB: D1Database
  WAITLIST_ADMIN_PASSWORD?: string
}

interface RuntimeLocals {
  runtime: { env: Env }
}

const STATUSES = ['new', 'contacted', 'enrolled', 'declined', 'lost'] as const

const Body = z.object({
  status: z.enum(STATUSES),
  notes: z.string().max(2000).optional(),
})

/**
 * POST /api/admin/waitlist/[id]/status — move a row through the funnel
 * state machine (new → contacted → enrolled | declined | lost) + optional
 * notes. Stamps updated_at. Auth: panda_admin cookie.
 */
export const POST: APIRoute = async ({ request, params, locals }) => {
  const env = (locals as unknown as RuntimeLocals).runtime?.env
  const ok = await adminCookieValid(request.headers.get('cookie'), env?.WAITLIST_ADMIN_PASSWORD)
  if (!ok) {
    return Response.json({ error: 'unauthorized' }, { status: 401 })
  }
  if (!env?.DB) {
    return Response.json({ error: 'storage_unavailable' }, { status: 503 })
  }

  const id = params.id
  if (!id) {
    return Response.json({ error: 'missing_id' }, { status: 400 })
  }

  let payload: unknown
  try {
    payload = await request.json()
  } catch {
    return Response.json({ error: 'invalid_json' }, { status: 400 })
  }
  const parsed = Body.safeParse(payload)
  if (!parsed.success) {
    return Response.json({ error: 'validation_failed', issues: parsed.error.flatten() }, { status: 422 })
  }

  const { status, notes } = parsed.data

  const res = await env.DB.prepare(
    `UPDATE panda_waitlist
     SET status = ?,
         notes = CASE WHEN ? IS NULL THEN notes ELSE ? END,
         updated_at = CURRENT_TIMESTAMP
     WHERE id = ?
     RETURNING id, name, email, status, notes, updated_at`,
  )
    .bind(status, notes ?? null, notes ?? null, Number(id))
    .all()

  if (!res.results || res.results.length === 0) {
    return Response.json({ error: 'not_found' }, { status: 404 })
  }

  return Response.json({ row: res.results[0] }, { status: 200 })
}
