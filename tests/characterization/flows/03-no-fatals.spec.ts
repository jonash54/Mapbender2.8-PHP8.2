import { test, expect, request } from '@playwright/test';

/**
 * Guard test: hitting common Mapbender URLs must not produce a PHP fatal in
 * the response body. PHP 8 fatals leak as plain-text "Fatal error:" messages
 * which is the surest sign of a migration regression.
 *
 * Uses the request API (not a full browser) so Mapbender's hardcoded
 * https:// redirects don't make Chromium follow into an unreachable SSL
 * listener — we want to inspect the immediate response.
 */

const URLS = [
    '/mapbender/',
    '/mapbender/frames/login.php',
    '/mapbender/frames/home.php',
    '/mapbender/php/mb_listGUIs.php',
    '/mapbender/php/wms.php?REQUEST=GetCapabilities',
    '/mapbender/php/mod_callMetadata.php',
    '/mapbender/php/mb_listKMLs.php',
    '/mapbender/php/mod_button_tooltips.php',
    '/mapbender/php/mod_changeEPSG_dynamic.php',
    '/mapbender/php/mod_loadwms.php',
    '/mapbender/php/mb_listGUIs.php?guiID=Geoportal-RLP',
    '/mapbender/php/mb_validateSession.php',
    '/mapbender/php/mb_listEPSG.php',
    '/mapbender/php/mod_layerISOMetadata.php',
    '/mapbender/javascripts/map.php',
    '/mapbender/javascripts/core.php',
    '/mapbender/javascripts/gui.php',
    '/mapbender/javascripts/user.php',
    '/mapbender/javascripts/group.php',
    '/mapbender/javascripts/mod_legend.php',
    '/mapbender/javascripts/mod_help.php',
    '/mapbender/javascripts/mod_state.php',
    '/mapbender/javascripts/mod_loadwmc.php',
    '/mapbender/javascripts/mod_savewmc.php',
];

for (const url of URLS) {
    test(`no PHP fatal in body of ${url}`, async ({ baseURL }) => {
        const api = await request.newContext({
            baseURL,
            maxRedirects: 0, // capture 302 -> https/... as the response itself
            ignoreHTTPSErrors: true,
        });
        const resp = await api.get(url);
        // Any 2xx/3xx is fine — Mapbender may redirect to login.
        // Only inspect bodies for fatals on 200/500 responses (where a body
        // can carry the error text).
        const status = resp.status();
        const body = await resp.text();
        expect(body, `${url} returned PHP fatal text (status ${status})`)
            .not.toMatch(/PHP Fatal error/i);
        expect(body, `${url} returned uncaught exception (status ${status})`)
            .not.toMatch(/Uncaught [A-Z][a-zA-Z]+(?:Error|Exception)/);
        // 5xx is a clear regression.
        expect(status, `${url} returned 5xx`).toBeLessThan(500);
        await api.dispose();
    });
}
