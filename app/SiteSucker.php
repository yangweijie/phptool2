<?php
/**
 * SiteSucker — 1:1 rebuild of FlyEnv's Site Sucker website downloader.
 *
 * Original (Electron + Vue3) uses real BrowserWindows to render JS pages and
 * capture cookies. In this PHP/LibUI webview port the engine runs server-side
 * with cURL + DOM parsing, mirroring the original's data model and behaviour:
 *  - same-host page crawl (BFS) with link/asset rewriting to local paths
 *  - asset download (images/scripts/css/video) with size limits + retry
 *  - exclude hosts (built-in analytics + user supplied)
 *  - proxy / timeout / page-limit / concurrency(windowCount) settings
 *
 * State is persisted to a temp JSON file per task; each `step()` advances the
 * crawl by one unit so the webview can poll for live progress (no orphaned
 * background processes).
 */

namespace App;

use DOMDocument;
use DOMElement;

class SiteSucker
{
    /** Base hosts always excluded (mirrors original BaseExcludeHost). */
    private const BASE_EXCLUDE_HOST = [
        'www.google-analytics.com',
        'hm.baidu.com',
        'www.googletagmanager.com',
        'static.hotjar.com',
        'apis.google.com',
        'www.google.com',
    ];

    private string $tmpDir;
    private array $config = [];
    private string $host = '';
    private string $dir = '';
    private array $store = [];

    public function __construct()
    {
        $this->tmpDir = rtrim(sys_get_temp_dir(), '/');
    }

    // ── Public API ────────────────────────────────────────────────

    public function start(string $url, array $config): array
    {
        $url = $this->normalizeUrl($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return ['error' => 'Invalid URL'];
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return ['error' => 'Invalid URL host'];
        }
        $this->host = $host;
        $this->applyConfig($config);

        $taskId = bin2hex(random_bytes(8));
        $hostKey = $host;
        if (($p = parse_url($url, PHP_URL_PORT))) {
            $hostKey .= '_' . $p;
        }
        $saveDir = trim((string) $this->config['dir']);
        if ($saveDir === '') {
            $saveDir = $this->tmpDir . '/flyenv-sites';
        }
        if (!is_dir($saveDir)) {
            @mkdir($saveDir, 0755, true);
        }
        $this->dir = rtrim($saveDir, '/') . '/' . $hostKey;

        $this->store = [
            'taskId'     => $taskId,
            'url'        => $url,
            'host'       => $host,
            'dir'        => $this->dir,
            'state'      => 'running',
            'cookie'     => '',
            'pages'      => [],
            'links'      => [],
            'excludeUrl' => [],
            'loadedUrl'  => [],
            'counts'     => ['page' => 0, 'link' => 0, 'success' => 0, 'fail' => 0, 'running' => 0, 'wait' => 0],
        ];

        // Enqueue the root page.
        $saveFile = $this->urlToDir($url, true);
        $this->enqueuePage($url, $saveFile);

        $this->save($taskId);
        // Do the first step synchronously so the UI shows immediate progress.
        $this->step($taskId);
        return $this->view($taskId);
    }

    public function step(string $taskId): array
    {
        $this->load($taskId);
        if ($this->store['state'] !== 'running') {
            return $this->view($taskId);
        }

        // Prefer processing a waiting page (fetch + parse + enqueue + save).
        $idx = $this->takeWait('pages');
        if ($idx >= 0) {
            $this->store['pages'][$idx]['state'] = 'running';
            $this->runPage($this->store['pages'][$idx]);
            $this->save($taskId);
            return $this->view($taskId);
        }

        // No pages left → download a batch of pending assets.
        $batch = (int) ($this->config['windowCount'] ?? 2);
        if ($batch < 1) {
            $batch = 1;
        }
        $processed = 0;
        while ($processed < $batch) {
            $lidx = $this->takeWait('links');
            if ($lidx < 0) {
                break;
            }
            $this->store['links'][$lidx]['state'] = 'running';
            $this->runLink($this->store['links'][$lidx]);
            $processed++;
        }

        // Done?
        $hasWait = $this->hasState($this->store['pages'], 'wait') || $this->hasState($this->store['links'], 'wait');
        $hasRunning = $this->hasState($this->store['pages'], 'running') || $this->hasState($this->store['links'], 'running');
        if (!$hasWait && !$hasRunning) {
            $this->store['state'] = 'done';
        }
        $this->save($taskId);
        return $this->view($taskId);
    }

