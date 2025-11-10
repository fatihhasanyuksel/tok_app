import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/workspace-v3.css',
        'resources/js/workspace-v3.ts',
      ],
      refresh: true,
    }),
  ],
  optimizeDeps: {
    disabled: true,   // ← disable esbuild pre-bundling in dev
  },
  build: {
    minify: false,
    cssMinify: false,
    target: 'es2018',
    rollupOptions: {
      output: { manualChunks: undefined },
    },
  },
})