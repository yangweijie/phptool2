/* FlyEnv Toolbox — Panel HTML Definitions (with i18n) */
var __p = {};
__p.diff = function() {
  return '<div class="dl"><div><lb>'+_t('ori')+'</lb><textarea id="dL"></textarea></div>'
    + '<div><lb>'+_t('mod')+'</lb><textarea id="dR"></textarea></div></div>'
    + '<div class="rw"><button class="btn p" onclick="fD()">'+_t('cmp')+'</button>'
    + '<button class="btn" onclick="sD()">'+_t('swp')+'</button>'
    + '<button class="btn" onclick="xDD()">'+_t('sam')+'</button></div>'
    + '<div class="ot" id="dO"></div>';
};

__p.cron = function() {
  var fields = ['Min','Hour','Day','Month','Week'];
  var html = '<div class="cd"><div class="rw gr">';
  for (var i = 0; i < 5; i++) {
    html += '<div><lb>'+fields[i]+'</lb><input id="cr'+i+'" value="*"></div>';
  }
  html += '</div><div class="prs">';
  var presets = [["1m","* * * * *"],["5m","*/5 * * * *"],["Hourly","0 * * * *"],["9AM","0 9 * * *"],["Mon","0 0 * * 1"]];
  for (var j = 0; j < presets.length; j++) {
    html += '<button onclick="sCr(\''+presets[j][1]+'\')">'+presets[j][0]+'</button>';
  }
  html += '</div><button class="btn p" onclick="fCr()">'+_t('cal')+'</button></div>'
    + '<div class="ot" id="crO"></div>';
  return html;
};

__p.json = function() {
  return '<lb>JSON:</lb><textarea id="jI" rows="4">{"name":"FlyEnv","ver":"4.15"}</textarea>'
    + '<div class="rw"><button class="btn" onclick="fJ(\'fmt\')">'+_t('fmt')+'</button>'
    + '<button class="btn" onclick="fJ(\'min\')">'+_t('min')+'</button>'
    + '<button class="btn" onclick="fJ(\'srt\')">'+_t('srt')+'</button>'
    + '<button class="btn" onclick="fJ(\'val\')">'+_t('val')+'</button></div>'
    + '<div class="ot" id="jO"></div>';
};

__p.jwt = function() {
  return '<div class="cd"><div class="hd">'+_t('dec')+'</div>'
    + '<input id="jT" placeholder="'+_t('token_ph')+'">'
    + '<input id="jS" type="password" placeholder="'+_t('secret')+'..." style="margin-top:4px">'
    + '<button class="btn" onclick="fJD()" style="margin-top:4px">'+_t('dec')+'</button></div>'
    + '<div class="cd"><div class="hd">'+_t('enc')+'</div>'
    + '<div class="rw gr"><div><lb>'+_t('algo')+'</lb><select id="jA"><option>HS256</option><option>HS384</option><option>HS512</option></select></div>'
    + '<div><lb>'+_t('secret')+'</lb><input id="jES" value="my-key"></div></div>'
    + '<lb>'+_t('payload')+':</lb><textarea id="jP" rows="3">{"sub":"123","name":"John"}</textarea>'
    + '<button class="btn" onclick="fJE()" style="margin-top:4px">'+_t('enc')+'</button></div>'
    + '<div class="ot" id="jO"></div>';
};

__p.hash = function() {
  return '<lb>'+_t('hash_lbl')+'</lb><textarea id="hI" rows="3">Hello FlyEnv!</textarea>'
    + '<div class="rw"><button class="btn sm" onclick="fH(\'md5\')">MD5</button>'
    + '<button class="btn sm" onclick="fH(\'sha1\')">SHA1</button>'
    + '<button class="btn sm" onclick="fH(\'sha256\')">SHA256</button>'
    + '<button class="btn sm" onclick="fH(\'sha384\')">SHA384</button>'
    + '<button class="btn sm" onclick="fH(\'sha512\')">SHA512</button>'
    + '<button class="btn sm" onclick="fH(\'crc32\')">CRC32</button>'
    + '<button class="btn sm" onclick="fH(\'b64\')">B64</button></div>'
    + '<div class="ot" id="hO"></div>';
};

