/* FlyEnv Toolbox — Frontend Tool Logic + App Bootstrap */
var g = function(i){return document.getElementById(i);};
var esc = function(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');};
var curTool = null;
var favs = JSON.parse(localStorage.getItem('flyenv_favs')||'[]');
var panelCache = {}; // Cache rendered panels so switching doesn't clear inputs

function goHome() {
  g('homeView').style.display = '';
  g('toolView').style.display = 'none';
  curTool = null;
  g('currentTool').textContent = '';
  renderHome();
  // Hide all cached panels
  Object.keys(panelCache).forEach(function(k) {
    var el = panelCache[k];
    if (el) el.style.display = 'none';
  });
  g('sidebarTree').querySelectorAll('.tree-item.active').forEach(function(e){e.classList.remove('active');});
}

function openTool(id) {
  var t = TOOLS.find(function(x){return x.id === id;});
  if (!t) return;
  curTool = id;
  g('homeView').style.display = 'none';
  var tv = g('toolView');
  tv.style.display = '';

  // Hide all cached panels first (always)
  Object.keys(panelCache).forEach(function(k) {
    panelCache[k].style.display = 'none';
  });

  // Check cache
  if (panelCache[id]) {
    panelCache[id].style.display = '';
  } else {
    // Create new panel, wrap in container, cache it
    var container = document.createElement('div');
    container.id = 'pnl_' + id;
    container.className = 'panel-container';
    container.innerHTML = getPanelContent(id);
    tv.appendChild(container);
    panelCache[id] = container;
  }
  
  g('currentTool').textContent = t.name;
  // Auto-render cheatsheets
  if (id === 'regex-cheatsheet') { setTimeout(function(){var e=g('regMemoOut');if(e)e.innerHTML=rMD(REGEX_MEMO);},10); }
  if (id === 'git-cheatsheet') { setTimeout(function(){var e=g('gitMemoOut');if(e){var raw=g('gitMemoRaw');e.innerHTML=renderGitMemo(raw?raw.textContent:'');}},10); }
  // Auto-focus keycode area
  if (id === 'keycode-info') { setTimeout(function(){var a=g('kcArea');if(a)a.focus();},200); }
  g('sidebarTree').querySelectorAll('.tree-item').forEach(function(e){e.classList.remove('active');});
  var a = g('sidebarTree').querySelector('.tree-item[data-id="'+id+'"]');
  if (a) a.classList.add('active');
  // Per-tool init hook (e.g. timestamp live clock, live conversions)
  if (typeof window['__init_' + id] === 'function') {
    setTimeout(function(){ window['__init_' + id](); }, 20);
  }
}

function toggleSidebar() {
  var sidebarOpen = g('sidebar').style.width !== '0px';
  var s = g('sidebar');
  if (sidebarOpen) {
    s.style.width = '0px'; s.style.minWidth = '0px'; s.style.padding = '0px';
    g('foldBtn').innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
  } else {
    s.style.width = '240px'; s.style.minWidth = '240px'; s.style.padding = '';
    g('foldBtn').innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
  }
}

function toggleFav(id) {
  var i = favs.indexOf(id);
  if (i > -1) favs.splice(i,1); else favs.push(id);
  localStorage.setItem('flyenv_favs', JSON.stringify(favs));
  if (!curTool) {
    renderHome();
  } else {
    // 在详情页：直接更新星星图标，不用重绘整个页面
    var star = document.querySelector('.tool-view-hdr .fav-star');
    if (star) star.textContent = isFav(id) ? '\u2605' : '\u2606';
    // 同时更新侧栏收藏
    renderSidebarFavs();
  }
}

function isFav(id) { return favs.indexOf(id) >= 0; }

function renderHome() {
  renderFavs();
  renderAll();
  g('sidebarTree').querySelectorAll('.tree-item.active').forEach(function(e){e.classList.remove('active');});
}

function renderFavs() {
  var el = g('favSection'); if (!el) return;
  var f = TOOLS.filter(function(t){return favs.indexOf(t.id) >= 0;});
  if (!f.length) { el.innerHTML = ''; renderSidebarFavs(); return; }
  var h = '<div class="section-title">'+_t('section_fav')+'</div><div class="tool-grid">';
  for (var i = 0; i < f.length; i++) h += tc(f[i], true);
  el.innerHTML = h + '</div>';
  renderSidebarFavs();
}

function renderSidebarFavs() {
  var el = g('sidebarTree').querySelector('.tree-cat:first-child + .tree-children');
  if (!el) return;
  var f = TOOLS.filter(function(t){return favs.indexOf(t.id) >= 0;});
  if (!f.length) { el.innerHTML = ''; return; }
  var h = '';
  for (var i = 0; i < f.length; i++) {
    h += '<div class="tree-item tree-leaf side-fav" data-id="'+f[i].id+'" onclick="openTool(\''+f[i].id+'\')">'
      + '<span class="tree-icon">'+f[i].icon+'</span><span class="tree-label">'+(f[i].name)+'</span></div>';
  }
  el.innerHTML = h;
}

function renderAll() {
  var m = {};
  TOOLS.forEach(function(t){ if (!m[t.cat]) m[t.cat] = []; m[t.cat].push(t); });
  var h = '<div class="section-title">'+_t('section_all')+'</div>';
  var catKey = {'Code':'code','Development':'development','Crypto':'crypto','Converter':'converter','Web':'web','Images':'images'};
  CATS.forEach(function(c){
    if (!m[c] || !m[c].length) return;
    h += '<div class="cat-title">'+_t('cat_'+catKey[c])+'</div><div class="tool-grid">';
    for (var i = 0; i < m[c].length; i++) h += tc(m[c][i], false);
    h += '</div>';
  });
  g('allSection').innerHTML = h;
}

function tc(t, f) {
  return '<div class="tool-card" onclick="openTool(\''+t.id+'\')"><div class="tool-icon">'+t.icon
    + '</div><div class="tool-name">'+t.name+'</div>'
    + '<span class="fav-star" onclick="event.stopPropagation();toggleFav(\''+t.id+'\')">'
    + (f ? '\u2605' : '\u2606') + '</span></div>';
}

function getPanelContent(id) {
  var pk = PMAP[id] || id;
  var p = __p[pk];
  if (!p) return '<div class="cd"><div class="hd">'+id+'</div></div>';
  var t = TOOLS.find(function(x){return x.id === id;});
  return buildToolHdr(id, t) + '<div class="tool-view-body">'+p()+'</div>';
}

function buildToolHdr(id, t) {
  if (!t) { t = TOOLS.find(function(x){return x.id === id;}); }
  return '<div class="tool-view-hdr"><div class="tv-icon">'+(t?t.icon:'')+'</div><h2>'+(t?t.name:id)+'</h2>'
    + '<span class="fav-star" style="position:static;opacity:1;font-size:20px;margin-left:auto;color:#67c23a" onclick="toggleFav(\''+id+'\')">'
    + (isFav(id)?'\u2605':'\u2606')+'</span></div>';
}

function doSearch(q) {
  q = q.toLowerCase();
  g('homeView').querySelectorAll('.tool-card').forEach(function(c){
    var n = c.querySelector('.tool-name').textContent.toLowerCase();
    var match = n.indexOf(q) >= 0;
    c.style.display = match ? '' : 'none';
    // Highlight matching text
    var nameEl = c.querySelector('.tool-name');
    var origName = nameEl.getAttribute('data-orig') || nameEl.textContent;
    if (!nameEl.getAttribute('data-orig')) nameEl.setAttribute('data-orig', origName);
    nameEl.innerHTML = q && match ? origName.replace(new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi'),'<mark style="background:#ffd54f;color:#333;border-radius:2px;padding:0 2px">$1</mark>') : origName;
  });
  g('allSection').querySelectorAll('.cat-title,.section-title').forEach(function(c){
    var s = c.nextElementSibling;
    c.style.display = s && Array.from(s.children).some(function(x){return x.style.display !== 'none';}) ? '' : 'none';
  });
  if (q) showSearchSuggestions(q);
}

function showAllSuggestions() { var q = g('searchInput').value; if (q) showSearchSuggestions(q); }

function showSearchSuggestions(q) {
  q = q.toLowerCase(); var sg = g('suggestions');
  var m = TOOLS.filter(function(t){return t.name.toLowerCase().indexOf(q) >= 0;});
  if (!m.length) { sg.style.display = 'none'; return; }
  var h = '';
  for (var i = 0; i < Math.min(8, m.length); i++) {
    h += '<div class="sg-item" onmousedown="event.preventDefault();selectSugg(\''+m[i].id+'\',\''+esc(m[i].name)+'\')">'
      + '<span class="sg-icon">'+m[i].icon+'</span>'+m[i].name+'</div>';
  }
  sg.innerHTML = h; sg.style.display = '';
}

function selectSugg(id, nm) { g('searchInput').value = nm; hideSugg(); openTool(id); }
function hideSugg() { g('suggestions').style.display = 'none'; }

// ── Cron Parser ──
var CRON_FIELD_HINT = { auto: 'Auto-detects 5 or 6 fields', linux: 'minute hour day-of-month month day-of-week', seconds: 'second minute hour day-of-month month day-of-week' };
var CRON_MONTH_ALIAS = { jan:1, feb:2, mar:3, apr:4, may:5, jun:6, jul:7, aug:8, sep:9, oct:10, nov:11, dec:12 };
var CRON_DAY_ALIAS = { sun:0, mon:1, tue:2, wed:3, thu:4, fri:5, sat:6 };
var CRON_FIELDS = {
  second: { min:0, max:59 }, minute: { min:0, max:59 }, hour: { min:0, max:23 },
  dayOfMonth: { min:1, max:31 }, month: { min:1, max:12, aliases: CRON_MONTH_ALIAS }, dayOfWeek: { min:0, max:7, aliases: CRON_DAY_ALIAS }
};
var CRON_MODE_FIELDS = { linux: ['minute','hour','dayOfMonth','month','dayOfWeek'], seconds: ['second','minute','hour','dayOfMonth','month','dayOfWeek'] };

function cronNormalize(value, name, cfg) {
  var lv = ('' + value).toLowerCase();
  var mapped = cfg.aliases && cfg.aliases[lv] !== undefined ? cfg.aliases[lv] : Number(value);
  if (!Number.isInteger(mapped)) throw new Error(value + ' is not valid for ' + name);
  if (name === 'dayOfWeek' && mapped === 7) return 0;
  if (mapped < cfg.min || mapped > cfg.max) throw new Error(value + ' is outside ' + name + ' range');
  return mapped;
}
function cronAddRange(values, start, end, step, name, cfg) {
  if (step < 1) throw new Error(name + ' step must be greater than 0');
  if (start > end) throw new Error(name + ' range start must be less than end');
  for (var v = start; v <= end; v += step) values.add(name === 'dayOfWeek' && v === 7 ? 0 : v);
}
function cronParseField(field, name, cfg) {
  var values = new Set();
  var parts = ('' + field).split(',');
  var wildcard = false;
  for (var pi = 0; pi < parts.length; pi++) {
    var part = parts[pi];
    if (!part) throw new Error(name + ' contains an empty value');
    var sp = part.split('/');
    var rangePart = sp[0];
    var step = sp[1] ? Number(sp[1]) : 1;
    if (sp.length > 2) throw new Error(name + ' contains an invalid step');
    if (!Number.isInteger(step)) throw new Error(name + ' step must be a number');
    if (rangePart === '*') { wildcard = true; cronAddRange(values, cfg.min, cfg.max, step, name, cfg); continue; }
    var rv = rangePart.split('-');
    if (rv.length === 1) { values.add(cronNormalize(rv[0], name, cfg)); continue; }
    if (rv.length === 2) { cronAddRange(values, cronNormalize(rv[0], name, cfg), cronNormalize(rv[1], name, cfg), step, name, cfg); continue; }
    throw new Error(name + ' contains an invalid range');
  }
  return { values: values, wildcard: wildcard };
}
function cronResolveMode(fields, mode) {
  if (mode === 'linux') { if (fields.length !== 5) throw new Error('Linux crontab mode requires 5 fields'); return 'linux'; }
  if (mode === 'seconds') { if (fields.length !== 6) throw new Error('Seconds mode requires 6 fields'); return 'seconds'; }
  if (fields.length === 5) return 'linux';
  if (fields.length === 6) return 'seconds';
  throw new Error('Cron expression must contain 5 or 6 fields');
}
function cronParseSchedule(expr, mode) {
  var fields = expr.trim().split(/\s+/);
  var resolved = cronResolveMode(fields, mode);
  var names = CRON_MODE_FIELDS[resolved];
  var sched = {};
  for (var i = 0; i < names.length; i++) {
    var nm = names[i];
    sched[nm] = cronParseField(fields[i], nm, CRON_FIELDS[nm]);
  }
  var second = sched.second || { values: new Set([0]), wildcard: false };
  return { mode: resolved, second: second, minute: sched.minute, hour: sched.hour, dayOfMonth: sched.dayOfMonth, month: sched.month, dayOfWeek: sched.dayOfWeek };
}
function cronMatchDay(date, sched) {
  var dom = sched.dayOfMonth.values.has(date.getDate());
  var dow = sched.dayOfWeek.values.has(date.getDay());
  if (sched.dayOfMonth.wildcard && sched.dayOfWeek.wildcard) return true;
  if (sched.dayOfMonth.wildcard) return dow;
  if (sched.dayOfWeek.wildcard) return dom;
  return dom || dow;
}
function cronIsMatch(date, sched) {
  return sched.second.values.has(date.getSeconds()) && sched.minute.values.has(date.getMinutes()) && sched.hour.values.has(date.getHours()) && cronMatchDay(date, sched) && sched.month.values.has(date.getMonth() + 1);
}
function cronShouldScanBySecond(sched) { return sched.mode !== 'linux' && !(sched.second.values.size === 1 && sched.second.values.has(0)); }
function cronGetNextRuns(expr, count, mode) {
  var sched = cronParseSchedule(expr, mode || 'auto');
  var runs = [];
  var cursor = new Date();
  cursor.setMilliseconds(0);
  var scanBySecond = cronShouldScanBySecond(sched);
  if (scanBySecond) cursor.setSeconds(cursor.getSeconds() + 1);
  else { cursor.setSeconds(0); cursor.setMinutes(cursor.getMinutes() + 1); }
  var limit = scanBySecond ? 366 * 24 * 60 * 60 : 366 * 24 * 60;
  for (var i = 0; i < limit && runs.length < count; i++) {
    if (cronIsMatch(cursor, sched)) runs.push(new Date(cursor));
    if (scanBySecond) cursor.setSeconds(cursor.getSeconds() + 1);
    else cursor.setMinutes(cursor.getMinutes() + 1);
  }
  if (runs.length === 0) throw new Error('No matching run time found in the next year');
  return { detectedMode: sched.mode, fieldHint: CRON_FIELD_HINT[sched.mode], runs: runs };
}
function cronToggleGen() {
  var mode = g('cGen') ? g('cGen').value : 'everyMinute';
  g('cIntMinBox').style.display = (mode === 'everyNMinutes') ? '' : 'none';
  g('cHMBox').style.display = (['hourly','daily','weekly','monthly'].indexOf(mode) >= 0) ? '' : 'none';
  g('cDowBox').style.display = (mode === 'weekly') ? '' : 'none';
  g('cDomBox').style.display = (mode === 'monthly') ? '' : 'none';
}
function cronBuildExpr(mode, genMode, intMin, minute, hour, dow, dom) {
  var outMode = mode === 'auto' ? 'linux' : mode;
  var withPrefix = function (e) { return outMode === 'linux' ? e : '0 ' + e; };
  if (genMode === 'everyMinute') return outMode === 'linux' ? '* * * * *' : '0 * * * * *';
  if (genMode === 'everyNMinutes') return withPrefix('*/' + intMin + ' * * * *');
  if (genMode === 'hourly') return withPrefix(minute + ' * * * *');
  if (genMode === 'daily') return withPrefix(minute + ' ' + hour + ' * * *');
  if (genMode === 'weekly') return withPrefix(minute + ' ' + hour + ' * * ' + dow);
  return withPrefix(minute + ' ' + hour + ' ' + dom + ' * *');
}
function cronGenerate() {
  if (!g('cGen')) return;
  var genMode = g('cGen').value;
  var mode = (g('cronMode_val') ? g('cronMode_val').value : 'auto');
  var intMin = Math.max(1, Math.min(59, +g('cIntMin').value || 5));
  var minute = Math.max(0, Math.min(59, +g('cMin').value || 0));
  var hour = Math.max(0, Math.min(23, +g('cHour').value || 0));
  var dow = Math.max(0, Math.min(6, +g('cDow').value || 0));
  var dom = Math.max(1, Math.min(31, +g('cDom').value || 1));
  var pad = function (v) { return ('0' + v).slice(-2); };
  var desc = '';
  if (genMode === 'everyMinute') desc = 'Runs every minute';
  else if (genMode === 'everyNMinutes') desc = 'Runs every ' + intMin + ' minutes';
  else if (genMode === 'hourly') desc = 'Runs hourly at minute ' + pad(minute);
  else if (genMode === 'daily') desc = 'Runs daily at ' + pad(hour) + ':' + pad(minute);
  else if (genMode === 'weekly') desc = 'Runs weekly on day ' + dow + ' at ' + pad(hour) + ':' + pad(minute);
  else desc = 'Runs monthly on day ' + dom + ' at ' + pad(hour) + ':' + pad(minute);
  var expr = cronBuildExpr(mode, genMode, intMin, minute, hour, dow, dom);
  if (g('cExpr')) g('cExpr').value = expr;
  if (g('cGenDesc')) g('cGenDesc').innerHTML = EP.alert(desc, 'success');
  cronParse();
}
function cronUseExample(expr) { if (g('cExpr')) { g('cExpr').value = expr; cronParse(); } }
function cronParse() {
  if (!g('cExpr')) return;
  var expr = g('cExpr').value.trim();
  var mode = (g('cronMode_val') ? g('cronMode_val').value : 'auto');
  var count = g('cCount') ? (+g('cCount').value || 10) : 10;
  if (g('cCountLbl')) g('cCountLbl').textContent = count;
  var errEl = g('cErr'), runsEl = g('cRuns'), tagsEl = g('cTags');
  if (errEl) errEl.innerHTML = '';
  try {
    var res = cronGetNextRuns(expr, count, mode);
    if (tagsEl) tagsEl.innerHTML = EP.tag(res.detectedMode, 'success') + ' ' + EP.tag(res.fieldHint, 'info');
    var rows = res.runs.map(function (d, i) { return [String(i + 1), d.toLocaleString()]; });
    if (runsEl) runsEl.innerHTML = EP.table([_t('cron_count') + ' #', _t('cron_runs')], rows);
  } catch (e) {
    if (tagsEl) tagsEl.innerHTML = '';
    if (runsEl) runsEl.innerHTML = '';
    if (errEl) errEl.innerHTML = EP.alert(e.message, 'danger');
  }
}


// ── Diff ──
function fD() {
  var l = g('dL').value.split('\n'), r = g('dR').value.split('\n');
  if (!l[0] && !r[0]) { g('dO').innerHTML = '// enter text'; return; }
  var m = l.length, n = r.length;
  var L = []; for (var i = 0; i <= m; i++) { L[i] = []; for (var j = 0; j <= n; j++) L[i][j] = 0; }
  for (var i = 1; i <= m; i++) for (var j = 1; j <= n; j++)
    L[i][j] = l[i-1] === r[j-1] ? L[i-1][j-1]+1 : Math.max(L[i-1][j], L[i][j-1]);
  var res = [], i = m, j = n, a = 0, d = 0, u = 0;
  while (i > 0 || j > 0) {
    if (i>0&&j>0&&l[i-1]===r[j-1]){res.unshift({t:'=',l:l[i-1]});i--;j--;u++;}
    else if(j>0&&(i===0||L[i][j-1]>=L[i-1][j])){res.unshift({t:'+',l:r[j-1]});j--;a++;}
    else{res.unshift({t:'-',l:l[i-1]});i--;d++;}
  }
  var h = '<table style="width:100%;border-collapse:collapse;font-family:monospace;font-size:12px">';
  res.forEach(function(x){
    h += '<tr style="background:'+(x.t==='+'?'rgba(0,184,148,.06)':x.t==='-'?'rgba(225,112,85,.06)':'transparent')
      + '"><td style="width:16px;font-weight:700;text-align:center;color:'+(x.t==='+'?'#00b894':x.t==='-'?'#e17055':'#999')
      + '">'+x.t+'</td><td style="padding:2px 4px">'+esc(x.l)+'</td></tr>';
  });
  g('dO').innerHTML = h + '</table>';
}
function sD() { var l=g('dL'),r=g('dR'),t=l.value; l.value=r.value; r.value=t; }
function xDD() {
  g('dL').value = 'function greet(name) {\n    return \'Hello, \' + name;\n}\n\ngreet(\'FlyEnv\');';
  g('dR').value = 'function greet(name, lang) {\n    const g2 = {en:\'Hello\',zh:\'你好\'};\n    return (g2[lang]||\'Hello\')+\', \'+name;\n}\n\ngreet(\'FlyEnv\',\'zh\');';
}

// ── JSON ──
function fJ(m) {
  try {
    var d = JSON.parse(g('jI').value);
    if (m === 'min') g('jO').textContent = JSON.stringify(d);
    else if (m === 'srt') g('jO').textContent = JSON.stringify(sK(d),null,2);
    else if (m === 'val') g('jO').textContent = 'Valid JSON | Keys: '+Object.keys(d).length;
    else g('jO').textContent = JSON.stringify(d,null,2);
  } catch(e) { g('jO').textContent = 'Error: '+e.message; }
}
function sK(d) { if(!d||typeof d!=='object'||Array.isArray(d))return d; var k=Object.keys(d).sort(),o={}; k.forEach(function(v){o[v]=sK(d[v]);}); return o; }

// ── JWT Encoder / Decoder ──
function b64u(s) { return btoa(unescape(encodeURIComponent(s))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); }
function b64ud(s) { try { s = s.replace(/-/g, '+').replace(/_/g, '/'); while (s.length % 4) s += '='; return decodeURIComponent(escape(atob(s))); } catch (e) { return null; } }
function jwtEnc() {
  if (typeof window.jwt !== 'function') { return; }
  var alg = g('jAlg').value, secret = g('jSec').value;
  var header = g('jHead').value, payload = g('jPay').value;
  cPHP('jwt', { mode: 'enc', alg: alg, secret: secret, header: header, payload: payload, _out: 'jTok' });
}
function jwtDec() {
  if (typeof window.jwt !== 'function') { return; }
  var alg = g('jDAlg').value, secret = g('jDSec').value, token = g('jDTok').value;
  cPHP('jwt', { mode: 'dec', alg: alg, secret: secret, token: token, _out: 'jDHead' });
}
function jwtUseCreated() {
  if (g('jDTok')) g('jDTok').value = g('jTok').value || '';
  if (g('jDSec')) g('jDSec').value = g('jSec').value || '';
  if (g('jDAlg')) g('jDAlg').value = g('jAlg').value || 'HS256';
  jwtDec();
}

// ── Hash ──
async function fH(a) {
  var i=g('hI').value;if(!i)return;
  if(a==='b64'){g('hO').textContent='Base64: '+btoa(i);return;}
  if(a==='crc32'){var c=-1;for(var k=0;k<i.length;k++){c^=i.charCodeAt(k);for(var j=0;j<8;j++)c=(c&1)?(c>>>1)^0xEDB88320:c>>>1;}g('hO').textContent='CRC32: '+(c>>>0).toString(16);return;}
  if(a==='md5'){g('hO').textContent='MD5: '+MD5(i);return;}
  var alg={sha1:'SHA-1',sha256:'SHA-256',sha384:'SHA-384',sha512:'SHA-512'}[a];
  if(!alg){g('hO').textContent='Unsupported: '+a;return;}
  var buf=await crypto.subtle.digest(alg,new TextEncoder().encode(i));
  g('hO').textContent=a.toUpperCase()+': '+Array.from(new Uint8Array(buf)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
}
function MD5(s){function F(x,y,z){return (x&y)|(~x&z);}function G(x,y,z){return (x&z)|(y&~z);}function H(x,y,z){return x^y^z;}function I(x,y,z){return y^(x|~z);}function rot(x,n){return (x<<n)|(x>>>(32-n));}function op(f,a,b,c,d,x,s,t){return rot((a+f(b,c,d)+x+t)|0,s)+b|0;}var i,b,k,AA,BB,CC,DD,a,b2,c2,d2;var x=[];var str=utf8Encode(s);var n=str.length*8;for(i=0;i<str.length;i++)x[i>>2]|=(str.charCodeAt(i)&0xFF)<<((i%4)*8);x[i>>2]|=0x80<<((i%4)*8);x[((i+8>>6)<<4)+15]=n;a=0x67452301;b2=0xEFCDAB89;c2=0x98BADCFE;d2=0x10325476;for(i=0;i<x.length;i+=16){AA=a;BB=b2;CC=c2;DD=d2;a=op(F,a,b2,c2,d2,x[i+0],7,0xD76AA478);d2=op(F,d2,a,b2,c2,x[i+1],12,0xE8C7B756);c2=op(F,c2,d2,a,b2,x[i+2],17,0x242070DB);b2=op(F,b2,c2,d2,a,x[i+3],22,0xC1BDCEEE);a=op(F,a,b2,c2,d2,x[i+4],7,0xF57C0FAF);d2=op(F,d2,a,b2,c2,x[i+5],12,0x4787C62A);c2=op(F,c2,d2,a,b2,x[i+6],17,0xA8304613);b2=op(F,b2,c2,d2,a,x[i+7],22,0xFD469501);a=op(F,a,b2,c2,d2,x[i+8],7,0x698098D8);d2=op(F,d2,a,b2,c2,x[i+9],12,0x8B44F7AF);c2=op(F,c2,d2,a,b2,x[i+10],17,0xFFFF5BB1);b2=op(F,b2,c2,d2,a,x[i+11],22,0x895CD7BE);a=op(F,a,b2,c2,d2,x[i+12],7,0x6B901122);d2=op(F,d2,a,b2,c2,x[i+13],12,0xFD987193);c2=op(F,c2,d2,a,b2,x[i+14],17,0xA679438E);b2=op(F,b2,c2,d2,a,x[i+15],22,0x49B40821);a=op(G,a,b2,c2,d2,x[i+1],5,0xF61E2562);d2=op(G,d2,a,b2,c2,x[i+6],9,0xC040B340);c2=op(G,c2,d2,a,b2,x[i+11],14,0x265E5A51);b2=op(G,b2,c2,d2,a,x[i+0],20,0xE9B6C7AA);a=op(G,a,b2,c2,d2,x[i+5],5,0xD62F105D);d2=op(G,d2,a,b2,c2,x[i+10],9,0x02441453);c2=op(G,c2,d2,a,b2,x[i+15],14,0xD8A1E681);b2=op(G,b2,c2,d2,a,x[i+4],20,0xE7D3FBC8);a=op(G,a,b2,c2,d2,x[i+9],5,0x21E1CDE6);d2=op(G,d2,a,b2,c2,x[i+14],9,0xC33707D6);c2=op(G,c2,d2,a,b2,x[i+3],14,0xF4D50D87);b2=op(G,b2,c2,d2,a,x[i+8],20,0x455A14ED);a=op(G,a,b2,c2,d2,x[i+13],5,0xA9E3E905);d2=op(G,d2,a,b2,c2,x[i+2],9,0xFCEFA3F8);c2=op(G,c2,d2,a,b2,x[i+7],14,0x676F02D9);b2=op(G,b2,c2,d2,a,x[i+12],20,0x8D2A4C8A);a=op(H,a,b2,c2,d2,x[i+5],4,0xFFFA3942);d2=op(H,d2,a,b2,c2,x[i+8],11,0x8771F681);c2=op(H,c2,d2,a,b2,x[i+11],16,0x6D9D6122);b2=op(H,b2,c2,d2,a,x[i+14],23,0xFDE5380C);a=op(H,a,b2,c2,d2,x[i+1],4,0xA4BEEA44);d2=op(H,d2,a,b2,c2,x[i+4],11,0x4BDECFA9);c2=op(H,c2,d2,a,b2,x[i+7],16,0xF6BB4B60);b2=op(H,b2,c2,d2,a,x[i+10],23,0xBEBFBC70);a=op(H,a,b2,c2,d2,x[i+13],4,0x289B7EC6);d2=op(H,d2,a,b2,c2,x[i+0],11,0xEAA127FA);c2=op(H,c2,d2,a,b2,x[i+3],16,0xD4EF3085);b2=op(H,b2,c2,d2,a,x[i+6],23,0x04881D05);a=op(H,a,b2,c2,d2,x[i+9],4,0xD9D4D039);d2=op(H,d2,a,b2,c2,x[i+12],11,0xE6DB99E5);c2=op(H,c2,d2,a,b2,x[i+15],16,0x1FA27CF8);b2=op(H,b2,c2,d2,a,x[i+2],23,0xC4AC5665);a=op(I,a,b2,c2,d2,x[i+0],6,0xF4292244);d2=op(I,d2,a,b2,c2,x[i+7],10,0x432AFF97);c2=op(I,c2,d2,a,b2,x[i+14],15,0xAB9423A7);b2=op(I,b2,c2,d2,a,x[i+5],21,0xFC93A039);a=op(I,a,b2,c2,d2,x[i+12],6,0x655B59C3);d2=op(I,d2,a,b2,c2,x[i+3],10,0x8F0CCC92);c2=op(I,c2,d2,a,b2,x[i+10],15,0xFFEFF47D);b2=op(I,b2,c2,d2,a,x[i+1],21,0x85845DD1);a=op(I,a,b2,c2,d2,x[i+8],6,0x6FA87E4F);d2=op(I,d2,a,b2,c2,x[i+15],10,0xFE2CE6E0);c2=op(I,c2,d2,a,b2,x[i+6],15,0xA3014314);b2=op(I,b2,c2,d2,a,x[i+13],21,0x4E0811A1);a=op(I,a,b2,c2,d2,x[i+4],6,0xF7537E82);d2=op(I,d2,a,b2,c2,x[i+11],10,0xBD3AF235);c2=op(I,c2,d2,a,b2,x[i+2],15,0x2AD7D2BB);b2=op(I,b2,c2,d2,a,x[i+9],21,0xEB86D391);a=a+AA|0;b2=b2+BB|0;c2=c2+CC|0;d2=d2+DD|0;}return hex(a)+hex(b2)+hex(c2)+hex(d2);function hex(n){var h='';for(var i=0;i<4;i++)h+=('0'+(n>>>(i*8+4)&0xF).toString(16)).slice(-1)+('0'+(n>>>(i*8)&0xF).toString(16)).slice(-1);return h;}}
function utf8Encode(s){return unescape(encodeURIComponent(s));}

// ── Crypt ──
async function fC(m) {
  var i=g('cI').value,p=g('cP').value;
  try {
    if(m==='enc'){
      var iv=crypto.getRandomValues(new Uint8Array(16));
      var key=await crypto.subtle.importKey('raw',await crypto.subtle.digest('SHA-256',new TextEncoder().encode(p)),{name:'AES-CBC'},false,['encrypt']);
      var enc=await crypto.subtle.encrypt({name:'AES-CBC',iv:iv},key,new TextEncoder().encode(i));
      var c2=new Uint8Array(iv.length+enc.byteLength);c2.set(iv);c2.set(new Uint8Array(enc),iv.length);
      g('cO').textContent=btoa(String.fromCharCode.apply(null,c2));
    } else {
      var raw=Uint8Array.from(atob(i),function(c){return c.charCodeAt(0);});
      var key=await crypto.subtle.importKey('raw',await crypto.subtle.digest('SHA-256',new TextEncoder().encode(p)),{name:'AES-CBC'},false,['decrypt']);
      g('cO').textContent=new TextDecoder().decode(await crypto.subtle.decrypt({name:'AES-CBC',iv:raw.slice(0,16)},key,raw.slice(16)));
    }
  } catch(e) { g('cO').textContent='Error: '+(m==='dec'?'Wrong key?':'Encrypt failed'); }
}

// ── Timestamp ──
function fTD(){var ts=+g('tI').value;if(!ts)return;var d=new Date(ts*1000);g('tO').textContent='Unix: '+ts+'\nDate: '+d.toLocaleString('zh-CN',{hour12:false});}
function fTT(){var v=[0,1,2,3,4,5].map(function(x){return +g('t'+x).value;});var ts=Math.floor(new Date(v[0],v[1]-1,v[2],v[3],v[4],v[5]).getTime()/1000);g('tI').value=ts||'';g('tO').textContent=ts?'Timestamp: '+ts:'Invalid';}
function fTN(){var n=new Date();g('tI').value=Math.floor(n.getTime()/1000);[0,1,2,3,4,5].forEach(function(i){g('t'+i).value=[n.getFullYear(),String(n.getMonth()+1).padStart(2,'0'),String(n.getDate()).padStart(2,'0'),String(n.getHours()).padStart(2,'0'),String(n.getMinutes()).padStart(2,'0'),String(n.getSeconds()).padStart(2,'0')][i];});fTD();}

// ── Base64 ──
function fB(m){try{g('bO').textContent=m==='enc'?btoa(g('bI').value):atob(g('bI').value);}catch(e){g('bO').textContent='Invalid input';}}

// ── URL ──
function fU(m){var i=g('uI').value;if(m==='enc'){g('uO').textContent=encodeURIComponent(i);return;}if(m==='dec'){g('uO').textContent=decodeURIComponent(i);return;}try{var u=new URL(i);var o='';[['protocol','Scheme'],['hostname','Host'],['port','Port'],['pathname','Path'],['search','Query'],['hash','Hash']].forEach(function(k){if(u[k[0]])o+=k[1]+': '+u[k[0]]+'\n';});if(u.searchParams){o+='\nParams:\n';u.searchParams.forEach(function(v,k){o+='  '+k+' = '+v+'\n';});}g('uO').textContent=o;}catch(e){g('uO').textContent='Invalid URL';}}

// ── HTML Escape ──
function fE(m){var i=g('eI').value;if(m==='esc'){var d=document.createElement('div');d.appendChild(document.createTextNode(i));g('eO').textContent=d.innerHTML;}else if(m==='unesc'){var d=document.createElement('div');d.innerHTML=i;g('eO').textContent=d.textContent||'';}else{var d=document.createElement('div');d.innerHTML=i;g('eO').textContent=d.textContent.replace(/\s+/g,' ').trim();}}

// ── Regex ──
function rxMatch(regex, text, flags) {
  var re = new RegExp(regex, flags);
  var results = [];
  var lastIndex = -1;
  var match = re.exec(text);
  while (match !== null) {
    if (re.lastIndex === lastIndex || match[0] === '') break;
    var idx = match.indices;
    var captures = [];
    Object.keys(match).forEach(function (k) {
      if (k !== '0' && /^\d+$/.test(k) && idx && idx[+k]) {
        captures.push({ name: k, value: match[k], start: idx[+k][0], end: idx[+k][1] });
      }
    });
    var groups = [];
    Object.keys(match.groups || {}).forEach(function (k) {
      if (idx && idx.groups && idx.groups[k]) {
        groups.push({ name: k, value: match.groups[k], start: idx.groups[k][0], end: idx.groups[k][1] });
      }
    });
    results.push({ index: match.index, value: match[0], captures: captures, groups: groups });
    lastIndex = re.lastIndex;
    match = re.exec(text);
  }
  return results;
}
function rxCompute() {
  var p = g('rxP') ? g('rxP').value : '';
  var t = g('rxT') ? g('rxT').value : '';
  var out = g('rxMatches');
  if (!out) return;
  if (!p) { out.innerHTML = EP.alert(_t('regex_nomatch'), 'warning'); return; }
  var flags = 'd';
  if (g('rx_g') && g('rx_g').checked) flags += 'g';
  if (g('rx_i') && g('rx_i').checked) flags += 'i';
  if (g('rx_m') && g('rx_m').checked) flags += 'm';
  if (g('rx_s') && g('rx_s').checked) flags += 's';
  if (g('rx_u') && g('rx_u').checked) flags += 'u';
  else if (g('rx_v') && g('rx_v').checked) flags += 'v';
  var results;
  try {
    results = rxMatch(p, t, flags);
  } catch (e) {
    out.innerHTML = EP.alert(_t('regex_invalid') + ': ' + e.message, 'danger');
    return;
  }
  if (!results.length) { out.innerHTML = EP.alert(_t('regex_nomatch'), 'warning'); return; }
  var rows = results.map(function (r) {
    var caps = r.captures.length
      ? r.captures.map(function (c) { return '"' + c.name + '" = ' + c.value + ' [' + c.start + '-' + c.end + ']'; }).join('<br>')
      : '-';
    var grps = r.groups.length
      ? r.groups.map(function (c) { return '"' + c.name + '" = ' + c.value + ' [' + c.start + '-' + c.end + ']'; }).join('<br>')
      : '-';
    return [String(r.index), r.value, caps, grps];
  });
  out.innerHTML = EP.table([_t('regex_index'), _t('regex_value'), _t('regex_captures'), _t('regex_groups')], rows);
}

// ── Chmod Calculator ──
function chmodSym(v) { return (v & 4 ? 'r' : '-') + (v & 2 ? 'w' : '-') + (v & 1 ? 'x' : '-'); }
function fCM() {
  var bit = { read: 4, write: 2, execute: 1 };
  function val(grp) {
    var v = 0;
    ['read', 'write', 'execute'].forEach(function (s) {
      var el = g('c_' + grp.charAt(0) + s.charAt(0));
      if (el && el.checked) v += bit[s];
    });
    return v;
  }
  var o = val('owner'), gr = val('group'), pu = val('public');
  var co = g('cOct'), cs = g('cSym'), cc = g('cCmd'), cn = g('cN');
  if (co) co.textContent = '' + o + gr + pu;
  if (cs) cs.textContent = chmodSym(o) + chmodSym(gr) + chmodSym(pu);
  if (cc) cc.value = 'chmod ' + o + gr + pu + ' path';
  if (cn && cn.value !== ('' + o + gr + pu)) cn.value = '' + o + gr + pu;
}
function fCN() {
  var v = g('cN') ? g('cN').value : '';
  if (/^\d{3}$/.test(v)) sCM(+v[0], +v[1], +v[2]);
}
function sCM(o, gr, pu) {
  function apply(grp, val) {
    [['read', 4], ['write', 2], ['execute', 1]].forEach(function (p) {
      var el = g('c_' + grp.charAt(0) + p[0].charAt(0));
      if (el) el.checked = !!(val & p[1]);
    });
  }
  apply('owner', o); apply('group', gr); apply('public', pu);
  fCM();
}

// ── Token ──
function fTK(){
  var len=+g('kL').value||32,cnt=Math.min(100,+g('kC').value||5),type=+g('kT').selectedIndex;
  var sets=['0123456789abcdef','abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789','abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_','0123456789'];
  g('kO').innerHTML=Array.from({length:cnt},function(){var t='',a=new Uint8Array(len);crypto.getRandomValues(a);for(var j=0;j<len;j++)t+=sets[type][a[j]%sets[type].length];return '<div class="tki">'+t+'</div>';}).join('');
}

// ── HTTP Status Codes ──
var HTTP_CATS = ['1xx informational response','2xx success','3xx redirection','4xx client error','5xx server error'];
var HTTP_CODES = [
  {code:100,name:'Continue',description:'Waiting for the client to emit the body of the request.',type:'HTTP',cat:'1xx informational response'},
  {code:101,name:'Switching Protocols',description:'The server has agreed to change protocol.',type:'HTTP',cat:'1xx informational response'},
  {code:102,name:'Processing',description:'The server is processing the request, but no response is available yet.',type:'WebDav',cat:'1xx informational response'},
  {code:103,name:'Early Hints',description:'The server returns some response headers before final HTTP message.',type:'HTTP',cat:'1xx informational response'},
  {code:200,name:'OK',description:'Standard response for successful HTTP requests.',type:'HTTP',cat:'2xx success'},
  {code:201,name:'Created',description:'The request has been fulfilled, resulting in the creation of a new resource.',type:'HTTP',cat:'2xx success'},
  {code:202,name:'Accepted',description:'The request has been accepted for processing, but the processing has not been completed.',type:'HTTP',cat:'2xx success'},
  {code:203,name:'Non-Authoritative Information',description:'The request is successful but the content has been modified by a transforming proxy.',type:'HTTP',cat:'2xx success'},
  {code:204,name:'No Content',description:'The server successfully processed the request and is not returning any content.',type:'HTTP',cat:'2xx success'},
  {code:205,name:'Reset Content',description:'The server indicates to reinitialize the document view which sent this request.',type:'HTTP',cat:'2xx success'},
  {code:206,name:'Partial Content',description:'The server is delivering only part of the resource due to a range header sent by the client.',type:'HTTP',cat:'2xx success'},
  {code:207,name:'Multi-Status',description:'The message body is an XML message and can contain separate response codes.',type:'WebDav',cat:'2xx success'},
  {code:208,name:'Already Reported',description:'The members of a DAV binding have already been enumerated in a preceding part of the response.',type:'WebDav',cat:'2xx success'},
  {code:226,name:'IM Used',description:'The server has fulfilled a request and the response is a representation of the result.',type:'HTTP',cat:'2xx success'},
  {code:300,name:'Multiple Choices',description:'Indicates multiple options for the resource that the client may follow.',type:'HTTP',cat:'3xx redirection'},
  {code:301,name:'Moved Permanently',description:'This and all future requests should be directed to the given URI.',type:'HTTP',cat:'3xx redirection'},
  {code:302,name:'Found',description:'Redirect to another URL. Industry practice contradicting the standard.',type:'HTTP',cat:'3xx redirection'},
  {code:303,name:'See Other',description:'The response can be found under another URI using a GET method.',type:'HTTP',cat:'3xx redirection'},
  {code:304,name:'Not Modified',description:'Indicates that the resource has not been modified since the version specified by the request headers.',type:'HTTP',cat:'3xx redirection'},
  {code:305,name:'Use Proxy',description:'The requested resource is available only through a proxy, the address provided in the response.',type:'HTTP',cat:'3xx redirection'},
  {code:306,name:'Switch Proxy',description:'No longer used. Originally meant subsequent requests should use the specified proxy.',type:'HTTP',cat:'3xx redirection'},
  {code:307,name:'Temporary Redirect',description:'The request should be repeated with another URI; future requests should still use the original URI.',type:'HTTP',cat:'3xx redirection'},
  {code:308,name:'Permanent Redirect',description:'The request and all future requests should be repeated using another URI.',type:'HTTP',cat:'3xx redirection'},
  {code:400,name:'Bad Request',description:'The server cannot or will not process the request due to an apparent client error.',type:'HTTP',cat:'4xx client error'},
  {code:401,name:'Unauthorized',description:'Authentication is required and has failed or has not yet been provided.',type:'HTTP',cat:'4xx client error'},
  {code:402,name:'Payment Required',description:'Reserved for future use.',type:'HTTP',cat:'4xx client error'},
  {code:403,name:'Forbidden',description:'The request was valid, but the server is refusing action.',type:'HTTP',cat:'4xx client error'},
  {code:404,name:'Not Found',description:'The requested resource could not be found but may be available in the future.',type:'HTTP',cat:'4xx client error'},
  {code:405,name:'Method Not Allowed',description:'A request method is not supported for the requested resource.',type:'HTTP',cat:'4xx client error'},
  {code:406,name:'Not Acceptable',description:'The resource is capable of generating only content not acceptable per the Accept headers.',type:'HTTP',cat:'4xx client error'},
  {code:407,name:'Proxy Authentication Required',description:'The client must first authenticate itself with the proxy.',type:'HTTP',cat:'4xx client error'},
  {code:408,name:'Request Timeout',description:'The server timed out waiting for the request.',type:'HTTP',cat:'4xx client error'},
  {code:409,name:'Conflict',description:'The request could not be processed because of conflict in the request.',type:'HTTP',cat:'4xx client error'},
  {code:410,name:'Gone',description:'The resource requested is no longer available and will not be available again.',type:'HTTP',cat:'4xx client error'},
  {code:411,name:'Length Required',description:'The request did not specify the length of its content, required by the resource.',type:'HTTP',cat:'4xx client error'},
  {code:412,name:'Precondition Failed',description:'The server does not meet one of the preconditions put on the request.',type:'HTTP',cat:'4xx client error'},
  {code:413,name:'Payload Too Large',description:'The request is larger than the server is willing or able to process.',type:'HTTP',cat:'4xx client error'},
  {code:414,name:'URI Too Long',description:'The URI provided was too long for the server to process.',type:'HTTP',cat:'4xx client error'},
  {code:415,name:'Unsupported Media Type',description:'The request entity has a media type which the server or resource does not support.',type:'HTTP',cat:'4xx client error'},
  {code:416,name:'Range Not Satisfiable',description:'The client asked for a portion of the file the server cannot supply.',type:'HTTP',cat:'4xx client error'},
  {code:417,name:'Expectation Failed',description:'The server cannot meet the requirements of the Expect request-header field.',type:'HTTP',cat:'4xx client error'},
  {code:418,name:"I'm a teapot",description:'The server refuses the attempt to brew coffee with a teapot.',type:'HTTP',cat:'4xx client error'},
  {code:421,name:'Misdirected Request',description:'The request was directed at a server that is not able to produce a response.',type:'HTTP',cat:'4xx client error'},
  {code:422,name:'Unprocessable Entity',description:'The request was well-formed but unable to be followed due to semantic errors.',type:'HTTP',cat:'4xx client error'},
  {code:423,name:'Locked',description:'The resource being accessed is locked.',type:'HTTP',cat:'4xx client error'},
  {code:424,name:'Failed Dependency',description:'The request failed due to failure of a previous request.',type:'HTTP',cat:'4xx client error'},
  {code:425,name:'Too Early',description:'The server is unwilling to risk processing a request that might be replayed.',type:'HTTP',cat:'4xx client error'},
  {code:426,name:'Upgrade Required',description:'The client should switch to a different protocol such as TLS/1.0.',type:'HTTP',cat:'4xx client error'},
  {code:428,name:'Precondition Required',description:'The origin server requires the request to be conditional.',type:'HTTP',cat:'4xx client error'},
  {code:429,name:'Too Many Requests',description:'The user has sent too many requests in a given amount of time.',type:'HTTP',cat:'4xx client error'},
  {code:431,name:'Request Header Fields Too Large',description:'The server is unwilling to process the request because header fields are too large.',type:'HTTP',cat:'4xx client error'},
  {code:451,name:'Unavailable For Legal Reasons',description:'A server operator has received a legal demand to deny access to a resource.',type:'HTTP',cat:'4xx client error'},
  {code:500,name:'Internal Server Error',description:'A generic error message, given when an unexpected condition was encountered.',type:'HTTP',cat:'5xx server error'},
  {code:501,name:'Not Implemented',description:'The server does not recognize the request method or lacks ability to fulfill it.',type:'HTTP',cat:'5xx server error'},
  {code:502,name:'Bad Gateway',description:'The server was acting as a gateway or proxy and received an invalid response.',type:'HTTP',cat:'5xx server error'},
  {code:503,name:'Service Unavailable',description:'The server is currently unavailable (overloaded or down for maintenance).',type:'HTTP',cat:'5xx server error'},
  {code:504,name:'Gateway Timeout',description:'The server did not receive a timely response from the upstream server.',type:'HTTP',cat:'5xx server error'},
  {code:505,name:'HTTP Version Not Supported',description:'The server does not support the HTTP protocol version used.',type:'HTTP',cat:'5xx server error'},
  {code:506,name:'Variant Also Negotiates',description:'Transparent content negotiation results in a circular reference.',type:'HTTP',cat:'5xx server error'},
  {code:507,name:'Insufficient Storage',description:'The server is unable to store the representation needed to complete the request.',type:'HTTP',cat:'5xx server error'},
  {code:508,name:'Loop Detected',description:'The server detected an infinite loop while processing the request.',type:'HTTP',cat:'5xx server error'},
  {code:510,name:'Not Extended',description:'Further extensions to the request are required for the server to fulfill it.',type:'HTTP',cat:'5xx server error'},
  {code:511,name:'Network Authentication Required',description:'The client needs to authenticate to gain network access.',type:'HTTP',cat:'5xx server error'}
];
var HTTP_FILTER = '';
function renderHTTP() {
  var q = (g('hQ') ? g('hQ').value : '').toLowerCase().trim();
  var data = HTTP_CODES.filter(function (c) {
    if (q) {
      var hay = (String(c.code) + ' ' + c.name + ' ' + c.description).toLowerCase();
      if (hay.indexOf(q) < 0) return false;
    }
    return true;
  });
  var out = g('hO2');
  if (!out) return;
  if (!data.length) { out.innerHTML = EP.alert('No results', 'warning'); return; }
  function card(c) {
    var extra = c.type && c.type !== 'HTTP' ? ' <span class="muted">For ' + c.type + '.</span>' : '';
    return '<div class="ep-card" style="margin-bottom:8px;padding:10px 14px">'
      + '<div style="font-weight:700;font-size:15px">' + c.code + ' ' + EP._a(c.name) + '</div>'
      + '<div class="muted" style="font-size:13px">' + EP._a(c.description) + extra + '</div>'
      + '</div>';
  }
  if (q) {
    out.innerHTML = '<div class="ep-subtitle">' + _t('http_search_results') + '</div>' + data.map(card).join('');
    return;
  }
  var html = '';
  HTTP_CATS.forEach(function (cat) {
    var cs = data.filter(function (c) { return c.cat === cat; });
    if (!cs.length) return;
    html += '<div class="ep-subtitle">' + cat + '</div>' + cs.map(card).join('');
  });
  out.innerHTML = html;
}
function fHQ() { renderHTTP(); }
function fHC(n) { HTTP_FILTER = n; renderHTTP(); }

// ── MIME Types ──
var MIME_LIST = [
  // text/*
  {mime:'text/html',ext:'html'},{mime:'text/css',ext:'css'},{mime:'text/plain',ext:'txt'},
  {mime:'text/markdown',ext:'md'},{mime:'text/csv',ext:'csv'},{mime:'text/xml',ext:'xml'},
  {mime:'text/calendar',ext:'ics'},{mime:'text/event-stream',ext:'stream'},{mime:'text/x-vcard',ext:'vcf'},
  {mime:'text/cache-manifest',ext:'appcache'},{mime:'text/vtt',ext:'vtt'},{mime:'text/x-sql',ext:'sql'},
  {mime:'text/yaml',ext:'yaml'},{mime:'text/javascript',ext:'js'},{mime:'text/less',ext:'less'},
  {mime:'text/sass',ext:'sass'},
  // application/*
  {mime:'application/javascript',ext:'js'},{mime:'application/json',ext:'json'},{mime:'application/ld+json',ext:'jsonld'},
  {mime:'application/xml',ext:'xml'},{mime:'application/xhtml+xml',ext:'xhtml'},{mime:'application/pdf',ext:'pdf'},
  {mime:'application/zip',ext:'zip'},{mime:'application/gzip',ext:'gz'},{mime:'application/x-bzip2',ext:'bz2'},
  {mime:'application/x-httpd-php',ext:'php'},{mime:'application/x-www-form-urlencoded',ext:'urlencoded'},
  {mime:'application/octet-stream',ext:'bin'},{mime:'application/msword',ext:'doc'},
  {mime:'application/vnd.openxmlformats-officedocument.wordprocessingml.document',ext:'docx'},
  {mime:'application/vnd.ms-excel',ext:'xls'},{mime:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',ext:'xlsx'},
  {mime:'application/vnd.ms-powerpoint',ext:'ppt'},{mime:'application/vnd.openxmlformats-officedocument.presentationml.presentation',ext:'pptx'},
  {mime:'application/vnd.oasis.opendocument.text',ext:'odt'},{mime:'application/vnd.oasis.opendocument.spreadsheet',ext:'ods'},
  {mime:'application/vnd.oasis.opendocument.presentation',ext:'odp'},{mime:'application/rtf',ext:'rtf'},
  {mime:'application/vnd.ms-access',ext:'mdb'},{mime:'application/x-7z-compressed',ext:'7z'},
  {mime:'application/x-rar-compressed',ext:'rar'},{mime:'application/x-tar',ext:'tar'},
  {mime:'application/postscript',ext:'ps'},{mime:'application/epub+zip',ext:'epub'},
  {mime:'application/java-archive',ext:'jar'},{mime:'application/vnd.android.package-archive',ext:'apk'},
  {mime:'application/x-sh',ext:'sh'},{mime:'application/sql',ext:'sql'},{mime:'application/graphql',ext:'graphql'},
  {mime:'application/wasm',ext:'wasm'},{mime:'application/x-ndjson',ext:'ndjson'},{mime:'application/json5',ext:'json5'},
  {mime:'application/atom+xml',ext:'atom'},{mime:'application/rss+xml',ext:'rss'},{mime:'application/yaml',ext:'yaml'},
  {mime:'application/vnd.api+json',ext:'jsonapi'},{mime:'application/vnd.mozilla.xul+xml',ext:'xul'},
  {mime:'application/font-woff',ext:'woff'},{mime:'application/x-font-ttf',ext:'ttf'},
  {mime:'application/x-font-otf',ext:'otf'},{mime:'application/pkcs7-signature',ext:'p7s'},
  {mime:'application/x-javascript',ext:'js'},{mime:'application/x-msdownload',ext:'exe'},
  {mime:'application/x-dmg',ext:'dmg'},{mime:'application/x-iso9660-image',ext:'iso'},
  {mime:'application/vnd.apple.mpegurl',ext:'m3u8'},{mime:'application/xspf+xml',ext:'xspf'},
  {mime:'application/smil+xml',ext:'smil'},{mime:'application/x-shockwave-flash',ext:'swf'},
  {mime:'application/vnd.amazon.ebook',ext:'azw'},{mime:'application/coap-group+json',ext:'json'},
  {mime:'application/cbor',ext:'cbor'},{mime:'application/problem+json',ext:'json'},
  {mime:'application/x-www-form-urlencoded',ext:'urlencoded'},
  // image/*
  {mime:'image/png',ext:'png'},{mime:'image/jpeg',ext:'jpg'},{mime:'image/jpg',ext:'jpg'},
  {mime:'image/gif',ext:'gif'},{mime:'image/svg+xml',ext:'svg'},{mime:'image/webp',ext:'webp'},
  {mime:'image/x-icon',ext:'ico'},{mime:'image/vnd.microsoft.icon',ext:'ico'},{mime:'image/bmp',ext:'bmp'},
  {mime:'image/tiff',ext:'tiff'},{mime:'image/avif',ext:'avif'},{mime:'image/heic',ext:'heic'},
  {mime:'image/heif',ext:'heif'},{mime:'image/apng',ext:'apng'},{mime:'image/jxl',ext:'jxl'},
  {mime:'image/x-portable-pixmap',ext:'ppm'},{mime:'image/x-portable-bitmap',ext:'pbm'},
  {mime:'image/x-portable-graymap',ext:'pgm'},{mime:'image/x-rgb',ext:'rgb'},{mime:'image/x-xbitmap',ext:'xbm'},
  {mime:'image/x-xpixmap',ext:'xpm'},{mime:'image/x-emf',ext:'emf'},{mime:'image/x-wmf',ext:'wmf'},
  // audio/*
  {mime:'audio/mpeg',ext:'mp3'},{mime:'audio/wav',ext:'wav'},{mime:'audio/x-wav',ext:'wav'},
  {mime:'audio/ogg',ext:'ogg'},{mime:'audio/aac',ext:'aac'},{mime:'audio/webm',ext:'weba'},
  {mime:'audio/flac',ext:'flac'},{mime:'audio/midi',ext:'midi'},{mime:'audio/x-midi',ext:'midi'},
  {mime:'audio/x-aiff',ext:'aiff'},{mime:'audio/x-matroska',ext:'mka'},{mime:'audio/amr',ext:'amr'},
  {mime:'audio/x-m4a',ext:'m4a'},{mime:'audio/mp4',ext:'m4a'},{mime:'audio/aiff',ext:'aiff'},
  // video/*
  {mime:'video/mp4',ext:'mp4'},{mime:'video/mpeg',ext:'mpeg'},{mime:'video/webm',ext:'webm'},
  {mime:'video/quicktime',ext:'mov'},{mime:'video/x-msvideo',ext:'avi'},{mime:'video/x-matroska',ext:'mkv'},
  {mime:'video/x-ms-wmv',ext:'wmv'},{mime:'video/x-flv',ext:'flv'},{mime:'video/3gpp',ext:'3gp'},
  {mime:'video/ogg',ext:'ogv'},{mime:'video/mp2t',ext:'ts'},{mime:'video/x-m4v',ext:'m4v'},
  {mime:'video/avi',ext:'avi'},
  // font/*
  {mime:'font/woff',ext:'woff'},{mime:'font/woff2',ext:'woff2'},{mime:'font/ttf',ext:'ttf'},
  {mime:'font/otf',ext:'otf'},{mime:'font/collection',ext:'ttc'},{mime:'font/opentype',ext:'otf'},
  // model/*
  {mime:'model/gltf-binary',ext:'glb'},{mime:'model/gltf+json',ext:'gltf'},
  {mime:'model/vnd.collada+xml',ext:'dae'},{mime:'model/iges',ext:'igs'},
  {mime:'model/mesh',ext:'msh'},{mime:'model/vrml',ext:'wrl'},{mime:'model/obj',ext:'obj'},
  {mime:'model/stl',ext:'stl'},
  // multipart /*
  {mime:'multipart/form-data',ext:'formdata'},{mime:'multipart/byteranges',ext:'byteranges'},
  {mime:'multipart/mixed',ext:'mixed'},{mime:'multipart/alternative',ext:'alternative'},
  {mime:'multipart/related',ext:'related'},{mime:'multipart/signed',ext:'signed'},
  {mime:'multipart/encrypted',ext:'encrypted'},
  // message *
  {mime:'message/rfc822',ext:'eml'},{mime:'message/partial',ext:'part'},
  // chemical *
  {mime:'chemical/x-cml',ext:'cml'},{mime:'chemical/x-pdb',ext:'pdb'},
  // application (extra docs)
  {mime:'application/x-tex',ext:'tex'},{mime:'application/x-latex',ext:'latex'},
  {mime:'application/x-font-type1',ext:'pfb'},{mime:'application/x-dvi',ext:'dvi'},
  {mime:'application/vnd.visio',ext:'vsd'},{mime:'application/vnd.ms-outlook',ext:'msg'},
  {mime:'application/x-x509-ca-cert',ext:'crt'},{mime:'application/x-pkcs12',ext:'p12'},
  {mime:'application/x-pkcs7-certificates',ext:'p7b'},{mime:'application/x-bittorrent',ext:'torrent'},
  {mime:'application/x-nintendo-nes-rom',ext:'nes'},{mime:'application/x-genesis-rom',ext:'gen'},
  {mime:'application/x-sql',ext:'sql'}
];
function fMI() {
  var v = g('mMime') ? g('mMime').value : '';
  var el = g('mExts');
  if (!el) return;
  var found = MIME_LIST.filter(function (m) { return m.mime === v; });
  if (!found.length) { el.innerHTML = EP.alert(_t('mime_none'), 'warning'); return; }
  el.innerHTML = found.map(function (m) {
    return EP.tag('.' + m.ext, 'info') + ' ';
  }).join('');
}
function fME() {
  var v = g('mExt') ? g('mExt').value : '';
  var el = g('mMimeOut');
  if (!el) return;
  var found = MIME_LIST.filter(function (m) { return m.ext === v; });
  if (!found.length) { el.innerHTML = EP.alert(_t('mime_none'), 'warning'); return; }
  el.innerHTML = found.map(function (m) { return EP.tag(m.mime, 'primary') + ' '; }).join('');
}

// ── BOM Clean (1:1 with FlyEnv Tools/BomClean) ──
var BOM_DEF_EXCLUDE = ".idea\n.git\n.svn\n.vscode\nnode_modules";
var BOM = {
  path: "",
  files: [],
  allExt: [],
  allowExt: [],
  loading: false,
  running: false,
  end: false,
  progress: { count: 0, finish: 0, fail: 0, failTask: [], success: 0, successTask: [] }
};

function bomChoose() { var f = g("bomDirInput"); if (f) f.click(); }
function bomOnDir(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) {
    BOM.path = f.path;
    var p = g("bomPath"); if (p) p.value = f.path;
    bomLoadFiles();
  }
}
function bomPathInput() {
  var p = g("bomPath");
  BOM.path = p ? p.value.trim() : "";
  if (BOM.path) bomLoadFiles();
}
function bomLoadFiles() {
  if (!BOM.path) { EP.toast(_t("bom_no_path")); return; }
  cPHP("bom_list", { path: BOM.path });
}
function bomExcludeInput() { bomRecomputeExt(); }
function bomParseExclude() {
  var ex = g("bomExclude");
  return (ex ? ex.value : "").split("\n").map(function (s) { return s.trim(); }).filter(function (s) { return s.length > 0; });
}
function bomRecomputeExt() {
  var exclude = bomParseExclude();
  var all = BOM.files.filter(function (f) {
    return exclude.length === 0 || exclude.every(function (e) { return f.indexOf(e) === -1; });
  });
  var exts = {};
  all.forEach(function (f) {
    var m = /\.([^.\\/]+)$/.exec(f);
    if (m) exts[m[1]] = (exts[m[1]] || 0) + 1;
  });
  var arr = [];
  for (var k in exts) arr.push({ ext: k, count: exts[k] });
  arr.sort(function (a, b) { return a.ext.localeCompare(b.ext); });
  BOM.allExt = arr;
  BOM.allowExt = arr.map(function (e) { return e.ext; });
  bomRenderExt();
  bomUpdateBtn();
}
function bomRenderExt() {
  var box = g("bomExtList"); if (!box) return;
  if (!BOM.allExt.length) { box.innerHTML = '<div class="bom-ext-empty">' + _t("bom_no_ext") + "</div>"; return; }
  box.innerHTML = BOM.allExt.map(function (e) {
    return '<label class="bom-ext-item"><input type="checkbox" class="bom-ext-cb" value="' + esc(e.ext) + '" checked onchange="bomExtToggle()">'
      + '<span class="bom-ext-name">.' + esc(e.ext) + '</span>'
      + '<span class="bom-ext-count">(' + e.count + ')</span></label>';
  }).join("");
}
function bomExtToggle() {
  var cbs = document.querySelectorAll("#bomExtList .bom-ext-cb");
  BOM.allowExt = Array.prototype.slice.call(cbs).filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
}
function bomEffectiveFiles() {
  var exclude = bomParseExclude();
  var allow = BOM.allowExt;
  return BOM.files.filter(function (f) {
    if (exclude.length && exclude.some(function (e) { return f.indexOf(e) !== -1; })) return false;
    var m = /\.([^.\\/]+)$/.exec(f);
    var ext = m ? m[1] : "";
    return allow.indexOf(ext) !== -1;
  });
}
function bomClean() {
  if (BOM.running || BOM.end) return;
  var files = bomEffectiveFiles();
  if (!files.length) { EP.toast(_t("bom_no_files")); return; }
  BOM.running = true;
  BOM.progress = { count: files.length, finish: 0, fail: 0, failTask: [], success: 0, successTask: [] };
  bomUpdateBtn();
  bomShowProgress();
  bomRenderProgress();
  cPHP("bom_clean", { files: files });
}
function bomUpdateBtn() {
  var btn = g("bomCleanBtn"); if (!btn) return;
  if (BOM.end) {
    btn.textContent = _t("bom_confirm");
    btn.disabled = false;
    btn.onclick = bomEnd;
  } else if (BOM.running) {
    btn.textContent = _t("bom_cleaning");
    btn.disabled = true;
  } else {
    btn.textContent = _t("bom_cleanup");
    btn.disabled = BOM.files.length === 0;
    btn.onclick = bomClean;
  }
}
function bomShowProgress() { var p = g("bomProgress"); if (p) p.style.display = "block"; }
function bomRenderProgress() {
  var fill = g("bomProgressFill"); var txt = g("bomProgressText");
  var pct = BOM.progress.count ? Math.floor((BOM.progress.finish / BOM.progress.count) * 100) : 0;
  if (fill) fill.style.width = pct + "%";
  if (txt) txt.textContent = BOM.progress.finish + " / " + BOM.progress.count;
}
function bomRenderResult() {
  var box = g("bomResult"); var body = g("bomResultBody");
  if (!box || !body) return;
  box.style.display = "block";
  var p = BOM.progress;
  var html = "";
  html += '<div class="bom-row"><span class="bom-row-label">' + _t("bom_total") + "</span><span>" + p.count + "</span></div>";
  html += '<div class="bom-row"><span class="bom-row-label">' + _t("bom_checked") + "</span><span>" + p.finish + "</span></div>";
  html += '<div class="bom-row bom-row--ok"><span class="bom-row-label">' + _t("bom_success") + "</span><span>" + p.success + "</span></div>";
  html += '<div class="bom-row bom-row--err"><span class="bom-row-label">' + _t("bom_fail") + "</span><span>" + p.fail + "</span></div>";
  body.innerHTML = html;
  if (p.successTask.length) {
    body.innerHTML += '<details class="bom-detail bom-detail--ok"><summary>' + _t("bom_success") + " (" + p.successTask.length + ")</summary><ul class=\"bom-list\">"
      + p.successTask.map(function (t) { return "<li>" + esc(t.path) + "</li>"; }).join("") + "</ul></details>";
  }
  if (p.failTask.length) {
    body.innerHTML += '<details class="bom-detail bom-detail--err"><summary>' + _t("bom_fail") + " (" + p.failTask.length + ")</summary><ul class=\"bom-list\">"
      + p.failTask.map(function (t) { return "<li>" + esc(t.path) + ": " + esc(t.msg) + "</li>"; }).join("") + "</ul></details>";
  }
}
function bomEnd() {
  BOM.path = "";
  BOM.files = [];
  BOM.allExt = [];
  BOM.allowExt = [];
  BOM.loading = false;
  BOM.running = false;
  BOM.end = false;
  BOM.progress = { count: 0, finish: 0, fail: 0, failTask: [], success: 0, successTask: [] };
  var p = g("bomPath"); if (p) p.value = "";
  var ex = g("bomExclude"); if (ex) ex.value = BOM_DEF_EXCLUDE;
  var prog = g("bomProgress"); if (prog) prog.style.display = "none";
  var res = g("bomResult"); if (res) res.style.display = "none";
  var ext = g("bomExtList"); if (ext) ext.innerHTML = "";
  bomUpdateBtn();
}
function bomReset() { bomEnd(); }

// ── Markdown ──
// GFM-flavored line-based renderer (headings, tables, nested lists, code fences, blockquote, inline)
function mdSplitRow(line) {
  var t = line.trim();
  if (t.charAt(0) === '|') t = t.slice(1);
  if (t.length && t.charAt(t.length - 1) === '|') t = t.slice(0, -1);
  var cells = [], cur = '', k = 0;
  while (k < t.length) {
    if (t.charAt(k) === '\\' && t.charAt(k + 1) === '|') { cur += '|'; k += 2; continue; }
    if (t.charAt(k) === '|') { cells.push(cur); cur = ''; k++; continue; }
    cur += t.charAt(k); k++;
  }
  cells.push(cur);
  return cells.map(function (c) { return c.trim(); });
}
function mdInline(t) {
  return t
    .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
    .replace(/\*([^*\n]+?)\*/g, '<i>$1</i>')
    .replace(/`([^`]+?)`/g, '<code>$1</code>')
    .replace(/\[([^\]]+?)\]\(([^)]+?)\)/g, '<a href="$2" target="_blank" rel="noopener" onclick="return mdExtLink(event,this)">$1</a>');
}
function mdRenderList(items) {
  function build(arr, start) {
    var html = '<ul>', k = start;
    while (k < arr.length) {
      var it = arr[k];
      html += '<li>' + mdInline(it.text);
      var j = k + 1;
      if (j < arr.length && arr[j].indent > it.indent) {
        var sub = build(arr, j);
        html += sub.html; k = sub.next;
      } else { k++; }
      html += '</li>';
    }
    html += '</ul>';
    return { html: html, next: k };
  }
  return build(items, 0).html;
}
function mdRenderGFM(src) {
  var s = String(src == null ? '' : src).replace(/\r\n/g, '\n');
  s = s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  var lines = s.split('\n'), out = [], i = 0;
  function isTableSep(l) { return /^\s*\|?[\s:|-]+\|?\s*$/.test(l) && l.indexOf('--') !== -1; }
  while (i < lines.length) {
    var line = lines[i];
    if (/^```/.test(line)) {
      var buf = []; i++;
      while (i < lines.length && !/^```/.test(lines[i])) { buf.push(lines[i]); i++; }
      i++;
      out.push('<pre class="ep-pre"><code>' + buf.join('\n') + '</code></pre>');
      continue;
    }
    if (line.indexOf('|') !== -1 && i + 1 < lines.length && isTableSep(lines[i + 1])) {
      var header = mdSplitRow(line), rows = [];
      i += 2;
      while (i < lines.length && lines[i].indexOf('|') !== -1 && lines[i].trim() !== '') {
        rows.push(mdSplitRow(lines[i])); i++;
      }
      var tbl = '<div class="md-table-wrap"><table class="md-table"><thead><tr>';
      header.forEach(function (c) { tbl += '<th>' + mdInline(c) + '</th>'; });
      tbl += '</tr></thead><tbody>';
      rows.forEach(function (r) {
        tbl += '<tr>';
        header.forEach(function (_, idx) { tbl += '<td>' + mdInline(r[idx] || '') + '</td>'; });
        tbl += '</tr>';
      });
      tbl += '</tbody></table></div>';
      out.push(tbl);
      continue;
    }
    var h = line.match(/^(#{1,6})\s+(.*)$/);
    if (h) { var lvl = h[1].length; out.push('<h' + lvl + '>' + mdInline(h[2]) + '</h' + lvl + '>'); i++; continue; }
    if (/^>\s?/.test(line)) {
      var bq = [];
      while (i < lines.length && /^>\s?/.test(lines[i])) { bq.push(lines[i].replace(/^>\s?/, '')); i++; }
      out.push('<blockquote>' + mdInline(bq.join(' ')) + '</blockquote>');
      continue;
    }
    if (/^\s*[-*]\s+/.test(line)) {
      var block = [];
      while (i < lines.length && /^\s*[-*]\s+/.test(lines[i])) {
        var m = lines[i].match(/^(\s*)[-*]\s+(.*)$/);
        block.push({ indent: m[1].replace(/\t/g, '    ').length, text: m[2] });
        i++;
      }
      out.push(mdRenderList(block));
      continue;
    }
    if (line.trim() === '') { i++; continue; }
    var para = [];
    while (i < lines.length && lines[i].trim() !== '' &&
      !/^```/.test(lines[i]) &&
      !(lines[i].indexOf('|') !== -1 && i + 1 < lines.length && isTableSep(lines[i + 1])) &&
      !/^#{1,6}\s/.test(lines[i]) &&
      !/^>\s?/.test(lines[i]) &&
      !/^\s*[-*]\s+/.test(lines[i])) {
      para.push(mdInline(lines[i]));
      i++;
    }
    if (para.length) out.push('<p>' + para.join('<br>') + '</p>');
  }
  return out.join('\n');
}
function rMD(s) { return mdRenderGFM(s); }
function fMD() { var el = g('mI2'); if (el) { var o = g('mO2'); if (o) o.innerHTML = mdRender(el.value); } }
// Open cheatsheet/markdown external links in the system browser (mirrors original shell.openExternal)
function mdExtLink(e, el) {
  e.preventDefault();
  var url = el.getAttribute('href') || '';
  if (/^https?:\/\//i.test(url)) {
    if (typeof window.open_url === 'function') { try { window.open_url(JSON.stringify({ url: url })); } catch (_) {} }
    if (window.open) window.open(url, '_blank');
  }
  return false;
}

// ── Git Cheatsheet (1:1 with FlyEnv GitCheatsheet) ──
// Renders the full git memo markdown with per-command copy buttons,
// comment-line styling, and the dark code-block layout from the original.
var GM_COPY_SVG = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
var GM_CHECK_SVG = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
function gmEsc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function gmCodeBlock(buf){
  var rendered = buf.map(function(l){
    var trimmed = l.trim();
    if (trimmed === '' || trimmed.charAt(0) === '#') {
      return '<div class="code-line comment"><span class="line-content">'+gmEsc(l)+'</span></div>';
    }
    return '<div class="code-line command"><span class="line-content">'+gmEsc(l)+'</span>'
      + '<button class="gm-copy" type="button" data-code="'+encodeURIComponent(trimmed)+'" aria-label="Copy" onclick="gmCopy(this)">'+GM_COPY_SVG+'</button></div>';
  }).join('');
  return '<div class="code-block-wrapper"><pre><code>'+rendered+'</code></pre></div>';
}
function renderGitMemo(raw){
  var s = String(raw == null ? '' : raw).replace(/\r\n/g,'\n');
  var lines = s.split('\n'), out = [], prose = [], i = 0;
  function flush(){ if (prose.length){ out.push(mdRenderGFM(prose.join('\n'))); prose = []; } }
  while (i < lines.length) {
    var line = lines[i];
    if (/^```/.test(line)) {
      flush();
      var buf = []; i++;
      while (i < lines.length && !/^```/.test(lines[i])) { buf.push(lines[i]); i++; }
      i++; // skip closing fence
      out.push(gmCodeBlock(buf));
      continue;
    }
    prose.push(line); i++;
  }
  flush();
  return out.join('\n');
}
function gmCopy(btn){
  var code = decodeURIComponent(btn.getAttribute('data-code') || '');
  if (window.EP && EP.copy) EP.copy(code);
  else if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code);
  btn.innerHTML = GM_CHECK_SVG;
  btn.classList.add('is-copied');
  clearTimeout(btn._t);
  btn._t = setTimeout(function(){ btn.innerHTML = GM_COPY_SVG; btn.classList.remove('is-copied'); }, 2000);
}

