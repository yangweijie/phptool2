<?php

declare(strict_types=1);

use App\FlyEnvWebApp;

/**
 * FlyEnvWebApp Unit Tests
 *
 * @see app/FlyEnvWebApp.php
 */

// ─── Constructor / Data Integrity ───────────────────────────────────────────

test('all tools have required fields', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);

    foreach ($tools as $i => $t) {
        expect($t)->toHaveKeys(['id', 'cat', 'name', 'icon'])
            ->and($t['id'])->not->toBeEmpty("tool[{$i}] id is empty")
            ->and($t['cat'])->not->toBeEmpty("tool[{$i}] cat is empty")
            ->and($t['name'])->not->toBeEmpty("tool[{$i}] name is empty")
            ->and($t['icon'])->not->toBeEmpty("tool[{$i}] icon is empty");
    }
});

test('all tool IDs are unique', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);

    $ids = array_map(fn($t) => $t['id'], $tools);
    expect(count($ids))->toBe(count(array_unique($ids)));
});

test('tools belong to defined categories only', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);
    $categories = $ref->getProperty('categories')->getValue($app);

    foreach ($tools as $t) {
        expect($categories)->toContain($t['cat']);
    }
});

test('every category has at least one tool', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);
    $categories = $ref->getProperty('categories')->getValue($app);

    $counts = [];
    foreach ($tools as $t) {
        $counts[$t['cat']] = ($counts[$t['cat']] ?? 0) + 1;
    }
    foreach ($categories as $cat) {
        expect(isset($counts[$cat]))->toBeTrue("Category '{$cat}' has no tools");
    }
});

// ─── PanelMap ───────────────────────────────────────────────────────────────

test('every tool has a panelMap entry', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);
    $panelMap = $ref->getProperty('panelMap')->getValue($app);

    foreach ($tools as $t) {
        expect($panelMap)->toHaveKey($t['id']);
    }
});

test('all panelMap entries are non-empty strings', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $panelMap = $ref->getProperty('panelMap')->getValue($app);

    foreach ($panelMap as $id => $pk) {
        expect($pk)->not->toBeEmpty("panelMap[{$id}] is empty")
            ->and($pk)->toBeString("panelMap[{$id}] is not a string");
    }
});

// ─── renderTree() ───────────────────────────────────────────────────────────

test('renderTree outputs expected categories and tool entries', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $categories = $ref->getProperty('categories')->getValue($app);
    $tools = $ref->getProperty('tools')->getValue($app);

    $renderTree = $ref->getMethod('renderTree');
    $html = $renderTree->invoke($app);

    // Must include "Favorites" section
    expect($html)->toContain('Favorites');

    // Must include all category names
    foreach ($categories as $cat) {
        expect($html)->toContain('◈ ' . $cat);
    }

    // Must include all tool names
    foreach ($tools as $t) {
        expect($html)->toContain($t['name']);
    }

    // Must include all tool IDs as data-id attributes
    foreach ($tools as $t) {
        expect($html)->toContain('data-id="' . $t['id'] . '"');
    }
});

// ─── getHtml() ──────────────────────────────────────────────────────────────

test('getHtml returns valid HTML with all sections', function () {
    $app = new FlyEnvWebApp();
    $html = $app->getHtml();

    // Basic HTML structure
    expect($html)->toContain('<!DOCTYPE html>')
        ->and($html)->toContain('</html>')
        ->and($html)->toContain('<head>')
        ->and($html)->toContain('</head>')
        ->and($html)->toContain('<body>')
        ->and($html)->toContain('</body>');

    // Key UI elements
    expect($html)->toContain('id="sidebar"')
        ->and($html)->toContain('id="content"')
        ->and($html)->toContain('id="searchInput"')
        ->and($html)->toContain('id="homeView"')
        ->and($html)->toContain('id="toolView"');

    // JavaScript data injection
    expect($html)->toContain('var TOOLS =')
        ->and($html)->toContain('var CATS =')
        ->and($html)->toContain('var PMAP =')
        ->and($html)->toContain('initApp();');

    // CSS injection
    expect($html)->toContain('<style>')
        ->and($html)->toContain('</style>');
});

test('getHtml inlines all asset files', function () {
    $app = new FlyEnvWebApp();
    $html = $app->getHtml();

    // All JS source files are inlined (no external script tags)
    expect($html)->not->toContain('<script src=');

    // CSS is inlined in <style> tag
    $styleStart = strpos($html, '<style>');
    $styleEnd = strpos($html, '</style>');
    expect($styleStart)->not->toBeFalse();
    expect($styleEnd)->not->toBeFalse();
    $inlineCss = substr($html, $styleStart + 7, $styleEnd - $styleStart - 7);
    expect(strlen($inlineCss))->toBeGreaterThan(100);

    // i18n JS is inlined
    expect($html)->toContain('const I18N');
    expect($html)->toContain('lang = localStorage.getItem');
    expect($html)->toContain('function _t(');
});

test('getHtml generates correct tool count', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);

    $html = $app->getHtml();

    // JSON-encoded tool data must parse to same count
    preg_match('/var TOOLS\s*=\s*(\[.*?\]);/s', $html, $m);
    expect($m)->toHaveCount(2);
    $decoded = json_decode($m[1], true);
    expect(count($decoded))->toBe(count($tools));
});

// ─── Edge Cases ─────────────────────────────────────────────────────────────

test('tool icons are valid inline SVG fragments', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $tools = $ref->getProperty('tools')->getValue($app);

    foreach ($tools as $t) {
        // Must be SVG markup
        expect($t['icon'])->toContain('<svg')
            ->and($t['icon'])->toContain('</svg>');
    }
});

test('categories are returned in expected order', function () {
    $app = new FlyEnvWebApp();
    $ref = new ReflectionClass($app);
    $categories = $ref->getProperty('categories')->getValue($app);

    expect($categories)->toBe([
        'Code',
        'Development',
        'Crypto',
        'Converter',
        'Web',
        'Images',
    ]);
});
