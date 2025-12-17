import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';
import laravel from 'laravel-vite-plugin';
import path from 'path';


export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    cors: true,
    strictPort: false,
    hmr: {
      host: '127.0.0.1',
      protocol: 'http',
      port: 5173,
    },
  },
  build: {
    // OPTIMIZACIÓN: Configuración para máximo rendimiento y compatibilidad
    rollupOptions: {
      output: {
        // Optimizar nombres de chunks
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: 'js/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
        // No especificar format - Laravel Vite plugin usa IIFE por defecto
        // IIFE es más compatible con Vue 2 y plugins antiguos como vue-clipboard2
      },
      // Optimizar árbol de dependencias pero mantener efectos secundarios necesarios
      treeshake: {
        moduleSideEffects: (id) => {
          // Mantener efectos secundarios para vue-clipboard2 y otros plugins
          return id.includes('vue-clipboard') || id.includes('element-ui');
        }
      }
    },
    // Optimizar tamaño de chunks
    chunkSizeWarningLimit: 1000,
    // Minificar con esbuild (más rápido que terser)
    minify: 'esbuild',
    // Optimizar source maps (solo en desarrollo)
    sourcemap: process.env.NODE_ENV === 'development',
    // CommonJS interop para compatibilidad con plugins antiguos
    commonjsOptions: {
      include: [/node_modules/],
      transformMixedEsModules: true
    }
  },
  plugins: [
    laravel({
      input: [
        'resources/js/system.js',
        'resources/js/app.js',
        // 'resources/sass/style.scss',
        // 'resources/sass/auth.scss'
      ],
      refresh: true,
    }),
    vue({
      template: {
        // No reescribir URLs absolutas (/logo/...) a imports
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      '@components': path.resolve(__dirname, 'resources/js/components'),
      '@views': path.resolve(__dirname, 'resources/js/views/tenant'),
      '@helpers': path.resolve(__dirname, 'resources/js/helpers'),
      '@mixins': path.resolve(__dirname, 'resources/js/mixins'),

      '@viewsModuleSale': path.resolve(__dirname, 'modules/Sale/Resources/assets/js/views'),
      '@viewsModuleFinance': path.resolve(__dirname, 'modules/Finance/Resources/assets/js/views'),
      '@viewsModulePurchase': path.resolve(__dirname, 'modules/Purchase/Resources/assets/js/views'),
      '@viewsModuleExpense': path.resolve(__dirname, 'modules/Expense/Resources/assets/js/views'),
      '@viewsModuleOrder': path.resolve(__dirname, 'modules/Order/Resources/assets/js/views'),
      '@viewsModuleAccount': path.resolve(__dirname, 'modules/Account/Resources/assets/js/views'),
      '@viewsModuleItem': path.resolve(__dirname, 'modules/Item/Resources/assets/js/views'),
      '@viewsModuleHotel': path.resolve(__dirname, 'modules/Hotel/Resources/assets/js/views'),
      '@viewsModuleDocumentary': path.resolve(__dirname, 'modules/DocumentaryProcedure/Resources/assets/js/views'),
      '@viewsModulePayment': path.resolve(__dirname, 'modules/Payment/Resources/assets/js/views'),
      '@viewsModuleMercadoPago': path.resolve(__dirname, 'modules/MercadoPago/Resources/assets/js/views'),
      '@viewsModuleSuscription': path.resolve(__dirname, 'modules/Suscription/Resources/assets/js/views'),
      '@viewsModuleMobileApp': path.resolve(__dirname, 'modules/MobileApp/Resources/assets/js/views'),
      '@viewsModuleLevelAccess': path.resolve(__dirname, 'modules/LevelAccess/Resources/assets/js/views'),
      '@viewsModuleReport': path.resolve(__dirname, 'modules/Report/Resources/assets/js/views'),
      '@componentsModuleReport': path.resolve(__dirname, 'modules/Report/Resources/assets/js/components'),
      '@viewsModuleInventory': path.resolve(__dirname, 'modules/Inventory/Resources/assets/js'),
      '@viewsModuleMultiUser': path.resolve(__dirname, 'modules/MultiUser/Resources/assets/js/views'),
      '@viewsModuleQrChatBuho': path.resolve(__dirname, 'modules/QrChatBuho/Resources/assets/js/views'),
      '@viewsModuleQrApi' : path.resolve(__dirname, 'modules/QrApi/Resources/assets/js/views'),
      'vue': path.resolve(__dirname, 'node_modules/vue/dist/vue.esm.js'),
    },
  },
});