__p.encrypt = function() {
  return '<div class="rw gr"><div><lb>'+_t('algo')+'</lb><select id="cM"><option>AES-256-CBC</option><option>AES-128-CBC</option></select></div>'
    + '<div><lb>'+_t('key')+'</lb><input id="cP" value="my-key" type="password"></div></div>'
    + '<textarea id="cI" rows="3">Hello secret!</textarea>'
    + '<div class="rw"><button class="btn" onclick="fC(\'enc\')">'+_t('cenc')+'</button>'
    + '<button class="btn" onclick="fC(\'dec\')">'+_t('cdec')+'</button></div>'
    + '<div class="ot" id="cO"></div>';
};

__p.ts = function() {
  var n = new Date();
  var vals = [n.getFullYear(), String(n.getMonth()+1).padStart(2,'0'), String(n.getDate()).padStart(2,'0'),
    String(n.getHours()).padStart(2,'0'), String(n.getMinutes()).padStart(2,'0'), String(n.getSeconds()).padStart(2,'0')];
  var labels = ['Y','M','D','H','I','S'];
  var html = '<div class="rw"><input id="tI" value="'+Math.floor(Date.now()/1000)+'" style="width:140px">'
    + '<button class="btn" onclick="fTD()">'+_t('ts2d')+'</button></div><div class="rw">';
  for (var i = 0; i < 6; i++) {
    html += '<input id="t'+i+'" value="'+vals[i]+'" style="width:45px">';
  }
  html += '<button class="btn" onclick="fTT()">'+_t('ts2t')+'</button></div>'
    + '<div class="rw"><button class="btn" onclick="fTN()">'+_t('now')+'</button></div>'
    + '<div class="ot" id="tO"></div>';
  return html;
};

__p.b64 = function() {
  return '<textarea id="bI" rows="4">Hello FlyEnv!</textarea>'
    + '<div class="rw"><button class="btn" onclick="fB(\'enc\')">'+_t('b64enc')+'</button>'
    + '<button class="btn" onclick="fB(\'dec\')">'+_t('b64dec')+'</button></div>'
    + '<div class="ot" id="bO"></div>';
};

__p.url = function() {
  return '<textarea id="uI" rows="2">https://example.com/path?q=FlyEnv</textarea>'
    + '<div class="rw"><button class="btn" onclick="fU(\'enc\')">'+_t('urlenc')+'</button>'
    + '<button class="btn" onclick="fU(\'dec\')">'+_t('urldec')+'</button>'
    + '<button class="btn" onclick="fU(\'par\')">'+_t('urlpar')+'</button></div>'
    + '<div class="ot" id="uO"></div>';
};

__p.html = function() {
  return '<textarea id="eI" rows="3">&lt;script&gt;alert("Hi")&lt;/script&gt;</textarea>'
    + '<div class="rw"><button class="btn" onclick="fE(\'esc\')">'+_t('esc')+'</button>'
    + '<button class="btn" onclick="fE(\'unesc\')">'+_t('unesc')+'</button>'
    + '<button class="btn" onclick="fE(\'strip\')">'+_t('strip')+'</button></div>'
    + '<div class="ot" id="eO"></div>';
};

__p.regex = function() {
  return '<div class="rw gr"><div><lb>'+_t('pat')+'</lb><input id="rP" value="[a-z]+"></div>'
    + '<div><lb>'+_t('flags')+'</lb><select id="rF"><option>i</option><option>m</option><option>s</option><option selected>u</option></select></div></div>'
    + '<textarea id="rS" rows="3">Hello World</textarea>'
    + '<div class="rw"><button class="btn" onclick="fR(\'match\')">'+_t('rmatch')+'</button>'
    + '<button class="btn" onclick="fR(\'all\')">'+_t('rall')+'</button>'
    + '<button class="btn" onclick="fR(\'rep\')">'+_t('rrep')+'</button>'
    + '<button class="btn" onclick="fR(\'spl\')">'+_t('rspl')+'</button></div>'
    + '<div class="ot" id="rO"></div>';
};