// ── WebSocket / SSE ──
var WSS_SOCK=null, WSS_ES=null, WSS_PROTO='websocket', WSS_HB=null, WSS_START=0, WSS_LOGS=[];
function wssStatus(type, text) {
  var el = g('wStatus'); if (!el) return;
  el.className = 'ep-tag ep-tag--' + (type==='connected'?'success':type==='error'?'danger':'info');
  el.textContent = text;
}
function wssDur() {
  if (!WSS_START) return '0s';
  var s = Math.floor((Date.now()-WSS_START)/1000);
  return s>=60 ? Math.floor(s/60)+'m '+(s%60)+'s' : s+'s';
}
function wssTick() {
  var d = wssDur();
  if (g('wDur')) g('wDur').textContent = d;
  if (g('wDur2')) g('wDur2').textContent = d;
}
function wssProtoChange() {
  var r = document.querySelector('input[name="wProto"]:checked');
  WSS_PROTO = r ? r.value : 'websocket';
  var isWs = WSS_PROTO === 'websocket';
  if (g('wTabWsOpts')) g('wTabWsOpts').style.display = isWs ? '' : 'none';
  if (g('wTabSseOpts')) g('wTabSseOpts').style.display = isWs ? 'none' : '';
  if (g('wSendCard')) g('wSendCard').style.display = isWs ? '' : 'none';
  if (g('wSseInfoCard')) g('wSseInfoCard').style.display = isWs ? 'none' : '';
  if (g('wWsAlert')) g('wWsAlert').style.display = isWs ? '' : 'none';
  var u = g('wUrl'); if (u) u.placeholder = isWs ? 'ws://localhost:3000/ws' : 'http://localhost:3000/events';
  if (!isWs && g('wPaneWsOpts') && !g('wPaneWsOpts').hasAttribute('hidden')) wssSwitchTab(document.getElementById('wTabSseOpts'),'wPaneSseOpts');
}
function wssSwitchTab(btn, paneId) {
  document.querySelectorAll('.wss-tab').forEach(function(x){x.classList.remove('active');});
  if (btn) btn.classList.add('active');
  document.querySelectorAll('.wss-pane').forEach(function(p){p.hidden = true;});
  var p = g(paneId); if (p) p.hidden = false;
}
function wssRowHtml() {
  return '<div class="wss-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">'
    + '<input type="checkbox" class="wss-en" checked>'
    + '<input class="ep-input wss-k" placeholder="'+_t('wss_key')+'">'
    + '<input class="ep-input wss-v" placeholder="'+_t('wss_value')+'">'
    + '<button type="button" class="ep-btn ep-btn--link" onclick="wssRemoveRow(this)">'+_t('wss_delete')+'</button>'
    + '</div>';
}
function wssAddParam() { var c=g('wParamRows'); if(c) c.insertAdjacentHTML('beforeend', wssRowHtml()); }
function wssAddHeader() { var c=g('wHeaderRows'); if(c) c.insertAdjacentHTML('beforeend', wssRowHtml()); }
function wssRemoveRow(btn) { var row=btn.closest('.wss-row'); if(row) row.remove(); }
function wssApplyAuth() {
  var t = g('wBearer'); if (!t || !t.value.trim()) return;
  var c = g('wHeaderRows'); if (!c) return;
  var row = document.createElement('div'); row.className='wss-row'; row.style.cssText='display:flex;gap:8px;margin-bottom:8px;align-items:center';
  row.innerHTML = '<input type="checkbox" class="wss-en" checked>'
    + '<input class="ep-input wss-k" value="Authorization" readonly>'
    + '<input class="ep-input wss-v" value="Bearer '+esc(t.value.trim())+'">'
    + '<button type="button" class="ep-btn ep-btn--link" onclick="wssRemoveRow(this)">'+_t('wss_delete')+'</button>';
  c.appendChild(row);
}
function wssHbToggle() { var h=g('wHb'); var o=g('wHbOpts'); if(o) o.style.display = (h&&h.checked)?'block':'none'; }
function wssFormat() {
  var m=g('wMsg'); if(!m) return;
  try { m.value = JSON.stringify(JSON.parse(m.value), null, 2); } catch(e) { wssLog('error','Format', e.message); }
}
function wssClearLogs() {
  WSS_LOGS=[]; var el=g('wO'); if(el) el.innerHTML='<div class="muted" style="padding:8px">'+_t('wss_nologs')+'</div>';
  if(g('wLogCount')) g('wLogCount').textContent='0';
}
function wssLog(type, label, content) {
  var el=g('wO'); if(!el) return;
  WSS_LOGS.push({type:type,label:label,content:content,time:new Date()});
  var tagCls = type==='error'?'danger':(type==='received'||type==='event')?'success':type==='sent'?'primary':'info';
  var size = content ? (''+content.length+' B') : '';
  var t = new Date().toLocaleTimeString('zh-CN',{hour12:false});
  var entry = '<div class="wss-log-item">'
    + '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:6px">'
    + '<span class="ep-tag ep-tag--'+tagCls+'">'+type+'</span>'
    + '<span style="font-weight:600">'+esc(label)+'</span>'
    + '<span class="muted" style="font-size:12px">'+t+'</span>'
    + '<span class="muted" style="font-size:12px">'+size+'</span>'
    + '</div>';
  if (content) entry += '<pre class="wss-log-pre">'+esc(content)+'</pre>';
  entry += '</div>';
  if (WSS_LOGS.length===1) el.innerHTML='';
  el.innerHTML += entry;
  el.scrollTop = el.scrollHeight;
  if(g('wLogCount')) g('wLogCount').textContent = WSS_LOGS.length;
}
function wssConnect() {
  var url = g('wUrl') ? g('wUrl').value.trim() : '';
  if (!url) return;
  wssDisconnect(true);
  WSS_START = Date.now();
  wssTick();
  if (WSS_PROTO === 'sse') {
    try {
      WSS_ES = new EventSource(url);
      WSS_ES.onopen = function(){ wssStatus('connected', _t('wss_connected')); wssLog('info','SSE','Connection opened'); };
      WSS_ES.onmessage = function(e){ wssLog('event','message', e.data); };
      WSS_ES.onerror = function(){ wssStatus('error', _t('wss_error')); wssLog('error','SSE','Connection error'); };
    } catch(e) { wssStatus('error', _t('wss_error')); wssLog('error','SSE', e.message); }
  } else {
    try {
      var sub = g('wSub') ? g('wSub').value.trim() : '';
      var protoList = sub ? sub.split(',').map(function(s){return s.trim();}).filter(Boolean) : undefined;
      WSS_SOCK = protoList ? new WebSocket(url, protoList) : new WebSocket(url);
      WSS_SOCK.onopen = function(){ wssStatus('connected', _t('wss_connected')); wssLog('info','WS','Connection opened'); wssHbStart(); };
      WSS_SOCK.onmessage = function(e){ wssLog('received','message', e.data); };
      WSS_SOCK.onerror = function(){ wssStatus('error', _t('wss_error')); wssLog('error','WS','Error'); };
      WSS_SOCK.onclose = function(){ wssStatus('info', _t('wss_disconnected')); wssLog('info','WS','Connection closed'); wssHbStop(); };
    } catch(e) { wssStatus('error', _t('wss_error')); wssLog('error','WS', e.message); }
  }
}
function wssHbStart() {
  var h=g('wHb'); if(!h||!h.checked) return;
  var iv = parseInt(g('wHbInt')?g('wHbInt').value:'30',10)||30;
  var msg = g('wHbMsg')?g('wHbMsg').value:'ping';
  WSS_HB = setInterval(function(){ if(WSS_SOCK&&WSS_SOCK.readyState===1){ WSS_SOCK.send(msg); wssLog('sent','heartbeat',msg); } }, iv*1000);
}
function wssHbStop() { if(WSS_HB){ clearInterval(WSS_HB); WSS_HB=null; } }
function wssDisconnect(silent) {
  wssHbStop();
  if (WSS_SOCK) { try{WSS_SOCK.close();}catch(e){} WSS_SOCK=null; }
  if (WSS_ES) { try{WSS_ES.close();}catch(e){} WSS_ES=null; }
  WSS_START=0; wssTick();
  if(!silent){ wssStatus('info', _t('wss_disconnected')); wssLog('info','WS','Disconnected'); }
}
function wssSend() {
  var m = g('wMsg') ? g('wMsg').value : '';
  if (WSS_SOCK && m) { WSS_SOCK.send(m); wssLog('sent','message', m); g('wMsg').value=''; }
}
function __init_wss() {
  wssClearLogs();
  wssProtoChange();
  wssHbToggle();
}
window.__init_wss = globalThis.__init_wss;

