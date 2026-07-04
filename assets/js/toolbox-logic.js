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
  if (id === 'git-cheatsheet') { setTimeout(function(){var e=g('gitMemoOut');if(e)e.innerHTML=rMD(GIT_MEMO);},10); }
  // Auto-focus keycode area
  if (id === 'keycode-info') { setTimeout(function(){var a=g('kcArea');if(a)a.focus();},200); }
  g('sidebarTree').querySelectorAll('.tree-item').forEach(function(e){e.classList.remove('active');});
  var a = g('sidebarTree').querySelector('.tree-item[data-id="'+id+'"]');
  if (a) a.classList.add('active');
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

// ── Cron ──
function sCr(e) { var parts = e.split(' '); for (var i = 0; i < 5; i++) g('cr'+i).value = parts[i]; }
function fCr() {
  var e = [0,1,2,3,4].map(function(i){return g('cr'+i).value;}).join(' ');
  var p = e.split(' ');
  function cm(e,v,mn,mx) {
    e=e.trim(); if(e.indexOf(',')>=0) return e.split(',').some(function(x){return cm(x.trim(),v,mn,mx);});
    if(e.indexOf('/')>=0){var a=e.split('/',2);return a[0]==='*'?(v-mn)%(+a[1])===0:v>=(+a[0])&&(v-+a[0])%(+a[1])===0;}
    if(e==='*') return true; if(e.indexOf('-')>=0){var b=e.split('-',2);return v>=+b[0]&&v<=+b[1];}
    return +e === v;
  }
  var runs = []; var cur = new Date(); cur.setSeconds(0,0); cur.setMinutes(cur.getMinutes()+1);
  for (var x = 0; x < 525600 && runs.length < 10; x++) {
    if (cm(p[0],cur.getMinutes(),0,59)&&cm(p[1],cur.getHours(),0,23)&&cm(p[2],cur.getDate(),1,31)&&cm(p[3],cur.getMonth()+1,1,12)&&cm(p[4],cur.getDay(),0,7))
      runs.push(cur.toISOString().replace('T',' ').slice(0,16));
    cur.setMinutes(cur.getMinutes()+1);
  }
  g('crO').textContent = 'Next '+runs.length+' runs:\n'+runs.map(function(t,i){return (i+1)+'. '+t;}).join('\n');
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

// ── JWT ──
function b64u(s){return btoa(s).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');}
function b64ud(s){try{s=s.replace(/-/g,'+').replace(/_/g,'/');while(s.length%4)s+='=';return atob(s);}catch(e){return null;}}
async function fJD() {
  var tok=g('jT').value,sec=g('jS').value;
  if(!tok||tok.split('.').length!==3){g('jO').textContent='Invalid token';return;}
  var p=tok.split('.'),hd=null,pay=null;
  try{hd=JSON.parse(b64ud(p[0])||'{}');}catch(e){hd={error:'Bad header'};}
  try{pay=JSON.parse(b64ud(p[1])||'{}');}catch(e){pay={error:'Bad payload'};}
  var o='Header:\n'+JSON.stringify(hd,null,2)+'\n\nPayload:\n'+JSON.stringify(pay,null,2);
  if(sec&&hd&&!hd.error){
    var hn=(hd.alg||'HS256').indexOf('384')>=0?'SHA-384':(hd.alg||'HS256').indexOf('512')>=0?'SHA-512':'SHA-256';
    try{
      var key=await crypto.subtle.importKey('raw',new TextEncoder().encode(sec),{name:'HMAC',hash:{name:hn}},false,['sign']);
      var sig=await crypto.subtle.sign('HMAC',key,new TextEncoder().encode(p[0]+'.'+p[1]));
      var exp=Uint8Array.from(b64ud(p[2])||'',function(c){return c.charCodeAt(0);});
      o+='\n\nVerification: '+(sig.byteLength===exp.length&&new Uint8Array(sig).every(function(v,i){return v===exp[i];})?'\u2713 Valid':'\u2717 Invalid');
    }catch(e){o+='\n\nVerification: \u2717 Error: '+e.message;}
  }else if(sec&&hd&&hd.error){
    o+='\n\nVerification: skipped (bad header)';
  }
  g('jO').textContent=o;
}
async function fJE() {
  var p=JSON.parse(g('jP').value||'{}'),s=g('jES').value,a=g('jA').value;
  var hb=b64u(JSON.stringify({alg:a,typ:'JWT'})),pb=b64u(JSON.stringify(p));
  var hn=a.indexOf('384')>=0?'SHA-384':a.indexOf('512')>=0?'SHA-512':'SHA-256';
  var key=await crypto.subtle.importKey('raw',new TextEncoder().encode(s),{name:'HMAC',hash:{name:hn}},false,['sign']);
  var sig=await crypto.subtle.sign('HMAC',key,new TextEncoder().encode(hb+'.'+pb));
  g('jO').textContent=hb+'.'+pb+'.'+b64u(String.fromCharCode.apply(null,new Uint8Array(sig)));
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
function fR(m){try{var p=new RegExp(g('rP').value,g('rF').value),s=g('rS').value;var r;if(m==='match'){var x=s.match(p);r=x?JSON.stringify(x):'No match';}else if(m==='all'){var x=Array.from(s.matchAll(p));r=x.length?x.map(function(a,i){return '#'+i+': '+JSON.stringify(a[0]);}).join('\n'):'No match';}else if(m==='rep'){var c=s.replace(p,'***');r=c!==s?'Replaced:\n'+c:'No match';}else{var x=s.split(p);r=x.length>1?x.map(function(v,i){return '['+i+'] '+v;}).join('\n'):'No split';}g('rO').textContent=r;}catch(e){g('rO').textContent='Error: '+e.message;}}

// ── Chmod ──
function fCM(){
  var o=+(g('cUU')?g('cUU').checked?4:0:0)+(g('cUW')?g('cUW').checked?2:0:0)+(g('cUX')?g('cUX').checked?1:0:0);
  var g2=+(g('cGU')?g('cGU').checked?4:0:0)+(g('cGW')?g('cGW').checked?2:0:0)+(g('cGX')?g('cGX').checked?1:0:0);
  var t=+(g('cOU')?g('cOU').checked?4:0:0)+(g('cOW')?g('cOW').checked?2:0:0)+(g('cOX')?g('cOX').checked?1:0:0);
  g('cN').value=''+o+g2+t;var sv=function(v){return (v&4?'r':'-')+(v&2?'w':'-')+(v&1?'x':'-');};
  g('cO2').textContent='chmod '+o+g2+t+'\n'+sv(o)+sv(g2)+sv(t);
}
function fCN(){var v=g('cN').value;if(v.length===3&&/^\d+$/.test(v))sCM(+v[0],+v[1],+v[2]);}
function sCM(o,g2,t){var ck=function(id,v){var e=g(id);if(e)e.checked=!!v;};ck('cUU',o&4);ck('cUW',o&2);ck('cUX',o&1);ck('cGU',g2&4);ck('cGW',g2&2);ck('cGX',g2&1);ck('cOU',t&4);ck('cOW',t&2);ck('cOX',t&1);fCM();}

// ── Token ──
function fTK(){
  var len=+g('kL').value||32,cnt=Math.min(100,+g('kC').value||5),type=+g('kT').selectedIndex;
  var sets=['0123456789abcdef','abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789','abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_','0123456789'];
  g('kO').innerHTML=Array.from({length:cnt},function(){var t='',a=new Uint8Array(len);crypto.getRandomValues(a);for(var j=0;j<len;j++)t+=sets[type][a[j]%sets[type].length];return '<div class="tki">'+t+'</div>';}).join('');
}

// ── HTTP Status ──
var HC={100:'Continue',200:'OK',201:'Created',204:'No Content',301:'Moved',302:'Found',304:'Not Modified',400:'Bad Request',401:'Unauthorized',403:'Forbidden',404:'Not Found',405:'Not Allowed',408:'Timeout',409:'Conflict',413:'Too Large',415:'Unsupported',422:'Unprocessable',429:'Too Many',500:'Server Error',502:'Bad Gateway',503:'Unavailable',504:'Gateway Timeout'};
function fHQ(){var q=g('hQ').value.toLowerCase();g('hO2').innerHTML=Object.entries(HC).filter(function(a){return a[0].indexOf(q)>=0||a[1].toLowerCase().indexOf(q)>=0;}).map(function(a){return a[0]+' '+a[1];}).join('\n')||'(no results)';}
function fHA(){g('hO2').innerHTML=Object.entries(HC).map(function(a){return a[0]+' '+a[1];}).join('\n');}
function fHC(cl){g('hO2').innerHTML=Object.entries(HC).filter(function(a){return a[0][0]===''+cl;}).map(function(a){return a[0]+' '+a[1];}).join('\n');}

// ── MIME ──
var MM={html:'text/html',css:'text/css',js:'application/javascript',json:'application/json',xml:'application/xml',txt:'text/plain',md:'text/markdown',csv:'text/csv',pdf:'application/pdf',png:'image/png',jpg:'image/jpeg',gif:'image/gif',svg:'image/svg+xml',webp:'image/webp',ico:'image/x-icon',mp3:'audio/mpeg',mp4:'video/mp4',zip:'application/zip',php:'application/x-httpd-php'};
function fMI(){var e=g('mI').value.toLowerCase().replace(/^\./,'');g('mO').textContent=MM[e]?'.'+e+' -> '+MM[e]:'Unknown';}
function sMI(e){g('mI').value=e;fMI();}

// ── BOM ──
function fBM(m){var t=g('bI2').value;if(m==='det'){g('bO2').textContent=t.charCodeAt(0)===0xFEFF?'Has UTF-8 BOM':'No BOM';}else{g('bO2').textContent=t.charCodeAt(0)===0xFEFF?t.slice(1):t||'(empty)';}}
function lBM(){g('bI2').value=String.fromCharCode(0xFEFF)+'<?php\n// Has BOM\n?>';}

// ── Markdown ──
function rMD(s){var h=s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');h=h.replace(/^### (.+)/gm,'<h3>$1</h3>').replace(/^## (.+)/gm,'<h2>$1</h2>').replace(/^# (.+)/gm,'<h1>$1</h1>');h=h.replace(/\*\*(.+?)\*\*/g,'<b>$1</b>').replace(/\*(.+?)\*/g,'<i>$1</i>').replace(/\`(.+?)\`/g,'<code>$1</code>');h=h.replace(/^> (.+)/gm,'<blockquote>$1</blockquote>');h=h.replace(/^- (.+)/gm,'<li>$1</li>').replace(/(<li>.*\n?)+/g,'<ul>$&</ul>');h=h.replace(/\`\`\`(\w*)\n([\s\S]*?)\`\`\`/g,'<pre><code>$2</code></pre>');h=h.replace(/\[(.+?)\]\((.+?)\)/g,'<a href="$2">$1</a>');h=h.replace(/\n\n/g,'<p>').replace(/\n/g,'<br>');return h;}
function fMD(){var h=g('mI2').value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');h=h.replace(/^### (.+)/gm,'<h3>$1</h3>').replace(/^## (.+)/gm,'<h2>$1</h2>').replace(/^# (.+)/gm,'<h1>$1</h1>');h=h.replace(/\*\*(.+?)\*\*/g,'<b>$1</b>').replace(/\*(.+?)\*/g,'<i>$1</i>').replace(/\`(.+?)\`/g,'<code>$1</code>');h=h.replace(/^> (.+)/gm,'<blockquote>$1</blockquote>');h=h.replace(/^- (.+)/gm,'<li>$1</li>').replace(/(<li>.*\n?)+/g,'<ul>$&</ul>');h=h.replace(/\`\`\`(\w*)\n([\s\S]*?)\`\`\`/g,'<pre><code>$2</code></pre>');h=h.replace(/\[(.+?)\]\((.+?)\)/g,'<a href="$2">$1</a>');h=h.replace(/\n\n/g,'<p>').replace(/\n/g,'<br>');g('mO2').innerHTML=h;}

// ── WebSocket ──
var ws=null;function fWS(){var url=g('wUrl').value;if(!url)return;try{ws=new WebSocket(url);ws.onopen=function(){lW('Connected','s');};ws.onmessage=function(e){lW('[RECV] '+esc(e.data),'r');};ws.onerror=function(){lW('Error','e');};ws.onclose=function(){lW('Disconnected');ws=null;};}catch(e){lW(e.message,'e');}}
function fWD(){if(ws){ws.close();ws=null;lW('Disconnected');}}
function fWSend(){var m=g('wMsg').value;if(ws&&m){ws.send(m);lW('[SENT] '+m,'w');g('wMsg').value='';}}
function lW(m,t){var el=g('wO');var c=t==='s'?'color:#00b894':t==='e'?'color:#e17055':t==='r'?'color:#00cec9':t==='w'?'color:#fdcb6e':'';el.innerHTML+='<div style="'+c+'">['+new Date().toLocaleTimeString('zh-CN',{hour12:false})+'] '+esc(m)+'</div>';el.scrollTop=el.scrollHeight;}

// ── Code Library ──
var cTab='snippets';var CLIB={snippets:['console.log("Hello");\nconst arr=[1,2,3];\narr.map(x=>x*2);','const obj={a:1,b:2};\nObject.keys(obj).forEach(k=>console.log(k));'],php:['<?php\n$db=new PDO("mysql:host=localhost;dbname=test","root","");','<?php\n$data=["a","b","c"];\necho implode(", ",$data);'],js:['const add=(a,b)=>a+b;\nconsole.log(add(1,2));','fetch("/api").then(r=>r.json()).then(console.log);']};
function sCT(el,t){cTab=t;document.querySelectorAll('.cl-tab').forEach(function(x){x.classList.remove('active');});el.classList.add('active');g('cL').value=(CLIB[t]||[])[0]||'';}
function cCopy(){navigator.clipboard.writeText(g('cL').value);}

// ── PHP Bridge ──
// Map binding names to their output element IDs
var PHP_OUT_MAP = {
  code_run:'cO3', qr:'qrOut', image_c:'cO4', capture:'cO4',
  file_info:'fO', url_timing:'tO2', site_suck:'sO', ssl_make:'sO2',
  php_obf:'obO', rsa:'rO2', port_kill:'pkO', process_kill:'procO'
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
  kcHist=[];g('kcHist').textContent='';
  g('kcV_key').textContent='';g('kcV_code').textContent='';g('kcV_keyCode').textContent='';
  g('kcV_loc').textContent='';g('kcV_mod').textContent='';g('kcV_type').textContent='';
  g('kcInfo').style.display='none';
  document.getElementById('kcHint').textContent=_t('kc_hint');
  document.getElementById('kcKeyDisplay').textContent='⌨';
}

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
