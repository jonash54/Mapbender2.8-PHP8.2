import { test, expect, request } from '@playwright/test';

/**
 * Authentication flow: POST credentials, verify session cookie is set and
 * the post-login UI returns the expected logged-in HTML.
 */

test('valid root credentials produce a logged-in session', async ({ baseURL }) => {
    const ctx = await request.newContext({
        baseURL, maxRedirects: 0, ignoreHTTPSErrors: true,
    });

    const resp = await ctx.post('/mapbender/frames/login.php?action=login', {
        form: { name: 'root', password: 'root' },
    });

    // Mapbender returns 200 with the post-login UI on success, never 5xx.
    expect(resp.status()).toBeLessThan(500);

    // A successful login leaves a MAPBENDER session cookie on the context.
    const state = await ctx.storageState();
    const session = state.cookies.find((c) => c.name === 'MAPBENDER');
    expect(session, 'MAPBENDER session cookie should be set').toBeDefined();

    // And renders a logout link somewhere on the page.
    const body = await resp.text();
    expect(body.toLowerCase()).toContain('logout');
    await ctx.dispose();
});

test('invalid credentials still produce a usable login form', async ({ baseURL }) => {
    const ctx = await request.newContext({
        baseURL, maxRedirects: 0, ignoreHTTPSErrors: true,
    });
    const resp = await ctx.post('/mapbender/frames/login.php?action=login', {
        form: { name: 'wronguser', password: 'wrongpass' },
    });
    expect(resp.status()).toBeLessThan(500);
    const body = await resp.text();
    // Either we're back on a login form OR we got a redirect to it.
    if (resp.status() === 200) {
        expect(body).toMatch(/name=['"]password['"]/);
    }
    await ctx.dispose();
});