// ── Code Library ──
var cTab='snippets';var CLIB={snippets:['console.log("Hello");\nconst arr=[1,2,3];\narr.map(x=>x*2);','const obj={a:1,b:2};\nObject.keys(obj).forEach(k=>console.log(k));'],php:['<?php\n$db=new PDO("mysql:host=localhost;dbname=test","root","");','<?php\n$data=["a","b","c"];\necho implode(", ",$data);'],js:['const add=(a,b)=>a+b;\nconsole.log(add(1,2));','fetch("/api").then(r=>r.json()).then(console.log);']};
function sCT(el,t){cTab=t;document.querySelectorAll('.cl-tab').forEach(function(x){x.classList.remove('active');});el.classList.add('active');g('cL').value=(CLIB[t]||[])[0]||'';}
function cCopy(){navigator.clipboard.writeText(g('cL').value);}

// ── PHP Bridge ──
// Map binding names to their output element IDs
var PHP_OUT_MAP = {
  code_run:'cO3', qr:'qrOut', image_c:'imgO', capture:'capO',
  file_info:'fO', url_timing:'tO2', site_suck:'sO', ssl_make:'sO2',
  php_obf:'obResult', rsa:null, port_kill:'pkO', process_kill:'procO',
  md:'mdOut'
};

async function cPHP(n,d){
  d = d||{};
  // Determine output element: _out hint > name-based map > null
  var outId = d._out || PHP_OUT_MAP[n] || null;
  var showErr = function(msg){
    var el = outId ? g(outId) : null;
    if(el) el.innerHTML = '<div style="color:#e74c3c;font-size:12px;padding:8px">⚠ '+esc(msg)+'</div>';
    else console.error('[PHP]',n,'!',msg);
  };

  for(var i=0;i<50;i++){if(typeof window[n]==='function')break;await new Promise(function(r){setTimeout(r,100);});}
  if(typeof window[n] !== 'function'){
    showErr('Bridge not ready: '+esc(n));
    return;
  }
  try{
    // Add 10s timeout
    var callPromise = window[n](JSON.stringify(d));
    var timeoutPromise = new Promise(function(_,reject){setTimeout(function(){reject(new Error('PHP call timed out'));},10000);});
    var r = await Promise.race([callPromise, timeoutPromise]);

    // Special handling per binding
    if(n==='port_kill'){handlePkResult(r);return}
    if(n==='process_kill'){handleProcResult(r);return}
    if(n==='qr' || n==='image_c' || n==='capture'){
      if(r&&r.error){showErr(r.error);return}
      var el = outId ? g(outId) : null;
      if(el){
        if(r.svg&&r.svg.indexOf('data:')===0)el.innerHTML='<div class="qbo"><img src="'+r.svg+'" style="max-width:260px;height:auto;display:block" alt="QR"></div>';
        else if(r.svg)el.innerHTML='<div class="qbo">'+r.svg+'</div>';
        else el.textContent=JSON.stringify(r,null,2);
      }
      return;
    }

    // Hash Text — fill each algorithm's input with its value
    if (n === 'hash') {
      if (r && r.error) { showErr(r.error); return; }
      if (r && typeof r === 'object') {
        Object.keys(r).forEach(function (algo) {
          var el = g('hash_' + algo);
          if (el) el.value = r[algo];
        });
      }
      return;
    }

    // Encryption — fill the result textarea
    if (n === 'encrypt') {
      var el = outId ? g(outId) : null;
      if (!el) return;
      if (r && r.error) { el.value = 'Error: ' + r.error; return; }
      el.value = (r && r.result != null) ? r.result : '';
      return;
    }

    // JWT — route by mode (enc writes token, dec writes header/payload + verification)
    if (n === 'jwt') {
      if (r && r.error) {
        if (d.mode === 'enc') { var je = g('jEncErr'); if (je) je.innerHTML = EP.alert(esc(r.error), 'danger'); return; }
        var jd = g('jDecMsg'); if (jd) jd.innerHTML = EP.alert(esc(r.error), 'danger');
        var jdh = g('jDHead'); if (jdh) jdh.value = ''; var jdp = g('jDPay'); if (jdp) jdp.value = '';
        return;
      }
      if (d.mode === 'enc') {
        var tok = g('jTok'); if (tok) tok.value = r.token || '';
        var jee = g('jEncErr'); if (jee) jee.innerHTML = '';
        return;
      }
      var dh = g('jDHead'), dp = g('jDPay'), dm = g('jDecMsg');
      if (dh) dh.value = r.header || '';
      if (dp) dp.value = r.payload || '';
      if (dm) dm.innerHTML = EP.alert(r.valid ? _t('jwt_sig_valid') : _t('jwt_sig_invalid'), r.valid ? 'success' : 'warning');
      return;
    }

    // Code Playground — fill output textarea with execution result
    if (n === 'code_run') {
      var ce = outId ? g(outId) : null;
      if (!ce) return;
      if (r && r.error) { ce.textContent = 'Error: ' + r.error; return; }
      ce.textContent = (r && r.output != null) ? r.output : '';
      return;
    }

    // Markdown — render returned HTML into the preview pane
    if (n === 'md') {
      var mdel = outId ? g(outId) : null;
      if (!mdel) return;
      if (r && r.error) { mdel.innerHTML = EP.alert(esc(r.error), 'danger'); return; }
      mdel.innerHTML = (r && r.html != null) ? r.html : '';
      return;
    }

    // RSA — split public/private into two textareas
    if (n === 'rsa') {
      if (r && r.error) { showErr(r.error); return; }
      var rp = g('rPub'), rv = g('rPriv');
      if (rp) rp.value = (r && r.public) || '';
      if (rv) rv.value = (r && r.private) || '';
      return;
    }

    // File Info — render descriptions grid (1:1 with FlyEnv el-descriptions)
    if (n === 'file_info') {
      if (r && r.error) { showErr(r.error); return; }
      var fo = g('fO');
      if (!fo) return;
      fo.innerHTML = renderFileInfo(r);
      return;
    }

    // BOM Clean — list files
    if (n === 'bom_list') {
      if (r && r.code) { EP.toast(r.msg || 'error'); BOM.loading = false; return; }
      BOM.files = (r && r.data) || [];
      BOM.loading = false;
      bomRecomputeExt();
      return;
    }

    // BOM Clean — strip BOM, render result
    if (n === 'bom_clean') {
      if (r && r.code) { EP.toast(r.msg || 'error'); BOM.running = false; bomUpdateBtn(); return; }
      BOM.progress = (r && r.progress) || BOM.progress;
      BOM.running = false;
      BOM.end = true;
      bomRenderProgress();
      bomRenderResult();
      bomUpdateBtn();
      return;
    }

    // Image compress — show result stats
    if (n === 'image_c') {
      if (r && r.error) { showErr(r.error); return; }
      var io = g('imgO');
      if (!io) return;
      io.innerHTML = '<div class="ep-stat-row">'
        + EP.tag(_t('img_dim') + ': ' + (r.dim || '-'), 'info') + ' '
        + EP.tag(_t('img_ratio') + ': ' + (r.ratio != null ? r.ratio + '%' : '-'), 'success') + ' '
        + EP.tag((r.orig || 0) + ' -> ' + (r.size || 0) + ' B', 'info')
        + '</div>'
        + '<div class="muted" style="margin-top:6px">' + esc(r.saved || '') + '</div>';
      return;
    }

    // Image base64 — render per-file result (Batch tab of ImageCompress)
    if (n === 'image_b64') {
      if (r && r.error) { showErr(r.error); return; }
      var bo = outId ? g(outId) : null;
      if (bo && r) {
        bo.innerHTML = EP.tag((r.size || 0) + ' B', 'success')
          + ' <a href="' + (r.data || '#') + '" download style="margin-left:8px;font-size:12px">↓</a>';
        if (outId) { var ik = outId.replace(/^imgRes_/, ''); if (ik !== outId && r.data) IMG_RESULTS[ik] = r.data; }
      }
      return;
    }

    // Capture — show saved path
    if (n === 'capture') {
      if (r && r.error) { showErr(r.error); return; }
      var co = g('capO');
      if (!co) return;
      co.innerHTML = EP.alert(_t('cap_saved') + ': ' + (r.path || ''), 'success');
      return;
    }

    // URL Timing — render waterfall + timing table (1:1 with FlyEnv)
    if (n === 'url_timing') {
      if (r && r.error) { showErr(r.error); return; }
      var to = g('tO2');
      if (!to) return;
      to.innerHTML = tmRender(r);
      return;
    }

    // Site Sucker — start / step / stop (polling crawler)
    if (n === 'site_suck_start') { if (r && r.error) { showErr(r.error); return; } ssOnStart(r); return; }
    if (n === 'site_suck_step') { if (r && r.error) { showErr(r.error); return; } ssOnStep(r); return; }
    if (n === 'site_suck_stop') { if (r && r.error) { showErr(r.error); return; } ssOnStop(r); return; }

    // SSL Make — fill cert/key textareas
    if (n === 'ssl_make') {
      if (r && r.error) { showErr(r.error); return; }
      // success — cert generated, show result
      var msg = (r && r.cert_path) ? ('Certificate saved to: ' + r.cert_path) : 'SSL certificate generated successfully';
      alert(msg);
      return;
    }

    // PHP Obfuscator — versions list (1:1 with FlyEnv phpVersions)
    if (n === 'php_obf_versions') {
      var pv = g('obPhp');
      if (!pv) return;
      if (r && r.error) { pv.innerHTML = '<option value="">' + _t('obf_no_php') + '</option>'; return; }
      var list = Array.isArray(r) ? r : [];
      if (!list.length) { pv.innerHTML = '<option value="">' + _t('obf_no_php') + '</option>'; return; }
      var vh = '';
      list.forEach(function (v) { vh += '<option value="' + EP._a(v.bin) + '">' + EP._a(v.version) + '</option>'; });
      pv.innerHTML = vh;
      OBF.phpBin = list[0].bin;
      obUpdateBtn();
      return;
    }

    // PHP Obfuscator — default config (1:1 with FlyEnv yakpro-po.default.cnf)
    if (n === 'php_obf_cnf') {
      if (r && !r.error && r.config) { OBF.config = r.config; }
      return;
    }

    // PHP Obfuscator — run result
    if (n === 'php_obf') {
      obHandleResult(r);
      return;
    }

    // System Environment — list of env config files
    if (n === 'php_env_files') {
      if (r && r.error) { EP.toast(r.error, "error"); return; }
      envRenderList(Array.isArray(r) ? r : []);
      return;
    }
    // System Environment — read file content into editor
    if (n === 'php_env_read') {
      envHandleRead(r);
      return;
    }
    // System Environment — save result
    if (n === 'php_env_save') {
      envHandleSave(r);
      return;
    }

    // Generic: get output element and display result
    if(r&&r.error){showErr(r.error);return}
    var el = outId ? g(outId) : null;
    if(el) el.textContent = typeof r === 'string' ? r : JSON.stringify(r,null,2);
    
  }catch(ex){
    var msg=ex&&typeof ex==='object'?(ex.error||ex.message||JSON.stringify(ex)):String(ex);
    showErr(msg);
  }
}

