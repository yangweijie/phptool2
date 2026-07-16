<?php

declare(strict_types=1);

use App\SiteSucker;

/**
 * Site Sucker crawler tests (1:1 with FlyEnv SiteSucker).
 *
 * - Deterministic unit checks for the URL/path/rewrite/exclude helpers.
 * - A real integration crawl against a tiny built-in HTTP server.
 */

// ── Reflection helpers ───────────────────────────────────────────────────────
function ss_invoke(SiteSucker $ss, string $method, array $args)
{
    $r = new ReflectionMethod($ss, $method);
    $r->setAccessible(true);
    return $r->invokeArgs($ss, $args);
}
function ss_prop(SiteSucker $ss, string $name, $value): void
{
    $r = new ReflectionProperty($ss, $name);
    $r->setAccessible(true);
    $r->setValue($ss, $value);
}

// ── absUrl: relative / absolute / protocol-relative / parent dir ─────────────
test('absUrl resolves relative, absolute, protocol-relative and .. paths', function () {
    $ss = new SiteSucker();
    $base = 'http://example.com/path/index.html';

    expect(ss_invoke($ss, 'absUrl', ['page2.html', $base]))
        ->toBe('http://example.com/path/page2.html');
    expect(ss_invoke($ss, 'absUrl', ['/root.html', $base]))
        ->toBe('http://example.com/root.html');
    expect(ss_invoke($ss, 'absUrl', ['../up.html', $base]))
        ->toBe('http://example.com/up.html');
    expect(ss_invoke($ss, 'absUrl', ['//cdn.com/x.png', 'https://example.com/']))
        ->toBe('https://cdn.com/x.png');
    expect(ss_invoke($ss, 'absUrl', ['https://abs.com/y', 'http://example.com/']))
        ->toBe('https://abs.com/y');
    // data:/mailto:/# are not crawlable → null
    expect(ss_invoke($ss, 'absUrl', ['mailto:a@b.com', $base]))->toBeNull();
});

// ── urlToDir: page → .html, folder → index, query → md5, cross-host → outsite
test('urlToDir maps pages to .html and cross-host assets to outsite/<md5>', function () {
    $ss = new SiteSucker();
    ss_prop($ss, 'host', 'example.com');
    ss_prop($ss, 'dir', '/tmp/sitedir');

    expect(ss_invoke($ss, 'urlToDir', ['http://example.com/about', true]))
        ->toBe('/tmp/sitedir/about.html');
    expect(ss_invoke($ss, 'urlToDir', ['http://example.com/', true]))
        ->toBe('/tmp/sitedir/index.html');
    expect(ss_invoke($ss, 'urlToDir', ['http://example.com/folder/', true]))
        ->toBe('/tmp/sitedir/folder/index.html');
    // dynamic query page → md5 name
    $dyn = ss_invoke($ss, 'urlToDir', ['http://example.com/search?q=1', true]);
    expect($dyn)->toMatch('#/tmp/sitedir/[a-f0-9]{32}\.html$#');
    // cross-host asset → outsite/<md5>.png
    $cross = ss_invoke($ss, 'urlToDir', ['http://cdn.other.com/logo.png']);
    expect($cross)->toMatch('#/tmp/sitedir/outsite/[a-f0-9]{32}\.png$#');
});

// ── relPath + strip helpers ─────────────────────────────────────────────────
test('relPath / stripFragment / stripQuery helpers', function () {
    $ss = new SiteSucker();
    ss_prop($ss, 'dir', '/tmp/sitedir');
    expect(ss_invoke($ss, 'relPath', ['/tmp/sitedir/about.html']))->toBe('./about.html');
    expect(ss_invoke($ss, 'stripFragment', ['http://e.com/a#frag']))->toBe('http://e.com/a');
    expect(ss_invoke($ss, 'stripQuery', ['http://e.com/a?b=1']))->toBe('http://e.com/a');
});

// ── isExcluded: built-in analytics hosts + user-supplied excludes ───────────
test('isExcluded honours base analytics hosts and user exclude list', function () {
    $ss = new SiteSucker();
    ss_prop($ss, 'host', 'example.com');
    ss_prop($ss, 'config', ['excludeLink' => "analytics.example.com\ntrack.example.com"]);

    expect(ss_invoke($ss, 'isExcluded', ['http://www.google-analytics.com/x', true]))->toBeTrue();
    expect(ss_invoke($ss, 'isExcluded', ['http://analytics.example.com/x', true]))->toBeTrue();
    expect(ss_invoke($ss, 'isExcluded', ['http://example.com/ok', true]))->toBeFalse();
});

// ── parsePageLinks rewrites same-host hrefs to local relative paths ──────────
test('parsePageLinks rewrites same-host links and enqueues pages', function () {
    $ss = new SiteSucker();
    ss_prop($ss, 'host', 'example.com');
    ss_prop($ss, 'dir', '/tmp/sitedir');
    ss_prop($ss, 'store', ['excludeUrl' => [], 'pages' => []]);

    $html = '<html><body><a href="page2.html">Two</a><a href="https://example.com/page3.html">Three</a>'
        . '<a href="https://other.com/x">Ext</a><a href="#frag">Frag</a></body></html>';
    $replace = ss_invoke($ss, 'parsePageLinks', [$html, ['url' => 'http://example.com/index.html']]);

    // same-host links rewritten to ./relative
    expect($replace)->toHaveKey('href="page2.html"');
    expect($replace['href="page2.html"'])->toBe('href="./page2.html"');
    expect($replace)->toHaveKey('href="https://example.com/page3.html"');
    // cross-host + fragment skipped
    expect($replace)->not->toHaveKey('href="https://other.com/x"');
    expect($replace)->not->toHaveKey('href="#frag"');
    // pages enqueued (page2 + page3)
    $store = ss_prop_get($ss, 'store');
    expect(count($store['pages']))->toBe(2);
});

