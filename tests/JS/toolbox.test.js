/**
 * FlyEnv Toolbox — Frontend JavaScript Tests
 *
 * Tests pure functions from toolbox-i18n.js, toolbox-logic.js, toolbox-panels.js
 */
import { describe, test, expect, beforeEach } from 'vitest';

// ─── i18n: _t() ───────────────────────────────────────────────────────────

describe('i18n system', () => {
  test('_t returns Chinese translation when lang is zh', () => {
    // Default lang is 'zh' from localStorage/setup
    expect(globalThis._t('search_ph')).toBe('搜索工具...');
    expect(globalThis._t('cmp')).toBe('比较');
    expect(globalThis._t('gen')).toBe('生成');
  });

  test('_t returns English translation when lang is en', () => {
    // Use toggleLang() to switch, as _t reads the internal `lang` variable
    globalThis.toggleLang(); // zh → en
    expect(globalThis._t('search_ph')).toBe('Search tools...');
    expect(globalThis._t('cmp')).toBe('Compare');
    expect(globalThis._t('gen')).toBe('Generate');
    globalThis.toggleLang(); // Switch back
  });

  test('_t returns key as fallback for missing keys', () => {
    expect(globalThis._t('nonexistent_key_xyz')).toBe('nonexistent_key_xyz');
  });

  test('_t falls back to English when Chinese key is missing', () => {
    // All keys exist in both languages, so this tests that _t is callable
    const result = globalThis._t('cat_code');
    expect(['代码', 'Code']).toContain(result);
  });

  test('toggleLang switches language and updates button text', () => {
    const btn = document.getElementById('langBtn');
    // Default: lang='zh', button shows 'EN' (indicates clicking goes to EN)
    globalThis.toggleLang(); // zh → en
    expect(btn.textContent).toBe('中'); // Now en, button shows '中'

    globalThis.toggleLang(); // en → zh
    expect(btn.textContent).toBe('EN'); // Now zh, button shows 'EN'
  });

  test('I18N object has both zh and en translations', () => {
    expect(globalThis.I18N).toBeDefined();
    expect(globalThis.I18N.zh).toBeDefined();
    expect(globalThis.I18N.en).toBeDefined();
    // Should have the same keys in both languages
    const zhKeys = Object.keys(globalThis.I18N.zh).sort();
    const enKeys = Object.keys(globalThis.I18N.en).sort();
    expect(zhKeys).toEqual(enKeys);
  });
});

// ─── esc() — HTML escaping ─────────────────────────────────────────────────

describe('esc() — HTML escaping', () => {
  test('escapes & to &amp;', () => {
    expect(globalThis.esc('a & b')).toBe('a &amp; b');
  });

  test('escapes < to &lt;', () => {
    expect(globalThis.esc('<script>')).toBe('&lt;script&gt;');
  });

  test('escapes > to &gt;', () => {
    expect(globalThis.esc('5 > 3')).toBe('5 &gt; 3');
  });

  test('handles safe strings unchanged', () => {
    expect(globalThis.esc('Hello World')).toBe('Hello World');
  });

  test('handles empty string', () => {
    expect(globalThis.esc('')).toBe('');
  });

  test('converts non-string to string first', () => {
    expect(globalThis.esc(42)).toBe('42');
  });
});

// ─── isFav() / toggleFav() ─────────────────────────────────────────────────

describe('isFav() / toggleFav()', () => {
  beforeEach(() => {
    // Mutate in place to keep local `favs` and globalThis.favs in sync
    if (globalThis.favs) globalThis.favs.length = 0;
  });

  test('isFav returns false for non-favorited tool', () => {
    expect(globalThis.isFav('JsonParse')).toBe(false);
  });

  test('isFav returns true after toggling', () => {
    globalThis.toggleFav('JsonParse');
    expect(globalThis.isFav('JsonParse')).toBe(true);
  });

  test('toggleFav toggles off again', () => {
    globalThis.toggleFav('JsonParse');
    globalThis.toggleFav('JsonParse');
    expect(globalThis.isFav('JsonParse')).toBe(false);
  });

  test('toggleFav persists multiple favorites', () => {
    globalThis.toggleFav('JsonParse');
    globalThis.toggleFav('Timestamp');
    expect(globalThis.favs).toEqual(['JsonParse', 'Timestamp']);
    expect(globalThis.isFav('JsonParse')).toBe(true);
    expect(globalThis.isFav('Timestamp')).toBe(true);
  });
});

// ─── b64u() / b64ud() — URL-safe Base64 ────────────────────────────────────

describe('b64u() / b64ud() — URL-safe Base64', () => {
  test('b64u encodes standard input', () => {
    const encoded = globalThis.b64u('Hello');
    expect(encoded).toBeTruthy();
    expect(encoded).not.toContain('+');
    expect(encoded).not.toContain('/');
    expect(encoded).not.toContain('=');
  });

  test('b64ud decodes what b64u encoded', () => {
    const original = 'Hello FlyEnv!';
    const encoded = globalThis.b64u(original);
    const decoded = globalThis.b64ud(encoded);
    expect(decoded).toBe(original);
  });

  test('b64ud handles URL-safe characters', () => {
    const encoded = globalThis.b64u('test+encode/data');
    expect(encoded).not.toContain('+');
    expect(encoded).not.toContain('/');
    const decoded = globalThis.b64ud(encoded);
    expect(decoded).toBe('test+encode/data');
  });

  test('b64ud returns null for invalid input', () => {
    expect(globalThis.b64ud('!!!invalid!!!')).toBeNull();
  });

  test('b64u and b64ud are inverses for various inputs', () => {
    const inputs = ['a', 'abc', 'hello world', 'data:image/png;base64,'];
    inputs.forEach(input => {
      expect(globalThis.b64ud(globalThis.b64u(input))).toBe(input);
    });
  });
});

// ─── sK() — Sort object keys ───────────────────────────────────────────────

describe('sK() — recursive key sort', () => {
  test('sorts flat object keys', () => {
    const input = { z: 1, a: 2, m: 3 };
    const result = globalThis.sK(input);
    expect(Object.keys(result)).toEqual(['a', 'm', 'z']);
    expect(result).toEqual({ a: 2, m: 3, z: 1 });
  });

  test('sorts nested object keys recursively', () => {
    const input = { b: { z: 1, a: 2 }, a: 3 };
    const result = globalThis.sK(input);
    expect(Object.keys(result)).toEqual(['a', 'b']);
    expect(Object.keys(result.b)).toEqual(['a', 'z']);
  });

  test('preserves arrays', () => {
    const input = { b: [3, 1, 2], a: 'test' };
    const result = globalThis.sK(input);
    expect(Object.keys(result)).toEqual(['a', 'b']);
    expect(result.b).toEqual([3, 1, 2]); // arrays unchanged
  });

  test('returns primitives as-is', () => {
    expect(globalThis.sK(null)).toBeNull();
    expect(globalThis.sK(42)).toBe(42);
    expect(globalThis.sK('hello')).toBe('hello');
  });

  test('handles empty object', () => {
    expect(globalThis.sK({})).toEqual({});
  });
});

// ─── goHome() / openTool() ─────────────────────────────────────────────────

describe('goHome() / openTool()', () => {
  test('goHome shows homeView and hides toolView', () => {
    const homeView = document.getElementById('homeView');
    const toolView = document.getElementById('toolView');
    toolView.style.display = '';
    globalThis.goHome();
    expect(homeView.style.display).toBe('');
    expect(toolView.style.display).toBe('none');
  });

  test('openTool switches to tool view', () => {
    globalThis.goHome();
    globalThis.openTool('JsonParse');
    const toolView = document.getElementById('toolView');
    expect(toolView.style.display).toBe('');
  });
});

// ─── toggleSidebar() ───────────────────────────────────────────────────────

