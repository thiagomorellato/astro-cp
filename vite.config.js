import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin'; // Import the laravel plugin
import tailwindcss from '@tailwindcss/vite'; // Keep your tailwindcss plugin
// You might not need 'path' anymore unless for other custom uses
// import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            // Define your entry points here instead of build.rollupOptions.input
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true, // Add refresh for HMR
        }),
        tailwindcss(), // Keep your tailwindcss plugin
    ],
    // Remove the custom build configurations like outDir, emptyOutDir, manifest
    // The laravel() plugin handles these
    // build: {
    //     outDir: 'public/build',
    //     emptyOutDir: true,
    //     manifest: true,
    // },
});