function handlePkResult(r) {
  if (r.processes && r.processes.length) {
    pkPids = r.processes.map(function(p){return p.PID;});
    pkSel = [];
    pkKilling = false;
    var h = '<div style="margin-bottom:6px;color:var(--dm);font-size:11px">Port '+r.port+': '+r.processes.length+' '+_t('pk_processes')+'</div>';
    h += '<div style="max-height:200px;overflow:auto">';
    r.processes.forEach(function(p, i) {
      h += '<label style="display:flex;align-items:center;gap:6px;padding:3px 4px;border-radius:3px;cursor:pointer;font-size:11px">'
        + '<input type="checkbox" onchange="pkToggle('+i+',this.checked)" style="width:14px;height:14px">'
        + '<span style="font-weight:700;min-width:50px">PID '+p.PID+'</span>'
        + '<span style="color:var(--dm)">'+esc(p.USER||'')+'</span>'
        + '<span style="color:var(--tx)">'+esc(p.COMMAND||'')+'</span></label>';
    });
    h += '</div>';
    g('pkO').innerHTML = h;
  } else if (r.killed !== undefined) {
    var msg = '\u2713 Killed '+(r.killed||0)+'/'+(r.attempted||0)+' process'+(r.attempted>1?'es':'');
    // Show detailed errors if any
    if (r.details && r.details.length) {
      var failed = r.details.filter(function(d){return !d.killed;});
      if (failed.length) {
        msg += '<br><br><div style="color:#e74c3c;font-size:11px">Failed PIDs:</div>';
        failed.forEach(function(d){
          msg += '<div style="font-size:10px;color:var(--dm);padding:2px 0">PID '+d.PID+': '+(d.error?esc(d.error):'Operation not permitted')+'</div>';
        });
        msg += '<div style="margin-top:6px"><button class="btn" onclick="pkRetryFailures()" style="font-size:10px">Retry failed</button></div>';
        window._pkFailedDetails = failed;
      }
    }
    g('pkO').innerHTML = msg;
    pkPids = []; pkSel = []; pkKilling = false;
  } else {
    g('pkO').innerHTML = _t('pk_no_res');
    pkPids = []; pkSel = []; pkKilling = false;
  }
}
function pkRetryFailures() {
  pkKilling = false;
  var failed = window._pkFailedDetails || [];
  if (!failed.length) return;
  var pids = failed.map(function(d){return d.PID;});
  g('pkO').innerHTML = '\u23F3 Retrying '+pids.length+' failed...';
  cPHP('port_kill', {pids: pids, action: 'kill_sel'});
}
function pkToggle(i,chk){if(chk)pkSel.push(pkPids[i]);else{var idx=pkSel.indexOf(pkPids[i]);if(idx>-1)pkSel.splice(idx,1);}}

function handleProcResult(r) {
  if (r.processes && r.processes.length) {
    var h = '<div style="margin-bottom:6px;color:var(--dm);font-size:11px">'+_t('pk_processes')+': '+r.processes.length+'</div>';
    h += '<div style="max-height:200px;overflow:auto">';
    r.processes.forEach(function(p, i) {
      h += '<label style="display:flex;align-items:center;gap:6px;padding:3px 4px;border-radius:3px;cursor:pointer;font-size:11px">'
        + '<input type="checkbox" onchange="procToggle('+i+',this.checked)" style="width:14px;height:14px">'
        + '<span style="font-weight:700;min-width:50px">PID '+p.PID+'</span>'
        + '<span style="color:var(--dm)">'+esc(p.USER||'')+'</span>'
        + '<span style="color:var(--tx)">'+esc(p.COMMAND||'')+'</span></label>';
    });
    h += '</div>';
    g('procO').innerHTML = h;
    procPids = r.processes.map(function(p){return p.PID;});
    procSel = [];
    procKilling = false;
  } else if (r.killed !== undefined) {
    var msg = '\u2713 Killed '+(r.killed||0)+'/'+(r.attempted||0)+' process'+(r.attempted>1?'es':'');
    if (r.details && r.details.length) {
      var failed = r.details.filter(function(d){return !d.killed;});
      if (failed.length) {
        msg += '<br><br><div style="color:#e74c3c;font-size:11px">Failed PIDs:</div>';
        failed.forEach(function(d){
          msg += '<div style="font-size:10px;color:var(--dm);padding:2px 0">PID '+d.PID+': '+(d.error?esc(d.error):'Operation not permitted')+'</div>';
        });
        msg += '<div style="margin-top:6px"><button class="btn" onclick="procRetryFailures()" style="font-size:10px">Retry failed</button></div>';
        window._procFailedDetails = failed;
      }
    }
    g('procO').innerHTML = msg;
    procPids = []; procSel = []; procKilling = false;
  } else {
    g('procO').innerHTML = _t('pk_no_res');
    procPids = []; procSel = []; procKilling = false;
  }
}
function procRetryFailures() {
  procKilling = false;
  var failed = window._procFailedDetails || [];
  if (!failed.length) return;
  var pids = failed.map(function(d){return d.PID;});
  g('procO').innerHTML = '\u23F3 Retrying '+pids.length+' failed...';
  cPHP('process_kill', {pids: pids, action: 'kill_sel'});
}
function procToggle(i,chk){if(chk)procSel.push(procPids[i]);else{var idx=procSel.indexOf(procPids[i]);if(idx>-1)procSel.splice(idx,1);}}

// ── Keycode Info ──
var kcCb = null;
var kcHist = [];
function kcInit(){
  if(kcCb)return;
  // Auto-focus on panel open
  var area = document.getElementById('kcArea');
  if (area) setTimeout(function(){area.focus();},100);
  document.getElementById('kcHint').textContent='';
  document.getElementById('kcKeyDisplay').textContent='⌨';
  kcCb = function(e){
    e.preventDefault();
    var info=g('kcInfo'),hd=g('kcHint'),kd=g('kcKeyDisplay');
    hd.textContent='';info.style.display='';
    var k=e.key.length===1?e.key:'('+e.key+')';
    kd.textContent=k;
    g('kcV_key').textContent=e.key||'';
    g('kcV_code').textContent=e.code||'';
    g('kcV_keyCode').textContent=e.keyCode||'';
    var locs=['','Left','Right','Numpad','Mobile','Joystick'];
    g('kcV_loc').textContent=(e.location<locs.length?locs[e.location]:e.location)||'';
    var mods=[];if(e.shiftKey)mods.push('Shift');if(e.ctrlKey)mods.push('Ctrl');if(e.altKey)mods.push('Alt');if(e.metaKey)mods.push('Meta');
    g('kcV_mod').textContent=mods.join(' + ')||'—';
    g('kcV_type').textContent=e.type;
    kcHist.unshift(e.code+' ('+e.key+')');
    if(kcHist.length>50)kcHist.length=50;
    g('kcHist').textContent=kcHist.join('\n');
  };
  document.addEventListener('keydown',kcCb);
}
function kcStop(){
  if(kcCb){document.removeEventListener('keydown',kcCb);kcCb=null;}
}
function kcClear(){
  kcHist=[];var kh=g('kcHist'); if(kh)kh.textContent='';
  g('kcV_key').textContent='';g('kcV_code').textContent='';g('kcV_keyCode').textContent='';
  g('kcV_loc').textContent='';g('kcV_mod').textContent='';
  var ki=g('kcInfo'); if(ki)ki.style.display='none';
  document.getElementById('kcHint').textContent=_t('kc_hint');
  document.getElementById('kcKeyDisplay').textContent='⌨';
}

// ── Batch-1 text/conversion tool logic ──────────────────
function hashCompute() {
  var text = g('hashText') ? g('hashText').value : '';
  var digest = g('hashDigest') ? g('hashDigest').value : 'Hex';
  cPHP('hash', { text: text, digest: digest });
}

function encRun(action) {
  if (action === 'dec') {
    var din = g('decText') ? g('decText').value : '';
    var dkey = g('decKey') ? g('decKey').value : '';
    var dalgo = g('decAlgo') ? g('decAlgo').value : 'AES';
    cPHP('encrypt', { text: din, key: dkey, algo: dalgo, action: 'dec', _out: 'decOut' });
  } else {
    var ein = g('encText') ? g('encText').value : '';
    var ekey = g('encKey') ? g('encKey').value : '';
    var ealgo = g('encAlgo') ? g('encAlgo').value : 'AES';
    cPHP('encrypt', { text: ein, key: ekey, algo: ealgo, action: 'enc', _out: 'encOut' });
  }
}