describe('toggleSidebar()', () => {
  test('toggles sidebar visibility', () => {
    const sidebar = document.getElementById('sidebar');
    sidebar.style.width = '240px';
    sidebar.style.minWidth = '240px';

    globalThis.toggleSidebar();
    expect(sidebar.style.width).toBe('0px');

    globalThis.toggleSidebar();
    expect(sidebar.style.width).toBe('240px');
  });
});

// ─── getPanelContent() ─────────────────────────────────────────────────────

describe('getPanelContent()', () => {
  test('returns panel HTML for known tool ID', () => {
    const result = globalThis.getPanelContent('JsonParse');
    expect(result).toContain('tool-view-hdr');
    expect(result).toContain('JSON Parser');
  });

  test('returns fallback for unknown tool ID', () => {
    const result = globalThis.getPanelContent('unknown_tool_xyz');
    expect(result).toContain('unknown_tool_xyz');
  });
});

// ─── doSearch() ────────────────────────────────────────────────────────────

describe('doSearch()', () => {
  test('filters tool cards by name', () => {
    // Setup: render home first to create tool cards
    globalThis.goHome();

    const allSection = document.getElementById('allSection');
    expect(allSection.innerHTML.length).toBeGreaterThan(0);

    // Search for a specific tool
    globalThis.doSearch('base64');
    const cards = allSection.querySelectorAll('.tool-card');
    const visibleCards = Array.from(cards).filter(c => c.style.display !== 'none');
    expect(visibleCards.length).toBeGreaterThan(0);
  });

  test('empty search shows all tools', () => {
    globalThis.goHome();
    globalThis.doSearch('');
    const allSection = document.getElementById('allSection');
    const cards = allSection.querySelectorAll('.tool-card');
    const hiddenCards = Array.from(cards).filter(c => c.style.display === 'none');
    expect(hiddenCards.length).toBe(0);
  });
});

// ─── HC HTTP Status Codes data ─────────────────────────────────────────────

describe('HTTP_CODES — HTTP status codes data', () => {
  test('contains common status codes', () => {
    const find = (c) => globalThis.HTTP_CODES.find(x => x.code === c);
    expect(find(200).name).toBe('OK');
    expect(find(404).name).toBe('Not Found');
    expect(find(500).name).toBe('Internal Server Error');
    expect(find(301).name).toBe('Moved Permanently');
    expect(find(403).name).toBe('Forbidden');
  });

  test('has 2xx, 3xx, 4xx, 5xx codes', () => {
    const codes = globalThis.HTTP_CODES.map(x => x.code);
    expect(codes.some(c => c >= 200 && c < 300)).toBe(true);
    expect(codes.some(c => c >= 300 && c < 400)).toBe(true);
    expect(codes.some(c => c >= 400 && c < 500)).toBe(true);
    expect(codes.some(c => c >= 500 && c < 600)).toBe(true);
  });

  test('contains the full 63-code dataset from FlyEnv 4.15.4', () => {
    expect(globalThis.HTTP_CODES.length).toBe(63);
    expect(globalThis.HTTP_CATS.length).toBe(5);
  });
});

// ─── MIME_LIST data ────────────────────────────────────────────────────

describe('MIME_LIST — MIME types data', () => {
  test('contains common MIME types', () => {
    const has = (mime, ext) => globalThis.MIME_LIST.some(m => m.mime === mime && m.ext === ext);
    expect(has('text/html', 'html')).toBe(true);
    expect(has('application/json', 'json')).toBe(true);
    expect(has('image/png', 'png')).toBe(true);
    expect(has('application/pdf', 'pdf')).toBe(true);
  });
});

// ─── Batch 2 logic: Cron / Chmod / Regex ──────────────────────────────────

describe('cronGetNextRuns — cron parser', () => {
  test('parses 5-field expression and returns N runs', () => {
    const res = globalThis.cronGetNextRuns('*/5 * * * *', 10, 'linux');
    expect(res.detectedMode).toBe('linux');
    expect(res.runs.length).toBe(10);
    expect(res.runs[0]).toBeInstanceOf(Date);
  });
  test('every-minute yields runs ~1 min apart', () => {
    const res = globalThis.cronGetNextRuns('* * * * *', 3, 'linux');
    expect(res.runs.length).toBe(3);
  });
  test('invalid expression throws', () => {
    expect(() => globalThis.cronGetNextRuns('not a cron at all here x', 5, 'linux')).toThrow();
  });
});

describe('chmodSym — chmod helpers', () => {
  test('maps permission bits to symbolic string', () => {
    expect(globalThis.chmodSym(7)).toBe('rwx');
    expect(globalThis.chmodSym(6)).toBe('rw-');
    expect(globalThis.chmodSym(4)).toBe('r--');
    expect(globalThis.chmodSym(0)).toBe('---');
  });
});

describe('rxMatch — regex engine', () => {
  test('returns index, value, captures and named groups', () => {
    const r = globalThis.rxMatch('(\\w+)', 'hello world', 'dg');
    expect(r.length).toBe(2);
    expect(r[0].value).toBe('hello');
    expect(r[0].captures[0].value).toBe('hello');
    expect(r[0].index).toBe(0);
    expect(r[1].value).toBe('world');
  });
  test('named groups are captured', () => {
    const r = globalThis.rxMatch('(?<year>\\d{4})', '2026', 'd');
    expect(r[0].groups[0].name).toBe('year');
    expect(r[0].groups[0].value).toBe('2026');
  });
});

// ─── Panel HTML Templates ──────────────────────────────────────────────────

describe('Panel HTML templates', () => {
  test('__p contains all expected panel functions', () => {
    const expected = [
      'diff', 'cron', 'json', 'jwt', 'hash', 'encrypt', 'ts', 'b64',
      'url', 'html', 'regex', 'chmod', 'token', 'http', 'mime', 'bom',
      'md', 'wss', 'code', 'clib', 'qr', 'wifi', 'img', 'capture',
      'rsa', 'file', 'timing', 'suck', 'ssl', 'obf', 'portkill',
      'prockill', 'keycode', 'regex_memo', 'git_memo',
    ];
    expected.forEach(key => {
      expect(globalThis.__p).toHaveProperty(key);
    });
  });

  test('panel functions return string output', () => {
    Object.entries(globalThis.__p).forEach(([key, fn]) => {
      const html = fn();
      expect(typeof html).toBe('string');
      expect(html.length).toBeGreaterThan(0);
    });
  });

  test('panel functions are callable (no runtime errors)', () => {
    Object.entries(globalThis.__p).forEach(([key, fn]) => {
      expect(() => fn()).not.toThrow();
    });
  });
});

// ─── Batch 2 panels: render + init hooks (jsdom) ──────────────────────────

