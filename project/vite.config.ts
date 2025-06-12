import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:3000', // Asegúrate que coincide con tu servidor PHP
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api/, ''),
        // Configuración esencial para PHP:
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }
    },
    port: 4173, // Usa el puerto estándar de Vite
    cors: false
  },
  
  build: {
    outDir: 'dist',
    emptyOutDir: true
  }
});