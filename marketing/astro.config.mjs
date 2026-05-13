import { defineConfig } from 'astro/config'
import react from '@astrojs/react'
import markdoc from '@astrojs/markdoc'
import sitemap from '@astrojs/sitemap'
import cloudflare from '@astrojs/cloudflare'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  site: 'https://panda.shira.farm',
  output: 'server',
  adapter: cloudflare(),
  security: {
    checkOrigin: false,
  },
  integrations: [
    react(),
    markdoc(),
    sitemap(),
  ],
  vite: {
    plugins: [tailwindcss()],
  },
})