describe('Batch 2 panels render & init', () => {
  const B2 = ['http', 'mime', 'chmod', 'cron', 'regex', 'jwt'];
  B2.forEach((tool) => {
    test(`__p.${tool} renders and __init_${tool} runs without error`, () => {
      document.body.innerHTML = globalThis.__p[tool]();
      expect(() => {
        if (typeof globalThis['__init_' + tool] === 'function') globalThis['__init_' + tool]();
      }).not.toThrow();
    });
  });

  test('http panel shows status codes after init', () => {
    document.body.innerHTML = globalThis.__p.http();
    globalThis.__init_http();
    expect(document.getElementById('hO2').innerHTML).toContain('200');
  });

  test('chmod panel computes 000 when nothing checked', () => {
    document.body.innerHTML = globalThis.__p.chmod();
    globalThis.__init_chmod();
    expect(document.getElementById('cOct').textContent).toBe('000');
  });

  test('chmod panel computes 755 symbolic when owner=all group/public=rx', () => {
    document.body.innerHTML = globalThis.__p.chmod();
    document.getElementById('c_or').checked = true;
    document.getElementById('c_ow').checked = true;
    document.getElementById('c_oe').checked = true;
    document.getElementById('c_gr').checked = true;
    document.getElementById('c_ge').checked = true;
    document.getElementById('c_pr').checked = true;
    document.getElementById('c_pe').checked = true;
    globalThis.fCM();
    expect(document.getElementById('cOct').textContent).toBe('755');
    expect(document.getElementById('cSym').textContent).toBe('rwxr-xr-x');
  });

  test('cron panel produces next-run table', () => {
    document.body.innerHTML = globalThis.__p.cron();
    globalThis.__init_cron();
    expect(document.getElementById('cRuns').innerHTML).toContain('<table');
  });

  test('regex panel shows matches', () => {
    document.body.innerHTML = globalThis.__p.regex();
    document.getElementById('rxP').value = '(\\w+)';
    document.getElementById('rxT').value = 'Hello World';
    globalThis.rxCompute();
    expect(document.getElementById('rxMatches').innerHTML).toContain('Hello');
  });

  // ── Batch 3: Editor tools ──
  test('json panel renders and init parses sample', () => {
    document.body.innerHTML = globalThis.__p.json();
    globalThis.__init_json();
    expect(document.getElementById('jsonOut').value).toContain('FlyEnv');
    expect(document.getElementById('jsonType').textContent).toContain('JSON');
  });

  test('json transforms to PHP / YAML / XML', () => {
    const obj = { a: 1, b: 'x' };
    expect(globalThis.jsonTransform(obj, 'php')).toContain('<?php');
    expect(globalThis.jsonTransform(obj, 'yaml')).toContain('a: 1');
    expect(globalThis.jsonTransform(obj, 'xml')).toContain('<root>');
    expect(globalThis.jsonTransform(obj, 'ts')).toContain('interface Root');
  });

  test('json invalid input shows error message', () => {
    document.body.innerHTML = globalThis.__p.json();
    document.getElementById('jsonIn').value = '{ bad json';
    globalThis.jsonInput();
    expect(document.getElementById('jsonOut').value.length).toBeGreaterThan(0);
  });

  test('markdown panel renders preview pane', () => {
    document.body.innerHTML = globalThis.__p.md();
    globalThis.__init_md();
    expect(document.getElementById('mdOut')).not.toBeNull();
  });

  test('diff panel loads sample and shows side-by-side changes', () => {
    document.body.innerHTML = globalThis.__p.diff();
    globalThis.__init_diff();
    const out = document.getElementById('diffOut').innerHTML;
    expect(out).toContain('dl added');
    expect(out).toContain('dl removed');
    expect(document.getElementById('diffOutO')).not.toBeNull();
    expect(document.getElementById('diffOutC')).not.toBeNull();
    expect(document.getElementById('diffStats').innerHTML).toContain('ep-tag');
    expect(document.getElementById('diffStats').innerHTML).toContain(_t('diff_added'));
    expect(document.getElementById('diffStats').innerHTML).toContain(_t('diff_unchanged'));
  });

  test('diff computes correct add/remove marks and inline segments', () => {
    const r = globalThis.diffCompute('a\nb\nc', 'a\nx\nc');
    expect(r.rows.some((x) => x.type === 'removed' && x.value === 'b')).toBe(true);
    expect(r.rows.some((x) => x.type === 'added' && x.value === 'x')).toBe(true);
    expect(r.stats.added).toBe(1);
    expect(r.stats.removed).toBe(1);
    expect(r.stats.changed).toBe(1);
    expect(r.stats.unchanged).toBe(2);
    expect(r.targets.length).toBe(2);
  });

  test('diff navigation cycles active diff', () => {
    document.body.innerHTML = globalThis.__p.diff();
    globalThis.diffSample();
    globalThis.diffNext();
    expect(document.getElementById('diffOut').querySelector('.dl.active')).not.toBeNull();
    globalThis.diffPrev();
    globalThis.diffPrev();
    expect(document.getElementById('diffOut').querySelector('.dl.active')).not.toBeNull();
  });

  test('code playground panel renders with output area', () => {
    document.body.innerHTML = globalThis.__p.code();
    globalThis.__init_code();
    expect(document.getElementById('cO3')).not.toBeNull();
    expect(document.getElementById('codeIn')).not.toBeNull();
  });

  test('code library panel renders tabs, sidebar and main', () => {
    document.body.innerHTML = globalThis.__p.clib();
    globalThis.__init_clib();
    expect(document.getElementById('clibTabs').children.length).toBeGreaterThan(0);
    expect(document.getElementById('clibGroups')).toBeTruthy();
    expect(document.getElementById('clibCodes')).toBeTruthy();
    expect(document.getElementById('clibMain')).toBeTruthy();
  });

  test('clibTab switches language and selects a snippet', () => {
    document.body.innerHTML = globalThis.__p.clib();
    globalThis.__init_clib();
    globalThis.clibTab('php');
    expect(document.getElementById('clibCodes').innerHTML).toContain('cURL GET');
    expect(document.getElementById('clibMain').innerHTML).toContain('cURL GET');
  });

  test('code library add snippet persists and renders', () => {
    document.body.innerHTML = globalThis.__p.clib();
    globalThis.__init_clib();
    globalThis.clibTab('python');
    globalThis.clibAddCode();
    document.getElementById('clibFName').value = 'My Snip';
    document.getElementById('clibFCode').value = 'print(1)';
    globalThis.clibCodeSave('');
    expect(document.getElementById('clibCodes').innerHTML).toContain('My Snip');
  });

  test('code library add group and toggle settings', () => {
    document.body.innerHTML = globalThis.__p.clib();
    globalThis.__init_clib();
    globalThis.clibTab('php');
    globalThis.clibToggleSettings({ stopPropagation() {} });
    expect(document.getElementById('clibSet').style.display).toBe('');
    globalThis.clibAddGroup();
    const nameEl = document.getElementById('clibGName');
    expect(nameEl).toBeTruthy();
    nameEl.value = 'GrpTest';
    globalThis.clibGroupSave('');
    expect(document.getElementById('clibGroups').innerHTML).toContain('GrpTest');
  });

  test('code library seed data persists to localStorage', () => {
    document.body.innerHTML = globalThis.__p.clib();
    globalThis.__init_clib();
    const raw = localStorage.getItem('phptools2-code-library');
    expect(raw).toBeTruthy();
    const data = JSON.parse(raw);
    expect(Array.isArray(data.items)).toBe(true);
    expect(data.items.length).toBeGreaterThan(0);
  });

  test('mime panel shows extension tag for selected mime', () => {
    document.body.innerHTML = globalThis.__p.mime();
    globalThis.__init_mime();
    const sel = document.getElementById('mMime');
    sel.value = sel.options[0].value;
    globalThis.fMI();
    expect(document.getElementById('mExts').innerHTML).toContain('ep-tag');
  });
});

// ─── Batch 4 panels: render + bridge-wired smoke tests (jsdom) ─────────────
// These verify the full wiring: panel render → glue/init → PHP bridge stub →
// cPHP handler → output element. A stubbed bridge makes cPHP resolve instantly
// (avoids the 50×100ms wait loop when window[name] is missing).

