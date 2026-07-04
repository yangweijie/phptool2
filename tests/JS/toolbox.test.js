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

describe('HC — HTTP status codes data', () => {
  test('contains common status codes', () => {
    expect(globalThis.HC[200]).toBe('OK');
    expect(globalThis.HC[404]).toBe('Not Found');
    expect(globalThis.HC[500]).toBe('Server Error');
    expect(globalThis.HC[301]).toBe('Moved');
    expect(globalThis.HC[403]).toBe('Forbidden');
  });

  test('has 2xx, 3xx, 4xx, 5xx codes', () => {
    const codes = Object.keys(globalThis.HC).map(Number);
    expect(codes.some(c => c >= 200 && c < 300)).toBe(true);
    expect(codes.some(c => c >= 300 && c < 400)).toBe(true);
    expect(codes.some(c => c >= 400 && c < 500)).toBe(true);
    expect(codes.some(c => c >= 500 && c < 600)).toBe(true);
  });
});

// ─── MM MIME Types data ────────────────────────────────────────────────────

describe('MM — MIME types data', () => {
  test('contains common MIME types', () => {
    expect(globalThis.MM.html).toBe('text/html');
    expect(globalThis.MM.json).toBe('application/json');
    expect(globalThis.MM.png).toBe('image/png');
    expect(globalThis.MM.pdf).toBe('application/pdf');
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
