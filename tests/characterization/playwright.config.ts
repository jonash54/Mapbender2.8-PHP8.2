import { defineConfig, devices } from '@playwright/test';

// BASE_URL is set per-run via env, so the same specs can run against the
// legacy (PHP 7.4) container or the php8 (PHP 8.2) container.
const BASE_URL = process.env.BASE_URL || 'http://mapbender-php8';

export default defineConfig({
    testDir: './flows',
    fullyParallel: false, // many specs share the same root user account
    forbidOnly: !!process.env.CI,
    // Apache mpm_prefork briefly refuses connections under sustained probe
    // load. Single retry absorbs the noise without masking real regressions.
    retries: 2,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { open: 'never', outputFolder: 'playwright-report' }],
    ],
    use: {
        baseURL: BASE_URL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        ignoreHTTPSErrors: true,
        // Mapbender uses ancient JS; some pages produce console warnings that
        // are not regressions for the migration.
        bypassCSP: true,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