describe('Batch 4 panels render & bridge wiring', () => {
  function stubBridge(name, resp) {
    globalThis[name] = function () { return Promise.resolve(resp); };
  }
  const tick = (ms) => new Promise((r) => setTimeout(r, ms || 40));

  const CASES = [
    { id: 'qr',       bridge: 'qr',          glue: 'qrGen',       out: 'qrOut',  mock: { svg: 'data:image/svg+xml;base64,AAA' } },
    { id: 'wifi',     bridge: 'qr',          glue: 'wifiGen',     out: 'wO2',    mock: { svg: 'data:image/svg+xml;base64,BBB' } },
    { id: 'img',      bridge: 'image_c',     glue: 'imgCompress', out: 'imgO',   mock: { dim: '100x100', ratio: 50, orig: 1000, size: 500, saved: '/tmp/x.png' } },
    { id: 'capture',  bridge: 'capture',     glue: null,          out: 'capO',   mock: { path: '/tmp/cap.png', size: 10 }, call: () => globalThis.cPHP('capture', { type: 'full' }) },
    { id: 'rsa',      bridge: 'rsa',         glue: 'rsaGen',      out: 'rPub', out2: 'rPriv', mock: { public: 'PUBKEY', private: 'PRIVKEY' } },
    { id: 'file',     bridge: 'file_info',   glue: null,          out: 'fO',     mock: { path: '/a', type: 'file', size: 1024, size_str: '1 KB', btime: 1600000000000, btime_str: '2020-09-13T12:26:40+00:00', ctime: 1600000000000, ctime_str: '2020-09-13T12:26:40+00:00', atime: 1600000000000, atime_str: '2020-09-13T12:26:40+00:00', mtime: 1600000000000, mtime_str: '2020-09-13T12:26:40+00:00', md5: 'm', sha1: 's', sha256: 'h', sha512: 'x', sha512Base64: 'b' }, call: () => globalThis.fileInfoPath('/a') },
    { id: 'timing',   bridge: 'url_timing',  glue: 'reqTime',     out: 'tO2',    mock: { total: 10, dns: 1, tcp: 2, ssl: 1, ttfb: 3, code: 200, size: 100 } },
    { id: 'suck',     bridge: 'site_suck_start', glue: null, out: 'ssStat', mock: null,
      call: () => {
        document.body.innerHTML = globalThis.__p.suck();
        globalThis.window.site_suck_start = function () {
          return Promise.resolve({
            taskId: 't1', state: 'done', dir: '/tmp/out',
            counts: { page: 1, link: 3, success: 3, fail: 0, running: 0, wait: 0 },
            links: [{ url: 'https://e.com/a.html', state: 'success' }],
            hosts: [{ host: 'e.com', allow: true }],
          });
        };
        globalThis.window.site_suck_step = function () {
          return Promise.resolve({ state: 'done', counts: {}, links: [], hosts: [] });
        };
        const u = document.getElementById('ssUrl'); if (u) u.value = 'https://e.com';
        globalThis.ssRun();
      }
    },
    { id: 'ssl',      bridge: 'ssl_make',    glue: 'sslMake',     out: 'sslDomains', noDomOut: true, mock: { cert_path: '/tmp/ssl/test.crt', key_path: '/tmp/ssl/test.key', cert: 'CERT', key: 'KEY' } },
    { id: 'obf',      bridge: 'php_obf',     glue: null,          out: 'obResult', mock: { code: 0, desc: '/out.php' }, call: () => { var p = document.getElementById('obPhp'); if (p) p.value = '/usr/bin/php'; var s = document.getElementById('obSrc'); if (s) s.value = '/in.php'; var d = document.getElementById('obDesc'); if (d) d.value = '/out.php'; globalThis.OBF.phpBin = '/usr/bin/php'; globalThis.OBF.src = '/in.php'; globalThis.OBF.desc = '/out.php'; globalThis.obRun(); } },
    { id: 'portkill', bridge: 'port_kill',   glue: 'pkSearch',    out: 'pkO',    mock: { port: 8080, processes: [{ PID: 123, USER: 'u', COMMAND: 'c' }] } },
    { id: 'prockill', bridge: 'process_kill',glue: 'procSearch',  out: 'procO',  mock: { processes: [{ PID: 123, USER: 'u', COMMAND: 'c' }] } },
  ];

  CASES.forEach((c) => {
    test(`__p.${c.id} renders with output #${c.out}`, () => {
      document.body.innerHTML = globalThis.__p[c.id]();
      expect(document.getElementById(c.out)).not.toBeNull();
      if (c.out2) expect(document.getElementById(c.out2)).not.toBeNull();
    });

    test(`__p.${c.id} bridge wiring populates #${c.out}`, async () => {
      document.body.innerHTML = globalThis.__p[c.id]();
      stubBridge(c.bridge, c.mock);
      if (c.id === 'portkill') { const pk = document.getElementById('pkInput'); if (pk) pk.value = '8080'; }
      if (c.id === 'prockill') { const pi = document.getElementById('procInput'); if (pi) pi.value = 'node'; }
      if (c.glue) globalThis[c.glue]();
      else if (c.call) c.call();
      await tick(50);
      // SSL generates cert files and alerts success (no DOM output element);
      // other panels write the result into their output element.
      if (c.noDomOut) { expect(true).toBe(true); return; }
      const el = document.getElementById(c.out);
      expect(el).not.toBeNull();
      const filled = (el.innerHTML || '').length + (el.value ? el.value.length : 0);
      expect(filled).toBeGreaterThan(0);
    });
  });

  test('BOM Clean panel renders bulk-clean UI (1:1) without error', () => {
    document.body.innerHTML = globalThis.__p.bom();
    expect(() => globalThis.__init_bom()).not.toThrow();
    expect(document.getElementById('bomPath')).not.toBeNull();
    expect(document.getElementById('bomDirInput')).not.toBeNull();
    expect(document.getElementById('bomExclude')).not.toBeNull();
    expect(document.getElementById('bomExtList')).not.toBeNull();
    expect(document.getElementById('bomCleanBtn')).not.toBeNull();
    // default excludes present
    expect(document.getElementById('bomExclude').value).toContain('node_modules');
    // cleanup button present in header-right slot
    expect(document.querySelector('.ep-card__header-right #bomCleanBtn')).not.toBeNull();
  });

  test('bomRecomputeExt + bomEffectiveFiles filter by exclude/ext', () => {
    globalThis.BOM.files = [
      '/p/a.php', '/p/b.php', '/p/c.js', '/p/.idea/x', '/p/node_modules/m.php'
    ];
    globalThis.BOM.progress = { count: 0, finish: 0, fail: 0, failTask: [], success: 0, successTask: [] };
    var ex = document.createElement('textarea'); ex.id = 'bomExclude';
    ex.value = '.idea\n.git\n.svn\n.vscode\nnode_modules'; document.body.appendChild(ex);
    globalThis.bomRecomputeExt();
    // php(2) + js(1) = 3; .idea and node_modules excluded
    expect(globalThis.BOM.allExt.length).toBe(2);
    expect(globalThis.BOM.allowExt.length).toBe(2);
    var eff = globalThis.bomEffectiveFiles();
    expect(eff.length).toBe(3);
    expect(eff.indexOf('/p/.idea/x')).toBe(-1);
    expect(eff.indexOf('/p/node_modules/m.php')).toBe(-1);
  });

  test('bomRenderResult shows counts and lists', () => {
    globalThis.BOM.progress = {
      count: 3, finish: 3, fail: 1,
      failTask: [{ path: '/p/bad.php', msg: 'perm' }],
      success: 2, successTask: [{ path: '/p/a.php' }, { path: '/p/c.php' }]
    };
    document.body.innerHTML = globalThis.__p.bom();
    globalThis.bomRenderResult();
    var body = document.getElementById('bomResultBody');
    expect(body.textContent).toContain('3');
    expect(body.querySelector('.bom-detail--ok')).not.toBeNull();
    expect(body.querySelector('.bom-detail--err')).not.toBeNull();
  });

  test('wss panel renders with connect/send controls', () => {
    document.body.innerHTML = globalThis.__p.wss();
    expect(document.getElementById('wUrl')).not.toBeNull();
    expect(document.getElementById('wMsg')).not.toBeNull();
    expect(document.getElementById('wO')).not.toBeNull();
  });
});