__p.chmod = function() {
  var owners = [['Owner','U'],['Group','G'],['Others','O']];
  var perms = [['r','U'],['w','W'],['x','X']];
  var html = '<div class="g3">';
  for (var o = 0; o < 3; o++) {
    html += '<div class="gi"><h4>'+_t({0:'owner',1:'group',2:'others'}[o])+'</h4>';
    for (var p = 0; p < 3; p++) {
      html += '<label class="grl"><input type="checkbox" id="c'+owners[o][1]+perms[p][1]+'" onchange="fCM()"> '+perms[p][0]+'</label>';
    }
    html += '</div>';
  }
  html += '</div><div class="rw"><input id="cN" value="644" style="width:55px" oninput="fCN()"><div class="prs">';
  var pre = [[755,'7,5,5'],[644,'6,4,4'],[777,'7,7,7'],[700,'7,0,0']];
  for (var k = 0; k < pre.length; k++) {
    html += '<button onclick="sCM('+pre[k][1]+')">'+pre[k][0]+'</button>';
  }
  html += '</div></div><div class="ot" id="cO2"></div>';
  return html;
};

__p.token = function() {
  return '<div class="rw gr"><div><lb>'+_t('type')+'</lb><select id="kT"><option>Hex</option><option>Base62</option><option>B64URL</option><option>PIN</option></select></div>'
    + '<div><lb>'+_t('len')+'</lb><input id="kL" value="32" type="number"></div>'
    + '<div><lb>'+_t('cnt')+'</lb><input id="kC" value="5" type="number"></div></div>'
    + '<button class="btn" onclick="fTK()">'+_t('gen')+'</button><div class="tk" id="kO"></div>';
};

__p.http = function() {
  return '<div class="rw"><input id="hQ" placeholder="'+_t('http_search')+'" style="flex:1"><button class="btn" onclick="fHQ()">'+_t('lookup')+'</button></div>'
    + '<div class="prs"><button onclick="fHC(2)">2xx</button><button onclick="fHC(3)">3xx</button>'
    + '<button onclick="fHC(4)">4xx</button><button onclick="fHC(5)">5xx</button>'
    + '<button onclick="fHA()">'+_t('rall')+'</button></div>'
    + '<div class="ot" id="hO2"></div>';
};

__p.mime = function() {
  var exts = ['html','css','js','json','png','jpg','svg','pdf','zip','php'];
  var html = '<div class="rw"><input id="mI" placeholder="'+_t('ext')+'" style="width:120px"><button class="btn" onclick="fMI()">'+_t('lookup')+'</button></div><div class="rw">';
  for (var i = 0; i < exts.length; i++) {
    html += '<button class="btn sm" onclick="sMI(\''+exts[i]+'\')">'+exts[i]+'</button>';
  }
  html += '</div><div class="ot" id="mO"></div>';
  return html;
};

__p.bom = function() {
  return '<textarea id="bI2" rows="4"></textarea>'
    + '<div class="rw"><button class="btn" onclick="fBM(\'det\')">'+_t('det')+'</button>'
    + '<button class="btn" onclick="fBM(\'cln\')">'+_t('cln')+'</button>'
    + '<button class="btn" onclick="lBM()">'+_t('sam')+'</button></div>'
    + '<div class="ot" id="bO2"></div>';
};

__p.md = function() {
  return '<textarea id="mI2" rows="5"># Hello\n**Bold** *italic*\n- Item 1\n- Item 2</textarea>'
    + '<button class="btn" onclick="fMD()">'+_t('prev')+'</button>'
    + '<div class="ot md-ot" id="mO2" style="max-height:400px"></div>';
};

__p.wss = function() {
  return '<div class="rw"><input id="wUrl" value="wss://echo.websocket.org" style="flex:1">'
    + '<select id="wT" style="width:80px"><option>WS</option><option>SSE</option></select>'
    + '<button class="btn" onclick="fWS()">'+_t('conn')+'</button>'
    + '<button class="btn" onclick="fWD()">'+_t('disc')+'</button></div>'
    + '<div class="rw"><input id="wMsg" placeholder="'+_t('msg_ph')+'" style="flex:1">'
    + '<button class="btn" onclick="fWSend()">'+_t('send')+'</button></div>'
    + '<div class="lv" id="wO"></div>';
};

__p.code = function() {
  return '<textarea id="cI2" rows="7">&lt;?php\n$name = "FlyEnv";\necho "Hello, $name!";</textarea>'
    + '<div class="rw"><button class="btn" onclick="cPHP(\'code_run\',{code:g(\'cI2\').value})">'+_t('run')+'</button></div>'
    + '<div class="ot" id="cO3"></div>';
};