    public function stop(string $taskId): array
    {
        $this->load($taskId);
        $this->store['state'] = 'stop';
        $this->save($taskId);
        return ['ok' => true];
    }

    public function view(string $taskId): array
    {
        $this->load($taskId);
        $links = array_merge($this->store['pages'], $this->store['links']);
        // Sort: running, wait, fail, then others (mirrors original links comput
        $order = ['running' => 0, 'wait' => 1, 'fail' => 2, 'success' => 3, 'replace' => 3];
        usort($links, function ($a, $b) use ($order) {
            return ($order[$a['state']] ?? 9) - ($order[$b['state']] ?? 9);
        });

        // Hosts (unique) with allow/exclude status.
        $hostSet = [];
        foreach ($links as $l) {
            $h = parse_url($l['url'], PHP_URL_HOST);
            if ($h) {
                $hostSet[$h] = true;
            }
        }
        $exclude = $this->excludeHosts();
        $hosts = [];
        foreach (array_keys($hostSet) as $h) {
            $hosts[] = ['host' => $h, 'allow' => !in_array($h, $exclude, true)];
        }

        // Counts.
        $c = ['page' => count($this->store['pages']), 'link' => count($this->store['links']), 'success' => 0, 'fail' => 0, 'running' => 0, 'wait' => 0];
        foreach ($links as $l) {
            if ($l['state'] === 'success' || $l['state'] === 'replace') {
                $c['success']++;
            } elseif ($l['state'] === 'fail') {
                $c['fail']++;
            } elseif ($l['state'] === 'running') {
                $c['running']++;
            } elseif ($l['state'] === 'wait') {
                $c['wait']++;
            }
        }

        return [
            'taskId' => $taskId,
            'state'  => $this->store['state'],
            'dir'    => $this->store['dir'],
            'host'   => $this->store['host'],
            'url'    => $this->store['url'],
            'counts' => $c,
            'links'  => $links,
            'hosts'  => $hosts,
        ];
    }

    // ── Crawl internals ───────────────────────────────────────────

    private function runPage(array &$page): void
    {
        $url = $page['url'];
        $max = (int) ($this->config['maxRetryTimes'] ?? 3);
        if (($page['retry'] ?? 0) >= $max) {
            $page['state'] = 'fail';
            return;
        }
        $page['retry'] = ($page['retry'] ?? 0) + 1;

        $res = $this->fetch($url);
        if ($res === null) {
            // Retry up to maxRetryTimes, else fail.
            $page['state'] = ($page['retry'] >= $max) ? 'fail' : 'wait';
            return;
        }
        $html = $res['body'];
        if (!empty($res['cookie'])) {
            $this->store['cookie'] = $res['cookie'];
        }

        // Parse page links (<a href>) → new pages
        $replace = $this->parsePageLinks($html, $page);
        // Parse asset links (<link/script/img/video/source>) → assets
        $replace = array_merge($replace, $this->parseAssetLinks($html, $page));

        foreach ($replace as $from => $to) {
            $html = str_replace($from, $to, $html);
        }

        $dir = dirname($page['saveFile']);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($page['saveFile'], $html);
        $page['state'] = 'success';
        $page['size'] = strlen($html);
    }