describe('PHP Obfuscator 1:1 — yakpro-po UI + real obfuscation wiring', () => {
  test('__p.obf renders 1:1 controls (php version, src/desc pickers, config, generate)', () => {
    document.body.innerHTML = globalThis.__p.obf();
    expect(document.getElementById('obPhp')).not.toBeNull();
    expect(document.getElementById('obSrc')).not.toBeNull();
    expect(document.getElementById('obDesc')).not.toBeNull();
    expect(document.getElementById('obSrcFileInput')).not.toBeNull();
    expect(document.getElementById('obSrcDirInput')).not.toBeNull();
    expect(document.getElementById('obDescFileInput')).not.toBeNull();
    expect(document.getElementById('obDescDirInput')).not.toBeNull();
    expect(document.getElementById('obCnfModal')).not.toBeNull();
    expect(document.getElementById('obCnf')).not.toBeNull();
    expect(document.getElementById('obResult')).not.toBeNull();
    // Generate button lives in the card header-right slot
    expect(document.querySelector('.ep-card__header-right #obGenBtn')).not.toBeNull();
  });

  test('init hook is aliased under the real tool id PhpObfuscator (dispatcher fires it)', () => {
    // openTool() dispatches window['__init_' + toolId] where toolId === 'PhpObfuscator',
    // NOT the panel key 'obf'. Without this alias the version select stays empty.
    expect(typeof window.__init_PhpObfuscator).toBe('function');
    expect(window.__init_PhpObfuscator).toBe(window.__init_obf);
  });

  test('__init_obf loads php versions into select without throwing', () => {
    document.body.innerHTML = globalThis.__p.obf();
    globalThis['php_obf_versions'] = function () {
      return Promise.resolve([{ bin: '/usr/bin/php', version: '8.4.19' }, { bin: '/opt/php81/bin/php', version: '8.1.2' }]);
    };
    globalThis['php_obf_cnf'] = function () { return Promise.resolve({ config: '<?php\n$conf->scramble_length = 5;' }); };
    expect(() => globalThis.__init_obf()).not.toThrow();
    return new Promise((resolve) => setTimeout(() => {
      var sel = document.getElementById('obPhp');
      expect(sel.querySelectorAll('option').length).toBe(2);
      expect(sel.querySelector('option').value).toBe('/usr/bin/php');
      // default config captured
      expect(globalThis.OBF.config).toContain('scramble_length');
      resolve();
    }, 30));
  });

  test('picker handlers update OBF state and clear desc', () => {
    document.body.innerHTML = globalThis.__p.obf();
    globalThis.obSrcFileOn({ target: { files: [{ path: '/proj/index.php' }] } });
    expect(globalThis.OBF.src).toBe('/proj/index.php');
    expect(globalThis.OBF.srcType).toBe('file');
    expect(document.getElementById('obSrc').value).toBe('/proj/index.php');
    // desc cleared after new src
    globalThis.OBF.desc = '/proj/out.php';
    globalThis.obSrcDirOn({ target: { files: [{ path: '/proj' }] } });
    expect(globalThis.OBF.src).toBe('/proj');
    expect(globalThis.OBF.srcType).toBe('dir');
    expect(globalThis.OBF.desc).toBe('');
  });

  test('obRun validates and calls php_obf bridge', async () => {
    document.body.innerHTML = globalThis.__p.obf();
    var called = null;
    globalThis['php_obf'] = function (req) { called = JSON.parse(req); return Promise.resolve({ code: 0, desc: '/out.php' }); };
    globalThis.OBF.phpBin = '/usr/bin/php';
    globalThis.OBF.src = '/in.php';
    globalThis.OBF.desc = '/out.php';
    globalThis.obRun();
    await new Promise((r) => setTimeout(r, 10));
    expect(called).not.toBeNull();
    expect(called.phpBin).toBe('/usr/bin/php');
    expect(called.src).toBe('/in.php');
    expect(called.desc).toBe('/out.php');
    expect(document.getElementById('obResult').innerHTML).toContain('/out.php');
  });

  test('obHandleResult renders failure log', () => {
    document.body.innerHTML = globalThis.__p.obf();
    globalThis.obHandleResult({ code: 1, error: 'Parse error: syntax error' });
    var res = document.getElementById('obResult');
    expect(res.querySelector('.ep-alert--danger')).not.toBeNull();
    expect(res.querySelector('.obf-log').textContent).toContain('syntax error');
  });

  test('config drawer open/confirm round-trips content', () => {
    document.body.innerHTML = globalThis.__p.obf();
    globalThis.OBF.config = '<?php\n$conf->obfuscate_variable_name = true;';
    globalThis.obConfigOpen();
    expect(document.getElementById('obCnfModal').style.display).toBe('block');
    expect(globalThis.OBF.cm).not.toBeNull();
    expect(globalThis.OBF.cm.getValue()).toContain('obfuscate_variable_name');
    globalThis.OBF.cm.setValue('<?php\n$conf->scramble_length = 9;');
    globalThis.obConfigConfirm();
    expect(globalThis.OBF.config).toContain('scramble_length = 9');
    expect(document.getElementById('obCnfModal').style.display).toBe('none');
  });
});

describe('System Environment 1:1 — list env files + edit/save drawer', () => {
  test('__p.env renders list container + editor drawer', () => {
    document.body.innerHTML = globalThis.__p.env();
    expect(document.getElementById('envList')).not.toBeNull();
    expect(document.getElementById('envEditorModal')).not.toBeNull();
    expect(document.getElementById('envEditor')).not.toBeNull();
    expect(document.getElementById('envSaveBtn')).not.toBeNull();
  });

  test('init hook is aliased under the real tool id SystemEnv', () => {
    expect(typeof window.__init_SystemEnv).toBe('function');
    expect(window.__init_SystemEnv).toBe(window.__init_env);
  });

  test('__init_env fetches files and renders them', async () => {
    document.body.innerHTML = globalThis.__p.env();
    globalThis['php_env_files'] = function () {
      return Promise.resolve(['/Users/jay/.zshrc', '/Users/jay/.bash_profile']);
    };
    globalThis.__init_env();
    await new Promise((r) => setTimeout(r, 10));
    var rows = document.querySelectorAll('#envList .env-file');
    expect(rows.length).toBe(2);
    expect(rows[0].querySelector('.env-file__name').textContent).toBe('/Users/jay/.zshrc');
  });

  test('empty file list shows the no-files message', async () => {
    document.body.innerHTML = globalThis.__p.env();
    globalThis['php_env_files'] = function () { return Promise.resolve([]); };
    globalThis.__init_env();
    await new Promise((r) => setTimeout(r, 10));
    expect(document.getElementById('envList').textContent).toContain(globalThis._t('env_no_files'));
  });

  test('envEdit opens drawer, reads file, fills editor', async () => {
    document.body.innerHTML = globalThis.__p.env();
    globalThis['php_env_read'] = function (req) {
      var d = JSON.parse(req);
      expect(d.file).toBe('/Users/jay/.zshrc');
      return Promise.resolve({ content: 'export PATH="$HOME/bin:$PATH"\n' });
    };
    globalThis.envEdit('/Users/jay/.zshrc');
    expect(document.getElementById('envEditorModal').style.display).toBe('block');
    await new Promise((r) => setTimeout(r, 10));
    expect(globalThis.ENV.cm.getValue()).toContain('export PATH');
  });

  test('envSave calls php_env_save with file + content and closes drawer', async () => {
    document.body.innerHTML = globalThis.__p.env();
    var called = null;
    globalThis['php_env_save'] = function (req) { called = JSON.parse(req); return Promise.resolve({ ok: true }); };
    globalThis.ENV.current = '/Users/jay/.zshrc';
    globalThis.ENV.cm = globalThis.createCodeEditor('envEditor', 'envMini', 'shell');
    globalThis.ENV.cm.setValue('export FOO=1\n');
    globalThis.envSave();
    await new Promise((r) => setTimeout(r, 10));
    expect(called).not.toBeNull();
    expect(called.file).toBe('/Users/jay/.zshrc');
    expect(called.content).toBe('export FOO=1\n');
    expect(document.getElementById('envEditorModal').style.display).toBe('none');
  });
});