function b64Live() {
  var tin = g('b64TextIn'), tout = g('b64TextOut');
  if (tin && tout) {
    var v = tin.value;
    if (g('b64EncSafe') && g('b64EncSafe').checked) {
      tout.value = btoa(unescape(encodeURIComponent(v))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    } else {
      tout.value = btoa(unescape(encodeURIComponent(v)));
    }
  }
  var bin = g('b64B64In'), bout = g('b64B64Out'), err = g('b64Err');
  if (bin && bout) {
    var s = bin.value.trim();
    if (s === '') { bout.value = ''; if (err) err.classList.add('ep-hidden'); return; }
    var safe = g('b64DecSafe') && g('b64DecSafe').checked;
    try {
      var norm = safe ? s.replace(/-/g, '+').replace(/_/g, '/') : s;
      bout.value = decodeURIComponent(escape(atob(norm)));
      if (err) err.classList.add('ep-hidden');
    } catch (e) {
      bout.value = '';
      if (err) err.classList.remove('ep-hidden');
    }
  }
}

function urlLive() {
  var ein = g('urlEncIn'), eout = g('urlEncOut');
  if (ein && eout) {
    try { eout.value = encodeURIComponent(ein.value); } catch (e) { eout.value = ''; }
  }
  var din = g('urlDecIn'), dout = g('urlDecOut');
  if (din && dout) {
    try { dout.value = decodeURIComponent(din.value); } catch (e) { dout.value = din.value; }
  }
}

function htmlLive() {
  var ein = g('htmlEncIn'), eout = g('htmlEncOut');
  if (ein && eout) {
    eout.value = ein.value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  var din = g('htmlDecIn'), dout = g('htmlDecOut');
  if (din && dout) {
    dout.value = din.value
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"')
      .replace(/&#39;/g, "'")
      .replace(/&amp;/g, '&');
  }
}

function urlParseRun() {
  var inp = g('upUrl');
  if (!inp) return;
  var keys = ['protocol', 'username', 'password', 'hostname', 'port', 'pathname', 'search', 'hash'];
  var setF = function (k, val) {
    var el = g('up_' + k);
    if (!el) return;
    var i = el.querySelector('input');
    if (i) i.value = val || '';
  };
  var u, ok = true;
  try { u = new URL(inp.value); } catch (e) { ok = false; }
  if (!ok) {
    keys.forEach(function (k) { setF(k, ''); });
    var q0 = g('upQuery'); if (q0) q0.innerHTML = '';
    return;
  }
  setF('protocol', u.protocol);
  setF('username', u.username);
  setF('password', u.password);
  setF('hostname', u.hostname);
  setF('port', u.port);
  setF('pathname', u.pathname);
  setF('search', u.search);
  var q = g('upQuery');
  if (q) {
    var params = [];
    u.searchParams.forEach(function (v, k) { params.push([k, v]); });
    if (params.length) {
      var rows = '<div class="ep-form-item" style="margin:10px 0 4px"><label style="font-weight:600">' + _t('urlparse_params') + '</label></div>';
      params.forEach(function (p, idx) {
        rows += '<div class="ep-form-item" style="display:flex;align-items:center;gap:8px;margin-bottom:6px">'
          + '<span style="width:28px;flex-shrink:0;color:var(--dm);text-align:center">&#8617;</span>'
          + '<div class="ep-input-group" style="flex:1"><input id="upqk_' + idx + '" class="ep-input" readonly value="' + EP._a(p[0]) + '"><span class="ep-input-group__append"><button class="ep-btn ep-btn--link" onclick="EP.copy(g(\'upqk_' + idx + '\').value)">' + _t('copy') + '</button></span></div>'
          + '<div class="ep-input-group" style="flex:1"><input id="upqv_' + idx + '" class="ep-input" readonly value="' + EP._a(p[1]) + '"><span class="ep-input-group__append"><button class="ep-btn ep-btn--link" onclick="EP.copy(g(\'upqv_' + idx + '\').value)">' + _t('copy') + '</button></span></div>'
          + '</div>';
      });
      q.innerHTML = rows;
    } else {
      q.innerHTML = '';
    }
  }
}

function __init_ts() {
  var nowEl = g('tsNow');
  if (window.__tsTimer) clearInterval(window.__tsTimer);
  function tick() {
    if (nowEl) nowEl.textContent = Math.floor(Date.now() / 1000);
  }
  tick();
  window.__tsTimer = setInterval(tick, 1000);

  var t0 = g('ts0'), d0 = g('tsDate0'), f0 = g('tsFlag0');
  if (t0) t0.oninput = function () {
    var v = parseInt(t0.value, 10);
    if (isNaN(v)) { if (d0) d0.value = ''; return; }
    var ms = (f0 && f0.value == '1') ? v : v * 1000;
    if (d0) d0.value = new Date(ms).toLocaleString();
  };
  var d1 = g('tsDate1'), s1 = g('tsStr1'), f1 = g('tsFlag1');
  if (d1) d1.oninput = function () {
    var t = new Date(d1.value).getTime();
    if (isNaN(t)) { if (s1) s1.value = ''; return; }
    if (s1) s1.value = (f1 && f1.value == '1') ? String(t) : String(Math.floor(t / 1000));
  };
}

function tokenRun() {
  var lenEl = g('tkLen');
  var len = parseInt(lenEl ? lenEl.value : '64', 10) || 64;
  if (len < 1) len = 1; if (len > 512) len = 512;
  var up = g('tkUpper') && g('tkUpper').checked;
  var lo = g('tkLower') && g('tkLower').checked;
  var num = g('tkNum') && g('tkNum').checked;
  var sym = g('tkSym') && g('tkSym').checked;
  var alpha = '';
  if (up) alpha += 'ABCDEFGHIJKLMOPQRSTUVWXYZ';
  if (lo) alpha += 'abcdefghijklmopqrstuvwxyz';
  if (num) alpha += '0123456789';
  if (sym) alpha += ".,;:!?./-\"'#{([-|\\@)]=}*+";
  var token = '';
  if (alpha.length) {
    var pool = alpha.repeat(Math.max(1, Math.ceil(len / alpha.length))).split('');
    for (var i = pool.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = pool[i]; pool[i] = pool[j]; pool[j] = tmp;
    }
    token = pool.slice(0, len).join('');
  }
  var out = g('tkOut');
  if (out) out.value = token;
  var lbl = g('tkLenLabel');
  if (lbl) lbl.textContent = len;
}

// Per-tool init hooks (run on panel open, matching original reactive compute)
window.__init_hash = globalThis.hashCompute;
window.__init_b64 = globalThis.b64Live;
window.__init_url = globalThis.urlLive;
window.__init_html = globalThis.htmlLive;
window.__init_urlparse = globalThis.urlParseRun;
window.__init_token = globalThis.tokenRun;
window.__init_encrypt = function () { globalThis.encRun('enc'); globalThis.encRun('dec'); };
// ── Batch 3: Editor tools (Json / Markdown / Diff / CodePlayground / CodeLibrary) ──

// ── Code Library (1:1 FlyEnv CodeLibrary) ──
var CLIB_KEY = 'phptools2-code-library';
var CLIB_LANGS = ['erlang', 'golang', 'java', 'javascript', 'perl', 'php', 'python', 'ruby', 'rust', 'typescript'];
var CLIB = { langs: [], group: [], items: [], langType: '', groupID: '', itemID: '' };
var CLIB_CHOICE = [];
var CLIB_CHOOSE = false;
var CLIB_SEARCH = '';

function clibUUID() {
  if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
  return 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}
function clibEsc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function clibLoad() {
  try {
    var raw = localStorage.getItem(CLIB_KEY);
    if (raw) {
      var d = JSON.parse(raw);
      CLIB.langs = d.langs || [];
      CLIB.group = d.group || [];
      CLIB.items = d.items || [];
      CLIB.langType = d.langType || '';
      CLIB.groupID = d.groupID || '';
      CLIB.itemID = d.itemID || '';
    }
  } catch (e) {}
  if (!CLIB.langs.length) {
    CLIB.langs = CLIB_LANGS.map(function (l) { return { type: l, show: true }; });
  }
  if (!CLIB.langType) {
    var f = null;
    for (var i = 0; i < CLIB.langs.length; i++) { if (CLIB.langs[i].show) { f = CLIB.langs[i]; break; } }
    CLIB.langType = f ? f.type : CLIB_LANGS[0];
  }
  if (!CLIB.group.length && !CLIB.items.length && !localStorage.getItem(CLIB_KEY)) {
    clibSeed();
  }
}
function clibSave() {
  try { localStorage.setItem(CLIB_KEY, JSON.stringify(CLIB)); } catch (e) {}
}
function clibSeed() {
  var g1 = clibUUID();
  CLIB.group.push({ id: g1, type: 'php', name: 'Database' });
  CLIB.items.push({ id: clibUUID(), groupID: g1, fromType: 'php', name: 'PDO Connect', comment: 'Connect via PDO', value: '<?php\n$pdo = new PDO("mysql:host=localhost;dbname=test", $u, $p);\n$stmt = $pdo->query("SELECT * FROM users");', toValue: '' });
  CLIB.items.push({ id: clibUUID(), groupID: '', fromType: 'php', name: 'cURL GET', comment: '', value: '<?php\n$ch = curl_init($url);\ncurl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\n$resp = curl_exec($ch);\ncurl_close($ch);', toValue: '' });
  CLIB.items.push({ id: clibUUID(), groupID: '', fromType: 'python', name: 'Requests GET', comment: '', value: 'import requests\nr = requests.get("https://api.example.com")\nprint(r.json())', toValue: '' });
}
function clibGroupsOf(lang) {
  return CLIB.group.filter(function (g) { return g.type === lang; });
}
function clibCodesNoGroup(lang) {
  return CLIB.items.filter(function (it) { return it.fromType === lang && !it.groupID; });
}

function clibRenderTabs() {
  var box = g('clibTabs'); if (!box) return;
  box.innerHTML = CLIB.langs.filter(function (l) { return l.show; }).map(function (l) {
    return '<div class="clib-tab' + (l.type === CLIB.langType ? ' active' : '') + '" data-lang="' + l.type + '" onclick="clibTab(\'' + l.type + '\')">' + l.type + '</div>';
  }).join('');
}
function clibRenderSettings() {
  var box = g('clibSet'); if (!box) return;
  box.innerHTML = CLIB.langs.map(function (l) {
    var on = l.show ? ' checked' : '';
    return '<label class="ep-check clib-set__row"><input type="checkbox"' + on + ' onchange="clibToggleLang(\'' + l.type + '\',this.checked)"> ' + l.type + '</label>';
  }).join('');
}
function clibRenderSide() {
  var gl = g('clibGroups');
  if (gl) {
    var groups = clibGroupsOf(CLIB.langType);
    var ic = window.__CLIB_ICONS;
    gl.innerHTML = groups.length ? groups.map(function (grp) {
      var active = (grp.id === CLIB.groupID && !CLIB.itemID) ? ' active' : '';
      return '<div class="clib-row' + active + '" onclick="clibGroupClick(\'' + grp.id + '\')">'
        + '<span class="clib-row__ic">' + (ic ? ic.folder : '') + '</span>'
        + '<span class="clib-row__txt">' + clibEsc(grp.name) + '</span>'
        + '<button class="clib-ico-btn xs" onclick="event.stopPropagation();clibGroupMenu(\'' + grp.id + '\',event)">' + (ic ? ic.more : '⋮') + '</button>'
        + '</div>';
    }).join('') : '<div class="clib-empty-sm">' + _t('clib_none') + '</div>';
  }
  var cl = g('clibCodes');
  if (cl) {
    var codes = clibCodesNoGroup(CLIB.langType);
    var ic2 = window.__CLIB_ICONS;
    if (!codes.length) {
      cl.innerHTML = '<div class="clib-empty-sm">' + _t('clib_empty') + '</div>';
    } else if (CLIB_CHOOSE) {
      cl.innerHTML = codes.map(function (it) {
        var chk = CLIB_CHOICE.indexOf(it.id) >= 0 ? ' checked' : '';
        return '<label class="clib-row clib-row--chk"><input type="checkbox" onclick="clibTogglePick(\'' + it.id + '\')"' + chk + '><span class="clib-row__txt">' + clibEsc(it.name) + '</span></label>';
      }).join('');
    } else {
      cl.innerHTML = codes.map(function (it) {
        var active = it.id === CLIB.itemID ? ' active' : '';
        return '<div class="clib-row' + active + '" onclick="clibCodeClick(\'' + it.id + '\')">'
          + '<span class="clib-row__txt">' + clibEsc(it.name) + '</span>'
          + '<button class="clib-ico-btn xs" onclick="event.stopPropagation();clibCodeMenu(\'' + it.id + '\',event)">' + (ic2 ? ic2.more : '⋮') + '</button>'
          + '</div>';
      }).join('');
    }
  }
  var b = g('clibBatch');
  if (b) b.style.display = CLIB_CHOOSE ? '' : 'none';
}
function clibRenderMain() {
  var m = g('clibMain'); if (!m) return;
  var ic = window.__CLIB_ICONS;
  var item = CLIB.itemID ? CLIB.items.find(function (i) { return i.id === CLIB.itemID; }) : null;
  if (item) {
    var html = '<div class="vp-doc clib-detail">';
    html += '<h3>' + clibEsc(item.name) + '</h3>';
    if (item.comment) html += '<p class="clib-comment">' + clibEsc(item.comment) + '</p>';
    html += '<h4>' + _t('clib_code') + '</h4>';
    html += '<pre class="clib-pre"><code>' + clibEsc(item.value) + '</code></pre>';
    if (item.toValue) { html += '<h4>' + _t('clib_result') + '</h4><pre class="clib-pre"><code>' + clibEsc(item.toValue) + '</code></pre>'; }
    html += '</div>';
    m.innerHTML = html;
    return;
  }
  var grp = CLIB.groupID ? CLIB.group.find(function (x) { return x.id === CLIB.groupID; }) : null;
  if (grp) {
    var items = CLIB.items.filter(function (i) { return i.groupID === grp.id; });
    var s = CLIB_SEARCH.trim();
    if (s) items = items.filter(function (i) { return (i.name || '').indexOf(s) >= 0 || (i.comment || '').indexOf(s) >= 0 || (i.value || '').indexOf(s) >= 0 || (i.toValue || '').indexOf(s) >= 0; });
    var h = '<div class="clib-grp">';
    h += '<div class="clib-grp__h"><span class="clib-grp__name">' + (ic ? ic.folder : '') + ' ' + clibEsc(grp.name) + '</span>'
      + '<div class="clib-grp__act">'
      + '<input class="clib-search" placeholder="' + _t('clib_search') + '" value="' + clibEsc(CLIB_SEARCH) + '" oninput="clibGrpSearch(this.value)">'
      + '<button class="clib-ico-btn sm" title="' + _t('clib_addcode') + '" onclick="clibAddCode(\'' + grp.id + '\')">' + (ic ? ic.plus : '+') + '</button>'
      + '<button class="clib-ico-btn sm" onclick="clibGroupMenu(\'' + grp.id + '\',event)">' + (ic ? ic.more : '⋮') + '</button>'
      + '</div></div>';
    if (!items.length) h += '<div class="clib-empty">' + _t('clib_empty') + '</div>';
    else h += items.map(function (it) {
      var active = it.id === CLIB.itemID ? ' active' : '';
      return '<div class="clib-grp-item' + active + '" onclick="clibCodeClick(\'' + it.id + '\')">'
        + '<div class="clib-grp-item__h"><span class="clib-row__ic">' + (ic ? ic.term : '') + '</span><span class="clib-grp-item__name">' + clibEsc(it.name) + '</span>'
        + '<button class="clib-ico-btn xs" onclick="event.stopPropagation();clibCodeMenu(\'' + it.id + '\',event)">' + (ic ? ic.more : '⋮') + '</button></div>'
        + '<div class="clib-grp-item__sub">' + clibEsc(it.comment || it.value || _t('clib_none')) + '</div>'
        + '</div>';
    }).join('');
    h += '</div>';
    m.innerHTML = h;
    return;
  }
  m.innerHTML = '<div class="clib-empty">' + _t('clib_empty') + '</div>';
}

function clibTab(lang) {
  CLIB.langType = lang;
  CLIB.groupID = '';
  CLIB.itemID = '';
  CLIB_CHOOSE = false; CLIB_CHOICE = []; CLIB_SEARCH = '';
  var f = clibCodesNoGroup(lang)[0];
  if (f) CLIB.itemID = f.id;
  else { var g0 = clibGroupsOf(lang)[0]; if (g0) CLIB.groupID = g0.id; }
  clibRenderTabs(); clibRenderSide(); clibRenderMain(); clibSave();
}
function clibGroupClick(id) {
  CLIB.groupID = id; CLIB.itemID = ''; CLIB_SEARCH = '';
  clibRenderSide(); clibRenderMain(); clibSave();
}
function clibCodeClick(id) {
  if (CLIB_CHOOSE) { clibTogglePick(id); return; }
  CLIB.itemID = id;
  var it = CLIB.items.find(function (i) { return i.id === id; });
  if (it) CLIB.groupID = it.groupID || '';
  clibRenderSide(); clibRenderMain(); clibSave();
}
function clibTogglePick(id) {
  var i = CLIB_CHOICE.indexOf(id);
  if (i >= 0) CLIB_CHOICE.splice(i, 1); else CLIB_CHOICE.push(id);
  clibRenderSide();
}
function clibGrpSearch(v) { CLIB_SEARCH = v; clibRenderMain(); }
function clibToggleSettings(e) {
  if (e && e.stopPropagation) e.stopPropagation();
  var p = g('clibSet');
  if (!p) return;
  if (p.style.display === 'none') { clibRenderSettings(); p.style.display = ''; }
  else p.style.display = 'none';
}
function clibToggleLang(lang, show) {
  var l = null;
  for (var i = 0; i < CLIB.langs.length; i++) { if (CLIB.langs[i].type === lang) { l = CLIB.langs[i]; break; } }
  if (l) l.show = show;
  if (!show && CLIB.langType === lang) {
    var f = null;
    for (var j = 0; j < CLIB.langs.length; j++) { if (CLIB.langs[j].show) { f = CLIB.langs[j]; break; } }
    if (f) clibTab(f.type);
    else { CLIB.langType = ''; clibRenderTabs(); clibRenderSide(); clibRenderMain(); }
  } else {
    clibRenderTabs();
  }
  clibSave();
}

function clibMenu(items, ev) {
  clibCloseMenu();
  var m = document.createElement('div');
  m.className = 'clib-menu';
  m.innerHTML = items.map(function (it) {
    return '<div class="clib-menu__i" onclick="clibCloseMenu();' + it.fn + '">' + (it.ic || '') + '<span>' + clibEsc(it.label) + '</span></div>';
  }).join('');
  document.body.appendChild(m);
  var r = ev.target.getBoundingClientRect();
  m.style.top = (r.bottom + 4) + 'px';
  m.style.left = Math.max(4, r.left - 120) + 'px';
  setTimeout(function () { document.addEventListener('click', clibCloseMenu, { once: true }); }, 0);
  window.__CLIB_MENU = m;
}
function clibCloseMenu() {
  if (window.__CLIB_MENU) { window.__CLIB_MENU.remove(); window.__CLIB_MENU = null; }
}
function clibGroupMenu(id, ev) {
  var ic = window.__CLIB_ICONS;
  clibMenu([
    { label: _t('clib_edit'), ic: ic ? ic.edit : '', fn: 'clibEditGroup(\'' + id + '\')' },
    { label: _t('clib_movetop'), ic: ic ? ic.top : '', fn: 'clibGroupMoveTop(\'' + id + '\')' },
    { label: _t('clib_del'), ic: ic ? ic.del : '', fn: 'clibDelGroup(\'' + id + '\')' }
  ], ev);
}
function clibCodeMenu(id, ev) {
  var ic = window.__CLIB_ICONS;
  clibMenu([
    { label: _t('clib_batch'), ic: ic ? ic.more : '', fn: 'clibBatchStart(\'' + id + '\')' },
    { label: _t('clib_edit'), ic: ic ? ic.edit : '', fn: 'clibEditCode(\'' + id + '\')' },
    { label: _t('clib_movetop'), ic: ic ? ic.top : '', fn: 'clibCodeMoveTop(\'' + id + '\')' },
    { label: _t('clib_movegroup'), ic: ic ? ic.move : '', fn: 'clibMoveToGroup(\'' + id + '\')' },
    { label: _t('clib_del'), ic: ic ? ic.del : '', fn: 'clibDelCode(\'' + id + '\')' }
  ], ev);
}

function clibAddGroup() { clibGroupDialog(null); }
function clibEditGroup(id) {
  var grp = CLIB.group.find(function (x) { return x.id === id; });
  if (grp) clibGroupDialog(grp);
}
function clibGroupDialog(grp) {
  var val = grp ? grp.name : '';
  var body = '<div class="clib-form">'
    + '<label class="clib-form__l">' + _t('clib_group_name') + '</label>'
    + '<input class="clib-input" id="clibGName" value="' + clibEsc(val) + '" placeholder="' + _t('clib_group_name_input') + '">'
    + '</div>';
  clibModal(grp ? _t('clib_edit_title') : _t('clib_addgroup'), body,
    '<button class="ep-btn" onclick="clibCloseModal()">' + _t('clib_cancel') + '</button>'
    + '<button class="ep-btn ep-btn--primary" onclick="clibGroupSave(\'' + (grp ? grp.id : '') + '\')">' + _t('clib_confirm') + '</button>');
  setTimeout(function () { var i = g('clibGName'); if (i) i.focus(); }, 30);
}
function clibGroupSave(id) {
  var v = (g('clibGName').value || '').trim();
  if (!v) { clibToast(_t('clib_name_need')); return; }
  if (id) { var grp = CLIB.group.find(function (x) { return x.id === id; }); if (grp) grp.name = v; }
  else { CLIB.group.unshift({ id: clibUUID(), type: CLIB.langType, name: v }); }
  clibCloseModal(); clibRenderSide(); clibRenderMain(); clibSave();
}
function clibDelGroup(id) {
  clibConfirm(_t('clib_group_del_tip'), function () {
    CLIB.group = CLIB.group.filter(function (x) { return x.id !== id; });
    CLIB.items = CLIB.items.filter(function (x) { return x.groupID !== id; });
    if (CLIB.groupID === id) { var g0 = clibGroupsOf(CLIB.langType)[0]; CLIB.groupID = g0 ? g0.id : ''; CLIB.itemID = ''; }
    clibRenderSide(); clibRenderMain(); clibSave();
  });
}
function clibGroupMoveTop(id) {
  var grp = CLIB.group.find(function (x) { return x.id === id; });
  if (!grp) return;
  CLIB.group = CLIB.group.filter(function (x) { return x.id !== id; });
  CLIB.group.unshift(grp);
  clibRenderSide(); clibSave();
}

function clibAddCode(groupID) { clibCodeDialog(null, groupID); }
function clibEditCode(id) { var it = CLIB.items.find(function (x) { return x.id === id; }); if (it) clibCodeDialog(it, null); }
function fld(label, control) { return '<div class="clib-form__row"><label class="clib-form__l">' + label + '</label>' + control + '</div>'; }
function clibCodeDialog(item, presetGroup) {
  var isEdit = !!item;
  var lang = item ? item.fromType : (presetGroup ? (function () { var gg = CLIB.group.find(function (x) { return x.id === presetGroup; }); return gg ? gg.type : CLIB.langType; })() : CLIB.langType);
  var grpID = item ? item.groupID : (presetGroup || '');
  var groups = clibGroupsOf(lang);
  var langOpts = CLIB_LANGS.map(function (l) { return '<option value="' + l + '"' + (l === lang ? ' selected' : '') + '>' + l + '</option>'; }).join('');
  var grpOpts = '<option value="">' + _t('clib_none') + '</option>' + groups.map(function (gg) { return '<option value="' + gg.id + '"' + (gg.id === grpID ? ' selected' : '') + '>' + clibEsc(gg.name) + '</option>'; }).join('');
  var body = '<div class="clib-form">'
    + fld(_t('clib_name'), '<input class="clib-input" id="clibFName" value="' + clibEsc(item ? item.name : '') + '">')
    + fld(_t('clib_comment'), '<textarea class="clib-input" id="clibFComment" rows="2">' + clibEsc(item ? item.comment : '') + '</textarea>')
    + fld(_t('clib_lang'), '<select class="clib-input" id="clibFLang" onchange="clibFDynGroup()">' + langOpts + '</select>')
    + fld(_t('clib_group'), '<select class="clib-input" id="clibFGroup">' + grpOpts + '</select>')
    + fld(_t('clib_code'), '<textarea class="clib-input mono" id="clibFCode" rows="6">' + clibEsc(item ? item.value : '') + '</textarea>')
    + fld(_t('clib_result'), '<textarea class="clib-input mono" id="clibFResult" rows="4">' + clibEsc(item ? item.toValue : '') + '</textarea>')
    + '</div>';
  clibModal(isEdit ? _t('clib_edit_title') : _t('clib_add_title'), body,
    '<button class="ep-btn" onclick="clibCloseModal()">' + _t('clib_cancel') + '</button>'
    + '<button class="ep-btn ep-btn--primary" onclick="clibCodeSave(\'' + (item ? item.id : '') + '\')">' + _t('clib_confirm') + '</button>');
  setTimeout(function () { var i = g('clibFName'); if (i) i.focus(); }, 30);
}
function clibFDynGroup() {
  var lang = g('clibFLang').value;
  var groups = clibGroupsOf(lang);
  g('clibFGroup').innerHTML = '<option value="">' + _t('clib_none') + '</option>' + groups.map(function (gg) { return '<option value="' + gg.id + '">' + clibEsc(gg.name) + '</option>'; }).join('');
}
function clibCodeSave(id) {
  var name = (g('clibFName').value || '').trim();
  var lang = g('clibFLang').value;
  if (!name) { clibToast(_t('clib_name_need')); return; }
  if (!lang) { clibToast(_t('clib_lang_need')); return; }
  var data = {
    name: name,
    comment: g('clibFComment').value,
    fromType: lang,
    groupID: g('clibFGroup').value,
    value: g('clibFCode').value,
    toValue: g('clibFResult').value
  };
  if (id) { var it = CLIB.items.find(function (x) { return x.id === id; }); if (it) Object.assign(it, data); }
  else { data.id = clibUUID(); CLIB.items.unshift(data); }
  var selID = data.id;
  clibCloseModal();
  CLIB.langType = lang;
  CLIB.itemID = selID;
  CLIB.groupID = data.groupID;
  clibRenderTabs(); clibRenderSide(); clibRenderMain(); clibSave();
}
function clibDelCode(id) {
  clibConfirm(_t('clib_del_tip'), function () {
    CLIB.items = CLIB.items.filter(function (x) { return x.id !== id; });
    if (CLIB.itemID === id) CLIB.itemID = '';
    clibRenderSide(); clibRenderMain(); clibSave();
  });
}
function clibCodeMoveTop(id) {
  var it = CLIB.items.find(function (x) { return x.id === id; });
  if (!it) return;
  CLIB.items = CLIB.items.filter(function (x) { return x.id !== id; });
  CLIB.items.unshift(it);
  clibRenderSide(); clibSave();
}
function clibMoveToGroup(id) { clibMoveGroupDialog([id]); }
function clibBatchStart(id) { CLIB_CHOOSE = true; CLIB_CHOICE = [id]; clibRenderSide(); }
function clibBatchMove() { if (!CLIB_CHOICE.length) return; clibMoveGroupDialog(CLIB_CHOICE.slice()); }
function clibMoveGroupDialog(ids) {
  var groups = clibGroupsOf(CLIB.langType);
  var opts = '<option value="">' + _t('clib_none') + '</option>' + groups.map(function (gg) { return '<option value="' + gg.id + '">' + clibEsc(gg.name) + '</option>'; }).join('');
  var body = '<div class="clib-form"><label class="clib-form__l">' + _t('clib_group') + '</label><select class="clib-input" id="clibMoveG">' + opts + '</select></div>';
  clibModal(_t('clib_movegroup'), body,
    '<button class="ep-btn" onclick="clibCloseModal()">' + _t('clib_cancel') + '</button>'
    + '<button class="ep-btn ep-btn--primary" onclick="clibMoveGroupApply()">' + _t('clib_confirm') + '</button>');
  window.__CLIB_MOVE_IDS = ids;
}
function clibMoveGroupApply() {
  var gid = g('clibMoveG').value;
  var ids = window.__CLIB_MOVE_IDS || [];
  ids.forEach(function (id) { var it = CLIB.items.find(function (x) { return x.id === id; }); if (it) it.groupID = gid; });
  clibCloseModal();
  CLIB_CHOOSE = false; CLIB_CHOICE = [];
  clibRenderSide(); clibRenderMain(); clibSave();
}
function clibBatchDel() {
  if (!CLIB_CHOICE.length) return;
  clibConfirm(_t('clib_del_tip'), function () {
    CLIB.items = CLIB.items.filter(function (x) { return CLIB_CHOICE.indexOf(x.id) < 0; });
    CLIB_CHOICE = []; CLIB_CHOOSE = false; CLIB.itemID = '';
    clibRenderSide(); clibRenderMain(); clibSave();
  });
}
function clibExitBatch() { CLIB_CHOOSE = false; CLIB_CHOICE = []; clibRenderSide(); }
function clibSelAll(on) {
  var codes = clibCodesNoGroup(CLIB.langType);
  CLIB_CHOICE = on ? codes.map(function (c) { return c.id; }) : [];
  clibRenderSide();
}

function clibModal(title, body, footer) {
  clibCloseModal();
  var ov = document.createElement('div');
  ov.className = 'clib-modal-ov';
  ov.id = 'clibModalOv';
  ov.innerHTML = '<div class="clib-modal">'
    + '<div class="clib-modal__h"><span>' + clibEsc(title) + '</span><button class="clib-ico-btn" onclick="clibCloseModal()">✕</button></div>'
    + '<div class="clib-modal__b">' + body + '</div>'
    + '<div class="clib-modal__f">' + footer + '</div>'
    + '</div>';
  ov.addEventListener('click', function (e) { if (e.target === ov) clibCloseModal(); });
  document.body.appendChild(ov);
}
function clibCloseModal() { var o = g('clibModalOv'); if (o) o.remove(); }
function clibConfirm(msg, ok) {
  clibModal(_t('clib_confirm'), '<p class="clib-confirm">' + clibEsc(msg) + '</p>',
    '<button class="ep-btn" onclick="clibCloseModal()">' + _t('clib_cancel') + '</button>'
    + '<button class="ep-btn ep-btn--danger" id="clibConfirmOk">' + _t('clib_del') + '</button>');
  var b = g('clibConfirmOk');
  if (b) b.onclick = function () { clibCloseModal(); ok(); };
}
function clibToast(msg) {
  var t = document.createElement('div');
  t.className = 'clib-toast';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(function () { t.remove(); }, 1800);
}

// ── JSON ──
var jsonLastSort = null;
var jsonParsed = null;

function phpToJson(s) {
  var t = s.replace(/<\?php/g, '').replace(/<\?/g, '').replace(/return\s*/i, '').replace(/;\s*$/, '').trim();
  t = t.replace(/=>/g, ':').replace(/\[/g, '{').replace(/\]/g, '}');
  t = t.replace(/([{,]\s*)([A-Za-z_][\w]*)\s*:/g, '$1"$2":');
  return JSON.parse(t);
}
function yamlScalarParse(v) {
  if (v === '') return '';
  if (v === 'true') return true;
  if (v === 'false') return false;
  if (/^\d+$/.test(v)) return parseInt(v, 10);
  if (/^\d*\.\d+$/.test(v)) return parseFloat(v);
  if (/^["'].*["']$/.test(v)) return v.slice(1, -1);
  return v;
}
function yamlToJson(s) {
  var obj = {}; var ok = false;
  s.split('\n').forEach(function (line) {
    var m = line.match(/^([A-Za-z_][\w]*):\s*(.*)$/);
    if (m) { obj[m[1]] = yamlScalarParse(m[2]); ok = true; }
  });
  return ok ? obj : null;
}
function xmlToJson(s) {
  var obj = {}; var ok = false;
  var re = /<([A-Za-z_][\w]*)>([^<]*)<\/\1>/g, m;
  while ((m = re.exec(s))) { obj[m[1]] = isNaN(m[2]) ? m[2] : +m[2]; ok = true; }
  return ok ? obj : null;
}
function detectUrlObj(s) {
  try { var u = new URL(s); return { Protocol: u.protocol, Hostname: u.hostname, Path: u.pathname }; }
  catch (e) { return null; }
}
function jsonSortObj(obj, mode) {
  if (Array.isArray(obj)) return obj.slice().sort(mode === 'asc' ? undefined : function (a, b) { return JSON.stringify(b).localeCompare(JSON.stringify(a)); });
  if (obj && typeof obj === 'object') {
    var keys = Object.keys(obj).sort(mode === 'asc' ? undefined : function (a, b) { return b.localeCompare(a); });
    var r = {};
    keys.forEach(function (k) { r[k] = obj[k]; });
    return r;
  }
  return obj;
}
function jsonToJS(obj) {
  return JSON.stringify(obj, null, 4).replace(/"([A-Za-z_$][\w$]*)"\s*:/g, '$1:');
}
function phpVal(v, indent) {
  if (v === null || v === undefined) return 'null';
  if (typeof v === 'boolean') return v ? 'true' : 'false';
  if (typeof v === 'number') return '' + v;
  if (typeof v === 'string') return "'" + v.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
  if (Array.isArray(v)) {
    if (!v.length) return '[]';
    var pad = '  '.repeat(indent + 1);
    return '[\n' + v.map(function (it) { return pad + phpVal(it, indent + 1); }).join(',\n') + '\n' + '  '.repeat(indent) + ']';
  }
  if (typeof v === 'object') {
    var keys = Object.keys(v);
    if (!keys.length) return '[]';
    var p2 = '  '.repeat(indent + 1);
    return '[\n' + keys.map(function (k) { return p2 + "'" + k + "' => " + phpVal(v[k], indent + 1); }).join(',\n') + '\n' + '  '.repeat(indent) + ']';
  }
  return 'null';
}
function jsonToPHP(obj) { return '<?php\n\nreturn ' + phpVal(obj, 0) + ';\n'; }
function yamlScalar(v) {
  if (v === null || v === undefined) return 'null';
  if (typeof v === 'number' || typeof v === 'boolean') return '' + v;
  var st = '' + v;
  if (st === '' || /[:#\-?\[\]{}&*!|>'"%@`,]/.test(st) || /^\s|\s$/.test(st)) return JSON.stringify(st);
  return st;
}
function yamlKey(k) { return /^[A-Za-z0-9_]+$/.test(k) ? k : JSON.stringify(k); }
function jsonToYAML(v, indent) {
  indent = indent || 0;
  var pad = '  '.repeat(indent);
  if (Array.isArray(v)) {
    if (!v.length) return '[]';
    return v.map(function (it) {
      if (it && typeof it === 'object') return pad + '- ' + jsonToYAML(it, indent + 1).replace(/^/gm, '').replace(/^-\s*/, '');
      return pad + '- ' + yamlScalar(it);
    }).join('\n');
  }
  if (v && typeof v === 'object') {
    var keys = Object.keys(v);
    if (!keys.length) return '{}';
    return keys.map(function (k) {
      var val = v[k];
      if (val && typeof val === 'object') return pad + yamlKey(k) + ':\n' + jsonToYAML(val, indent + 1);
      return pad + yamlKey(k) + ': ' + yamlScalar(val);
    }).join('\n');
  }
  return pad + yamlScalar(v);
}
function jsonToXML(obj) {
  function node(name, val) {
    if (Array.isArray(val)) return val.map(function (it) { return node(name, it); }).join('');
    if (val && typeof val === 'object') return '<' + name + '>' + Object.keys(val).map(function (k) { return node(k, val[k]); }).join('') + '</' + name + '>';
    return '<' + name + '>' + (val == null ? '' : esc('' + val)) + '</' + name + '>';
  }
  var body = Object.keys(obj).map(function (k) { return node(k, obj[k]); }).join('');
  return '<?xml version="1.0"?>\n<root>' + body + '</root>';
}
function plistVal(v) {
  if (v === null || v === undefined) return '<string></string>';
  if (typeof v === 'boolean') return v ? '<true/>' : '<false/>';
  if (typeof v === 'number') return (Number.isInteger(v) ? '<integer>' : '<real>') + v + (Number.isInteger(v) ? '</integer>' : '</real>');
  if (typeof v === 'string') return '<string>' + esc(v) + '</string>';
  if (Array.isArray(v)) return '<array>' + v.map(plistVal).join('') + '</array>';
  if (typeof v === 'object') return '<dict>' + Object.keys(v).map(function (k) { return '<key>' + esc(k) + '</key>' + plistVal(v[k]); }).join('') + '</dict>';
  return '<string></string>';
}
function jsonToPList(obj) {
  return '<?xml version="1.0" encoding="UTF-8"?>\n<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">\n<plist version="1.0">\n<dict>' + Object.keys(obj).map(function (k) { return '<key>' + esc(k) + '</key>' + plistVal(obj[k]); }).join('') + '</dict>\n</plist>';
}
function jsonToTOML(obj) {
  function tv(v) {
    if (v === null) return '""';
    if (typeof v === 'boolean') return v ? 'true' : 'false';
    if (typeof v === 'number') return '' + v;
    if (typeof v === 'string') return JSON.stringify(v);
    return JSON.stringify(v);
  }
  var lines = [];
  Object.keys(obj).forEach(function (k) {
    var v = obj[k];
    if (Array.isArray(v)) lines.push(k + ' = ' + JSON.stringify(v));
    else if (v && typeof v === 'object') { lines.push('[' + k + ']'); Object.keys(v).forEach(function (k2) { lines.push(k2 + ' = ' + tv(v[k2])); }); }
    else lines.push(k + ' = ' + tv(v));
  });
  return lines.join('\n');
}
function inferType(v) {
  if (v === null || v === undefined) return 'mixed';
  if (Array.isArray(v)) return 'array';
  if (typeof v === 'boolean') return 'bool';
  if (typeof v === 'number') return Number.isInteger(v) ? 'int' : 'float';
  if (typeof v === 'string') return 'string';
  if (typeof v === 'object') return 'object';
  return 'mixed';
}
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function jsonToStruct(obj, lang, bson) {
  var keys = Object.keys(obj);
  function line(k, v) {
    var t = inferType(v);
    if (lang === 'go') {
      var gt = { bool: 'bool', int: 'int', float: 'float64', string: 'string', array: '[]interface{}', object: 'map[string]interface{}', mixed: 'interface{}' }[t] || 'interface{}';
      var tag = bson ? '`bson:"' + k + '"`' : '`json:"' + k + '"`';
      return '  ' + cap(k) + ' ' + gt + ' ' + tag;
    }
    if (lang === 'ts') { var tt = { bool: 'boolean', int: 'number', float: 'number', string: 'string', array: 'any[]', object: 'any', mixed: 'any' }[t] || 'any'; return '  ' + k + ': ' + tt + ';'; }
    if (lang === 'rust') { var rt = { bool: 'bool', int: 'i64', float: 'f64', string: 'String', array: 'Vec<Value>', object: 'Value', mixed: 'Value' }[t] || 'Value'; return '  ' + k + ': ' + rt + ','; }
    if (lang === 'java') { var jt = { bool: 'boolean', int: 'int', float: 'double', string: 'String', array: 'List<Object>', object: 'Map<String,Object>', mixed: 'Object' }[t] || 'Object'; return '  private ' + jt + ' ' + k + ';'; }
    if (lang === 'kotlin') { var kt = { bool: 'Boolean', int: 'Int', float: 'Double', string: 'String', array: 'List<Any>', object: 'Map<String,Any>', mixed: 'Any' }[t] || 'Any'; return '  val ' + k + ': ' + kt + ','; }
    if (lang === 'mysql') { var mt = { bool: 'TINYINT(1)', int: 'INT', float: 'DOUBLE', string: 'VARCHAR(255)', array: 'TEXT', object: 'JSON', mixed: 'TEXT' }[t] || 'TEXT'; return '  `' + k + '` ' + mt + ' NOT NULL,'; }
    return '  ' + k + ': ' + t + ';';
  }
  var body = keys.map(line).join('\n');
  if (lang === 'go') return 'type Root struct {\n' + body + '\n}';
  if (lang === 'ts') return 'export interface Root {\n' + body + '\n}';
  if (lang === 'rust') return '#[derive(Serialize, Deserialize)]\nstruct Root {\n' + body + '\n}';
  if (lang === 'java') return 'public class Root {\n' + body + '\n}';
  if (lang === 'kotlin') return 'data class Root(\n' + body + '\n)';
  if (lang === 'mysql') return 'CREATE TABLE root (\n' + body.replace(/,$/, '') + '\n);';
  return body;
}
function jsonToJSDoc(obj) {
  var lines = ['/**', ' * @typedef {Object} Root'];
  Object.keys(obj).forEach(function (k) { lines.push(' * @property {' + inferType(obj[k]) + '} ' + k); });
  lines.push(' */');
  return lines.join('\n');
}
function jsonTransform(obj, fmt) {
  if (fmt === 'json') return JSON.stringify(obj, null, 4);
  if (fmt === 'json-minify') return JSON.stringify(obj);
  if (fmt === 'js') return jsonToJS(obj);
  if (fmt === 'php') return jsonToPHP(obj);
  if (fmt === 'yaml') return jsonToYAML(obj);
  if (fmt === 'xml') return jsonToXML(obj);
  if (fmt === 'plist') return jsonToPList(obj);
  if (fmt === 'toml') return jsonToTOML(obj);
  if (fmt === 'ts') return jsonToStruct(obj, 'ts');
  if (fmt === 'goStruct' || fmt === 'goBson') return jsonToStruct(obj, 'go', fmt === 'goBson');
  if (fmt === 'rustSerde') return jsonToStruct(obj, 'rust');
  if (fmt === 'Java') return jsonToStruct(obj, 'java');
  if (fmt === 'Kotlin') return jsonToStruct(obj, 'kotlin');
  if (fmt === 'MySQL') return jsonToStruct(obj, 'mysql');
  if (fmt === 'JSDoc') return jsonToJSDoc(obj);
  return JSON.stringify(obj, null, 4);
}
function jsonApplySort() {
  var fmt = (g('jsonFmt') && g('jsonFmt').value) || 'json';
  var obj = jsonParsed;
  if (obj && jsonLastSort) obj = jsonSortObj(obj, jsonLastSort);
  var outEl = g('jsonOut');
  if (outEl) outEl.value = obj ? jsonTransform(obj, fmt) : '';
}
function jsonInput() {
  var inEl = g('jsonIn'), outEl = g('jsonOut'), typeEl = g('jsonType');
  if (!inEl) return;
  if (!JSON_TABS[JSON_CUR]) JSON_TABS[JSON_CUR] = { value: '' };
  JSON_TABS[JSON_CUR].value = inEl.value;
  var raw = inEl.value;
  if (!raw.trim()) { if (outEl) outEl.value = ''; if (typeEl) typeEl.textContent = ''; jsonParsed = null; return; }
  var parsed = null, type = '';
  try { parsed = JSON.parse(raw); type = 'JSON'; } catch (e) {}
  if (parsed === null) { try { parsed = phpToJson(raw); type = 'PHP'; } catch (e) {} }
  if (parsed === null) { try { var y = yamlToJson(raw); if (y != null) { parsed = y; type = 'YAML'; } } catch (e) {} }
  if (parsed === null) { try { parsed = xmlToJson(raw); type = 'XML'; } catch (e) {} }
  if (parsed === null) { parsed = detectUrlObj(raw); type = parsed ? 'JSON' : 'fail'; }
  if (parsed === null || type === 'fail') {
    if (outEl) outEl.value = _t('json_parse_fail');
    if (typeEl) typeEl.textContent = '';
    jsonParsed = null;
    return;
  }
  jsonParsed = parsed;
  if (typeEl) typeEl.textContent = '(' + type + ')';
  jsonApplySort();
}
function jsonSort(mode) { jsonLastSort = mode; jsonApplySort(); }

// ── JSON tabs (1:1 with FlyEnv Json tool) ──
var JSON_TABS = { 'tab-1': { value: '' } };
var JSON_CUR = 'tab-1';
function jsonSwitchTab(name) {
  if (!JSON_TABS[name]) return;
  var inEl = g('jsonIn'), outEl = g('jsonOut');
  if (inEl) JSON_TABS[JSON_CUR].value = inEl.value;
  if (outEl) JSON_TABS[JSON_CUR].out = outEl.value;
  JSON_CUR = name;
  document.querySelectorAll('#jsonTabs .tb-tab').forEach(function (t) {
    t.classList.toggle('active', t.getAttribute('data-tab') === name);
  });
  if (inEl) inEl.value = JSON_TABS[name].value || '';
  if (outEl) outEl.value = JSON_TABS[name].out || '';
  jsonInput();
}
function jsonAddTab() {
  var n = Object.keys(JSON_TABS).length + 1;
  var name = 'tab-' + n;
  JSON_TABS[name] = { value: '' };
  var tabs = g('jsonTabs');
  if (tabs) {
    var btn = document.createElement('div');
    btn.className = 'tb-tab';
    btn.setAttribute('data-tab', name);
    btn.innerHTML = name + '<span class="tb-tab__close" onclick="event.stopPropagation();jsonCloseTab(\'' + name + '\')">×</span>';
    btn.onclick = function () { jsonSwitchTab(name); };
    var add = tabs.querySelector('.tb-tab-add');
    if (add) tabs.insertBefore(btn, add); else tabs.appendChild(btn);
  }
  jsonSwitchTab(name);
}
function jsonCloseTab(name) {
  if (name === 'tab-1') return;
  delete JSON_TABS[name];
  var t = document.querySelector('#jsonTabs .tb-tab[data-tab="' + name + '"]');
  if (t) t.remove();
  if (JSON_CUR === name) jsonSwitchTab('tab-1');
}
function jsonOpenFile() { var f = g('jsonFile'); if (f) f.click(); }
function jsonOnFile(e) {
  var file = e.target.files && e.target.files[0];
  if (!file) return;
  var r = new FileReader();
  r.onload = function () {
    var inEl = g('jsonIn');
    if (inEl) { inEl.value = r.result; JSON_TABS[JSON_CUR].value = r.result; jsonInput(); }
  };
  r.readAsText(file);
}
function jsonSplitDown(e) { splitMove(e, 'jsonLeft', 'jsonRight'); }
function jsonSave() {
  var out = g('jsonOut');
  if (!out || !out.value) return;
  var blob = new Blob([out.value], { type: 'text/plain' });
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'json-output.txt';
  a.click();
  URL.revokeObjectURL(a.href);
}

// ── Shared splitter drag ──
function splitMove(e, leftId, rightId) {
  e.preventDefault();
  var left = g(leftId), right = g(rightId);
  if (!left || !right) return;
  var main = left.parentNode;
  var rect = main.getBoundingClientRect();
  function mm(ev) {
    var w = ev.clientX - rect.left;
    w = Math.max(140, Math.min(rect.width - 140, w));
    left.style.flex = '0 0 ' + w + 'px';
    right.style.flex = '1 1 0';
  }
  function mu() { document.removeEventListener('mousemove', mm); document.removeEventListener('mouseup', mu); }
  document.addEventListener('mousemove', mm);
  document.addEventListener('mouseup', mu);
}

// ── Markdown ──
var MD_TABS = { 'tab-1': { md: '', html: '' } };
var MD_CUR = 'tab-1';
function md2html(s) {
  var h = s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  h = h.replace(/^### (.+)$/gm, '<h3>$1</h3>')
        .replace(/^## (.+)$/gm, '<h2>$1</h2>')
        .replace(/^# (.+)$/gm, '<h1>$1</h1>')
        .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
        .replace(/\*(.+?)\*/g, '<i>$1</i>')
        .replace(/`([^`]+?)`/g, '<code>$1</code>')
        .replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>')
        .replace(/^- (.+)$/gm, '<li>$1</li>')
        .replace(/(<li>.*(\n|$))+/g, '<ul>$&</ul>')
        .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>')
        .replace(/\[(.+?)\]\((.*?)\)/g, '<a href="$2">$1</a>')
        .replace(/\n\n+/g, '<p>').replace(/\n/g, '<br>');
  return h;
}
function mdRender() {
  var el = g('mdIn'); if (!el) return;
  var html = md2html(el.value);
  var out = g('mdOut'); if (out) out.innerHTML = html;
  MD_TABS[MD_CUR] = { md: el.value, html: html };
  try { localStorage.setItem('FlyEnv-MD-tabs', JSON.stringify({ cur: MD_CUR, tabs: MD_TABS })); } catch (e) {}
}
function mdSwitchTab(name) {
  if (!MD_TABS[name]) return;
  var el = g('mdIn');
  if (el) { MD_TABS[MD_CUR].md = el.value; MD_TABS[MD_CUR].html = g('mdOut') ? g('mdOut').innerHTML : ''; }
  MD_CUR = name;
  document.querySelectorAll('#mdTabs .tb-tab').forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-tab') === name); });
  if (el) el.value = MD_TABS[name].md || '';
  var out = g('mdOut'); if (out) out.innerHTML = MD_TABS[name].html || '';
}
function mdAddTab() {
  var n = Object.keys(MD_TABS).length + 1; var name = 'tab-' + n;
  MD_TABS[name] = { md: '', html: '' };
  var tabs = g('mdTabs'); if (!tabs) return;
  var add = tabs.querySelector('.tb-tab-add');
  var d = document.createElement('div'); d.className = 'tb-tab active'; d.setAttribute('data-tab', name);
  d.innerHTML = name + '<span class="tb-tab__close" onclick="event.stopPropagation();mdCloseTab(\'' + name + '\')">×</span>';
  d.setAttribute('onclick', "mdSwitchTab('" + name + "')");
  tabs.insertBefore(d, add);
  document.querySelectorAll('#mdTabs .tb-tab').forEach(function (t) { t.classList.remove('active'); });
  d.classList.add('active');
  MD_CUR = name;
  var el = g('mdIn'); if (el) el.value = '';
  var out = g('mdOut'); if (out) out.innerHTML = '';
}
function mdCloseTab(name) {
  if (name === 'tab-1') return;
  var keys = Object.keys(MD_TABS);
  if (keys.length <= 1) return;
  delete MD_TABS[name];
  var prev = keys[Math.max(0, keys.indexOf(name) - 1)] || 'tab-1';
  var tabEl = document.querySelector('#mdTabs .tb-tab[data-tab="' + name + '"]');
  if (tabEl) tabEl.remove();
  mdSwitchTab(prev);
}
function mdOpenFile() { var f = g('mdFile'); if (f) f.click(); }
function mdOnFile(e) {
  var file = e.target.files && e.target.files[0]; if (!file) return;
  var r = new FileReader();
  r.onload = function () { var el = g('mdIn'); if (el) { el.value = r.result; mdRender(); } };
  r.readAsText(file);
}
function mdSplitDown(e) { splitMove(e, 'mdLeft', 'mdRight'); }

// ── Diff Compare ──
var diffSampleO = 'FlyEnv is an all-in-one local development environment.\nIt supports PHP, Node.js, databases and more.\nJWT and Cron tools are available.';
var diffSampleC = 'FlyEnv is an all-in-one local development environment.\nIt supports PHP, Node.js, databases, Redis and more.\nJWT, Cron and Diff tools are available.';
var DIFF = { rows: [], stats: { added: 0, removed: 0, changed: 0, unchanged: 0 }, targets: [], active: -1 };

function diffSplit(v) { return v.replace(/\r\n/g, '\n').split('\n'); }
function diffPushSeg(segs, value, changed) {
  var last = segs[segs.length - 1];
  if (last && last.changed === changed) { last.value += value; return; }
  segs.push({ value: value, changed: changed });
}
function diffInlineSegs(oldV, newV) {
  var oc = Array.from(oldV), nc = Array.from(newV), M = oc.length, N = nc.length;
  var matrix = [];
  for (var a = 0; a <= M; a++) matrix.push(new Array(N + 1).fill(0));
  for (var a = M - 1; a >= 0; a--) for (var b = N - 1; b >= 0; b--) {
    matrix[a][b] = oc[a] === nc[b] ? matrix[a + 1][b + 1] + 1 : Math.max(matrix[a + 1][b], matrix[a][b + 1]);
  }
  var oSegs = [], nSegs = [], i = 0, j = 0;
  while (i < M && j < N) {
    if (oc[i] === nc[j]) { diffPushSeg(oSegs, oc[i], false); diffPushSeg(nSegs, nc[j], false); i++; j++; }
    else if (matrix[i + 1][j] >= matrix[i][j + 1]) { diffPushSeg(oSegs, oc[i], true); i++; }
    else { diffPushSeg(nSegs, nc[j], true); j++; }
  }
  while (i < M) { diffPushSeg(oSegs, oc[i], true); i++; }
  while (j < N) { diffPushSeg(nSegs, nc[j], true); j++; }
  return { oldSegments: oSegs, newSegments: nSegs };
}
function diffLineDiff(ol, cl) {
  var m = ol.length, n = cl.length, dp = [];
  for (var a = 0; a <= m; a++) dp.push(new Array(n + 1).fill(0));
  for (var a = m - 1; a >= 0; a--) for (var b = n - 1; b >= 0; b--) dp[a][b] = ol[a] === cl[b] ? dp[a + 1][b + 1] + 1 : Math.max(dp[a + 1][b], dp[a][b + 1]);
  var raw = [], i = 0, j = 0;
  while (i < m && j < n) {
    if (ol[i] === cl[j]) { raw.push({ type: 'same', oldLine: i + 1, newLine: j + 1, value: ol[i] }); i++; j++; }
    else if (dp[i + 1][j] >= dp[i][j + 1]) { raw.push({ type: 'removed', oldLine: i + 1, newLine: '', value: ol[i] }); i++; }
    else { raw.push({ type: 'added', oldLine: '', newLine: j + 1, value: cl[j] }); j++; }
  }
  while (i < m) { raw.push({ type: 'removed', oldLine: i + 1, newLine: '', value: ol[i] }); i++; }
  while (j < n) { raw.push({ type: 'added', oldLine: '', newLine: j + 1, value: cl[j] }); j++; }
  return raw;
}
function diffPair(lines) {
  var res = [], idx = 0;
  while (idx < lines.length) {
    var cur = lines[idx];
    if (cur.type !== 'removed') { res.push(cur); idx++; continue; }
    var rem = [];
    while (idx < lines.length && lines[idx].type === 'removed') { rem.push(lines[idx]); idx++; }
    var add = [];
    while (idx < lines.length && lines[idx].type === 'added') { add.push(lines[idx]); idx++; }
    if (add.length === 0) { for (var p = 0; p < rem.length; p++) res.push(rem[p]); continue; }
    var pair = Math.min(rem.length, add.length);
    for (var k = 0; k < pair; k++) {
      var sg = diffInlineSegs(rem[k].value, add[k].value);
      res.push({ type: 'removed', oldLine: rem[k].oldLine, newLine: '', value: rem[k].value, segments: sg.oldSegments });
      res.push({ type: 'added', oldLine: '', newLine: add[k].newLine, value: add[k].value, segments: sg.newSegments });
    }
    for (var k = pair; k < rem.length; k++) res.push(rem[k]);
    for (var k = pair; k < add.length; k++) res.push(add[k]);
  }
  return res;
}
function diffCompute(o, c) {
  var ol = diffSplit(o), cl = diffSplit(c);
  var rows = diffPair(diffLineDiff(ol, cl));
  var added = 0, removed = 0, unchanged = 0, targets = [];
  for (var r = 0; r < rows.length; r++) {
    var t = rows[r].type;
    if (t === 'added') added++;
    else if (t === 'removed') removed++;
    else unchanged++;
    if (t === 'added' || t === 'removed') targets.push(r);
  }
  return { rows: rows, stats: { added: added, removed: removed, changed: Math.max(added, removed), unchanged: unchanged }, targets: targets };
}
function diffRenderSegs(segs, cls) {
  if (!segs || !segs.length) return '';
  return segs.map(function (s) {
    return s.changed ? '<span class="' + cls + '">' + esc(s.value) + '</span>' : esc(s.value);
  }).join('');
}
function diffRun() {
  var o = g('diffO'), c = g('diffC');
  if (!o || !c) return;
  var res = diffCompute(o.value, c.value);
  DIFF.rows = res.rows; DIFF.stats = res.stats; DIFF.targets = res.targets;
  if (DIFF.active >= res.targets.length) DIFF.active = res.targets.length - 1;
  var st = g('diffStats');
  if (st) st.innerHTML =
    EP.tag(_t('diff_added') + ': ' + res.stats.added, 'success') + ' ' +
    EP.tag(_t('diff_removed') + ': ' + res.stats.removed, 'danger') + ' ' +
    EP.tag(_t('diff_changed') + ': ' + res.stats.changed, 'warning') + ' ' +
    EP.tag(_t('diff_unchanged') + ': ' + res.stats.unchanged, 'info');
  var oo = g('diffOutO'), cc = g('diffOutC');
  if (oo && cc) {
    var hO = '', hC = '';
    for (var r = 0; r < res.rows.length; r++) {
      var row = res.rows[r], active = (DIFF.targets[DIFF.active] === r);
      if (row.type === 'same') {
        hO += '<div class="dl same">' + esc(row.value) + '</div>';
        hC += '<div class="dl same">' + esc(row.value) + '</div>';
      } else if (row.type === 'removed') {
        hO += '<div class="dl removed' + (active ? ' active' : '') + '">' + diffRenderSegs(row.segments, 'diff-inline-removed') + '</div>';
        hC += '<div class="dl placeholder"></div>';
      } else {
        hO += '<div class="dl placeholder"></div>';
        hC += '<div class="dl added' + (active ? ' active' : '') + '">' + diffRenderSegs(row.segments, 'diff-inline-added') + '</div>';
      }
    }
    oo.innerHTML = hO; cc.innerHTML = hC;
  }
  var prev = document.querySelector('[onclick="diffPrev()"]');
  var next = document.querySelector('[onclick="diffNext()"]');
  var none = res.targets.length === 0;
  if (prev) prev.disabled = none;
  if (next) next.disabled = none;
}
function diffGo(dir) {
  if (!DIFF.targets.length) return;
  DIFF.active = DIFF.active === -1
    ? (dir > 0 ? 0 : DIFF.targets.length - 1)
    : (DIFF.active + dir + DIFF.targets.length) % DIFF.targets.length;
  diffRun();
  var act = document.querySelector('#diffOutO .dl.active, #diffOutC .dl.active');
  if (act && act.scrollIntoView) act.scrollIntoView({ block: 'center' });
}
function diffPrev() { diffGo(-1); }
function diffNext() { diffGo(1); }
function diffSample() { var o = g('diffO'), c = g('diffC'); if (o) o.value = diffSampleO; if (c) c.value = diffSampleC; DIFF.active = -1; diffRun(); }
function diffSwap() { var o = g('diffO'), c = g('diffC'); if (o && c) { var t = o.value; o.value = c.value; c.value = t; DIFF.active = -1; diffRun(); } }
function diffClear() { var o = g('diffO'), c = g('diffC'); if (o) o.value = ''; if (c) c.value = ''; DIFF.active = -1; diffRun(); }
function diffCopy() {
  var o = g('diffO'), c = g('diffC');
  if (!o || !c) return;
  if (o.value.length === 0 && c.value.length === 0) { EP.toast(_t('diff_empty'), 'warning'); return; }
  var res = diffCompute(o.value, c.value);
  var out = res.rows.map(function (row) {
    if (row.type === 'added') return '+ ' + row.value;
    if (row.type === 'removed') return '- ' + row.value;
    return '  ' + row.value;
  }).join('\n');
  EP.copy(out);
}
// ── Code Playground ──
var CODE_TABS = { 'tab-1': { lang: 'php', bin: '', value: "<?php echo 'Hello FlyEnv';", to: 'raw', toValue: '' } };
var CODE_CUR = 'tab-1';
function codeOnLang() { var s = CODE_TABS[CODE_CUR]; var el = g('codeLang'); if (s && el) s.lang = el.value; }
function codeRun() {
  var s = CODE_TABS[CODE_CUR]; if (!s) return;
  var el = g('codeIn'); if (el) s.value = el.value;
  var code = el ? el.value : '';
  cPHP('code_run', { code: code, lang: s.lang });
}
function codeTransform() {
  var s = CODE_TABS[CODE_CUR]; if (!s) return;
  var toEl = g('codeTo'); var toFmt = toEl ? toEl.value : 'raw';
  var el = g('codeIn'); var src = el ? el.value : '';
  var out = g('cO3'); if (!out) return;
  if (toFmt === 'raw') { out.value = src; s.toValue = src; return; }
  var parsed = null;
  try { parsed = JSON.parse(src); } catch (e1) {
    try { parsed = phpToJson(src); } catch (e2) {
      try { var y = yamlToJson(src); if (y != null) parsed = y; } catch (e3) {
        try { parsed = xmlToJson(src); } catch (e4) { parsed = null; }
      }
    }
  }
  if (parsed == null) { out.value = '无法转换：仅支持 JSON / PHP / YAML / XML 数据'; return; }
  out.value = jsonTransform(parsed, toFmt);
  s.toValue = out.value;
}
function codeSwitchTab(name) {
  if (!CODE_TABS[name]) return;
  var s = CODE_TABS[name];
  var inEl = g('codeIn'); if (inEl) { CODE_TABS[CODE_CUR].value = inEl.value; }
  var outEl = g('cO3'); if (outEl) { CODE_TABS[CODE_CUR].toValue = outEl.value; }
  CODE_CUR = name;
  document.querySelectorAll('#codeTabs .tb-tab').forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-tab') === name); });
  if (inEl) inEl.value = s.value || '';
  if (g('codeLang')) g('codeLang').value = s.lang || 'php';
  if (g('codeTo')) g('codeTo').value = s.to || 'raw';
  if (outEl) outEl.value = s.toValue || '';
}
function codeAddTab() {
  var n = Object.keys(CODE_TABS).length + 1; var name = 'tab-' + n;
  CODE_TABS[name] = { lang: 'php', bin: '', value: '', to: 'raw', toValue: '' };
  var tabs = g('codeTabs'); if (!tabs) return;
  var add = tabs.querySelector('.tb-tab-add');
  var d = document.createElement('div'); d.className = 'tb-tab active'; d.setAttribute('data-tab', name);
  d.innerHTML = name + '<span class="tb-tab__close" onclick="event.stopPropagation();codeCloseTab(\'' + name + '\')">×</span>';
  d.setAttribute('onclick', "codeSwitchTab('" + name + "')");
  tabs.insertBefore(d, add);
  document.querySelectorAll('#codeTabs .tb-tab').forEach(function (t) { t.classList.remove('active'); });
  d.classList.add('active');
  CODE_CUR = name;
  var inEl = g('codeIn'); if (inEl) inEl.value = '';
  var outEl = g('cO3'); if (outEl) outEl.value = '';
}
function codeCloseTab(name) {
  if (name === 'tab-1') return;
  var keys = Object.keys(CODE_TABS);
  if (keys.length <= 1) return;
  delete CODE_TABS[name];
  var prev = keys[Math.max(0, keys.indexOf(name) - 1)] || 'tab-1';
  var tabEl = document.querySelector('#codeTabs .tb-tab[data-tab="' + name + '"]');
  if (tabEl) tabEl.remove();
  codeSwitchTab(prev);
}
function codeOpenFile() { var f = g('codeFile'); if (f) f.click(); }
function codeOnFile(e) {
  var file = e.target.files && e.target.files[0]; if (!file) return;
  var r = new FileReader();
  r.onload = function () { var el = g('codeIn'); if (el) { el.value = r.result; CODE_TABS[CODE_CUR].value = r.result; } };
  r.readAsText(file);
}
function codeSave() {
  var outEl = g('cO3'); var txt = outEl ? outEl.value : '';
  var blob = new Blob([txt], { type: 'text/plain' });
  var a = document.createElement('a'); a.href = URL.createObjectURL(blob);
  a.download = 'flyenv-code-' + CODE_CUR + '.txt'; a.click();
  setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
}
function codeSplitDown(e) { splitMove(e, 'codeLeft', 'codeRight'); }


// ── Batch 4: Backend-bound tools (QR / Wifi / Image / Capture / RSA / File / Timing / Site / SSL / Obf / BOM / Port / Process) ──

var QR_T = null;
function qrGen() {
  if (QR_T) clearTimeout(QR_T);
  QR_T = setTimeout(function () {
    var t = g('qI') ? g('qI').value : '';
    var ecc = g('qE') ? g('qE').value : 'medium';
    var fg = g('qFg') ? g('qFg').value : '#000000';
    var bg = g('qBg') ? g('qBg').value : '#ffffff';
    cPHP('qr', { text: t, ecc: ecc, fg: fg, bg: bg, _out: 'qrOut' });
  }, 30);
}
function qrLive() { qrGen(); }
function qrDownload() {
  var box = g('qrOut');
  if (!box) return;
  var svg = box.querySelector('svg');
  if (!svg) { if (window.EP && EP.toast) EP.toast(_t('qr_data')); else alert(_t('qr_data')); return; }
  var s = new XMLSerializer().serializeToString(svg);
  var blob = new Blob([s], { type: 'image/svg+xml' });
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'qr-code.svg';
  document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(a.href);
}

function wifiEscape(s) {
  return String(s == null ? '' : s).replace(/([\\;,:"])/g, '\\$1');
}
function wifiToggle() {
  var enc = g('wEnc') ? g('wEnc').value : 'WPA';
  var pb = g('wifiPwdBox'); if (pb) pb.style.display = enc === 'nopass' ? 'none' : '';
  var eb = g('wifiEapBox'); if (eb) eb.style.display = enc === 'WPA2-EAP' ? '' : 'none';
  wifiGen();
}
function wifiGen() {
  var enc = g('wEnc') ? g('wEnc').value : 'WPA';
  var ssid = g('wSSID') ? g('wSSID').value : '';
  var hidden = (g('wHidden') && g('wHidden').checked) ? 'H:true;' : '';
  var t = '';
  if (ssid) {
    if (enc === 'nopass') {
      t = 'WIFI:S:' + wifiEscape(ssid) + ';;';
    } else if (enc !== 'WPA2-EAP') {
      var pass = g('wPass') ? g('wPass').value : '';
      t = 'WIFI:S:' + wifiEscape(ssid) + ';T:' + enc + ';P:' + wifiEscape(pass) + ';' + hidden;
    } else {
      var pass2 = g('wPass') ? g('wPass').value : '';
      var eap = g('wEap') ? g('wEap').value : '';
      var anon = g('wEapAnon') && g('wEapAnon').checked;
      var id = g('wEapId') ? g('wEapId').value : '';
      var p2 = g('wEapP2') ? g('wEapP2').value : 'None';
      if (id || anon) {
        var identity = anon ? 'A:anon' : 'I:' + wifiEscape(id);
        var phase2 = p2 !== 'None' ? 'PH2:' + p2 + ';' : '';
        t = 'WIFI:S:' + wifiEscape(ssid) + ';T:WPA2-EAP;P:' + wifiEscape(pass2) + ';E:' + eap + ';' + phase2 + identity + ';' + hidden;
      }
    }
  }
  var fg = g('wFg') ? g('wFg').value : '#000000';
  var bg = g('wBg') ? g('wBg').value : '#ffffff';
  cPHP('qr', { text: t, ecc: 0, fg: fg, bg: bg, _out: 'wO2' });
}
function wifiDownload() {
  var box = g('wO2');
  if (!box) return;
  var svg = box.querySelector('svg');
  if (!svg) { if (window.EP && EP.toast) EP.toast(_t('wifi_ssid')); else alert(_t('wifi_ssid')); return; }
  var s = new XMLSerializer().serializeToString(svg);
  var blob = new Blob([s], { type: 'image/svg+xml' });
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'wifi-qr.svg';
  document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(a.href);
}

var IMG_CFG = { quality: 70, maxWidth: 1920, format: 'jpeg', brightness: 0, contrast: 0, blur: 0, wmText: '', wmPos: 'center', wmOp: 50, tex: 'none' };
var IMG_FILES = [];
var IMG_RESULTS = {};
function imgCfg() {
  IMG_CFG.quality = g('iQ') ? (+g('iQ').value) : (g('iQ2') ? (+g('iQ2').value) : 70);
  IMG_CFG.maxWidth = g('iW') ? (+g('iW').value) : (g('iW2') ? (+g('iW2').value) : 1920);
  IMG_CFG.format = g('iFmt') ? g('iFmt').value : 'jpeg';
  IMG_CFG.brightness = g('iBright') ? (+g('iBright').value) : 0;
  IMG_CFG.contrast = g('iContrast') ? (+g('iContrast').value) : 0;
  IMG_CFG.blur = g('iBlur') ? (+g('iBlur').value) : 0;
  IMG_CFG.wmText = g('iWmText') ? g('iWmText').value : '';
  IMG_CFG.wmPos = g('iWmPos') ? g('iWmPos').value : 'center';
  IMG_CFG.wmOp = g('iWmOp') ? (+g('iWmOp').value) : 50;
  IMG_CFG.tex = g('iTex') ? g('iTex').value : 'none';
}
function imgSwitch(name) {
  ['batch', 'basic', 'compress', 'effects', 'watermark', 'texture'].forEach(function (n) {
    var id = 'imgPane' + n.charAt(0).toUpperCase() + n.slice(1);
    var p = g(id); if (p) p.style.display = n === name ? '' : 'none';
  });
  var tabs = document.querySelectorAll('#imgTabs .img-tab');
  for (var i = 0; i < tabs.length; i++) {
    tabs[i].classList.toggle('active', tabs[i].getAttribute('data-tab') === name);
  }
}
function imgAddClick() { var f = g('imgFile'); if (f) f.click(); }
function imgOnFiles(e) {
  var files = e.target.files || [];
  for (var i = 0; i < files.length; i++) IMG_FILES.push(files[i]);
  imgRenderList();
}
function imgRenderList() {
  var el = g('imgList'); if (!el) return;
  if (!IMG_FILES.length) { el.innerHTML = '<div class="muted">' + _t('img_no_files') + '</div>'; return; }
  var h = '<table class="ep-table"><thead><tr><td>' + _t('img_files') + '</td><td>KB</td><td></td></tr></thead><tbody>';
  IMG_FILES.forEach(function (f, i) {
    h += '<tr><td>' + esc(f.name) + '</td><td>' + (f.size / 1024).toFixed(1) + '</td><td id="imgRes_' + i + '"></td></tr>';
  });
  h += '</tbody></table>';
  el.innerHTML = h;
}
function imgCompressFiles() {
  if (!IMG_FILES.length) { imgRenderList(); return; }
  imgCfg();
  IMG_FILES.forEach(function (f, i) {
    var reader = new FileReader();
    reader.onload = function () {
      cPHP('image_b64', { data: reader.result, quality: IMG_CFG.quality, maxWidth: IMG_CFG.maxWidth, _out: 'imgRes_' + i });
    };
    reader.readAsDataURL(f);
  });
}
function imgBasicCompress() {
  imgCfg();
  var p = g('iI') ? g('iI').value : '';
  cPHP('image_c', { path: p, quality: IMG_CFG.quality, maxWidth: IMG_CFG.maxWidth });
}
function imgCompress() { imgBasicCompress(); }
function imgDownload(i) {
  var d = IMG_RESULTS[i];
  if (!d) return;
  var a = document.createElement('a');
  a.href = d; a.download = 'compressed_' + i + '.jpg';
  document.body.appendChild(a); a.click(); a.remove();
}

// ── Capturer ──
var CAP_CFG = { key: [], dir: '', name: 'fly_ss_{datetime}' };
var capKeyRec = false;
function capLoad() {
  try { var s = localStorage.getItem('cap_cfg'); if (s) CAP_CFG = Object.assign(CAP_CFG, JSON.parse(s)); } catch (e) {}
}
function capSave() {
  try { localStorage.setItem('cap_cfg', JSON.stringify(CAP_CFG)); } catch (e) {}
  var o = g('capO'); if (o) o.innerHTML = EP.alert(_t('cap_saved_cfg'), 'success');
}
function capCfg() {
  CAP_CFG.dir = g('capDir') ? g('capDir').value : '';
  CAP_CFG.name = g('capName') ? g('capName').value : 'fly_ss_{datetime}';
}
function capAddRule(rule) {
  var el = g('capName'); if (!el) return;
  var s = el.selectionStart, e = el.selectionEnd, v = el.value;
  el.value = v.substring(0, s) + rule + v.substring(e);
  el.focus(); el.setSelectionRange(s + rule.length, s + rule.length);
  CAP_CFG.name = el.value;
}
function capChooseDir() { var f = g('capDirInput'); if (f) f.click(); }
function capOnDir(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { CAP_CFG.dir = f.path; var d = g('capDir'); if (d) d.value = f.path; }
}
function capKeyEnter() { capKeyRec = true; }
function capKeyLeave() { capKeyRec = false; }
function capRenderKey() {
  var el = g('capKey'); if (!el) return;
  el.textContent = CAP_CFG.key.length ? CAP_CFG.key.join(' + ') : _t('cap_none');
}
function capClearKey() { CAP_CFG.key = []; capRenderKey(); }
function capDo(hide) { capCfg(); cPHP('capture', { type: 'select', dir: CAP_CFG.dir, name: CAP_CFG.name, hide: hide ? 1 : 0 }); }
if (typeof document !== 'undefined') {
  document.addEventListener('keydown', function (e) {
    if (!capKeyRec) return;
    e.stopPropagation(); e.preventDefault();
    var a = [];
    if (e.metaKey) a.push('Meta');
    if (e.shiftKey) a.push('Shift');
    if (e.ctrlKey) a.push('Control');
    if (e.altKey) a.push('Alt');
    if (e.key && a.indexOf(e.key) < 0) a.push(e.key);
    CAP_CFG.key = a; capRenderKey();
  });
}
window.__init_capture = function () { capLoad(); capRenderKey(); };
window.__init_file = function () { globalThis.setupFileDrop(); };

function rsaGen() {
  var el = g('rB');
  var b = el ? (+el.value) : 2048;
  var lbl = g('rBVal'); if (lbl) lbl.textContent = b;
  cPHP('rsa', { bits: b });
}

/* ── File Info (1:1 with FlyEnv Tools/FileInfo) ── */
function fiPick() { var i = g('fiInput'); if (i) i.click(); }

function fiOnFile(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) fileInfoPath(f.path);
}

function fileInfoPath(path) {
  if (!path) return;
  cPHP('file_info', { path: path });
}

function setupFileDrop() {
  var dz = g('fiDroper');
  if (!dz) return;
  dz.addEventListener('dragover', function (e) {
    e.preventDefault(); e.stopPropagation(); dz.classList.add('is-drag');
  });
  dz.addEventListener('dragleave', function (e) {
    e.preventDefault(); e.stopPropagation(); dz.classList.remove('is-drag');
  });
  dz.addEventListener('drop', function (e) {
    e.preventDefault(); e.stopPropagation(); dz.classList.remove('is-drag');
    var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
    if (f && f.path) fileInfoPath(f.path);
  });
}

function fiPair(label, str, raw) {
  var s = str == null ? '—' : esc(String(str));
  var v = raw == null ? '—' : esc(String(raw));
  return '<div class="fi-item"><div class="fi-label">' + esc(label) + '</div><div class="fi-value">' + s + '</div></div>'
    + '<div class="fi-item"><div class="fi-label"></div><div class="fi-value fi-value--mono">' + v + '</div></div>';
}

function fiHash(label, val, isLong) {
  var v = val == null ? '—' : esc(String(val));
  return '<div class="fi-item fi-item--full' + (isLong ? ' fi-item--long' : '') + '">'
    + '<div class="fi-label">' + esc(label) + '</div>'
    + '<div class="fi-value fi-value--mono">' + v + '</div></div>';
}

function renderFileInfo(r) {
  if (!r) return '';
  var html = '<div class="fi-desc">';
  html += '<div class="fi-item fi-item--full fi-path"><div class="fi-label">path</div>'
    + '<div class="fi-value fi-value--mono">' + esc(r.path || '') + '</div></div>';
  html += fiPair('file size', r.size_str, r.size);
  html += fiPair('create time', r.btime_str, r.btime);
  html += fiPair('change time', r.ctime_str, r.ctime);
  html += fiPair('access time', r.atime_str, r.atime);
  html += fiPair('modify time', r.mtime_str, r.mtime);
  html += fiHash('MD5', r.md5);
  html += fiHash('SHA-1', r.sha1);
  html += fiHash('SHA-256', r.sha256);
  html += fiHash('SHA-512', r.sha512, true);
  html += fiHash('SHA-512-Base64', r.sha512Base64);
  html += '</div>';
  return html;
}

function reqTime() {
  var u = g('tI2') ? g('tI2').value : '';
  var to = g('tO2');
  if (to) to.innerHTML = '<div class="tm-loading">' + _t('timing_testing') + '</div>';
  cPHP('url_timing', { url: u });
}

function tmFmtSize(b) {
  b = parseFloat(b);
  if (isNaN(b) || b < 0) return '-';
  if (b < 1024) return b + ' B';
  if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
  return (b / 1024 / 1024).toFixed(2) + ' MB';
}

function tmRender(r) {
  var rows = [
    [_t('timing_dns'), (r.dns != null ? r.dns + ' ms' : '-')],
    [_t('timing_tcp'), (r.tcp != null ? r.tcp + ' ms' : '-')],
    [_t('timing_ssl'), (r.ssl != null ? r.ssl + ' ms' : '-')],
    [_t('timing_ttfb'), (r.ttfb != null ? r.ttfb + ' ms' : '-')],
    [_t('timing_down'), (r.download != null ? r.download + ' ms' : '-')],
    [_t('timing_speed'), (r.speed != null ? r.speed + ' KB/s' : '-')],
    [_t('timing_size'), (r.size != null ? tmFmtSize(r.size) : '-')],
    [_t('timing_version'), (r.version || '-')]
  ];
  var html = '<table class="tm-tbl"><thead><tr><th>' + esc(_t('timing_metric')) + '</th><th>' + esc(_t('timing_value')) + '</th></tr></thead><tbody>';
  for (var i = 0; i < rows.length; i++) {
    html += '<tr><td>' + esc(rows[i][0]) + '</td><td class="tm-val">' + esc(rows[i][1]) + '</td></tr>';
  }
  html += '</tbody></table>';
  return html;
}

function siteSuck() {
  var u = g('sI') ? g('sI').value : '';
  cPHP('site_suck', { url: u });
}

function sslMake() {
  var domains = g('sslDomains') ? g('sslDomains').value : '';
  var caPath = g('sslCaPath') ? g('sslCaPath').value : '';
  var savePath = g('sslSavePath') ? g('sslSavePath').value : '';
  cPHP('ssl_make', { domains: domains, ca_path: caPath, save_path: savePath });
}

function sslOnCaFile(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { var el = g('sslCaPath'); if (el) el.value = f.path; }
}
function sslOnSaveDir(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { var el = g('sslSavePath'); if (el) el.value = f.path; }
}

// ── PHP Obfuscator (yakpro-po, 1:1 with FlyEnv) ──
var OBF = { phpBin: "", src: "", srcType: "", desc: "", config: "", running: false };

function obPhpChange() {
  var sel = g("obPhp");
  OBF.phpBin = sel ? sel.value : "";
  obUpdateBtn();
}

function obPickSrcFile() { var i = g("obSrcFileInput"); if (i) i.click(); }
function obPickSrcDir() { var i = g("obSrcDirInput"); if (i) i.click(); }
function obSrcFileOn(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { OBF.src = f.path; OBF.srcType = "file"; var el = g("obSrc"); if (el) el.value = f.path; obClearDesc(); }
}
function obSrcDirOn(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { OBF.src = f.path; OBF.srcType = "dir"; var el = g("obSrc"); if (el) el.value = f.path; obClearDesc(); }
}
function obClearDesc() {
  OBF.desc = "";
  var el = g("obDesc"); if (el) el.value = "";
  obUpdateBtn();
}

function obPickDescFile() { if (!OBF.srcType) { EP.toast(_t("obf_valid_src"), "warning"); return; } var i = g("obDescFileInput"); if (i) i.click(); }
function obPickDescDir() { if (!OBF.srcType) { EP.toast(_t("obf_valid_src"), "warning"); return; } var i = g("obDescDirInput"); if (i) i.click(); }
function obDescFileOn(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { OBF.desc = f.path; var el = g("obDesc"); if (el) el.value = f.path; obUpdateBtn(); }
}
function obDescDirOn(e) {
  var f = (e.target.files || [])[0];
  if (f && f.path) { OBF.desc = f.path; var el = g("obDesc"); if (el) el.value = f.path; obUpdateBtn(); }
}
function obDescInput() {
  var el = g("obDesc");
  OBF.desc = el ? el.value.trim() : "";
  obUpdateBtn();
}

function obUpdateBtn() {
  var b = g("obGenBtn");
  if (!b) return;
  var ready = !OBF.running && !!OBF.phpBin && !!OBF.src && !!OBF.desc
    && OBF.desc !== OBF.src && OBF.desc.indexOf(OBF.src + "/") !== 0;
  b.disabled = !ready;
  b.textContent = OBF.running ? (_t("obf_generate") + "…") : _t("obf_generate");
}

function obRun() {
  if (OBF.running) return;
  if (!OBF.phpBin) { EP.toast(_t("obf_no_php"), "warning"); return; }
  if (!OBF.src) { EP.toast(_t("obf_valid_src"), "warning"); return; }
  if (!OBF.desc || OBF.desc === OBF.src || OBF.desc.indexOf(OBF.src + "/") === 0) {
    EP.toast(_t("obf_valid_desc"), "warning"); return;
  }
  OBF.running = true;
  obUpdateBtn();
  var res = g("obResult");
  if (res) res.innerHTML = '<div class="ep-alert ep-alert--info">⏳ ' + _t("obf_running") + "</div>";
  cPHP("php_obf", { phpBin: OBF.phpBin, src: OBF.src, desc: OBF.desc, config: OBF.config });
}

function obHandleResult(r) {
  OBF.running = false;
  obUpdateBtn();
  var res = g("obResult");
  if (!res) return;
  if (r && r.code === 0) {
    res.innerHTML = EP.alert(_t("obf_success") + " — " + _t("obf_open_out") + ": <span class=\"mono\">" + esc(r.desc || "") + "</span>", "success");
  } else {
    var msg = (r && r.error) ? r.error : "error";
    res.innerHTML = EP.alert(_t("obf_fail") + "<br><pre class=\"obf-log\">" + esc(msg) + "</pre>", "danger");
  }
}

// ── Reusable code editor (CodeMirror 5) + custom minimap ──────────────
var __editors = {};
function createCodeEditor(divId, canvasId, mode) {
  if (__editors[divId]) return __editors[divId];
  var el = g(divId);
  if (!el || typeof CodeMirror === "undefined") return null;
  var cm = CodeMirror(el, {
    value: "",
    mode: mode || "shell",
    lineNumbers: true,
    lineWrapping: false,
    tabSize: 4,
    indentUnit: 4,
    theme: "default",
    viewportMargin: Infinity,
    extraKeys: { Tab: function (c) { c.replaceSelection("    ", "end"); } }
  });
  if (canvasId) attachMinimap(cm, g(canvasId), el);
  __editors[divId] = cm;
  return cm;
}

function attachMinimap(cm, canvas, editorEl) {
  if (!canvas || !canvas.getContext) return;
  var ctx = null;
  try { ctx = canvas.getContext("2d"); } catch (e) { ctx = null; }
  if (!ctx) return;
  function sizeCanvas() {
    canvas.width = canvas.clientWidth || 92;
    canvas.height = canvas.clientHeight || (editorEl ? editorEl.clientHeight : 200);
  }
  function draw() {
    sizeCanvas();
    var w = canvas.width, h = canvas.height;
    ctx.clearRect(0, 0, w, h);
    var text = cm.getValue() || "";
    var lines = text.split("\n");
    var total = lines.length;
    if (!total) return;
    var lh = h / total;
    if (lh > 3) lh = 3;
    var maxLen = 0;
    for (var i = 0; i < lines.length; i++) if (lines[i].length > maxLen) maxLen = lines[i].length;
    ctx.fillStyle = "rgba(212,212,212,0.22)";
    for (var i = 0; i < lines.length; i++) {
      var len = lines[i].length;
      if (!len) continue;
      var y = (h / total) * i;
      var bw = maxLen ? (len / maxLen) * (w - 4) : 0;
      ctx.fillRect(2, y, Math.max(1, bw), Math.max(1, lh - 0.5));
    }
    if (cm.getScrollInfo) {
      var info = cm.getScrollInfo();
      if (info.height > info.clientHeight) {
        var ratio = info.top / (info.height - info.clientHeight);
        var vpH = (info.clientHeight / info.height) * h;
        var vy = ratio * (h - vpH);
        ctx.fillStyle = "rgba(255,255,255,0.20)";
        ctx.fillRect(0, vy, w, vpH);
      }
    }
  }
  cm.on("changes", draw);
  cm.on("viewportChange", draw);
  cm.on("scroll", draw);
  cm.on("refresh", draw);
  if (window.addEventListener) window.addEventListener("resize", draw);
  function jump(ev) {
    var rect = canvas.getBoundingClientRect();
    var ry = (ev.clientY - rect.top) / rect.height;
    if (cm.getScrollInfo) {
      var info = cm.getScrollInfo();
      var target = ry * (info.height - info.clientHeight);
      cm.scrollTo(null, Math.max(0, target));
    }
  }
  canvas.addEventListener("mousedown", function (ev) {
    jump(ev);
    function move(e) { jump(e); }
    function up() {
      window.removeEventListener("mousemove", move);
      window.removeEventListener("mouseup", up);
    }
    window.addEventListener("mousemove", move);
    window.addEventListener("mouseup", up);
  });
  setTimeout(draw, 30);
}

// Config drawer
function obConfigOpen() {

  var m = g("obCnfModal"); if (m) m.style.display = "block";
  OBF.cm = createCodeEditor("obCnf", "obMini", "shell");
  if (OBF.cm) { OBF.cm.setValue(OBF.config || ""); OBF.cm.refresh(); if (OBF.cm.clearHistory) OBF.cm.clearHistory(); }
}
function obConfigClose() {
  var m = g("obCnfModal"); if (m) m.style.display = "none";
  var menu = g("obCnfMenu"); if (menu) menu.style.display = "none";
}
function obConfigConfirm() {
  if (OBF.cm) OBF.config = OBF.cm.getValue();
  obConfigClose();
  EP.toast(_t("obf_confirm"));
}
function obConfigToggleMenu() {
  var m = g("obCnfMenu"); if (!m) return;
  m.style.display = m.style.display === "none" ? "block" : "none";
}
function obCnfImport() {
  var i = g("obCnfInput"); if (i) i.click();
  var m = g("obCnfMenu"); if (m) m.style.display = "none";
}
function obCnfImportOn(e) {
  var f = (e.target.files || [])[0];
  if (!f) return;
  var rd = new FileReader();
  rd.onload = function () {
    OBF.config = String(rd.result || "");
    if (OBF.cm) { OBF.cm.setValue(OBF.config); OBF.cm.refresh(); if (OBF.cm.clearHistory) OBF.cm.clearHistory(); }
  };
  rd.readAsText(f);
}
function obCnfExport() {
  var content = OBF.cm ? OBF.cm.getValue() : OBF.config;
  var blob = new Blob([content], { type: "text/plain" });
  var a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = "php-obfuscator.cnf";
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  var m = g("obCnfMenu"); if (m) m.style.display = "none";
}

function __init_obf() {
  OBF.phpBin = ""; OBF.src = ""; OBF.srcType = ""; OBF.desc = ""; OBF.running = false;
  cPHP("php_obf_versions", {});
  cPHP("php_obf_cnf", {});
  obUpdateBtn();
}
window.__init_obf = globalThis.__init_obf;

// ── System Environment (1:1 with FlyEnv SystenEnv macOS) ──
var ENV = { files: [], current: "", content: "" };

function __init_env() {
  ENV.files = []; ENV.current = ""; ENV.content = "";
  cPHP("php_env_files", {});
}
window.__init_env = globalThis.__init_env;

function envRenderList(list) {
  var box = g("envList");
  if (!box) return;
  if (!list || !list.length) {
    box.innerHTML = '<div class="muted p-2">' + _t("env_no_files") + "</div>";
    return;
  }
  ENV.files = list;
  var h = "";
  list.forEach(function (f) {
    h += '<div class="env-file">'
      + '<span class="env-file__name" title="' + EP._a(f) + '" onclick="envOpen(\'' + EP._a(f) + '\')">' + EP._a(f) + "</span>"
      + '<button class="ep-btn ep-btn--small" onclick="envEdit(\'' + EP._a(f) + '\')">' + _t("env_edit") + "</button>"
      + "</div>";
  });
  box.innerHTML = h;
}

// Open the file location in the OS file manager (best-effort in webview).
function envOpen(f) {
  if (f && window.__openInFolder && typeof window.__openInFolder === "function") {
    window.__openInFolder(f);
    return;
  }
  // Fallback: copy the path and tell the user where it is.
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(f).then(function () {
      EP.toast(_t("env_open") + ": " + f, "info");
    }, function () {
      EP.toast(_t("env_open") + ": " + f, "info");
    });
  } else {
    EP.toast(_t("env_open") + ": " + f, "info");
  }
}

function envEdit(f) {
  ENV.current = f;
  ENV.content = "";
  var modal = g("envEditorModal");
  if (modal) modal.style.display = "block";
  var title = g("envEditorTitle");
  if (title) title.textContent = f;
  ENV.cm = createCodeEditor("envEditor", "envMini", "shell");
  if (ENV.cm) ENV.cm.setValue(_t("env_loading"));
  cPHP("php_env_read", { file: f });
}

function envHandleRead(r) {
  if (!ENV.cm) return;
  if (r && r.error) {
    ENV.cm.setValue("");
    ENV.cm.refresh();
    EP.toast(r.error, "error");
    return;
  }
  ENV.cm.setValue((r && r.content != null) ? r.content : "");
  ENV.cm.refresh();
  if (ENV.cm.clearHistory) ENV.cm.clearHistory();
}

function envSave() {
  if (!ENV.cm) return;
  ENV.content = ENV.cm.getValue();
  cPHP("php_env_save", { file: ENV.current, content: ENV.content });
}

function envHandleSave(r) {
  if (r && r.error) {
    EP.toast(r.error, "error");
    return;
  }
  var modal = g("envEditorModal");
  if (modal) modal.style.display = "none";
  EP.toast(_t("env_success"), "success");
}

function envEditorClose() {
  var modal = g("envEditorModal");
  if (modal) modal.style.display = "none";
}
window.__init_SystemEnv = globalThis.__init_env;   // tool id 'SystemEnv' → panel key 'env'

window.__init_http = globalThis.renderHTTP;
window.__init_mime = function () { globalThis.fMI(); globalThis.fME(); };
window.__init_chmod = globalThis.fCM;
window.__init_cron = function () { globalThis.cronToggleGen(); globalThis.cronGenerate(); };
window.__init_regex = globalThis.rxCompute;
window.__init_jwt = function () { globalThis.jwtEnc(); };
window.__init_json = function () {
  var el = g('jsonIn');
  if (el && !el.value) el.value = '{\n  "name": "FlyEnv",\n  "version": 1,\n  "active": true\n}';
  globalThis.jsonInput();
};
window.__init_md = function () { globalThis.mdRender(); };
window.__init_diff = function () { globalThis.diffSample(); };
window.__init_code = function () { globalThis.codeRun(); };
window.__init_keycode = function () { globalThis.kcInit(); };
window.__init_clib = function () {
  clibLoad();
  var f = clibCodesNoGroup(CLIB.langType)[0];
  if (f) CLIB.itemID = f.id;
  else { var g0 = clibGroupsOf(CLIB.langType)[0]; if (g0) CLIB.groupID = g0.id; }
  clibRenderTabs();
  clibRenderSide();
  clibRenderMain();
  document.addEventListener('click', function (e) {
    var set = g('clibSet');
    if (!set) return;
    if (set.style.display !== 'none') {
      var bar = set.closest('.clib-bar');
      if (bar && !bar.contains(e.target)) set.style.display = 'none';
    }
  });
};
/* openTool() looks up window['__init_' + toolId] where toolId is the original
   ID (e.g. 'CodeLibrary'), NOT the panel key ('clib').  Alias every init that
   was registered under the panel key so the dispatcher can find it. */
window.__init_CodeLibrary = window.__init_clib;
window.__init_Timestamp  = window.__init_ts;
window.__init_MarkdownPreview = window.__init_md;
window.__init_CodePlay   = window.__init_code;
window.__init_diff_compare = window.__init_diff;       // note: no hyphen in func name
window.__init_websocket_sse  = window.__init_wss;      // note: hyphen → underscore
window.__init_keycode_info   = window.__init_keycode;   // note: hyphen → underscore
window.__init_Capturer       = window.__init_capture;
window.__init_bom = function () { globalThis.bomReset(); };
window.__init_qr = globalThis.qrGen;
window.__init_wifi = function () { globalThis.wifiToggle(); globalThis.wifiGen(); };
window.__init_rsa = globalThis.rsaGen;
window.__init_PhpObfuscator = globalThis.__init_obf;   // tool id 'PhpObfuscator' → panel key 'obf'

// ── Init ──
function initApp() {
  g('currentTool').textContent = '';
  g('searchInput').placeholder = _t('search_ph');
  renderHome();
  g('langBtn').textContent = lang === 'zh' ? 'EN' : '中';
}

// ── Port Kill ──
var pkPids = [];
var pkSel = [];
var pkKilling = false;
function pkSearch() {
  var port = g('pkInput').value.trim();
  if (!port) { g('pkO').textContent = _t('pk_no_res'); return; }
  g('pkO').innerHTML = '\u23F3 Searching port '+esc(port)+'...';
  pkPids = []; pkSel = [];
  cPHP('port_kill', {port: port, action: 'search'});
}
function pkKillAll() {
  if (!pkPids.length) { g('pkO').innerHTML = '⚠ '+_t('pk_none'); return; }
  if (pkKilling) return;
  pkKilling = true;
  g('pkO').innerHTML = '\u23F3 Killing '+pkPids.length+' process(es)...<div style="font-size:10px;color:var(--dm);margin-top:4px">PIDs: '+pkPids.join(', ')+'</div>';
  cPHP('port_kill', {pids: pkPids, action: 'kill_all'});
}
function pkKillSel() {
  if (!pkSel.length) { g('pkO').innerHTML = '⚠ '+_t('pk_no_sel'); return; }
  if (pkKilling) return;
  pkKilling = true;
  g('pkO').innerHTML = '\u23F3 Killing '+pkSel.length+' selected...<div style="font-size:10px;color:var(--dm);margin-top:4px">PIDs: '+JSON.stringify(pkSel)+'</div>';
  cPHP('port_kill', {pids: pkSel, action: 'kill_sel'});
}

// ── Process Kill ──
var procPids = [];
var procSel = [];
var procKilling = false;
function procSearch() {
  var key = g('procInput').value.trim();
  if (!key) { g('procO').textContent = _t('pk_no_res'); return; }
  g('procO').innerHTML = '\u23F3 Searching '+esc(key)+'...';
  procPids = []; procSel = [];
  cPHP('process_kill', {keyword: key, action: 'search'});
}
function procKillAll() {
  if (!procPids.length) { g('procO').innerHTML = '⚠ '+_t('pk_none'); return; }
  if (procKilling) return;
  procKilling = true;
  g('procO').innerHTML = '\u23F3 Killing '+procPids.length+' process(es)...<div style="font-size:10px;color:var(--dm);margin-top:4px">PIDs: '+procPids.join(', ')+'</div>';
  cPHP('process_kill', {pids: procPids, action: 'kill_all'});
}
function procKillSel() {
  if (!procSel.length) { g('procO').innerHTML = '⚠ '+_t('pk_no_sel'); return; }
  if (procKilling) return;
  procKilling = true;
  g('procO').innerHTML = '\u23F3 Killing '+procSel.length+' selected...<div style="font-size:10px;color:var(--dm);margin-top:4px">PIDs: '+JSON.stringify(procSel)+'</div>';
  cPHP('process_kill', {pids: procSel, action: 'kill_sel'});
}

/* ════════════════════════════════════════════════════════════════
   Site Sucker — 1:1 with FlyEnv SiteSucker (BFS site downloader)
   ════════════════════════════════════════════════════════════════ */
var SS = {
  taskId: '',
  state: 'stop',
  running: false,
  timer: null,
  dir: '',
  data: { links: [], hosts: [] },
  cfg: { dir: '', proxy: '', timeout: 10, maxImgSize: 0, maxVideoSize: 0, pageLimit: '', excludeLink: '', windowCount: 2 }
};

function ssLoadCfg() {
  try {
    var s = JSON.parse(localStorage.getItem('flyenv_ss_cfg') || '{}');
    Object.assign(SS.cfg, s);
  } catch (e) {}
}
function ssSaveCfg() {
  try { localStorage.setItem('flyenv_ss_cfg', JSON.stringify(SS.cfg)); } catch (e) {}
}

function __init_SiteSucker() {
  window.__init_suck = globalThis.__init_SiteSucker;
  ssLoadCfg();
  ssRenderControls();
  ssRenderLinks();
  ssRenderHosts();
}
window.__init_suck = globalThis.__init_SiteSucker;

function ssCfgFromForm() {
  SS.cfg.dir = (g('ssDir') ? g('ssDir').value : SS.cfg.dir) || '';
  SS.cfg.proxy = (g('ssProxy') ? g('ssProxy').value : '') || '';
  SS.cfg.timeout = parseInt((g('ssTimeout') ? g('ssTimeout').value : '10'), 10) || 10;
  SS.cfg.windowCount = parseInt((g('ssWin') ? g('ssWin').value : '2'), 10) || 2;
  SS.cfg.maxImgSize = parseInt((g('ssMaxImg') ? g('ssMaxImg').value : '0'), 10) || 0;
  SS.cfg.maxVideoSize = parseInt((g('ssMaxVideo') ? g('ssMaxVideo').value : '0'), 10) || 0;
  SS.cfg.pageLimit = (g('ssPageLimit') ? g('ssPageLimit').value : '') || '';
  SS.cfg.excludeLink = (g('ssExclude') ? g('ssExclude').value : '') || '';
  ssSaveCfg();
}

function ssToggle() {
  if (SS.running) ssStop();
  else ssRun();
}

function ssRun() {
  var url = (g('ssUrl') ? g('ssUrl').value : '').trim();
  if (!url) { EP.toast(_t('ss_need_url') || 'Please enter a URL', 'danger'); return; }
  ssCfgFromForm();
  SS.running = true;
  ssRenderControls();
  cPHP('site_suck_start', { url: url, config: SS.cfg });
}

function ssOnStart(r) {
  if (!r || r.error) { SS.running = false; ssRenderControls(); return; }
  SS.taskId = r.taskId;
  SS.dir = r.dir || '';
  SS.state = r.state;
  SS.data = { links: r.links || [], hosts: r.hosts || [] };
  ssRenderControls();
  ssRenderStat(r.counts);
  ssRenderLinks();
  ssRenderHosts();
  if (r.state === 'running') {
    ssScheduleStep();
  } else {
    SS.running = false;
    ssRenderControls();
  }
}

function ssScheduleStep() {
  if (SS.timer) clearTimeout(SS.timer);
  SS.timer = setTimeout(function () {
    if (!SS.running) return;
    cPHP('site_suck_step', { taskId: SS.taskId });
  }, 350);
}

function ssOnStep(r) {
  if (!r || r.error || !SS.running) { SS.running = false; ssRenderControls(); return; }
  SS.data = { links: r.links || [], hosts: r.hosts || [] };
  ssRenderStat(r.counts);
  ssRenderLinks();
  ssRenderHosts();
  if (r.state === 'running' && SS.running) {
    ssScheduleStep();
  } else {
    SS.running = false;
    ssRenderControls();
    if (r.dir) SS.dir = r.dir;
  }
}

function ssStop() {
  if (!SS.taskId) { SS.running = false; ssRenderControls(); return; }
  SS.running = false;
  if (SS.timer) clearTimeout(SS.timer);
  cPHP('site_suck_stop', { taskId: SS.taskId });
}

function ssOnStop(r) {
  SS.running = false;
  ssRenderControls();
}

function ssRenderControls() {
  var btn = g('ssRunBtn');
  if (!btn) return;
  if (SS.running) {
    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>';
    btn.title = _t('ss_stop') || 'Stop';
    btn.classList.add('is-running');
  } else {
    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
    btn.title = _t('ss_run') || 'Run';
    btn.classList.remove('is-running');
  }
  var ob = g('ssOpenBtn');
  if (ob) ob.style.display = (SS.dir && !SS.running) ? '' : 'none';
}

function ssRenderStat(c) {
  var el = g('ssStat');
  if (!el) return;
  c = c || { page: 0, link: 0, success: 0, fail: 0, running: 0, wait: 0 };
  var label = SS.running ? (_t('ss_running') || 'Running...') : (SS.state === 'done' ? (_t('ss_done') || 'Done') : (_t('ss_stopped') || 'Stopped'));
  el.innerHTML =
    EP.tag((_t('ss_success') || 'Success') + ': ' + c.success, 'success') + ' ' +
    EP.tag((_t('ss_fail') || 'Fail') + ': ' + c.fail, 'danger') + ' ' +
    EP.tag((_t('ss_wait') || 'Wait') + ': ' + c.wait, 'info') + ' ' +
    EP.tag(label, SS.running ? 'warning' : 'info');
  if (SS.dir) {
    el.innerHTML += ' <span class="ss-dir" title="' + esc(SS.dir) + '">' + esc(SS.dir) + '</span>';
  }
}

function ssStateIcon(state) {
  if (state === 'fail') {
    return '<span class="ss-ic ss-ic--fail" title="' + (_t('ss_state_fail') || 'Fail') + '">'
      + '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>';
  }
  if (state === 'success' || state === 'replace') {
    return '<span class="ss-ic ss-ic--ok" title="' + (_t('ss_state_success') || 'Success') + '">'
      + '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>';
  }
  if (state === 'running') {
    return '<span class="ss-ic ss-ic--run" title="' + (_t('ss_state_running') || 'Running') + '">'
      + '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="ss-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg></span>';
  }
  return '<span class="ss-ic ss-ic--wait" title="' + (_t('ss_state_wait') || 'Wait') + '"></span>';
}

function ssRenderLinks() {
  var el = g('ssLinks');
  if (!el) return;
  var q = (g('ssSearch') ? g('ssSearch').value : '').trim().toLowerCase();
  var links = SS.data.links.filter(function (l) {
    return !q || (l.url || '').toLowerCase().indexOf(q) >= 0;
  });
  var hd = g('ssLinksHd');
  if (hd) hd.textContent = 'url (' + (SS.data.links.filter(function(l){return l.state==='success'||l.state==='replace'||l.state==='fail';}).length) + '/' + SS.data.links.length + ')';
  if (!links.length) {
    el.innerHTML = '<div class="ss-empty">' + (_t('ss_no_links') || 'No links') + '</div>';
    return;
  }
  var h = '<table class="ep-table ss-table"><tbody>';
  links.forEach(function (l) {
    h += '<tr><td class="ss-url">' + ssStateIcon(l.state)
      + '<span class="ss-url-txt" onclick="ssOpenUrl(\'' + esc(l.url) + '\')" title="' + esc(l.url) + '">' + esc(l.url) + '</span></td></tr>';
  });
  h += '</tbody></table>';
  el.innerHTML = h;
}

function ssRenderHosts() {
  var el = g('ssHosts');
  if (!el) return;
  var q = (g('ssHostSearch') ? g('ssHostSearch').value : '').trim().toLowerCase();
  var hosts = (SS.data.hosts || []).filter(function (h) {
    return !q || (h.host || '').toLowerCase().indexOf(q) >= 0;
  });
  if (!hosts.length) {
    el.innerHTML = '<div class="ss-empty">' + (_t('ss_no_hosts') || 'No hosts') + '</div>';
    return;
  }
  var h = '<table class="ep-table ss-table"><tbody>';
  hosts.forEach(function (host) {
    var cnt = SS.data.links.filter(function (l) {
      try { return new URL(l.url).host === host.host; } catch (e) { return false; }
    }).length;
    var ok = host.allow;
    h += '<tr><td class="ss-host"><span class="ss-host-name">' + esc(host.host) + '</span>'
      + '<span class="ss-host-cnt">(' + cnt + ')</span></td>'
      + '<td class="ss-host-act"><span class="ss-allow ' + (ok ? 'is-allow' : 'is-exclude') + '" onclick="ssToggleHost(\'' + esc(host.host) + '\')">'
      + (ok ? (_t('ss_allow') || 'Allow') : (_t('ss_exclude') || 'Exclude')) + '</span></td></tr>';
  });
  h += '</tbody></table>';
  el.innerHTML = h;
}

function ssToggleHost(host) {
  // Reflect into excludeLink config: add/remove host line.
  var lines = (SS.cfg.excludeLink || '').split('\\n').map(function (s) { return s.trim(); }).filter(Boolean);
  var i = lines.indexOf(host);
  if (i >= 0) lines.splice(i, 1); else lines.push(host);
  SS.cfg.excludeLink = lines.join('\\n');
  // Update open drawer textarea if visible
  if (g('ssExclude')) g('ssExclude').value = SS.cfg.excludeLink;
  ssSaveCfg();
  ssRenderHosts();
}

function ssOpenUrl(url) {
  if (window.open_url) { try { window.open_url(JSON.stringify({ url: url })); return; } catch (e) {} }
  EP.toast(url, 'info');
}

function ssOpenSet() {
  // Prefill form from saved cfg
  if (g('ssDir')) g('ssDir').value = SS.cfg.dir || '';
  if (g('ssProxy')) g('ssProxy').value = SS.cfg.proxy || '';
  if (g('ssTimeout')) g('ssTimeout').value = SS.cfg.timeout || 10;
  if (g('ssWin')) g('ssWin').value = SS.cfg.windowCount || 2;
  if (g('ssMaxImg')) g('ssMaxImg').value = SS.cfg.maxImgSize || 0;
  if (g('ssMaxVideo')) g('ssMaxVideo').value = SS.cfg.maxVideoSize || 0;
  if (g('ssPageLimit')) g('ssPageLimit').value = SS.cfg.pageLimit || '';
  if (g('ssExclude')) g('ssExclude').value = SS.cfg.excludeLink || '';
  var m = g('ssSetModal');
  if (m) m.style.display = 'block';
}
function ssCloseSet() {
  var m = g('ssSetModal');
  if (m) m.style.display = 'none';
  ssCfgFromForm();
}
function ssSaveSet() {
  ssCfgFromForm();
  ssCloseSet();
  EP.toast(_t('ss_settings_saved') || 'Settings saved', 'success');
}
function ssPickDir() {
  // No native folder dialog in webview; fall back to a sensible default.
  var def = (SS.cfg.dir && SS.cfg.dir.trim()) ? SS.cfg.dir.trim()
    : ((typeof process !== 'undefined' && process.env && process.env.HOME) ? process.env.HOME + '/Downloads/flyenv-sites' : '/tmp/flyenv-sites');
  if (g('ssDir')) g('ssDir').value = def;
  SS.cfg.dir = def; ssSaveCfg();
}
function ssOpenDir() {
  if (SS.dir) EP.toast(SS.dir, 'info');
}
