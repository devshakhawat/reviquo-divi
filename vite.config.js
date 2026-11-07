import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const target = process.env.BUILD_TARGET || 'admin';

const targets = {
  admin: {
    input: path.resolve(__dirname, 'dev/admin/src/main.jsx'),
    jsFile: 'admin/js/admin.min.js',
    cssFile: 'admin/css/admin.min.css',
    plugin: react(),
    external: ['react', 'react-dom'],
    globals: { react: 'React', 'react-dom': 'ReactDOM' },
    iifeName: 'SktprAdmin',
  },
  public: {
    input: path.resolve(__dirname, 'dev/public/js/public.js'),
    jsFile: 'public/js/public.min.js',
    cssFile: 'public/css/public.min.css',
    plugin: null,
    external: ['jquery'],
    globals: { jquery: 'jQuery' },
    iifeName: 'SktprPublic',
  },
  block: {
    input: path.resolve(__dirname, 'dev/blocks/reviews/index.js'),
    jsFile: 'blocks/reviews/index.js',
    cssFile: 'blocks/reviews/editor.css',
    plugin: null,
    external: [],
    globals: {},
    iifeName: 'SktprReviewsBlock',
  },
  divi4: {
    input: path.resolve(__dirname, 'dev/divi4/reviews/index.jsx'),
    jsFile: 'divi4/reviews.js',
    cssFile: 'divi4/reviews.css',
    plugin: react(),
    // The Divi 4 Visual Builder replaces the 'react' handle with its React 16.
    external: ['react', 'jquery'],
    globals: { react: 'React', jquery: 'jQuery' },
    iifeName: 'SktprDivi4Reviews',
  },
  divi5: {
    input: path.resolve(__dirname, 'dev/divi5/reviews/index.jsx'),
    jsFile: 'divi5/reviews.js',
    cssFile: 'divi5/reviews.css',
    plugin: react(),
    external: ['react'],
    // Divi 5 renders modules with its own React copy (window.vendor.React),
    // not WordPress's window.React — hooks break if the copies differ.
    globals: { react: 'vendor.React' },
    iifeName: 'SktprDivi5Reviews',
  },
};

const cfg = targets[target];

export default defineConfig({
  plugins: cfg.plugin ? [cfg.plugin] : [],

  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
  },

  build: {
    outDir: 'assets',
    emptyOutDir: false,

    lib: {
      entry: cfg.input,
      formats: ['iife'],
      name: cfg.iifeName,
      fileName: () => cfg.jsFile,
    },

    rollupOptions: {
      external: cfg.external,
      output: {
        globals: cfg.globals,
        assetFileNames: (asset) => {
          if (asset.name?.endsWith('.css')) {
            return cfg.cssFile;
          }
          return '[name].[ext]';
        },
      },
    },
  },
});