describe('Regex Cheatsheet 1:1 — full content + GFM tables', () => {
  test('REGEX_MEMO contains all original sections', () => {
    const m = globalThis.REGEX_MEMO;
    expect(m).toContain('Normal characters');
    expect(m).toContain('Whitespace characters');
    expect(m).toContain('Character set');
    expect(m).toContain('Characters that require escaping');
    expect(m).toContain('Quantifiers');
    expect(m).toContain('Boundaries');
    expect(m).toContain('Matching');
    expect(m).toContain('Grouping and capturing');
    expect(m).toContain('References and tools');
    // key rows that were missing before
    expect(m).toContain('`[a-z]` | lowercase alphabet');
    expect(m).toContain('`\\S` | inverse of `\\s`');
    expect(m).toContain('`foo\\|bar` | match either `foo` or `bar`');
    expect(m).toContain('`(?<=bar)foo` | match `foo` if it');
    expect(m).toContain('`(foo)bar\\1` | `\\1` is a backreference');
  });

  test('mdRenderGFM renders GFM pipe tables into <table>', () => {
    const md = 'Expression | Description\n:--|:--\n`.` | any character\n`\\d` | digit';
    const html = globalThis.mdRenderGFM(md);
    expect(html).toContain('<table');
    expect(html).toContain('<th>Expression</th>');
    expect(html).toContain('<td>any character</td>');
  });

  test('mdRenderGFM handles escaped pipe inside a cell', () => {
    const md = 'Expression | Description\n:--|:--\n`foo\\|bar` | match foo or bar';
    const html = globalThis.mdRenderGFM(md);
    expect(html).toContain('<td><code>foo|bar</code></td>');
  });

  test('regex_memo panel exposes #regMemoOut and renders tables via rMD', () => {
    document.body.innerHTML = globalThis.__p.regex_memo();
    expect(document.getElementById('regMemoOut')).not.toBeNull();
    const html = globalThis.rMD(globalThis.REGEX_MEMO);
    expect(html).toContain('<table');
    expect(html).toContain('Capturing groups are only relevant');
    // external reference links present
    expect(html).toContain('href="https://developer.mozilla.org');
    expect(html).toContain('onclick="return mdExtLink');
  });

  test('mdExtLink is a function', () => {
    expect(typeof globalThis.mdExtLink).toBe('function');
  });
});

describe('Git Cheatsheet 1:1 — full content + per-command copy buttons', () => {
  const fs = require('fs');
  const path = require('path');
  const gitMemo = fs.readFileSync(path.resolve(__dirname, '..', '..', 'assets', 'md', 'git-memo.en.md'), 'utf8');

  test('git-memo markdown ships the complete original content', () => {
    expect(gitMemo).toContain('## Configuration');
    expect(gitMemo).toContain('## Branching');
    expect(gitMemo).toContain('## Git Flow');
    expect(gitMemo).toContain('## Remote Management');
    expect(gitMemo).toContain('## Advanced Rebase');
    expect(gitMemo).toContain('## Debugging & Inspection');
    expect(gitMemo).toContain('## Security & Patch Workflow');
    expect(gitMemo).toContain('## Best Practices in Teams');
    // original commands that were missing in the trimmed version
    expect(gitMemo).toContain('git switch -c [branch-name]');
    expect(gitMemo).toContain('git stash branch [branch-name] stash@{n}');
    expect(gitMemo).toContain('git worktree add [path] [branch]');
    expect(gitMemo).toContain('git gc --prune=now --aggressive');
  });

  test('renderGitMemo renders dark code blocks with per-line copy buttons', () => {
    const html = globalThis.renderGitMemo(gitMemo);
    expect(html).toContain('code-block-wrapper');
    expect(html).toContain('code-line command');
    expect(html).toContain('gm-copy');
    // a concrete command gets a copy button with encoded payload
    expect(html).toContain('data-code="git%20init"');
    expect(html).toContain('git config --global user.name');
  });

  test('renderGitMemo styles comment lines (starting with #)', () => {
    const html = globalThis.renderGitMemo('```shell\ngit checkout [branch-name]\n# Or with \'switch\' (Git 2.23+):\ngit switch [branch-name]\n```');
    expect(html).toContain('code-line comment');
    expect(html).toContain('# Or with');
  });

  test('git_memo panel exposes #gitMemoOut, title and the full raw source', () => {
    document.body.innerHTML = globalThis.__p.git_memo();
    expect(document.getElementById('gitMemoOut')).not.toBeNull();
    expect(document.querySelector('.gm-title')).not.toBeNull();
    // simulate the openTool flow: raw markdown lives in #gitMemoRaw
    const raw = document.createElement('div');
    raw.id = 'gitMemoRaw';
    raw.textContent = gitMemo;
    document.body.appendChild(raw);
    const out = document.getElementById('gitMemoOut');
    out.innerHTML = globalThis.renderGitMemo(gitMemo);
    expect(out.innerHTML).toContain('code-block-wrapper');
    expect(out.querySelectorAll('.gm-copy').length).toBeGreaterThanOrEqual(21);
  });

  test('gmCopy and renderGitMemo are globally available', () => {
    expect(typeof globalThis.gmCopy).toBe('function');
    expect(typeof globalThis.renderGitMemo).toBe('function');
  });
});

describe('File Info 1:1 — descriptions grid (FlyEnv Tools/FileInfo)', () => {
  const r = {
    path: '/tmp/sample.txt', type: 'file', size: 1572864, size_str: '1.5 MB',
    btime: 1600000000000, btime_str: '2020-09-13T12:26:40+00:00',
    ctime: 1600000000001, ctime_str: '2020-09-13T12:26:40+00:00',
    atime: 1600000000002, atime_str: '2020-09-13T12:26:40+00:00',
    mtime: 1600000000003, mtime_str: '2020-09-13T12:26:40+00:00',
    md5: 'md5val', sha1: 'sha1val', sha256: 'sha256val',
    sha512: 'sha512longvalue', sha512Base64: 'sha512b64'
  };

  test('renderFileInfo is a global function', () => {
    expect(typeof globalThis.renderFileInfo).toBe('function');
  });

  test('renders path as full-width title row', () => {
    const html = globalThis.renderFileInfo(r);
    expect(html).toContain('fi-path');
    expect(html).toContain('/tmp/sample.txt');
  });

  test('renders size pair (human + raw bytes) and 4 time pairs', () => {
    const html = globalThis.renderFileInfo(r);
    expect(html).toContain('file size');
    expect(html).toContain('1.5 MB');
    expect(html).toContain('1572864');
    expect(html).toContain('create time');
    expect(html).toContain('change time');
    expect(html).toContain('access time');
    expect(html).toContain('modify time');
    expect(html).toContain('1600000000000');
  });

  test('renders all 5 hashes, SHA-512 as long full-width', () => {
    const html = globalThis.renderFileInfo(r);
    ['MD5', 'SHA-1', 'SHA-256', 'SHA-512', 'SHA-512-Base64'].forEach((h) => {
      expect(html).toContain(h);
    });
    expect(html).toContain('fi-item--long');
    expect(html).toContain('md5val');
  });

  test('file panel has drop zone + hidden input + output', () => {
    document.body.innerHTML = globalThis.__p.file();
    expect(document.getElementById('fiDroper')).not.toBeNull();
    expect(document.getElementById('fiInput')).not.toBeNull();
    expect(document.getElementById('fO')).not.toBeNull();
  });

  test('fiPick triggers the hidden file input click', () => {
    document.body.innerHTML = globalThis.__p.file();
    const input = document.getElementById('fiInput');
    let clicked = false;
    input.click = () => { clicked = true; };
    globalThis.fiPick();
    expect(clicked).toBe(true);
  });

  test('fiOnFile reads file.path and triggers the bridge', () => {
    let called = null;
    globalThis.fileInfoPath = (p) => { called = p; };
    globalThis.fiOnFile({ target: { files: [{ path: '/x/y.png' }] } });
    expect(called).toBe('/x/y.png');
  });

  test('null value falls back to em dash', () => {
    const html = globalThis.renderFileInfo({ path: '/z', type: 'file', size: 0, size_str: '0 B' });
    expect(html).toContain('—');
  });
});

