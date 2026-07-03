import { test, expect, request } from '@playwright/test';

/**
 * Verify the public-facing pages render and produce the expected critical
 * fragments. Uses request API (not browser) so Mapbender's https-redirects
 * don't trip the test.
 */

async function api(baseURL: string | undefined) {
    return request.newContext({ baseURL, maxRedirects: 0, ignoreHTTPSErrors: true });
}

test('landing page returns Mapbender portal HTML', async ({ baseURL }) => {
    const ctx = await api(baseURL);
    const resp = await ctx.get('/mapbender/');
    expect(resp.status()).toBe(200);
    const html = await resp.text();
    // The landing page is a frame wrapper around login.php — verify the
    // characteristic title and that it references the login frame.
    expect(html).toMatch(/Welcome to the Mapbender Portal/);
    expect(html).toMatch(/loginForm/);
    await ctx.dispose();
});

test('login.php directly accessible', async ({ baseURL }) => {
    const ctx = await api(baseURL);
    const resp = await ctx.get('/mapbender/frames/login.php');
    expect(resp.status()).toBe(200);
    const html = await resp.text();
    expect(html).toMatch(/name=['"]password['"]/);
    await ctx.dispose();
});

test('home frame loads without 5xx', async ({ baseURL }) => {
    const ctx = await api(baseURL);
    const resp = await ctx.get('/mapbender/frames/home.php');
    // 200 or 302 (redirect to login) is fine — never 5xx.
    expect(resp.status()).toBeLessThan(500);
    await ctx.dispose();
});

test('mb_listGUIs.php returns 200', async ({ baseURL }) => {
    const ctx = await api(baseURL);
    const resp = await ctx.get('/mapbender/php/mb_listGUIs.php?guiID=Geoportal-RLP');
    expect(resp.status()).toBe(200);
    await ctx.dispose();
});

test('WMS GetCapabilities endpoint reachable', async ({ baseURL }) => {
    const ctx = await api(baseURL);
    const resp = await ctx.get('/mapbender/php/wms.php?REQUEST=GetCapabilities&SERVICE=WMS&VERSION=1.1.1');
    expect(resp.status()).toBe(200);
    await ctx.dispose();
});
