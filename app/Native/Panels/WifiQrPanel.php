<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * WiFi QR Generator — WebView implementation matching original FlyEnv layout.
 */
final class WifiQrPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $w, height: $height - 40);
        $leaf->style->grow = 1.0;

        $children = [
            LayoutNode::leaf(null, new \Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec('WiFi QR Generator', size: 16.0, opacity: 0.85), width: $w, height: 36.0),
            $leaf,
        ];

        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 4.0, padding: 18.0, contentHeight: max(600.0, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private static function initialHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="zh"><head><meta charset="utf-8">
<style>
:root{
  --el-color-primary:#409eff;--el-color-success:#67c23a;--el-color-danger:#f56c6c;
  --el-text-color-primary:#303133;--el-text-color-regular:#606266;
  --el-border-color:#dcdfe6;--el-bg-color:#fff;--el-fill-color-blank:#fff;
  --el-border-radius-base:4px;--el-font-size-base:13px;
  --el-font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'PingFang SC','Microsoft YaHei',sans-serif;
  --bg:#f5f7fa;--sf:#fff;--bd:#e4e7ed;--tx:#303133;
}
body.dark{
  --el-text-color-primary:#e5eaf3;--el-text-color-regular:#cfd3dc;
  --el-border-color:#4c4d4f;--el-fill-color-blank:#1d1e1f;--el-bg-color:#1d1e1f;
  --bg:#1a1a2e;--sf:#16213e;--bd:#2a2a4a;--tx:#e4e6ef;
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow:auto;padding:16px}
.card{background:var(--sf);border:1px solid var(--bd);border-radius:8px;padding:20px;margin-bottom:16px}
.card-title{font-size:15px;font-weight:600;margin-bottom:16px;color:var(--tx)}
.form-item{margin-bottom:14px}
.form-label{display:block;font-size:12px;color:var(--el-text-color-regular);margin-bottom:6px}
.form-input{width:100%;padding:8px 12px;border:1px solid var(--bd);border-radius:var(--el-border-radius-base);font-size:13px;background:var(--el-fill-color-blank);color:var(--tx);outline:none;transition:border-color .2s}
.form-input:focus{border-color:var(--el-color-primary)}
.form-select{width:100%;padding:8px 12px;border:1px solid var(--bd);border-radius:var(--el-border-radius-base);font-size:13px;background:var(--el-fill-color-blank);color:var(--tx);outline:none;appearance:auto}
.form-row2{display:flex;gap:12px}
.form-row2 .form-item{flex:1}
.switch-wrap{display:flex;align-items:center;gap:8px}
.switch{position:relative;width:40px;height:22px;background:#ccc;border-radius:11px;cursor:pointer;transition:background .3s}
.switch.on{background:var(--el-color-primary)}
.switch::after{content:'';position:absolute;top:2px;left:2px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .3s}
.switch.on::after{transform:translateX(18px)}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 20px;border:none;border-radius:var(--el-border-radius-base);font-size:13px;cursor:pointer;transition:opacity .2s}
.btn-primary{background:var(--el-color-primary);color:#fff}
.btn-primary:hover{opacity:.85}
.btn-outline{background:transparent;border:1px solid var(--bd);color:var(--el-text-color-regular)}
.btn-outline:hover{border-color:var(--el-color-primary);color:var(--el-color-primary)}
.qr-box{display:flex;align-items:center;justify-content:center;min-height:200px;margin:12px 0}
.qr-box svg{max-width:100%;max-height:300px}
.color-row{display:flex;gap:12px}
.color-row .form-item{flex:1}
.color-input{width:100%;height:36px;border:1px solid var(--bd);border-radius:var(--el-border-radius-base);cursor:pointer;padding:2px}
.text-center{text-align:center}
.mt-12{margin-top:12px}
</style>
</head>
<body>
<div class="card">
  <div class="card-title">WiFi QR Generator</div>
  <div class="form-item">
    <label class="form-label">Encryption Type</label>
    <select id="wEnc" class="form-select" onchange="wifiToggle();wifiGen()">
      <option value="WPA">WPA/WPA2</option>
      <option value="WEP">WEP</option>
      <option value="nopass">No Password</option>
      <option value="WPA2-EAP">WPA2-EAP</option>
    </select>
  </div>
  <div class="form-item">
    <label class="form-label">SSID</label>
    <input id="wSSID" class="form-input" value="MyWiFi" oninput="wifiGen()">
  </div>
  <div class="form-item">
    <label class="form-label">Hidden Network</label>
    <div class="switch-wrap">
      <div id="wHidden" class="switch" onclick="this.classList.toggle('on');wifiGen()"></div>
      <span style="font-size:12px;color:var(--el-text-color-regular)">Off</span>
    </div>
  </div>
  <div id="wifiPwdBox">
    <div class="form-item">
      <label class="form-label">Password</label>
      <input id="wPass" class="form-input" type="password" value="password123" oninput="wifiGen()">
    </div>
  </div>
  <div id="wifiEapBox" style="display:none">
    <div class="form-item">
      <label class="form-label">EAP Method</label>
      <select id="wEap" class="form-select" onchange="wifiGen()">
        <option>PEAP</option><option>TLS</option><option>TTLS</option>
        <option>SIM</option><option>AKA</option><option>AKA'</option>
        <option>PWD</option><option>FAST</option><option>LEAP</option>
        <option>EKE</option><option>WFA-DPP</option>
      </select>
    </div>
    <div class="form-item">
      <label class="form-label">Identity</label>
      <input id="wEapId" class="form-input" oninput="wifiGen()">
    </div>
    <div class="form-item">
      <label class="form-label">Anonymous Identity</label>
      <div class="switch-wrap">
        <div id="wEapAnon" class="switch" onclick="this.classList.toggle('on');wifiGen()"></div>
      </div>
    </div>
    <div class="form-item">
      <label class="form-label">Phase 2</label>
      <select id="wEapP2" class="form-select" onchange="wifiGen()">
        <option>None</option><option>MSCHAPV2</option>
      </select>
    </div>
  </div>
  <div class="form-row2">
    <div class="form-item">
      <label class="form-label">Foreground</label>
      <input id="wFg" class="color-input" type="color" value="#000000" oninput="wifiGen()">
    </div>
    <div class="form-item">
      <label class="form-label">Background</label>
      <input id="wBg" class="color-input" type="color" value="#ffffff" oninput="wifiGen()">
    </div>
  </div>
</div>
<div class="card">
  <div class="qr-box" id="wO2">
    <div style="color:#999;font-size:13px">Fill in the form above to generate QR code</div>
  </div>
  <div class="text-center mt-12">
    <button class="btn btn-primary" onclick="wifiDownload()">Download SVG</button>
  </div>
</div>

<script>
var QR=(function(){
  function utf8Encode(s){var a=[];for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c<128)a.push(c);else if(c<2048){a.push(192|(c>>6));a.push(128|(c&63))}else{a.push(224|(c>>12));a.push(128|((c>>6)&63));a.push(128|(c&63))}}return a}
  function gfMul(a,b){var r=0;for(var i=0;i<8;i++){if(b&1)r^=a;a=(a<<1)^(a&128?0x11d:0);b>>=1}return r}
  function ecPoly(n){var g=[1];for(var i=0;i<n;i++){var ng=new Array(g.length+1).fill(0);for(var j=0;j<g.length;j++){ng[j]^=g[j];ng[j+1]^=gfMul(g[j],[1,25,198,35,194,93,207,91,213,41,72,83,137,195,44,56,140,53,63,108,168,210,229,239,226,55,94,53,220,59,53,35,45,215,233,53,53,66,135,58,160,165,48,119,15,52,76,21,128,118,87,214,194,230,193,193,161,130,203,211,229,179,48,181,113,160,135,225,122,136,12,51,193,21,179,167,41,167,177,139,53,145,170,46,175,248,70,63,222,176,156,1,64,209,154,215,47,246,208,137,169,239,249,60,41,178,118,7,118,166,179,113,220,111,206,79,72,14,232,50,80,161,137,12,85,225,100,85,79,228,172,143,98,70,70,68,88,94,67,31,24,12,47,17,21][i]||1);}g=ng}return g}
  function encodeRs(data,ecLen){var gen=ecPoly(ecLen),r=new Array(data.length+ecLen).fill(0);for(var i=0;i<data.length;i++)r[i]=data[i];for(var i=0;i<data.length;i++){var c=r[i];if(c)for(var j=0;j<gen.length;j++)r[i+j]^=gfMul(gen[j],c)}return r.slice(data.length)}
  var CAPS=[0,26,44,70,100,134,172,196,242,292,346];
  var ECWORDS=[0,7,10,15,20,26,18,20,24,30,18];
  var BLOCKS=[0,1,1,1,1,1,2,2,2,2,4];
  var ALIGN=[[],[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,50]];
  function bestVer(len){for(var v=1;v<=10;v++)if(len<=CAPS[v])return v;return 1}
  function mkMatrix(v){var s=17+v*4,m=[],r=[];for(var i=0;i<s;i++){m[i]=new Array(s).fill(0);r[i]=new Array(s).fill(0)}return{m:m,r:r,s:s}}
  function setFinder(mt,x,y){for(var dy=0;dy<7;dy++)for(var dx=0;dx<7;dx++){var v=(dx===0||dx===6||dy===0||dy===6||(dx>=2&&dx<=4&&dy>=2&&dy<=4))?1:0;mt.m[y+dy][x+dx]=v;mt.r[y+dy][x+dx]=1}}
  function setAlign(mt,cx,cy){for(var dy=-2;dy<=2;dy++)for(var dx=-2;dx<=2;dx++){var v=(Math.abs(dx)===2||Math.abs(dy)===2||(dx===0&&dy===0))?1:0;mt.m[cy+dy][cx+dx]=v;mt.r[cy+dy][cx+dx]=1}}
  function setTiming(mt){for(var i=8;i<mt.s-8;i++){mt.m[6][i]=i%2;mt.r[6][i]=1;mt.m[i][6]=i%2;mt.r[i][6]=1}}
  function setFormat(mt,ec,mask){
    var rem=(ec<<3)|mask;
    for(var i=0;i<10;i++){if(rem&(1<<9))rem=(rem<<1)^0x537;else rem<<=1;rem&=0x3ff}
    rem^=0x5412;
    for(var i=0;i<6;i++)mt.m[8][(i<8)?(i):(i+1)]=(rem>>(14-i))&1;
    mt.m[8][7]=(rem>>8)&1;mt.m[8][8]=(rem>>7)&1;mt.m[7][8]=(rem>>6)&1;
    for(var i=0;i<6;i++)mt.m[5-i][8]=(rem>>i)&1;
    for(var i=0;i<8;i++)mt.m[mt.s-1-i][8]=(rem>>(14-i))&1;
    for(var i=0;i<7;i++)mt.m[8][mt.s-7+i]=(rem>>i)&1;
    for(var i=0;i<=8;i++){mt.r[8][i]=1;mt.r[i][8]=1;mt.r[mt.s-1-i][8]=1;mt.r[8][mt.s-1-i]=1}
  }
  function placeData(mt,bits){
    var bi=0,x=mt.s-1,up=true;
    while(x>=0){
      if(x===6)x--;
      for(var i=0;i<mt.s;i++){
        var y=up?mt.s-1-i:i;
        if(!mt.r[y][x]&&bi<bits.length)mt.m[y][x]=bits[bi++];
        if(x>0&&!mt.r[y][x-1]&&bi<bits.length)mt.m[y][x-1]=bits[bi++];
      }
      x-=2;up=!up;
    }
  }
  var MASKS=[
    function(y,x){return(x+y)%2===0},
    function(y){return y%2===0},
    function(y,x){return x%3===0},
    function(y,x){return(x+y)%3===0},
    function(y,x){return((y>>1)+(x/3|0))%2===0},
    function(y,x){return(x*y)%2+(x*y)%3===0},
    function(y,x){return((x*y)%2+(x*y)%3)%2===0},
    function(y,x){return((x+y)%2+(x*y)%3)%2===0}
  ];
  function applyMask(mt,fn){for(var y=0;y<mt.s;y++)for(var x=0;x<mt.s;x++)if(!mt.r[y][x]&&fn(y,x))mt.m[y][x]^=1}
  function encode(text,ecLvl){
    ecLvl=ecLvl||'M';var EC_MAP={L:1,M:0,Q:3,H:2};
    var data=utf8Encode(text),v=bestVer(data.length+2),mt=mkMatrix(v);
    setFinder(mt,0,0);setFinder(mt,mt.s-7,0);setFinder(mt,0,mt.s-7);
    var ac=ALIGN[v]||[];
    for(var i=0;i<ac.length;i++)for(var j=0;j<ac.length;j++){var cx=ac[i],cy=ac[j];if(!mt.r[cy][cx])setAlign(mt,cx,cy)}
    setTiming(mt);mt.m[mt.s-8][8]=1;mt.r[mt.s-8][8]=1;
    for(var i=0;i<=8;i++){mt.r[8][i]=1;mt.r[i][8]=1;mt.r[mt.s-1-i][8]=1;mt.r[8][mt.s-1-i]=1}
    if(v>=7)for(var i=0;i<6;i++)for(var j=0;j<3;j++){mt.r[i][mt.s-11+j]=1;mt.r[mt.s-11+j][i]=1}
    var bits=[];bits.push(0,1,0,0);
    var cb=data.length.toString(2).padStart(8,'0');for(var i=0;i<8;i++)bits.push(parseInt(cb[i]));
    for(var i=0;i<data.length;i++)for(var b=7;b>=0;b--)bits.push((data[i]>>b)&1);
    for(var i=0;i<4;i++)bits.push(0);while(bits.length%8)bits.push(0);
    var pb=[0xEC,0x11],pi=0;while(bits.length<CAPS[v]*8){var p=pb[pi%2];for(var b=7;b>=0;b--)bits.push((p>>b)&1);pi++}
    var bytes=[];for(var i=0;i<bits.length;i+=8){var by=0;for(var b=0;b<8;b++)by=(by<<1)|bits[i+b];bytes.push(by)}
    var ec=ECWORDS[v],nb=BLOCKS[v],bs=Math.floor(CAPS[v]/nb),lb=CAPS[v]%nb;
    var db=[],eb=[];var off=0;
    for(var i=0;i<nb;i++){var sz=bs+(i<lb?1:0);var bl=bytes.slice(off,off+sz);off+=sz;db.push(bl);eb.push(encodeRs(bl,ec))}
    var ib=[];var mx=Math.max.apply(null,db.map(function(b){return b.length}));
    for(var i=0;i<mx;i++)for(var j=0;j<nb;j++)if(i<db[j].length){var v2=db[j][i];for(var b2=7;b2>=0;b2--)ib.push((v2>>b2)&1)}
    for(var i=0;i<ec;i++)for(var j=0;j<nb;j++){var v2=eb[j][i];for(var b2=7;b2>=0;b2--)ib.push((v2>>b2)&1)}
    placeData(mt,ib);
    var bm=0,bp=Infinity;
    for(var m=0;m<8;m++){
      var tm={m:mt.m.map(function(r){return r.slice()}),r:mt.r,s:mt.s};applyMask(tm,MASKS[m]);var p=0;
      for(var y=0;y<tm.s;y++){var run=1;for(var x=1;x<tm.s;x++){if(tm.m[y][x]===tm.m[y][x-1])run++;else{if(run>=5)p+=run-2;run=1}}if(run>=5)p+=run-2}
      for(var x=0;x<tm.s;x++){var run=1;for(var y=1;y<tm.s;y++){if(tm.m[y][x]===tm.m[y-1][x])run++;else{if(run>=5)p+=run-2;run=1}}if(run>=5)p+=run-2}
      if(p<bp){bp=p;bm=m}
    }
    applyMask(mt,MASKS[bm]);setFormat(mt,EC_MAP[ecLvl],bm);
    return mt;
  }
  function toSvg(mt,fg,bg,q){
    q=q||4;var s=mt.s,ts=s+q*2;
    var svg='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '+ts+' '+ts+'" shape-rendering="crispEdges">';
    svg+='<rect width="'+ts+'" height="'+ts+'" fill="'+bg+'"/>';
    for(var y=0;y<s;y++)for(var x=0;x<s;x++)if(mt.m[y][x])svg+='<rect x="'+(x+q)+'" y="'+(y+q)+'" width="1" height="1" fill="'+fg+'"/>';
    return svg+'</svg>';
  }
  return{encode:encode,toSvg:toSvg}
})();

