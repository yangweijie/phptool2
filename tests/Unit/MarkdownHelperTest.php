<?php

declare(strict_types=1);

uses()->group('markdown');

test('empty markdown renders to empty string', function () {
    expect(\App\MarkdownHelper::render(''))->toBe('');
    expect(\App\MarkdownHelper::render('   '))->toBe('');
});

test('headings and paragraphs are converted', function () {
    $html = \App\MarkdownHelper::render("# Title\n\nA paragraph.");
    expect($html)->toContain('<h1>Title</h1>');
    expect($html)->toContain('<p>A paragraph.</p>');
});

test('inline code is wrapped', function () {
    $html = \App\MarkdownHelper::render("Use `php -v` to check.");
    expect($html)->toContain('<code>php -v</code>');
});

test('lists render as ul', function () {
    $html = \App\MarkdownHelper::render("- one\n- two");
    expect($html)->toContain('<ul>');
    expect($html)->toContain('<li>one</li>');
    expect($html)->toContain('<li>two</li>');
});

test('tables render when enabled', function () {
    $html = \App\MarkdownHelper::render("| a | b |\n| - | - |\n| 1 | 2 |");
    expect($html)->toContain('<table>');
    expect($html)->toContain('<th>a</th>');
});

test('html is escaped in safe mode', function () {
    $html = \App\MarkdownHelper::render("<script>alert(1)</script>");
    // Parsedown safe mode should not emit a raw script tag.
    expect($html)->not->toContain('<script>alert(1)</script>');
});
