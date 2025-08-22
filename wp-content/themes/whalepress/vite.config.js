import { defineConfig } from "vite";
import tailwindVite from "@tailwindcss/vite";
import path from "path";
import autoprefixer from "autoprefixer";
import tailwindPostCss from '@tailwindcss/postcss';

export default defineConfig({
  plugins: [
    tailwindVite()
  ],
  css: {
    postcss: {
      plugins: [
        tailwindPostCss(),
        autoprefixer
      ],
    },
    preprocessorOptions: {
      scss: {
        api: "modern-compiler",
      },
    },
  },
  build: {
    outDir: "dist",
    emptyOutDir: true,
    manifest: true,
    cssCodeSplit: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, "src/js/index.js"),
      },
      output: {
        entryFileNames: "index.js",
        assetFileNames: ({ filename }) => {
          if (filename && filename.endsWith(".css")) {
            return "style-index.css";
          }
          return "index.[ext]";
        },
      },
    },
    sourcemap: true,
  },
});