__p.clib = function() {
  return '<div class="cl-tabs"><button class="cl-tab active" onclick="sCT(this,\'snippets\')">Snippets</button>'
    + '<button class="cl-tab" onclick="sCT(this,\'php\')">PHP</button>'
    + '<button class="cl-tab" onclick="sCT(this,\'js\')">JS</button></div>'
    + '<textarea id="cL" rows="8">Select a snippet...</textarea>'
    + '<div class="rw"><button class="btn" onclick="cCopy()">'+_t('copy')+'</button></div>';
};

__p.qr = function() {
  return '<div class="cd"><div class="hd">QR Code</div>'
    + '<lb>'+_t('gen_text')+'</lb><input id="qI" value="https://github.com/xpf0000/FlyEnv">'
    + '<div class="rw"><div><lb>ECC</lb><select id="qE"><option>L</option><option selected>M</option><option>Q</option><option>H</option></select></div>'
    + '<button class="btn p" onclick="var o=g(\'qrOut\');o.innerHTML=\'<div style=color:var(--dm);font-size:13px>\u23F3 Connecting PHP bridge...</div>\';cPHP(\'qr\',{text:g(\'qI\').value,ecc:+g(\'qE\').selectedIndex})">'+_t('gen')+'</button></div>'
    + '<div id="qrOut" class="ot qr-out" style="min-height:200px;display:flex;align-items:center;justify-content:center;padding:16px;flex-direction:column"></div></div>';
};

__p.wifi = function() {
  return '<div class="cd"><div class="hd">WiFi QR</div>'
    + '<lb>SSID:</lb><input id="wSSID" value="MyWiFi"><lb>'+_t('pass')+':</lb><input id="wPass" value="password123">'
    + '<div class="rw"><div><lb>'+_t('enc')+'</lb><select id="wEnc"><option>WPA</option><option>WEP</option><option>None</option></select></div>'
    + '<button class="btn p" onclick="var o=g(\'wO2\');o.innerHTML=\'<div style=color:var\\(--dm\\);font-size:13px>⏳ Generating...</div>\';cPHP(\'qr\',{text:\'WIFI:T:\'+[,\'WPA\',\'WEP\',\'nopass\'][+g(\'wEnc\').selectedIndex+1]+\';S:\'+g(\'wSSID\').value+\';P:\'+g(\'wPass\').value+\';;\',ecc:0,_out:\'wO2\'})">'+_t('gen')+'</button></div>'
    + '<div id="wO2" class="ot qr-out" style="min-height:200px;display:flex;align-items:center;justify-content:center;padding:16px;flex-direction:column"></div></div>';
};

__p.img = function() {
  return '<div class="cd"><div class="hd">'+_t('compress')+'</div><lb>'+_t('path')+':</lb><input id="iI">'
    + '<div class="rw"><div><lb>'+_t('quality')+'</lb><select id="iQ"><option>90</option><option selected>70</option><option>50</option><option>30</option></select></div>'
    + '<div><lb>'+_t('maxw')+'</lb><select id="iW"><option>3840</option><option selected>1920</option><option>1280</option><option>800</option></select></div></div>'
    + '<button class="btn" onclick="cPHP(\'image_c\',{path:g(\'iI\').value,quality:+g(\'iQ\').value,maxWidth:+g(\'iW\').value})">'+_t('compress')+'</button></div>'
    + '<div class="ot" id="iO"></div>';
};

__p.capture = function() {
  return '<div class="cd"><div class="hd">'+_t('screenshot')+'</div>'
    + '<div class="rw"><button class="btn" onclick="cPHP(\'capture\',{type:\'full\'})">'+_t('full')+'</button>'
    + '<button class="btn" onclick="cPHP(\'capture\',{type:\'select\'})">'+_t('area')+'</button>'
    + '<button class="btn" onclick="cPHP(\'capture\',{type:\'window\'})">'+_t('win')+'</button></div></div>'
    + '<div class="ot" id="cO4"></div>';
};

__p.rsa = function() {
  return '<div class="rw"><div><lb>'+_t('bits')+'</lb><select id="rB"><option>1024</option><option selected>2048</option><option>4096</option></select></div>'
    + '<button class="btn" onclick="cPHP(\'rsa\',{bits:+g(\'rB\').selectedIndex})">'+_t('gen')+'</button></div>'
    + '<div class="ot" id="rO2" style="max-height:400px"></div>';
};

