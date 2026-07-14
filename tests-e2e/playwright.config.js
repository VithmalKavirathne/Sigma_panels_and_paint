// Playwright configuration for the Sigma Panels & Paint admin/public suite.
// Requires the local app running at BASE_URL (default http://localhost:8088).
require('dotenv').config();

const { defineConfig, devices } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://localhost:8088';

module.exports = defineConfig({
  testDir: './specs',
  // Run serially: many specs create/delete shared TEST_ADMIN_ records and must
  // not race each other against a single local database.
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: 0,
  timeout: 45_000,
  expect: { timeout: 8_000 },

  reporter: [
    ['list'],
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ['json', { outputFile: 'playwright-report/results.json' }],
  ],

  use: {
    baseURL: BASE_URL,
    actionTimeout: 10_000,
    navigationTimeout: 15_000,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'retain-on-failure',
    ignoreHTTPSErrors: true,
  },

  projects: [
    // 1) Authenticate once and store the admin session for reuse.
    { name: 'setup', testMatch: /auth\.setup\.js/ },

    // 2) Desktop run (default viewport 1440x900). Depends on the stored session.
    {
      name: 'desktop',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1440, height: 900 },
        storageState: '.auth/admin.json',
      },
    },

    // 3) Mobile run for the responsive + mobile-nav specs.
    {
      name: 'mobile',
      dependencies: ['setup'],
      testMatch: /(responsive|public-regression)\.spec\.js/,
      use: {
        ...devices['iPhone 14 Pro Max'],
        storageState: '.auth/admin.json',
      },
    },
  ],
});
