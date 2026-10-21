import type { APIRoute } from 'astro'
import { adminCookieValid } from '../../../lib/admin-auth'

export const prerender = false

interface Env {
  DB: D1Database
  WAITLIST_ADMIN_PASSWORD?: string
}

interface RuntimeLocals {
  runtime: { env: Env }
}

const STATUSES = ['new', 'contacted', 'enrolled', 'declined', 'lost']

/**
 * GET /api/admin/waitlist — list rows for the Phase-8 CRM.
 *
 * Filters (query params): status, county, q (name/email/phone substring),
 * source (substring over the pipe-joined UTM string), limit (default 200).
 * Auth: panda_admin cookie signed with WAITLIST_ADMIN_PASSWORD.
 */
export const GET: APIRoute = async ({ request, locals }) => {
  const env = (locals as unknown as RuntimeLocals).runtime?.env
  const ok = await adminCookieValid(request.headers.get('cookie'), env?.WAITLIST_ADMIN_PASSWORD)
  if (!ok) {
    return Response.json({ error: 'unauthorized' }, { status: 401 })
  }
  if (!env?.DB) {
    return Response.json({ error: 'storage_unavailable' }, { status: 503 })
  }

  const url = new URL(request.url)
  const status = url.searchParams.get('status') ?? ''
  const county = url.searchParams.get('county') ?? ''
  const q = url.searchParams.get('q') ?? ''
  const source = url.searchParams.get('source') ?? ''
  const limit = Math.min(Number(url.searchParams.get('limit') ?? 200) || 200, 500)

  if (status && !STATUSES.includes(status)) {
    return Response.json({ error: 'invalid_status' }, { status: 422 })
  }

  const clauses: string[] = []
  const binds: unknown[] = []
  if (status) {
    clauses.push('status = ?')
    binds.push(status)
  }
  if (county) {
    clauses.push('county = ?')
    binds.push(county)
  }
  if (source) {
    clauses.push("source LIKE ? ESCAPE '\\'")
    binds.push(`%${source}%`)
  }
  if (q) {
    clauses.push('(name LIKE ? OR email LIKE ? OR phone LIKE ?)')
    const like = `%${q}%`
    binds.push(like, like, like)
  }
  const where = clauses.length ? `WHERE ${clauses.join(' AND ')}` : ''

  const rows = await env.DB.prepare(
    `SELECT id, name, email, phone, county, acreage, crop, status, notes, source, created_at, updated_at
     FROM panda_waitlist
     ${where}
     ORDER BY created_at DESC
     LIMIT ?`,
  )
    .bind(...binds, limit)
    .all()

  const counts = await env.DB.prepare(
    'SELECT status, COUNT(*) AS n FROM panda_waitlist GROUP BY status',
  ).all()

  return Response.json({
    rows: rows.results,
    counts: Object.fromEntries(
      (counts.results as Array<{ status: string; n: number }>).map((r) => [r.status, r.n]),
    ),
    total: rows.results.length,
  })
}