__p.file = function() {
  return '<div class="rw"><input id="fI" placeholder="'+_t('path')+'..." style="flex:1">'
    + '<button class="btn" onclick="cPHP(\'file_info\',{path:g(\'fI\').value})">'+_t('info')+'</button></div>'
    + '<div class="ot" id="fO"></div>';
};

__p.timing = function() {
  return '<div class="rw"><input id="tI2" value="https://example.com" style="flex:1">'
    + '<button class="btn" onclick="cPHP(\'url_timing\',{url:g(\'tI2\').value})">'+_t('test')+'</button></div>'
    + '<div class="ot" id="tO2"></div>';
};

__p.suck = function() {
  return '<div class="rw"><input id="sI" value="https://example.com" style="flex:1">'
    + '<button class="btn" onclick="cPHP(\'site_suck\',{url:g(\'sI\').value})">'+_t('fetch')+'</button></div>'
    + '<div class="ot" id="sO"></div>';
};

__p.ssl = function() {
  return '<div class="rw gr"><div><lb>'+_t('domain')+'</lb><input id="sCN" value="localhost"></div>'
    + '<div><lb>'+_t('days')+'</lb><input id="sDay" value="365" type="number"></div></div>'
    + '<div class="rw"><div><lb>'+_t('bits')+'</lb><select id="sBits"><option>2048</option><option>4096</option></select></div>'
    + '<button class="btn" onclick="cPHP(\'ssl_make\',{cn:g(\'sCN\').value,days:+g(\'sDay\').value,bits:+g(\'sBits\').value})">'+_t('gen')+'</button></div>'
    + '<div class="ot" id="sO2" style="max-height:400px"></div>';
};

__p.obf = function() {
  return '<textarea id="obI" rows="6">&lt;?php\necho "Hello World";</textarea>'
    + '<button class="btn" onclick="cPHP(\'php_obf\',{code:g(\'obI\').value})">'+_t('obf')+'</button>'
    + '<div class="ot" id="obO"></div>';
};

// Panels with no active implementation yet
__p.mp4 = function() { return '<div class="cd"><div class="hd">Media Info</div><p>Coming soon</p></div>'; };

__p.portkill = function() {
  return '<div class="cd"><div class="hd">'+_t('portkill')+'</div><div class="rw"><input id="pkInput" placeholder="'+_t('pk_ph')+'" style="flex:1">'
    + '<button class="btn" onclick="pkSearch()">'+_t('lookup')+'</button></div>'
    + '<div class="rw"><button class="btn" onclick="pkKillSel()">'+_t('pk_kill_sel')+'</button>'
    + '<button class="btn d" onclick="pkKillAll()">'+_t('pk_kill_all')+'</button></div></div>'
    + '<div class="ot" id="pkO"></div>';
};

__p.prockill = function() {
  return '<div class="cd"><div class="hd">'+_t('prockill')+'</div><div class="rw"><input id="procInput" placeholder="'+_t('proc_ph')+'" style="flex:1">'
    + '<button class="btn" onclick="procSearch()">'+_t('lookup')+'</button></div>'
    + '<div class="rw"><button class="btn" onclick="procKillSel()">'+_t('pk_kill_sel')+'</button>'
    + '<button class="btn d" onclick="procKillAll()">'+_t('pk_kill_all')+'</button></div></div>'
    + '<div class="ot" id="procO"></div>';
};