function wifiBuildString(){
  var enc=document.getElementById('wEnc').value;
  var ssid=document.getElementById('wSSID').value;
  var hidden=document.getElementById('wHidden').classList.contains('on');
  var pass=document.getElementById('wPass').value;
  var s='WIFI:';
  s+='S:'+ssid.replace(/([\\;:,"])/g,'\\$1')+';';
  if(enc==='nopass'){s+=';;';return s;}
  s+='T:'+enc+';';
  if(enc==='WPA2-EAP'){
    s+='E:'+document.getElementById('wEap').value+';';
    var anon=document.getElementById('wEapAnon').classList.contains('on');
    if(anon)s+='A:'+document.getElementById('wEapId').value+';';
    else if(document.getElementById('wEapId').value)s+='I:'+document.getElementById('wEapId').value+';';
    var p2=document.getElementById('wEapP2').value;
    if(p2!=='None')s+='PH2:'+p2+';';
  }
  s+='P:'+pass.replace(/([\\;:,"])/g,'\\$1')+';';
  if(hidden)s+='H:true;';
  return s+';';
}

function wifiGen(){
  var str=wifiBuildString();
  var fg=document.getElementById('wFg').value;
  var bg=document.getElementById('wBg').value;
  try{
    var mat=QR.encode(str,'M');
    var svg=QR.toSvg(mat,fg,bg,4);
    document.getElementById('wO2').innerHTML=svg;
  }catch(e){
    document.getElementById('wO2').innerHTML='<div style="color:#f56c6c;font-size:12px">Error: '+e.message+'</div>';
  }
}

function wifiToggle(){
  var enc=document.getElementById('wEnc').value;
  document.getElementById('wifiPwdBox').style.display=(enc==='nopass')?'none':'block';
  document.getElementById('wifiEapBox').style.display=(enc==='WPA2-EAP')?'block':'none';
  wifiGen();
}

function wifiDownload(){
  var str=wifiBuildString();
  var fg=document.getElementById('wFg').value;
  var bg=document.getElementById('wBg').value;
  try{
    var mat=QR.encode(str,'M');
    var svg=QR.toSvg(mat,fg,bg,4);
    var blob=new Blob([svg],{type:'image/svg+xml'});
    var url=URL.createObjectURL(blob);
    var a=document.createElement('a');
    a.href=url;a.download='wifi-qr.svg';a.click();
    URL.revokeObjectURL(url);
  }catch(e){alert('Error: '+e.message);}
}

// Init
wifiGen();
</script>
</body></html>
HTML;
    }
}
