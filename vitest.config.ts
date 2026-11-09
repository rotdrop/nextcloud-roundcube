import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
  plugins: [vue()],
  define: {
    APP_NAME: JSON.stringify('mail_roundcube'),
  },
  resolve: {
    // The real Nextcloud components are heavy and irrelevant for these tests.
    alias: {
      '@nextcloud/vue': path.resolve(import.meta.dirname, 'tests/js/stubs/nextcloud-vue.ts'),
      '@nextcloud/dialogs': path.resolve(import.meta.dirname, 'tests/js/stubs/nextcloud-dialogs.ts'),
      '@rotdrop/nextcloud-vue-components/lib/components': path.resolve(import.meta.dirname, 'tests/js/stubs'),
    },
  },
  test: {
    environment: 'jsdom',
    include: ['tests/js/**/*.test.ts'],
  },
});