// ── Keycode Info ──
__p.keycode = function() {
  return '<div class="cd"><div class="hd">'+_t('kc_key')+' Code Info</div>'
    + '<div id="kcArea" tabindex="0" autofocus style="outline:none;text-align:center;min-height:220px;cursor:text;border:2px dashed var(--bd);border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;margin-bottom:12px" onfocus="kcInit()" onblur="kcStop()">'
    + '<div style="font-size:48px;font-weight:800;color:var(--dm);margin-bottom:8px" id="kcKeyDisplay">⌨</div>'
    + '<div style="font-size:13px;color:var(--dm)" id="kcHint">'+_t('kc_hint')+'</div>'
    + '<div id="kcInfo" style="display:none;width:100%">'
    + '<div class="g3" style="margin-top:16px"><div class="gi"><h4>key</h4><div id="kcV_key" style="font-size:20px;font-weight:700;word-break:break-all"></div></div>'
    + '<div class="gi"><h4>code</h4><div id="kcV_code" style="font-size:20px;font-weight:700;word-break:break-all"></div></div>'
    + '<div class="gi"><h4>keyCode</h4><div id="kcV_keyCode" style="font-size:20px;font-weight:700"></div></div></div>'
    + '<div class="g3"><div class="gi"><h4>location</h4><div id="kcV_loc" style="font-size:14px;font-weight:600"></div></div>'
    + '<div class="gi"><h4>'+_t('kc_mod')+'</h4><div id="kcV_mod" style="font-size:13px"></div></div>'
    + '<div class="gi"><h4>type</h4><div id="kcV_type" style="font-size:13px"></div></div></div></div></div>'
    + '<div class="rw"><button class="btn" onclick="kcClear()">'+_t('kc_clr')+'</button>'
    + '<span style="font-size:11px;color:var(--dm);margin-left:auto">'+_t('kc_his')+':</span></div>'
    + '<div id="kcHist" class="ot" style="min-height:40px;max-height:120px;overflow-y:auto;font-size:11px;font-family:monospace;padding:6px 10px"></div></div>';
};

// ── Regex / Git Cheatsheet Content ──
var REGEX_MEMO = '### Normal characters\n\nExpression | Description\n:--|:--\n`.` | any character except newline\n`[A-Za-z]` | alphabet\n`\\d` or `[0-9]` | digit\n`\\D` or `[^0-9]` | non-digit\n`\\w` | word character\n`\\W` | non-word character\n\n### Quantifiers\n\nExpression | Description\n:--|:--\n`{2}` | exactly 2\n`{2,}` | at least 2\n`*` | 0 or more\n`+` | 1 or more\n`?` | 0 or 1\n\n### Anchors\n\n`^` | start of string\n`$` | end of string\n`\\b` | word boundary\n\n### Groups\n\n`(abc)` | capturing group\n`(?:abc)` | non-capturing\n`(?=abc)` | lookahead\n`(?!abc)` | negative lookahead\n\n### Character Classes\n\n`[abc]` | a, b, or c\n`[^abc]` | not a, b, c\n`[a-z]` | range a to z\n\n### Flags\n\n`i` | case insensitive\n`g` | global match\n`m` | multiline\n`s` | dotall\n`u` | unicode\n';

var GIT_MEMO = '## Configuration\n\n```\ngit config --global user.name "[name]"\ngit config --global user.email "[email]"\n```\n\n## Getting Started\n\n```\ngit init\ngit clone [url]\n```\n\n## Branching\n\n- `git branch` — list branches\n- `git branch [name]` — create branch\n- `git checkout [name]` — switch branch\n- `git checkout -b [name]` — create + switch\n- `git merge [name]` — merge into current\n- `git branch -d [name]` — delete branch\n\n## Commits\n\n- `git status` — check status\n- `git add [file]` — stage changes\n- `git commit -m "msg"` — commit\n- `git commit -am "msg"` — add+commit tracked\n- `git log --oneline` — view history\n- `git diff` — show changes\n\n## Stash\n\n- `git stash push -m "msg"` — save to stash\n- `git stash list` — list stashes\n- `git stash pop` — apply + delete\n- `git stash drop` — delete stash\n\n## Undo\n\n- `git commit --amend` — change last commit\n- `git reset HEAD~1` — undo commit, keep changes\n- `git reset HEAD~1 --hard` — undo + discard changes\n- `git reset --hard origin/[branch]` — reset to remote\n\n## Remote\n\n- `git remote add [name] [url]` — add remote\n- `git fetch [remote]` — fetch changes\n- `git pull --rebase [remote] [branch]` — pull + rebase\n- `git push origin [branch]` — push branch\n- `git push origin --tags` — push tags\n\n## Advanced\n\n- `git rebase -i HEAD~N` — interactive rebase\n- `git stash -u` — stash including untracked\n- `git bisect start/bad/good` — binary search bugs\n- `git reflog` — view reference history\n- `git gc --prune=now --aggressive` — optimize repo\n- `git blame [file]` — who changed each line\n- `git tag -a [name] -m "msg"` — annotated tag\n';

__p.regex_memo = function() {
  return '<div class="ot md-ot" id="regMemoOut" style="min-height:100px;max-height:none"></div>';
};

__p.git_memo = function() {
  return '<div class="ot md-ot" id="gitMemoOut" style="min-height:100px;max-height:none"></div>';
};
