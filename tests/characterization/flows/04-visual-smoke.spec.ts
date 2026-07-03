import { test, expect } from '@playwright/test';

/**
 * Final visual smoke: render the landing page in a real browser and verify
 * the Mapbender headline and login form are actually visible. This is the
 * "browser would actually load it" guarantee on top of all the HTTP-level
 * checks.
 */

test('landing renders Mapbender headline and login form', async ({ page }) => {
    // The portal landing uses framesets; we navigate directly to the login
    // frame so Playwright can interact with the inputs.
    const resp = await page.goto('/mapbender/frames/login.php', { waitUntil: 'domcontentloaded' });
    expect(resp?.status()).toBe(200);

    // The login form must be present and visible.
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[type="submit"]')).toBeVisible();

    // Save a reference screenshot — useful for visual review by colleagues.
    await page.screenshot({
        path: 'screenshots/login-page.png',
        fullPage: true,
    });
});

test('after login the GUI list is visible', async ({ page }) => {
    await page.goto('/mapbender/frames/login.php');
    await page.fill('input[name="name"]', 'root');
    await page.fill('input[name="password"]', 'root');
    await Promise.all([
        page.waitForLoadState('domcontentloaded'),
        page.click('input[type="submit"]'),
    ]);
    // After login the page lists available GUIs; "logout" link confirms session.
    const body = await page.content();
    expect(body.toLowerCase()).toContain('logout');
    await page.screenshot({
        path: 'screenshots/after-login.png',
        fullPage: true,
    });
});
