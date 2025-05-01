import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
  plugins: [tailwindcss()],
  css: {
    preprocessorOptions: {
      scss: {
        includePaths: [path.resolve(__dirname, 'node_modules')],
      },
    },
  },
  root: path.resolve(__dirname, 'public'),
  build: {
    outDir: 'build',
    publicDir: false,
    emptyOutDir: true,
    manifest: true,
    cssCodeSplit: true,
    rollupOptions: {
      input: {
        main: '/src/js/index.js',
      },
      output: {
        entryFileNames: 'index.js',
        assetFileNames: ({ filename }) => {
          if (filename && filename.endsWith('.css')) {
            return 'style-index.css';
          }
          return 'index.[ext]';
        },
      },
    },
    sourcemap: true,
  },
});
