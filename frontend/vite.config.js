import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  base: './',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/uploads': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  },
  build: {
    outDir: 'C:/xampp/htdocs/simpelkesrsig',
    emptyOutDir: false,
    chunkSizeWarningLimit: 1200,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('jspdf') || id.includes('html2canvas') || id.includes('canvg') || id.includes('jspdf-autotable')) {
              return 'vendor-pdf';
            }
            if (id.includes('xlsx')) {
              return 'vendor-excel';
            }
            if (id.includes('html5-qrcode') || id.includes('qrcode')) {
              return 'vendor-qr';
            }
            if (id.includes('lucide-vue-next')) {
              return 'vendor-icons';
            }
            if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router') || id.includes('axios')) {
              return 'vendor-core';
            }
          }
        }
      }
    }
  }
})
