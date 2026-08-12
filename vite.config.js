import {
    defineConfig,
    loadEnv 
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());
    const hmrHost = env.VITE_APP_URL?.replace(/https?:\/\//g, '') || 'localhost';
    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/passkeys.js',
                ],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            ws: {
                host: hmrHost,
            },
            host: true,
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    }
});
