<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * WebSocket / SSE client — 1:1 with the legacy webview tool (`__p.wss`).
 *
 * A real WebSocket / EventSource needs a browser runtime, so the whole tool
 * (connection card, Params/Headers/Auth/WS-opts/SSE-opts tabs, send card, SSE
 * info, logs) is rendered as a self-contained HTML document inside a WebView,
 * reusing the legacy markup + behaviour verbatim. The native layer only hosts
 * the WebView and fills the content area.
 */
final class WsSsePanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $width, height: $height);
        $leaf->style->grow = 1.0;

        return $leaf;
    }

    private static function initialHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html><html lang="zh"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root{
  --el-color-primary:#409eff;--el-color-success:#67c23a;--el-color-warning:#e6a23c;--el-color-danger:#f56c6c;--el-color-info:#909399;
  --el-text-color-primary:#303133;--el-text-color-regular:#606266;--el-text-color-secondary:#909399;--el-text-color-placeholder:#a8abb2;
  --el-border-color:#dcdfe6;--el-border-color-light:#e4e7ed;--el-border-color-lighter:#ebeef5;
  --el-fill-color:#f0f2f5;--el-fill-color-light:#f5f7fa;--el-fill-color-blank:#fff;
  --el-bg-color:#fff;--el-bg-color-page:#f2f3f5;
  --el-border-radius-base:4px;--el-font-size-small:12px;--el-font-size-base:13px;
  --el-font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'PingFang SC','Microsoft YaHei',sans-serif;
  --dm:#909399;--pr:#409eff;--bg:#f5f7fa;--sf:#fff;--bd:#e4e7ed;--tx:#303133;
}
body.dark{
  --el-text-color-primary:#e5eaf3;--el-text-color-regular:#cfd3dc;--el-text-color-secondary:#a3a6ad;--el-text-color-placeholder:#8d9095;
  --el-border-color:#4c4d4f;--el-border-color-light:#414243;--el-border-color-lighter:#363637;
  --el-fill-color:#262727;--el-fill-color-light:#1f1f1f;--el-fill-color-blank:#1d1e1f;
  --el-bg-color:#1d1e1f;--el-bg-color-page:#141414;
  --dm:#7a7d90;--pr:#6c5ce7;--bg:#1a1a2e;--sf:#16213e;--bd:#2a2a4a;--tx:#e4e6ef;
}
*{box-sizing:border-box}html,body{margin:0;padding:0;height:100%}
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow:auto}
.tool-main{padding:16px}
.ep-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:760px){.ep-grid2{grid-template-columns:1fr}}
.flex{display:flex}.gap-2{gap:8px}.items-end{align-items:flex-end}.justify-between{justify-content:space-between}
.muted{color:var(--el-text-color-secondary);font-size:13px}
.ep-hidden{display:none!important}
.ep-card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px;margin-bottom:12px}
.ep-card__header{font-weight:600;font-size:15px;margin-bottom:12px}
.ep-form-item{margin-bottom:12px}
.ep-form-item>label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.ep-input,.ep-textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:inherit;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.ep-input:focus,.ep-textarea:focus{border-color:var(--pr)}
.ep-textarea{resize:vertical;min-height:60px}
.ep-btn{border:1px solid var(--el-border-color);background:var(--el-fill-color-blank);color:var(--el-text-color-primary);border-radius:var(--el-border-radius-base);padding:7px 14px;font-size:13px;cursor:pointer}
.ep-btn:hover{border-color:var(--pr);color:var(--pr)}
.ep-btn--primary{background:var(--el-color-primary);border-color:var(--el-color-primary);color:#fff}
.ep-btn--success{background:var(--el-color-success);border-color:var(--el-color-success);color:#fff}
.ep-btn--danger{background:var(--el-color-danger);border-color:var(--el-color-danger);color:#fff}
.ep-btn--link{background:none;border:none;color:var(--el-color-primary);padding:4px 6px}
.ep-btn--link:hover{text-decoration:underline}
.ep-btn--small{padding:4px 10px;font-size:12px}
.ep-tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;background:var(--el-fill-color);color:var(--el-text-color-regular)}
.ep-tag--success{background:rgba(103,194,58,.15);color:var(--el-color-success)}
.ep-tag--warning{background:rgba(230,162,60,.15);color:var(--el-color-warning)}
.ep-tag--danger{background:rgba(245,108,108,.15);color:var(--el-color-danger)}
.ep-tag--info{background:rgba(144,147,153,.18);color:var(--el-text-color-regular)}
.ep-tag--primary{background:rgba(64,158,255,.15);color:var(--el-color-primary)}
.ep-alert{border-radius:var(--el-border-radius-base);padding:10px 12px;font-size:13px;margin-bottom:10px}
.ep-alert--info{background:rgba(144,147,153,.12);color:var(--el-text-color-regular)}
.ep-alert--warning{background:rgba(230,162,60,.12);color:var(--el-color-warning)}
.ep-alert--danger{background:rgba(245,108,108,.12);color:var(--el-color-danger)}
.ep-radio-group{display:flex;gap:16px}.ep-radio{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer}
.ep-radio.is-active{color:var(--pr);font-weight:600}
.wss-tabbar{display:flex;flex-wrap:wrap;gap:4px;border-bottom:1px solid var(--el-border-color);margin-bottom:12px}
.wss-tab{border:none;background:transparent;padding:8px 12px;cursor:pointer;font-size:13px;color:var(--el-text-color-regular);border-bottom:2px solid transparent;margin-bottom:-1px}
.wss-tab:hover{color:var(--pr)}.wss-tab.active{color:var(--pr);border-bottom-color:var(--pr);font-weight:600}
.wss-row{display:flex;gap:8px;margin-bottom:8px;align-items:center}
.wss-row .wss-k,.wss-row .wss-v{flex:1;min-width:0}
.wss-logs{max-height:340px;overflow:auto;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px}
.wss-log-item{padding:8px;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);margin-bottom:8px;background:var(--el-fill-color-blank)}
.wss-log-pre{margin:0;white-space:pre-wrap;word-break:break-all;font-size:12px;line-height:1.5;color:var(--el-text-color-primary)}
</style></head>
<body class="dark"><div class="tool-main">

<div class="ep-card">
  <div class="ep-card__header">WebSocket / SSE 客户端</div>
  <div class="ep-form-item"><label>协议</label>
    <div class="ep-radio-group">
      <label class="ep-radio is-active"><input type="radio" name="wProto" value="websocket" checked onchange="wssProtoChange()"> WebSocket</label>
      <label class="ep-radio"><input type="radio" name="wProto" value="sse" onchange="wssProtoChange()"> SSE</label>
    </div>
  </div>
  <div class="flex gap-2 items-end" style="margin:12px 0">
    <input id="wUrl" class="ep-input" value="wss://echo.websocket.events" placeholder="ws://localhost:3000/ws" onkeyup="if(event.key==='Enter')wssConnect()" style="flex:1">
    <button class="ep-btn ep-btn--primary" id="wConnBtn" onclick="wssConnect()">连接</button>
    <button class="ep-btn" id="wDiscBtn" onclick="wssDisconnect()">断开</button>
    <span id="wStatus" class="ep-tag ep-tag--info">未连接</span>
  </div>
  <div id="wWsAlert" class="ep-alert ep-alert--info" style="margin-top:8px">浏览器 WebSocket 不支持自定义请求头</div>
  <div id="wErr" class="ep-alert ep-alert--danger ep-hidden"></div>
</div>

<div class="ep-grid2" style="margin-top:12px">
  <div class="ep-card">
    <div class="wss-tabbar">
      <button type="button" class="wss-tab active" onclick="wssSwitchTab(this,'wPaneParams')">参数</button>
      <button type="button" class="wss-tab" onclick="wssSwitchTab(this,'wPaneHeaders')">请求头</button>
      <button type="button" class="wss-tab" onclick="wssSwitchTab(this,'wPaneAuth')">认证</button>
      <button type="button" class="wss-tab" id="wTabWsOpts" onclick="wssSwitchTab(this,'wPaneWsOpts')">WS 选项</button>
      <button type="button" class="wss-tab" id="wTabSseOpts" style="display:none" onclick="wssSwitchTab(this,'wPaneSseOpts')">SSE 选项</button>
    </div>
    <div class="wss-pane" id="wPaneParams"><div id="wParamRows"></div>
      <button class="ep-btn ep-btn--small" onclick="wssAddParam()">添加参数</button></div>
    <div class="wss-pane" id="wPaneHeaders" hidden>
      <div class="ep-alert ep-alert--warning" style="margin-bottom:8px">WebSocket 请求头无法自定义（浏览器限制）</div>
      <div id="wHeaderRows"></div><button class="ep-btn ep-btn--small" onclick="wssAddHeader()">添加请求头</button></div>
    <div class="wss-pane" id="wPaneAuth" hidden>
      <div class="ep-form-item"><label>Bearer Token</label><textarea id="wBearer" class="ep-textarea" rows="4"></textarea></div>
      <button class="ep-btn ep-btn--small" onclick="wssApplyAuth()">应用认证</button></div>
    <div class="wss-pane" id="wPaneWsOpts" hidden>
      <div class="ep-form-item"><label>子协议</label><input id="wSub" class="ep-input" placeholder="graphql-transport-ws, chat"></div>
      <div class="ep-form-item"><label style="display:flex;align-items:center;gap:6px"><input type="checkbox" id="wHb" onchange="wssHbToggle()"> 心跳</label></div>
      <div id="wHbOpts" style="display:none">
        <div class="ep-form-item"><label>间隔(秒)</label><input id="wHbInt" class="ep-input" value="30"></div>
        <div class="ep-form-item"><label>心跳消息</label><textarea id="wHbMsg" class="ep-textarea" rows="4">ping</textarea></div>
      </div>
    </div>
    <div class="wss-pane" id="wPaneSseOpts" hidden>
      <div class="ep-form-item"><label>事件过滤</label><input id="wSseFilter" class="ep-input" placeholder="message"></div>
      <div class="ep-form-item"><label>Last-Event-ID</label><input id="wSseLastId" class="ep-input"></div>
    </div>
  </div>

  <div id="wRightCol">
    <div id="wSendCard"><div class="ep-card">
      <div class="ep-form-item"><label>消息类型</label>
        <div class="ep-radio-group">
          <label class="ep-radio is-active"><input type="radio" name="wMsgMode" value="json" checked> JSON</label>
          <label class="ep-radio"><input type="radio" name="wMsgMode" value="text"> Text</label>
        </div>
      </div>
      <div class="ep-form-item"><label>发送消息</label><textarea id="wMsg" class="ep-textarea" rows="10"></textarea></div>
      <div class="flex gap-2"><button class="ep-btn ep-btn--primary" onclick="wssSend()">发送</button><button class="ep-btn" onclick="wssFormat()">格式化</button></div>
    </div></div>
    <div id="wSseInfoCard" style="display:none"><div class="ep-card">
      <div class="ep-alert ep-alert--info">SSE 通过 EventSource 接收服务器推送事件</div>
      <div class="muted" style="font-size:13px;margin-top:8px">支持服务器推送事件<br>时长 <span id="wDur">0s</span></div>
    </div></div>
  </div>
</div>

<div style="margin-top:12px"><div class="ep-card">
  <div class="flex justify-between" style="margin-bottom:8px">
    <span class="muted" style="font-size:13px"><span id="wLogCount">0</span> 条 · 时长 <span id="wDur2">0s</span></span>
    <button class="ep-btn ep-btn--link" onclick="wssClearLogs()">清空</button>
  </div>
  <div id="wO" class="wss-logs"><div class="muted" style="padding:8px">暂无日志</div></div>
</div></div>

<script>
var WSS_PROTO='websocket', WSS_START=0, WSS_SOCK=null, WSS_ES=null, WSS_HB=null, WSS_LOGS=[];
function g(id){return document.getElementById(id);}
function esc(s){return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
var I18N={zh:{
  wss_protocol:'协议',wss_connect:'连接',wss_disconnect:'断开',wss_disconnected:'已断开',wss_connected:'已连接',wss_error:'错误',
  wss_ws_hdr_note:'浏览器 WebSocket 不支持自定义请求头',wss_hdr_note:'WebSocket 请求头无法自定义（浏览器限制）',
  wss_params:'参数',wss_headers:'请求头',wss_auth:'认证',wss_ws_opts:'WS 选项',wss_sse_opts:'SSE 选项',
  wss_add_param:'添加参数',wss_add_header:'添加请求头',wss_key:'键',wss_value:'值',wss_delete:'删除',
  wss_bearer:'Bearer Token',wss_apply_auth:'应用认证',wss_subprotocols:'子协议',wss_heartbeat:'心跳',
  wss_interval:'间隔(秒)',wss_hb_msg:'心跳消息',wss_event_filter:'事件过滤',wss_last_event_id:'Last-Event-ID',
  wss_msg_type:'消息类型',wss_send_msg:'发送消息',wss_send:'发送',wss_format:'格式化',
  wss_sse_info:'SSE 通过 EventSource 接收服务器推送事件',wss_supported:'支持服务器推送事件',wss_duration:'时长',
  wss_entries:'条',wss_logs:'日志',wss_clear:'清空',wss_nologs:'暂无日志',wss_title:'WebSocket / SSE 客户端'
},en:{}};
var lang='zh';
function _t(k){return (I18N[lang]&&I18N[lang][k])||(I18N.en[k])||k;}

function wssStatus(type, text){
  var el=g('wStatus'); if(!el) return;
  el.className='ep-tag ep-tag--'+(type==='connected'?'success':type==='error'?'danger':'info');
  el.textContent=text;
}
function wssDur(){
  if(!WSS_START) return '0s';
  var s=Math.floor((Date.now()-WSS_START)/1000);
  return s>=60?Math.floor(s/60)+'m '+(s%60)+'s':s+'s';
}
function wssTick(){
  var d=wssDur();
  if(g('wDur')) g('wDur').textContent=d;
  if(g('wDur2')) g('wDur2').textContent=d;
}
function wssProtoChange(){
  var r=document.querySelector('input[name="wProto"]:checked');
  WSS_PROTO=r?r.value:'websocket';
  var isWs=WSS_PROTO==='websocket';
  if(g('wTabWsOpts')) g('wTabWsOpts').style.display=isWs?'':'none';
  if(g('wTabSseOpts')) g('wTabSseOpts').style.display=isWs?'none':'';
  if(g('wSendCard')) g('wSendCard').style.display=isWs?'':'none';
  if(g('wSseInfoCard')) g('wSseInfoCard').style.display=isWs?'none':'';
  if(g('wWsAlert')) g('wWsAlert').style.display=isWs?'':'none';
  var u=g('wUrl'); if(u) u.placeholder=isWs?'ws://localhost:3000/ws':'http://localhost:3000/events';
  if(!isWs && g('wPaneWsOpts') && !g('wPaneWsOpts').hasAttribute('hidden')) wssSwitchTab(document.getElementById('wTabSseOpts'),'wPaneSseOpts');
}
function wssSwitchTab(btn, paneId){
  document.querySelectorAll('.wss-tab').forEach(function(x){x.classList.remove('active');});
  if(btn) btn.classList.add('active');
  document.querySelectorAll('.wss-pane').forEach(function(p){p.hidden=true;});
  var p=g(paneId); if(p) p.hidden=false;
}
function wssRowHtml(){
  return '<div class="wss-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">'
    + '<input type="checkbox" class="wss-en" checked>'
    + '<input class="ep-input wss-k" placeholder="'+_t('wss_key')+'">'
    + '<input class="ep-input wss-v" placeholder="'+_t('wss_value')+'">'
    + '<button type="button" class="ep-btn ep-btn--link" onclick="wssRemoveRow(this)">'+_t('wss_delete')+'</button>'
    + '</div>';
}
function wssAddParam(){var c=g('wParamRows'); if(c) c.insertAdjacentHTML('beforeend', wssRowHtml());}
function wssAddHeader(){var c=g('wHeaderRows'); if(c) c.insertAdjacentHTML('beforeend', wssRowHtml());}
function wssRemoveRow(btn){var row=btn.closest('.wss-row'); if(row) row.remove();}
function wssApplyAuth(){
  var t=g('wBearer'); if(!t||!t.value.trim()) return;
  var c=g('wHeaderRows'); if(!c) return;
  var row=document.createElement('div'); row.className='wss-row'; row.style.cssText='display:flex;gap:8px;margin-bottom:8px;align-items:center';
  row.innerHTML='<input type="checkbox" class="wss-en" checked>'
    + '<input class="ep-input wss-k" value="Authorization" readonly>'
    + '<input class="ep-input wss-v" value="Bearer '+esc(t.value.trim())+'">'
    + '<button type="button" class="ep-btn ep-btn--link" onclick="wssRemoveRow(this)">'+_t('wss_delete')+'</button>';
  c.appendChild(row);
}
function wssHbToggle(){var h=g('wHb'); var o=g('wHbOpts'); if(o) o.style.display=(h&&h.checked)?'block':'none';}
function wssFormat(){
  var m=g('wMsg'); if(!m) return;
  try{ m.value=JSON.stringify(JSON.parse(m.value),null,2); }catch(e){ wssLog('error','Format',e.message); }
}
function wssClearLogs(){
  WSS_LOGS=[]; var el=g('wO'); if(el) el.innerHTML='<div class="muted" style="padding:8px">'+_t('wss_nologs')+'</div>';
  if(g('wLogCount')) g('wLogCount').textContent='0';
}
function wssLog(type, label, content){
  var el=g('wO'); if(!el) return;
  WSS_LOGS.push({type:type,label:label,content:content,time:new Date()});
  var tagCls=type==='error'?'danger':(type==='received'||type==='event')?'success':type==='sent'?'primary':'info';
  var size=content?(''+content.length+' B'):'';
  var t=new Date().toLocaleTimeString('zh-CN',{hour12:false});
  var entry='<div class="wss-log-item">'
    + '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:6px">'
    + '<span class="ep-tag ep-tag--'+tagCls+'">'+type+'</span>'
    + '<span style="font-weight:600">'+esc(label)+'</span>'
    + '<span class="muted" style="font-size:12px">'+t+'</span>'
    + '<span class="muted" style="font-size:12px">'+size+'</span>'
    + '</div>';
  if(content) entry+='<pre class="wss-log-pre">'+esc(content)+'</pre>';
  entry+='</div>';
  if(WSS_LOGS.length===1) el.innerHTML='';
  el.innerHTML+=entry;
  el.scrollTop=el.scrollHeight;
  if(g('wLogCount')) g('wLogCount').textContent=WSS_LOGS.length;
}
function wssConnect(){
  var url=g('wUrl')?g('wUrl').value.trim():'';
  if(!url) return;
  wssDisconnect(true);
  WSS_START=Date.now();
  wssTick();
  if(WSS_PROTO==='sse'){
    try{
      WSS_ES=new EventSource(url);
      WSS_ES.onopen=function(){wssStatus('connected',_t('wss_connected')); wssLog('info','SSE','Connection opened');};
      WSS_ES.onmessage=function(e){wssLog('event','message',e.data);};
      WSS_ES.onerror=function(){wssStatus('error',_t('wss_error')); wssLog('error','SSE','Connection error');};
    }catch(e){wssStatus('error',_t('wss_error')); wssLog('error','SSE',e.message);}
  }else{
    try{
      var sub=g('wSub')?g('wSub').value.trim():'';
      var protoList=sub?sub.split(',').map(function(s){return s.trim();}).filter(Boolean):undefined;
      WSS_SOCK=protoList?new WebSocket(url,protoList):new WebSocket(url);
      WSS_SOCK.onopen=function(){wssStatus('connected',_t('wss_connected')); wssLog('info','WS','Connection opened'); wssHbStart();};
      WSS_SOCK.onmessage=function(e){wssLog('received','message',e.data);};
      WSS_SOCK.onerror=function(){wssStatus('error',_t('wss_error')); wssLog('error','WS','Error');};
      WSS_SOCK.onclose=function(){wssStatus('info',_t('wss_disconnected')); wssLog('info','WS','Connection closed'); wssHbStop();};
    }catch(e){wssStatus('error',_t('wss_error')); wssLog('error','WS',e.message);}
  }
}
function wssHbStart(){
  var h=g('wHb'); if(!h||!h.checked) return;
  var iv=parseInt(g('wHbInt')?g('wHbInt').value:'30',10)||30;
  var msg=g('wHbMsg')?g('wHbMsg').value:'ping';
  WSS_HB=setInterval(function(){ if(WSS_SOCK&&WSS_SOCK.readyState===1){ WSS_SOCK.send(msg); wssLog('sent','heartbeat',msg); } }, iv*1000);
}
function wssHbStop(){ if(WSS_HB){ clearInterval(WSS_HB); WSS_HB=null; } }
function wssDisconnect(silent){
  wssHbStop();
  if(WSS_SOCK){ try{WSS_SOCK.close();}catch(e){} WSS_SOCK=null; }
  if(WSS_ES){ try{WSS_ES.close();}catch(e){} WSS_ES=null; }
  WSS_START=0; wssTick();
  if(!silent){ wssStatus('info',_t('wss_disconnected')); wssLog('info','WS','Disconnected'); }
}
function wssSend(){
  var m=g('wMsg')?g('wMsg').value:'';
  if(WSS_SOCK && m){ WSS_SOCK.send(m); wssLog('sent','message',m); g('wMsg').value=''; }
}
function __init_wss(){ wssClearLogs(); wssProtoChange(); wssHbToggle(); }
__init_wss();
</script>
</body></html>
HTML;
    }
}
