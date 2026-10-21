import { defineCollection, z } from 'astro:content'
import { glob } from 'astro/loaders'

/**
 * Blog content collection — Panda marketing.
 *
 * Pattern ported from Shira's farmcore website blog (Keystatic there),
 * reduced to the native Astro glob loader + Markdoc: posts are plain
 * `.mdoc` files in src/content/blog/. Publishing is date-driven:
 * `publishedDate <= today` and `draft !== true` — future-dated posts
 * self-publish when their date arrives, no editor action needed
 * (the same auto-schedule Shira uses).
 */
const blog = defineCollection({
  loader: glob({ pattern: '**/*.mdoc', base: './src/content/blog' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    publishedDate: z.coerce.date(),
    draft: z.boolean().default(false),
    gated: z.boolean().default(false),
    tags: z.array(z.string()).default([]),
    image: z.string().optional(),
  }),
})

export const collections = { blog }
