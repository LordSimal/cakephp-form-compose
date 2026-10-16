import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/TestCase/Browser',
  outputDir: './build/playwright',
  use: { baseURL: 'http://127.0.0.1:8765', browserName: 'chromium' },
  webServer: {
    command: 'php -S 127.0.0.1:8765 tests/Fixture/Http/router.php',
    url: 'http://127.0.0.1:8765/forms',
    reuseExistingServer: false,
  },
});