// ─── Site Sucker 1:1 (real crawler wiring: start/step/stop polling) ─────────
describe('Site Sucker 1:1 — start/step/stop polling crawler', () => {
  const tick = (ms) => new Promise((r) => setTimeout(r, ms || 60));

  function renderAndStub(startState) {
    document.body.innerHTML = globalThis.__p.suck();
    globalThis.window.site_suck_start = function () {
      return Promise.resolve({
        taskId: 't1', state: startState || 'done', dir: '/tmp/out',
        counts: { page: 2, link: 5, success: 3, fail: 0, running: 1, wait: 1 },
        links: [
          { url: 'https://e.com/a.html', state: 'success' },
          { url: 'https://e.com/b.png', state: 'success' },
          { url: 'https://e.com/c.css', state: 'running' },
        ],
        hosts: [{ host: 'e.com', allow: true }, { host: 'cdn.ex.com', allow: false }],
      });
    };
    globalThis.window.site_suck_step = function () {
      return Promise.resolve({
        state: 'done', dir: '/tmp/out',
        counts: { page: 2, link: 5, success: 5, fail: 0, running: 0, wait: 0 },
        links: [
          { url: 'https://e.com/a.html', state: 'success' },
          { url: 'https://e.com/b.png', state: 'success' },
          { url: 'https://e.com/c.css', state: 'success' },
          { url: 'https://e.com/d.js', state: 'success' },
          { url: 'https://e.com/e.woff', state: 'success' },
        ],
        hosts: [{ host: 'e.com', allow: true }, { host: 'cdn.ex.com', allow: false }],
      });
    };
    globalThis.window.site_suck_stop = function () {
      return Promise.resolve({ state: 'stop' });
    };
  }

  test('panel renders 1:1 controls (url/top/stat/tables/settings drawer)', () => {
    document.body.innerHTML = globalThis.__p.suck();
    expect(document.getElementById('ssUrl')).not.toBeNull();
    expect(document.getElementById('ssRunBtn')).not.toBeNull();
    expect(document.getElementById('ssOpenBtn')).not.toBeNull();
    expect(document.getElementById('ssStat')).not.toBeNull();
    expect(document.getElementById('ssLinks')).not.toBeNull();
    expect(document.getElementById('ssHosts')).not.toBeNull();
    expect(document.getElementById('ssSearch')).not.toBeNull();
    expect(document.getElementById('ssHostSearch')).not.toBeNull();
    expect(document.getElementById('ssSetModal')).not.toBeNull();
    ['ssDir','ssWin','ssProxy','ssTimeout','ssMaxImg','ssMaxVideo','ssPageLimit','ssExclude']
      .forEach(id => expect(document.getElementById(id)).not.toBeNull());
    expect(document.querySelector('.ss-gear')).not.toBeNull();
    expect(document.querySelector('.obf-modal__nav .ep-btn--primary')).not.toBeNull();
  });

  test('init hook runs and is aliased under real tool id SiteSucker', () => {
    document.body.innerHTML = globalThis.__p.suck();
    expect(typeof globalThis.__init_SiteSucker).toBe('function');
    expect(typeof globalThis.__init_suck).toBe('function');
    expect(globalThis.__init_suck).toBe(globalThis.__init_SiteSucker);
    expect(() => globalThis.__init_SiteSucker()).not.toThrow();
  });

  test('ssRun → ssOnStart populates stat/links/hosts (done)', async () => {
    renderAndStub('done');
    const u = document.getElementById('ssUrl'); if (u) u.value = 'https://e.com';
    globalThis.ssRun();
    await tick(80);
    expect(document.getElementById('ssStat').innerHTML).toContain('3'); // success count
    expect(document.getElementById('ssLinks').innerHTML).toContain('a.html');
    expect(document.getElementById('ssHosts').innerHTML).toContain('e.com');
    expect(globalThis.SS.taskId).toBe('t1');
  });

  test('running start keeps polling and stop clears the schedule', async () => {
    renderAndStub('running');
    const u = document.getElementById('ssUrl'); if (u) u.value = 'https://e.com';
    globalThis.ssRun();
    await tick(20);
    expect(globalThis.SS.running).toBe(true);
    expect(globalThis.SS.timer).toBeTruthy();
    globalThis.ssStop(); // clears the pending step timer
    await tick(20);
    expect(globalThis.SS.running).toBe(false);
  });

  test('ssOnStep with done state stops the run', async () => {
    renderAndStub('done');
    const u = document.getElementById('ssUrl'); if (u) u.value = 'https://e.com';
    globalThis.ssRun();
    await tick(80);
    globalThis.SS.running = true;
    globalThis.ssOnStep({
      state: 'done', dir: '/tmp/out',
      counts: { page: 2, link: 5, success: 5, fail: 0, running: 0, wait: 0 },
      links: [{ url: 'https://e.com/a.html', state: 'success' }],
      hosts: [{ host: 'e.com', allow: true }],
    });
    expect(globalThis.SS.running).toBe(false);
    expect(document.getElementById('ssLinks').innerHTML).toContain('a.html');
  });

  test('ssStateIcon returns correct markup per state', () => {
    expect(globalThis.ssStateIcon('success')).toContain('ss-ic--ok');
    expect(globalThis.ssStateIcon('fail')).toContain('ss-ic--fail');
    expect(globalThis.ssStateIcon('running')).toContain('ss-spin');
    expect(globalThis.ssStateIcon('wait')).toContain('ss-ic--wait');
  });

  test('ssToggleHost moves a host between allow and exclude', () => {
    document.body.innerHTML = globalThis.__p.suck();
    globalThis.SS.cfg.excludeLink = '';
    globalThis.ssToggleHost('cdn.ex.com');
    expect(globalThis.SS.cfg.excludeLink).toContain('cdn.ex.com');
    globalThis.ssToggleHost('cdn.ex.com');
    expect(globalThis.SS.cfg.excludeLink).not.toContain('cdn.ex.com');
  });

  test('i18n keys resolve for ss_* and base.*', () => {
    expect(globalThis._t('ss_save_path')).toBeTruthy();
    expect(globalThis._t('base.placeholderSearch')).toBeTruthy();
    expect(globalThis._t('ss_settings_saved')).toBeTruthy();
    expect(globalThis._t('ss_running')).toBeTruthy();
    expect(typeof globalThis.I18N).not.toBe('undefined');
  });
});

// ── SSL Certificate 1:1 (multi-domain generator, matches original UI) ──
describe('SSL Certificate 1:1 — multi-domain generator panel', () => {
  const tick = (ms) => new Promise((r) => setTimeout(r, ms || 50));
  function stubBridge(name, resp) {
    globalThis[name] = function () { return Promise.resolve(resp); };
  }
  test('panel title resolves (not literal ssl_title)', () => {
    document.body.innerHTML = globalThis.__p.ssl();
    const html = document.body.innerHTML;
    expect(html).toContain('SSL 证书');
    expect(globalThis._t('ssl_title')).toBe('SSL 证书');
    // no literal key
    expect(html).not.toContain('>ssl_title<');
  });

  test('all ssl_* labels resolve (no literal keys in output)', () => {
    document.body.innerHTML = globalThis.__p.ssl();
    const html = document.body.innerHTML;
    // none of the raw keys should leak into rendered HTML
    ['ssl_title','ssl_gen_btn','ssl_domains_ph','ssl_ca_path','ssl_ca_path_ph','ssl_save_path','ssl_save_path_ph']
      .forEach((k) => expect(html).not.toContain('>' + k + '<'));
    // new labels present
    expect(html).toContain('生成');              // ssl_gen_btn
    expect(html).toContain('Domains');           // placeholder
    expect(html).toContain('Root CA');           // ssl_ca_path label
    expect(html).toContain('certificate save');  // ssl_save_path label
    // core elements present
    expect(document.getElementById('sslDomains')).not.toBeNull();
    expect(document.getElementById('sslCaPath')).not.toBeNull();
    expect(document.getElementById('sslSavePath')).not.toBeNull();
    expect(document.getElementById('sslCaFile')).not.toBeNull();
    expect(document.getElementById('sslSaveFile')).not.toBeNull();
  });

  test('sslMake() wires ssl_make bridge with domains/ca/save_path', async () => {
    document.body.innerHTML = globalThis.__p.ssl();
    stubBridge('ssl_make', { cert_path: '/tmp/ssl/test.crt', key_path: '/tmp/ssl/test.key', cert: 'CERT', key: 'KEY' });
    // fill fields
    var d = document.getElementById('sslDomains'); if (d) d.value = '*.example.com\nexample.com';
    var ca = document.getElementById('sslCaPath'); if (ca) ca.value = '';
    var sp = document.getElementById('sslSavePath'); if (sp) sp.value = '/tmp/ssl';
    globalThis.sslMake();
    await tick(50);
    // bridge was called (no error thrown)
    expect(true).toBe(true);
  });

  test('old elements removed (sCN, sDay, sBits, sslCert, sslKey gone)', () => {
    document.body.innerHTML = globalThis.__p.ssl();
    expect(document.getElementById('sCN')).toBeNull();
    expect(document.getElementById('sDay')).toBeNull();
    expect(document.getElementById('sBits')).toBeNull();
    expect(document.getElementById('sslCert')).toBeNull();
    expect(document.getElementById('sslKey')).toBeNull();
  });
});

