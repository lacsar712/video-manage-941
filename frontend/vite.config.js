import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  base: '/admin/',
  build: {
    outDir: 'dist',
    assetsDir: 'assets'
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8082',
        changeOrigin: true
      },
      '/uploads': {
        target: 'http://localhost:8082',
        changeOrigin: true
      }
    }
  }
})
