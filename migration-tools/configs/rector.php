<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

/**
 * Rector config for the Mapbender 2.8 PHP 8.2 migration.
 *
 * Strategy:
 *  - LevelSet up to PHP 8.2 — automated rewrites for every released-PHP change.
 *  - Conservative quality sets — we DO NOT want huge style diffs in one go,
 *    so PSR-12 / type-coverage / dead-code are kept out of the global sweep.
 *    They may be applied per-module manually later.
 *
 * Scope: the entire repo tree except dev-only directories. The repo is
 * mounted at /var/www/html/mapbender inside the mig-tools container, so
 * paths here are relative to that.
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/../../core',
        __DIR__ . '/../../lib',
        __DIR__ . '/../../http',
        __DIR__ . '/../../tools',
        __DIR__ . '/../../mapserver',
        __DIR__ . '/../../owsproxy',
        __DIR__ . '/../../owsproxy_api',
        __DIR__ . '/../../cors_proxy',
        __DIR__ . '/../../http_auth',
        __DIR__ . '/../../resources',
        __DIR__ . '/../../conf',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/../../vendor',
        __DIR__ . '/../../tests',
        __DIR__ . '/../../docker',
        __DIR__ . '/../../migration-tools',
        __DIR__ . '/../../resources/mapbender-2.6-i386',
        // Bundled third-party libs that we should not touch:
        '*/http/extensions/*',
        '*/http/include/openlayers/*',
        '*/http/fpdf/*',
        '*/http/print/*',
    ]);

    // Bring everything up to PHP 8.2 step by step (Rector handles each step).
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
    ]);

    // Format-stable output: don't reflow what we don't have to.
    $rectorConfig->importNames(false);
    $rectorConfig->importShortClasses(false);

    $rectorConfig->parallel();
};
