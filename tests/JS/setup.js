/**
 * Vitest setup: create DOM environment and load JS source files.
 */
import { existsSync, readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = join(__dirname, '../../');

// ─── Create DOM structure ─────────────────────────────────────────────────
function createDOM() {
  document.body.innerHTML = `
    <div id="app"><div class="layout">
      <aside class="sidebar" id="sidebar" style="width:240px">
        <div class="sidebar-header">TOOLS</div>
        <div class="sidebar-tree" id="sidebarTree">
          <div class="tree-cat">◈ Favorites</div><div class="tree-children"></div>
          <div class="tree-cat">◈ Code</div><div class="tree-children"></div>
          <div class="tree-cat">◈ Development</div><div class="tree-children"></div>
        </div>
      </aside>
      <main class="main">
        <div class="toolbar">
          <button class="tb-btn" id="foldBtn"><svg></svg></button>
          <button class="tb-btn" onclick="goHome()"><svg></svg></button>
          <div class="search-wrap">
            <input class="search-input" id="searchInput" placeholder="Search tools..." onfocus="showAllSuggestions()" oninput="doSearch(this.value)">
            <div class="search-suggestions" id="suggestions"></div>
          </div>
          <span class="tool-current" id="currentTool"></span>
          <button class="lang-btn" id="langBtn" onclick="toggleLang()">中</button>
        </div>
        <div class="content" id="content">
          <div id="homeView">
            <div id="favSection"></div>
            <div id="allSection"></div>
          </div>
          <div id="toolView" style="display:none"></div>
        </div>
      </main>
    </div></div>
  `;

  // Diff panel
  const dl = document.createElement('div'); ['dL','dR','dO'].forEach(id => {
    const el = document.createElement(id === 'dO' ? 'div' : 'textarea');
    el.id = id; document.body.appendChild(el);
  });

  // JSON panel
  ['jI','jO','hI','hO'].forEach(id => {
    const el = document.createElement('textarea');
    el.id = id; document.body.appendChild(el);
  });

  // Cron panel
  ['cr0','cr1','cr2','cr3','cr4','crO'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Base64 panel
  ['bI','bO'].forEach(id => {
    const el = document.createElement('textarea');
    el.id = id; document.body.appendChild(el);
  });

  // URL panel
  ['uI','uO'].forEach(id => {
    const el = document.createElement(id === 'uO' ? 'div' : 'textarea');
    el.id = id; document.body.appendChild(el);
  });

  // HTML escape panel
  ['eI','eO'].forEach(id => {
    const el = document.createElement(id === 'eO' ? 'div' : 'textarea');
    el.id = id; document.body.appendChild(el);
  });

  // Hash panel
  ['cI','cO','cM','cP'].forEach(id => {
    const el = document.createElement(document.createElement.length > 0 ? 'textarea' : 'div');
    el.id = id; document.body.appendChild(el);
  });

  // Timestamp panel
  ['tI','tO','t0','t1','t2','t3','t4','t5'].forEach(id => {
    const el = document.createElement(id === 'tO' ? 'div' : 'input');
    el.id = id;
    if (el.tagName === 'INPUT') el.value = '';
    document.body.appendChild(el);
  });

  // Chmod panel
  ['cUU','cUW','cUX','cGU','cGW','cGX','cOU','cOW','cOX','cN','cO2'].forEach(id => {
    const el = document.createElement('div');
    el.id = id;
    if (id.startsWith('c') && id.endsWith('U') || id.startsWith('c') && id.endsWith('W') || id.startsWith('c') && id.endsWith('X')) {
      el.checked = false;
    }
    document.body.appendChild(el);
  });

  // Token panel
  ['kL','kC','kT','kO'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // HTTP status panel
  ['hQ','hO2'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // MIME panel
  ['mI','mO'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // BOM Clean panel (elements are created by __p.bom() at render time)

  // Markdown panel
  ['mI2','mO2'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // WS/SSE panel
  ['wUrl','wMsg','wO'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Code panel
  ['cI2','cO3'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Code Library panel
  ['cL'].forEach(id => {
    const el = document.createElement('textarea');
    el.id = id; document.body.appendChild(el);
  });

  // QR Code panels
  ['qI','qE','qrOut','wSSID','wPass','wEnc','wO2'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Port/Process kill panels
  ['pkInput','pkO','procInput','procO'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Keycode panel
  ['kcArea','kcHint','kcKeyDisplay','kcInfo','kcV_key','kcV_code','kcV_keyCode','kcV_loc','kcV_mod','kcV_type','kcHist'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Image compress / capture
  ['iI','iQ','iW','iO','cO4','fO','tI2','tO2','sI','sO','sCN','sDay','sBits','sO2','obResult','rB','rO2'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // System Environment
  ['envList','envEditor','envEditorModal','envEditorTitle','envSaveBtn'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // Site Sucker (DOM is rebuilt by __p.suck(), but init hooks need these present)
  ['ssUrl','ssRunBtn','ssOpenBtn','ssStat','ssLinks','ssHosts','ssSearch','ssHostSearch',
   'ssSetModal','ssDir','ssWin','ssProxy','ssTimeout','ssMaxImg','ssMaxVideo','ssPageLimit','ssExclude'
  ].forEach(id => {
    const el = document.createElement(id === 'ssUrl' ? 'input' : 'div');
    el.id = id;
    if (el.tagName === 'INPUT') el.value = '';
    document.body.appendChild(el);
  });

  // Regex panel
  ['rP','rF','rS','rO'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });

  // JWT panel
  ['jT','jS','jP','jO','jES','jA'].forEach(id => {
    const el = document.createElement('div');
    el.id = id; document.body.appendChild(el);
  });
}

// ─── Load JS source files and evaluate in global scope ────────────────────
function loadJS(filename) {
  const filePath = join(PROJECT_ROOT, 'assets', 'js', filename);
  if (!existsSync(filePath)) {
    console.warn(`[setup] SKIP: ${filename} not found at ${filePath}`);
    return;
  }
  const code = readFileSync(filePath, 'utf-8');

  // Transform top-level declarations to expose them on globalThis.
  //   var|const|let name = ...  →  var name = globalThis.name = ...
  //   function name(...) {      →  globalThis.name = function(...) {
  //   async function name(...)  →  globalThis.name = async function(...) {
  const transformed = code
    .replace(/^(var|const|let)\s+(\w+)\s*=/gm, '$1 $2 = globalThis.$2 =')
    .replace(/^(async\s+)?function\s+(\w+)\s*\(/gm, 'globalThis.$2 = $1function(');

  const fn = new Function(transformed);
  fn();
  console.log(`[setup] Loaded: ${filename} (${code.length} bytes → ${transformed.length} bytes)`);
}

// ─── Main setup ───────────────────────────────────────────────────────────
createDOM();

// Load i18n first (pure data + _t function)
loadJS('toolbox-i18n.js');

// Load Element Plus UI helpers (EP.* used by panel templates)
loadJS('ep-ui.js');

// Load panels (panel HTML templates, depends on _t and EP)
loadJS('toolbox-panels.js');

// Set up TOOLS, CATS, PMAP globals for toolbox-logic
globalThis.TOOLS = [
  { id: 'JsonParse', cat: 'Development', name: 'JSON Parser', icon: '<svg></svg>' },
  { id: 'base64-string-converter', cat: 'Converter', name: 'Base64', icon: '<svg></svg>' },
  { id: 'HashText', cat: 'Crypto', name: 'Hash Text', icon: '<svg></svg>' },
  { id: 'Timestamp', cat: 'Converter', name: 'Timestamp', icon: '<svg></svg>' },
  { id: 'MarkdownPreview', cat: 'Code', name: 'Markdown Preview', icon: '<svg></svg>' },
];
globalThis.CATS = ['Code', 'Development', 'Crypto', 'Converter', 'Web', 'Images'];
globalThis.PMAP = {
  'JsonParse': 'json',
  'base64-string-converter': 'b64',
  'HashText': 'hash',
  'Timestamp': 'ts',
  'MarkdownPreview': 'md',
};
globalThis.favs = [];

// Load logic (depends on TOOLS, CATS, PMAP, DOM, _t)
loadJS('toolbox-logic.js');

// ── CodeMirror mock (real lib is inlined only in the webview build) ──
// Provides just enough API for createCodeEditor()/attachMinimap() to run in jsdom.
globalThis.CodeMirror = function (place, opts) {
  var el = (typeof place === 'string') ? document.getElementById(place) : place;
  var ta = document.createElement('textarea');
  ta.style.display = 'none';
  if (el && el.appendChild) el.appendChild(ta);
  var handlers = {};
  var cm = {
    _ta: ta,
    getValue: function () { return ta.value; },
    setValue: function (v) {
      ta.value = (v == null ? '' : String(v));
      if (handlers.changes) handlers.changes.forEach(function (f) { f(); });
    },
    on: function (ev, fn) { (handlers[ev] = handlers[ev] || []).push(fn); },
    refresh: function () {},
    scrollTo: function () {},
    getScrollInfo: function () { return { top: 0, height: 100, clientHeight: 50 }; },
    replaceSelection: function () {},
    clearHistory: function () {},
    getWrapperElement: function () { return el; }
  };
  return cm;
};
globalThis.CodeMirror.defineMode = function () {};
globalThis.CodeMirror.defineMIME = function () {};
globalThis.CodeMirror.registerHelper = function () {};
