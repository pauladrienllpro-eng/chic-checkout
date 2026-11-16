import { defineConfig } from 'vite';
import path from 'node:path';

// Une entrée par build (scripts/build.mjs), en script classique (IIFE). React est fourni
// par WordPress (handles react, react-dom, react-jsx-runtime).
const ENTRIES = {
  main: path.resolve(import.meta.dirname, 'src/main.js'),
  cart: path.resolve(import.meta.dirname, 'src/main-cart.js'),
  thankyou: path.resolve(import.meta.dirname, 'src/thankyou.css'),
};
const entry = process.env.VITE_ENTRY;

export default defineConfig({
  build: {
    outDir: 'build',
    manifest: true,
    emptyOutDir: process.env.VITE_EMPTY_OUT_DIR !== 'false',
    rollupOptions: {
      input: { [entry]: ENTRIES[entry] },
      external: ['react', 'react-dom', 'react-dom/client', 'react/jsx-runtime'],
      output: {
        format: entry === 'thankyou' ? 'es' : 'iife',
        globals: { react: 'React', 'react-dom': 'ReactDOM', 'react-dom/client': 'ReactDOM', 'react/jsx-runtime': 'ReactJSXRuntime' },
      },
    },
  },
});