// tiny accessor used above
function ss_prop_get(SiteSucker $ss, string $name)
{
    $r = new ReflectionProperty($ss, $name);
    $r->setAccessible(true);
    return $r->getValue($ss);
}

// ── Integration: crawl a local fixture site and save files ──────────────────
test('SiteSucker crawls a local fixture site end-to-end', function () {
    $fixture = sys_get_temp_dir() . '/flyenv_ss_fixture_' . uniqid('', true);
    $saveDir = sys_get_temp_dir() . '/flyenv_ss_out_' . uniqid('', true);
    mkdir($fixture . '/img', 0777, true);
    mkdir($fixture . '/css', 0777, true);
    file_put_contents($fixture . '/index.html', <<<HTML
<!doctype html><html><head><link rel="stylesheet" href="css/style.css"></head>
<body><h1>Home</h1>
<a href="page2.html">Page 2</a><a href="page3.html">Page 3</a>
<img src="img/logo.png" alt="logo"><script src="app.js"></script></body></html>
HTML);
    file_put_contents($fixture . '/page2.html', '<html><body><a href="index.html">Home</a><a href="page3.html">Page 3</a></body></html>');
    file_put_contents($fixture . '/page3.html', '<html><body><a href="index.html">Home</a></body></html>');
    file_put_contents($fixture . '/css/style.css', 'body{color:red}');
    file_put_contents($fixture . '/img/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    file_put_contents($fixture . '/app.js', 'console.log(1);');

    // pick a guaranteed-free port
    $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($sock === false) {
        $this->markTestSkipped('cannot allocate a local port');
    }
    $port = (int) parse_url((string) stream_socket_get_name($sock, false), PHP_URL_PORT);
    fclose($sock);
    $host = '127.0.0.1:' . $port;
    $base = 'http://' . $host . '/';

    $pid = (int) trim((string) shell_exec(sprintf(
        '%s -S %s -t %s >/dev/null 2>&1 & echo $!',
        escapeshellarg(PHP_BINARY),
        escapeshellarg($host),
        escapeshellarg($fixture)
    )));

    // wait for the server to accept connections
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $ch = curl_init($base);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 1, CURLOPT_TIMEOUT => 1]);
        $r = curl_exec($ch);
        curl_close($ch);
        if ($r !== false) { $ready = true; break; }
        usleep(100000);
    }
    if (!$ready) {
        if ($pid) { @exec('kill -9 ' . $pid . ' 2>/dev/null'); }
        $this->markTestSkipped('local test server did not start');
    }

    try {
        $ss = new SiteSucker();
        $view = $ss->start($base, ['dir' => $saveDir, 'windowCount' => 4, 'timeout' => 5]);
        $guard = 0;
        while (($view['state'] ?? '') !== 'done' && $guard < 80) {
            $view = $ss->step($view['taskId']);
            $guard++;
        }

        expect($view['state'])->toBe('done');
        $counts = $view['counts'] ?? [];
        expect($counts['success'] ?? 0)->toBeGreaterThan(0);
        expect(count($view['links'] ?? []))->toBeGreaterThan(0);
        expect(count($view['hosts'] ?? []))->toBeGreaterThan(0);

        // files were written under <saveDir>/<hostKey>/
        $saved = glob($saveDir . '/*/index.html');
        expect($saved)->not->toBeEmpty();
        $idx = (string) file_get_contents($saved[0]);
        // original page2.html link was rewritten to a local relative path
        expect($idx)->toContain('href="./page2.html"');
        // asset files present
        expect(glob($saveDir . '/*/css/style.css'))->not->toBeEmpty();
        expect(glob($saveDir . '/*/img/logo.png'))->not->toBeEmpty();
        expect(glob($saveDir . '/*/app.js'))->not->toBeEmpty();
        // page2 + page3 downloaded
        expect(glob($saveDir . '/*/page2.html'))->not->toBeEmpty();
        expect(glob($saveDir . '/*/page3.html'))->not->toBeEmpty();

        // cleanup temp task json
        @unlink(sys_get_temp_dir() . '/flyenv_ss_' . $view['taskId'] . '.json');
    } finally {
        if ($pid) { @exec('kill -9 ' . $pid . ' 2>/dev/null'); }
        ss_rrm($fixture);
        ss_rrm($saveDir);
    }
});

// ── recursive, depth-safe cleanup (files before their parent dirs) ──────────
function ss_rrm(string $path): void
{
    if (is_dir($path) && !is_link($path)) {
        foreach (array_diff(scandir($path), ['.', '..']) as $e) {
            ss_rrm($path . '/' . $e);
        }
        @rmdir($path);
    } elseif (is_file($path)) {
        @unlink($path);
    }
}