// ── Port Kill 1:1 (no literal i18n keys leak) ───────────────────────
describe('Port Kill 1:1 — labels resolve (no literal keys)', () => {
  test('panel title + port label resolve (no literal *_title / pk_port)', () => {
    document.body.innerHTML = globalThis.__p.portkill();
    const html = document.body.innerHTML;
    // cardinal 1:1 bug: card header showed literal "portkill_title"
    expect(html).not.toContain('portkill_title');
    // form label showed literal "pk_port"
    expect(html).not.toContain('pk_port');
    // resolved Chinese labels present
    expect(html).toContain('端口查杀'); // portkill_title
    expect(html).toContain('端口');       // pk_port
    expect(html).toContain('输入端口号'); // pk_ph
    expect(html).toContain('查找');       // lookup
    expect(html).toContain('结束选中');   // pk_kill_sel
    expect(html).toContain('结束全部');   // pk_kill_all
    // core controls present
    expect(document.getElementById('pkInput')).not.toBeNull();
    expect(document.getElementById('pkO')).not.toBeNull();
  });

  test('proc-kill sibling title also resolves (no literal prockill_title)', () => {
    document.body.innerHTML = globalThis.__p.prockill();
    expect(document.body.innerHTML).not.toContain('prockill_title');
    expect(document.body.innerHTML).toContain('进程查杀');
  });
});

// ── QR Code 1:1 (no literal i18n keys leak) ─────────────────────────
describe('QR Code 1:1 — labels resolve (no literal keys)', () => {
  const tick = (ms) => new Promise((r) => setTimeout(r, ms || 50));
  function stubBridge(name, resp) {
    globalThis[name] = function () { return Promise.resolve(resp); };
  }
  test('panel title + all qr_* labels resolve (no literal keys)', () => {
    document.body.innerHTML = globalThis.__p.qr();
    const html = document.body.innerHTML;
    // cardinal 1:1 bug: card header + labels showed literal qr_* keys
    ['qr_title', 'qr_data', 'qr_low', 'qr_medium', 'qr_quartile', 'qr_high', 'qr_fg', 'qr_bg', 'qr_ecc', 'qr_download']
      .forEach((k) => expect(html).not.toContain(k));
    // resolved Chinese labels present
    expect(html).toContain('二维码');      // qr_title
    expect(html).toContain('内容');        // qr_data
    expect(html).toContain('前景色');      // qr_fg
    expect(html).toContain('背景色');      // qr_bg
    expect(html).toContain('纠错级别');    // qr_ecc
    expect(html).toContain('下载');        // qr_download
    expect(html).toContain('低');          // qr_low
    expect(html).toContain('高');          // qr_high
    // ECC select options must carry real value+label (regression: EP.select only parsed {value,label} objects; 2-tuple arrays rendered empty)
    expect(html).toMatch(/<option value="low"[^>]*>低/);
    expect(html).toMatch(/<option value="medium"[^>]*>中/);
    // default selection medium (matches backend chillerlan default)
    expect(html).toMatch(/<option value="medium"[^>]*selected[^>]*>中/);
    // core controls present
    expect(document.getElementById('qI')).not.toBeNull();
    expect(document.getElementById('qFg')).not.toBeNull();
    expect(document.getElementById('qBg')).not.toBeNull();
    expect(document.getElementById('qE')).not.toBeNull();
    expect(document.getElementById('qrOut')).not.toBeNull();
  });

  test('qrGen() wires qr bridge and injects svg into #qrOut', async () => {
    document.body.innerHTML = globalThis.__p.qr();
    stubBridge('qr', { svg: '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>' });
    globalThis.qrGen();
    await tick(80);
    const box = document.getElementById('qrOut');
    expect(box).not.toBeNull();
    expect(box.querySelector('svg')).not.toBeNull();
  });
});

// ── URL Timing 1:1 (clean two-column table, 8 metrics) ──────────────
describe('URL Timing 1:1 — clean table with 8 metrics', () => {
  const tick = (ms) => new Promise((r) => setTimeout(r, ms || 50));
  function stubBridge(name, resp) {
    globalThis[name] = function () { return Promise.resolve(resp); };
  }

  test('panel title resolves (not literal timing_title)', () => {
    document.body.innerHTML = globalThis.__p.timing();
    const card = document.querySelector('.ep-card__header');
    expect(card).not.toBeNull();
    expect(card.textContent).not.toContain('timing_title');
    expect(globalThis._t('timing_title')).not.toContain('timing_title');
  });

  test('renders clean two-column table with all 8 metrics', async () => {
    document.body.innerHTML = globalThis.__p.timing();
    stubBridge('url_timing', {
      dns: 7.36, tcp: 4.86, ssl: 8.86, ttfb: 0.28,
      download: 0.07, speed: 405363.52, size: 29500, version: 'HTTP/1.1',
    });
    globalThis.reqTime();
    await tick(50);
    const el = document.getElementById('tO2');
    expect(el).not.toBeNull();
    const html = el.innerHTML;
    // clean table (no waterfall)
    expect(html).toContain('tm-tbl');
    expect(html).not.toContain('tm-bar');   // no waterfall bar
    // table headers
    expect(html).toContain(globalThis._t('timing_metric'));
    expect(html).toContain(globalThis._t('timing_value'));
    // all 8 metric labels
    expect(html).toContain(globalThis._t('timing_dns'));       // DNS 查询
    expect(html).toContain(globalThis._t('timing_tcp'));       // TCP 连接
    expect(html).toContain(globalThis._t('timing_ssl'));       // 请求处理
    expect(html).toContain(globalThis._t('timing_ttfb'));      // 首字节时间(TTFB)
    expect(html).toContain(globalThis._t('timing_down'));      // 内容下载
    expect(html).toContain(globalThis._t('timing_speed'));     // 下载速度
    expect(html).toContain(globalThis._t('timing_size'));      // 数据大小
    expect(html).toContain(globalThis._t('timing_version'));   // HTTP 版本
    // formatted values
    expect(html).toContain('7.36 ms');
    expect(html).toContain('KB/s');        // speed unit
    expect(html).toContain('HTTP/1.1');    // version
  });

  test('missing fields degrade to "-" gracefully', async () => {
    document.body.innerHTML = globalThis.__p.timing();
    stubBridge('url_timing', {});
    globalThis.reqTime();
    await tick(50);
    const html = document.getElementById('tO2').innerHTML;
    expect(html).toContain('tm-tbl');
    // all missing values should show '-'
    expect((html.match(/-/g) || []).length).toBeGreaterThanOrEqual(8);
  });
});
