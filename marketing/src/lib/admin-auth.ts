/**
 * Password-gated session cookie for the Phase-8 waitlist CRM.
 *
 * Single-admin pilot surface: the only credential is the
 * WAITLIST_ADMIN_PASSWORD Pages secret. A valid password issues a signed
 * cookie (`panda_admin`) — HMAC-SHA256 over `panda_admin.<email>` with
 * the password as the key, verified on every admin request. No user
 * accounts, no sessions table, no third-party auth.
 *
 * Cookie is set by the Astro page via Astro.cookies.set (httpOnly,
 * SameSite=Strict, 12h).
 */

const COOKIE_NAME = 'panda_admin'
export const ADMIN_COOKIE_TTL_SECONDS = 12 * 60 * 60

async function hmac(secret: string, data: string): Promise<string> {
  const key = await crypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  )
  const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(data))
  return btoa(String.fromCharCode(...new Uint8Array(sig)))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '')
}

function constantTimeEqual(a: string, b: string): boolean {
  if (a.length !== b.length) return false
  let diff = 0
  for (let i = 0; i < a.length; i++) diff |= a.charCodeAt(i) ^ b.charCodeAt(i)
  return diff === 0
}

/** Value stored in the cookie: b64(email) + '.' + HMAC. */
export async function issueAdminCookieValue(password: string, email = 'admin'): Promise<string> {
  const emailB64 = btoa(email)
  const sig = await hmac(password, `${COOKIE_NAME}.${email}`)
  return `${emailB64}.${sig}`
}

export async function adminCookieValid(
  cookieHeader: string | null,
  password: string | undefined,
): Promise<boolean> {
  if (!password || !cookieHeader) return false
  const pair = cookieHeader
    .split(';')
    .map((s) => s.trim())
    .find((s) => s.startsWith(`${COOKIE_NAME}=`))
  if (!pair) return false
  let value = pair.slice(COOKIE_NAME.length + 1)
  try {
    value = decodeURIComponent(value)
  } catch {
    return false
  }
  const dot = value.lastIndexOf('.')
  if (dot < 0) return false
  const emailB64 = value.slice(0, dot)
  const sig = value.slice(dot + 1)
  const email = safeAtob(emailB64)
  const expected = await hmac(password, `${COOKIE_NAME}.${email}`)
  return constantTimeEqual(sig, expected)
}

function safeAtob(s: string): string {
  try {
    return atob(s)
  } catch {
    return s
  }
}
