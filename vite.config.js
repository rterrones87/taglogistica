import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    optimizeDeps: {
        include: ["jspdf", "html2canvas"],
    },
    resolve: {
        alias: {
          'jspdf/dist/jspdf.umd.min.js': path.resolve(__dirname, 'node_modules/jspdf/dist/jspdf.umd.js')
        },
    },
});



