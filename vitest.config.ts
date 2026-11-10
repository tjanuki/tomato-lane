import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite-plus';

/**
 * Unit tests for plain TypeScript modules. Kept apart from vite.config.ts so
 * the Laravel plugin, which refuses to start a dev server in CI, never loads.
 */
export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        include: ['resources/js/**/*.test.ts'],
    },
});
