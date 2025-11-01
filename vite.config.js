import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue(),
  ],
  // ↓ Reduce memory for shared hosting builds
  build: {
    minify: false,      // disable JS minification (esbuild)
    cssMinify: false,   // disable CSS minification
    target: 'es2018',   // lighter transforms
    rollupOptions: {
      // avoid extra chunking to keep memory low
      output: { manualChunks: undefined },
    },
  },
});