    private function runLink(array &$link): void
    {
        $url = $link['url'];
        $max = (int) ($this->config['maxRetryTimes'] ?? 3);
        if (($link['retry'] ?? 0) >= $max) {
            $link['state'] = 'fail';
            return;
        }
        $link['retry'] = ($link['retry'] ?? 0) + 1;

        if ($this->isExcluded($url, false)) {
            $link['state'] = 'fail';
            return;
        }

        $res = $this->fetch($url);
        if ($res === null) {
            $link['state'] = ($link['retry'] >= $max) ? 'fail' : 'wait';
            return;
        }
        $type = $res['contentType'] ?? '';
        $size = (int) ($res['size'] ?? 0);
        // Size limits (mirrors original LinkTask).
        if (str_starts_with($type, 'image/')) {
            $lim = ($this->config['maxImgSize'] ?? 0) * 1024 * 1024;
            if ($lim > 0 && $size > $lim) {
                $link['state'] = 'fail';
                return;
            }
        } elseif (str_starts_with($type, 'audio/') || str_starts_with($type, 'video/')) {
            $lim = ($this->config['maxVideoSize'] ?? 0) * 1024 * 1024;
            if ($lim > 0 && $size > $lim) {
                $link['state'] = 'fail';
                return;
            }
        }

        $dir = dirname($link['saveFile']);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ok = file_put_contents($link['saveFile'], $res['body']) !== false;
        $link['state'] = $ok ? 'success' : 'fail';
        $link['size'] = $ok ? strlen($res['body']) : 0;
        $link['type'] = $type;
    }

    private function parsePageLinks(string $html, array $page): array
    {
        $replace = [];
        $reg = '/<a([^<]+)href="([^"]+)"/i';
        if (!preg_match_all($reg, $html, $m, PREG_SET_ORDER)) {
            return $replace;
        }
        foreach ($m as $set) {
            $href = $set[2];
            $href = trim($href);
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }
            $abs = $this->absUrl($href, $page['url']);
            if ($abs === null) {
                continue;
            }
            if (!in_array(parse_url($abs, PHP_URL_SCHEME), ['http', 'https'], true)) {
                continue;
            }
            if (parse_url($abs, PHP_URL_HOST) !== $this->host) {
                continue;
            }
            if (($this->config['pageLimit'] ?? '') && !str_contains($abs, $this->config['pageLimit'])) {
                continue;
            }
            if ($this->isExcluded($abs, true)) {
                continue;
            }
            $linkUrl = $this->stripFragment($abs);
            // Canonicalize the directory index (/index.html ≡ /) so the same
            // resource is not crawled twice under two different URLs.
            $pu = parse_url($linkUrl);
            if (($pu['path'] ?? '') === '/index.html') {
                $linkUrl = ($pu['scheme'] ?? 'http') . '://' . ($pu['host'] ?? '')
                    . (isset($pu['port']) ? ':' . $pu['port'] : '') . '/';
            }
            // Always rewrite the local path, even if the link was already
            // enqueued (dedup only affects crawling, not rewriting).
            $saveFile = $this->urlToDir($linkUrl, true);
            $replace['href="' . $href . '"'] = 'href="' . $this->relPath($saveFile) . '"';
            if (!isset($this->store['excludeUrl'][$linkUrl])) {
                $this->store['excludeUrl'][$linkUrl] = true;
                $this->enqueuePage($linkUrl, $saveFile);
            }
        }
        return $replace;
    }

    private function parseAssetLinks(string $html, array $page): array
    {
        $replace = [];
        $pats = [
            '/<link([^<]+)href="([^"]+)"/i',
            '/<script([^<]+)src="([^"]+)"/i',
            '/<img([^<]+)src="([^"]+)"/i',
            '/<video([^<]+)src="([^"]+)"/i',
            '/<video([^<]+)poster="([^"]+)"/i',
            '/<source([^<]+)src="([^"]+)"/i',
        ];
        foreach ($pats as $reg) {
            if (!preg_match_all($reg, $html, $m, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($m as $set) {
                $src = $set[2];
                $src = trim($src);
                if ($src === '' || str_starts_with($src, '#') || str_starts_with($src, 'data:')) {
                    continue;
                }
                $abs = $this->absUrl($src, $page['url']);
                if ($abs === null) {
                    continue;
                }
                if (!in_array(parse_url($abs, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    continue;
                }
                if ($this->isExcluded($abs, false)) {
                    continue;
                }
                $lower = strtolower($src);
                if (str_contains($lower, '.html') || str_contains($lower, '.htm') || str_contains($lower, '.php')) {
                    continue;
                }
                $linkUrl = $this->stripFragment($abs);
                if (isset($this->store['excludeUrl'][$linkUrl])) {
                    continue;
                }
                $this->store['excludeUrl'][$linkUrl] = true;
                $saveFile = $this->urlToDir($linkUrl);
                $replace['href="' . $src . '"'] = 'href="' . $this->relPath($saveFile) . '"';
                $replace['poster="' . $src . '"'] = 'poster="' . $this->relPath($saveFile) . '"';
                $replace['src="' . $src . '"'] = 'src="' . $this->relPath($saveFile) . '"';
                $this->enqueueLink($linkUrl, $saveFile);
            }
        }
        return $replace;
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function enqueuePage(string $url, string $saveFile): void
    {
        $this->store['excludeUrl'][$url] = true;
        $this->store['pages'][] = [
            'url'      => $url,
            'saveFile' => $saveFile,
            'state'    => 'wait',
            'type'     => 'text/html',
            'size'     => 0,
            'retry'    => 0,
        ];
    }

    private function enqueueLink(string $url, string $saveFile): void
    {
        $this->store['excludeUrl'][$url] = true;
        $this->store['links'][] = [
            'url'      => $url,
            'saveFile' => $saveFile,
            'state'    => 'wait',
            'type'     => '',
            'size'     => 0,
            'retry'    => 0,
        ];
    }

    private function takeWait(string $key): int
    {
        foreach ($this->store[$key] as $i => $item) {
            if ($item['state'] === 'wait') {
                return $i;
            }
        }
        return -1;
    }

    private function hasState(array $list, string $state): bool
    {
        foreach ($list as $item) {
            if ($item['state'] === $state) {
                return true;
            }
        }
        return false;
    }

    private function isExcluded(string $url, bool $isPage): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && in_array($host, $this->excludeHosts(), true)) {
            return true;
        }
        if ($isPage && ($this->config['pageLimit'] ?? '') && !str_contains($url, $this->config['pageLimit'])) {
            return true;
        }
        return false;
    }

    private function excludeHosts(): array
    {
        $ex = self::BASE_EXCLUDE_HOST;
        $user = trim((string) ($this->config['excludeLink'] ?? ''));
        if ($user !== '') {
            foreach (preg_split('/\r?\n/', $user) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $ex[] = $line;
                }
            }
        }
        return array_values(array_unique($ex));
    }

    /** Map a URL to a local absolute save path (mirrors original urlToDir). */
    private function urlToDir(string $url, bool $isPage = false): string
    {
        $pu = parse_url($url);
        $host = $pu['host'] ?? '';
        if ($host === $this->host) {
            $pathDir = $pu['path'] ?? '/';
            if ($pathDir === '' || str_ends_with($pathDir, '/')) {
                $pathDir .= 'index.html';
            }
            $segs = array_filter(explode('/', $pathDir), fn($s) => trim($s) !== '');
            $pathDir = implode('/', $segs);
            if ($isPage) {
                $name = basename($pathDir);
                if (isset($pu['query']) && $pu['query'] !== '') {
                    $newName = md5($name) . '.html';
                    $pathDir = substr($pathDir, 0, -strlen($name)) . $newName;
                } else {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    if ($ext !== '') {
                        if ($ext !== 'html') {
                            $newName = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.html', $name);
                            $pathDir = substr($pathDir, 0, -strlen($name)) . $newName;
                        }
                    } else {
                        $pathDir = $pathDir . '.html';
                    }
                }
            }
            $pathDir = trim($pathDir);
            if ($pathDir === '.html' || $pathDir === '') {
                $pathDir = 'index.html';
            }
            return $this->dir . '/' . $pathDir;
        }
        // Cross-host asset → outsite/<md5>.<ext>
        $clean = $this->stripQuery($this->stripFragment($url));
        $ext = pathinfo((string) parse_url($clean, PHP_URL_PATH), PATHINFO_EXTENSION);
        return $this->dir . '/outsite/' . md5($clean) . ($ext ? '.' . $ext : '');
    }

    /** Relative path of a saved file vs the site root dir (for rewriting). */
    private function relPath(string $saveFile): string
    {
        $rel = ltrim(substr($saveFile, strlen($this->dir)), '/');
        return './' . $rel;
    }

    private function normalizeUrl(string $url): string
    {
        return $this->stripFragment($url);
    }

    /** Resolve a possibly-relative href against a base absolute URL. */
    private function absUrl(string $rel, string $base): ?string
    {
        $rel = trim($rel);
        if ($rel === '' || str_starts_with($rel, '//')) {
            // protocol-relative
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $rel;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $rel)) {
            return $rel; // already absolute
        }
        if (str_starts_with($rel, '#') || str_starts_with($rel, 'data:') || str_starts_with($rel, 'mailto:')) {
            return null;
        }
        $bu = parse_url($base);
        if (!isset($bu['host'])) {
            return null;
        }
        $scheme = $bu['scheme'] ?? 'https';
        $host = $bu['host'];
        $port = isset($bu['port']) ? ':' . $bu['port'] : '';
        $basePath = $bu['path'] ?? '/';
        if (str_starts_with($rel, '/')) {
            $path = $rel;
        } else {
            $dir = preg_replace('#/[^/]*$#', '/', $basePath);
            $path = $dir . $rel;
        }
        // Normalize '.' and '..'
        $parts = explode('/', $path);
        $out = [];
        foreach ($parts as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }
            if ($p === '..') {
                array_pop($out);
                continue;
            }
            $out[] = $p;
        }
        $path = '/' . implode('/', $out);
        $query = '';
        if (($q = strpos($rel, '?'))) {
            $query = substr($rel, $q);
        }
        return $scheme . '://' . $host . $port . $path . $query;
    }

    private function stripFragment(string $url): string
    {
        $i = strpos($url, '#');
        return $i === false ? $url : substr($url, 0, $i);
    }

    private function stripQuery(string $url): string
    {
        $i = strpos($url, '?');
        return $i === false ? $url : substr($url, 0, $i);
    }

    private function fetch(string $url): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => (int) ($this->config['timeout'] ?? 10),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FlyEnvSiteSucker/1.0)',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $this->store['cookie'] ? ['Cookie: ' . $this->store['cookie']] : [],
        ]);
        if (!empty($this->config['proxy'])) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['proxy']);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            curl_close($ch);
            return null;
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $headerBlob = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        curl_close($ch);

        if ($code >= 400) {
            return null;
        }
        // Capture cookies from Set-Cookie headers (mirrors original cookie usage).
        $cookie = $this->store['cookie'];
        foreach (explode("\r\n", $headerBlob) as $line) {
            if (preg_match('/^Set-Cookie:\s*(.+?)=(.+?);/i', $line, $mm)) {
                $cookie = ($cookie ? $cookie . ' ' : '') . $mm[1] . '=' . $mm[2] . ';';
            }
        }

        return [
            'body'        => $body,
            'contentType' => $ct,
            'size'        => $size,
            'cookie'      => $cookie,
        ];
    }

    // ── Persistence ───────────────────────────────────────────────

    private function file(string $taskId): string
    {
        return $this->tmpDir . '/flyenv_ss_' . $taskId . '.json';
    }

    private function load(string $taskId): void
    {
        $f = $this->file($taskId);
        if (is_file($f)) {
            $data = json_decode((string) file_get_contents($f), true);
            if (is_array($data)) {
                $this->store = $data;
                $this->host = $data['host'] ?? '';
                $this->dir = $data['dir'] ?? '';
                $this->config = $data['config'] ?? [];
                return;
            }
        }
        $this->store = ['state' => 'missing'];
    }

    private function save(string $taskId): void
    {
        $this->store['config'] = $this->config;
        file_put_contents($this->file($taskId), json_encode($this->store));
    }

    private function applyConfig(array $config): void
    {
        $this->config = [
            'dir'          => $config['dir'] ?? '',
            'proxy'        => trim((string) ($config['proxy'] ?? '')),
            'excludeLink'  => $config['excludeLink'] ?? '',
            'pageLimit'    => trim((string) ($config['pageLimit'] ?? '')),
            'timeout'      => (int) ($config['timeout'] ?? 10),
            'maxImgSize'   => (int) ($config['maxImgSize'] ?? 0),
            'maxVideoSize' => (int) ($config['maxVideoSize'] ?? 0),
            'maxRetryTimes' => (int) ($config['maxRetryTimes'] ?? 3),
            'windowCount'  => (int) ($config['windowCount'] ?? 2),
        ];
        if ($this->config['timeout'] <= 0) {
            $this->config['timeout'] = 10;
        }
    }
}
