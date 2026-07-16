<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * Regex Tester — 1:1 with the legacy webview tool (`__p.regex`).
 *
 * ECMAScript-flavoured regex matching (flags g/i/m/s/u/v, named capture
 * groups, match indices via the `d` flag) is only available in a browser
 * runtime, so the whole tool is rendered as a self-contained HTML document
 * inside a WebView, reusing the legacy `rxMatch`/`rxCompute` behaviour
 * verbatim. The native layer only hosts the WebView and fills the content area.
 */
final class RegexTesterPanel implements Panel
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
  --el-color-primary:#409eff;--el-color-danger:#f56c6c;--el-color-warning:#e6a23c;
  --el-text-color-primary:#303133;--el-text-color-regular:#606266;--el-text-color-secondary:#909399;--el-text-color-placeholder:#a8abb2;
  --el-border-color:#dcdfe6;--el-border-color-light:#e4e7ed;
  --el-fill-color:#f0f2f5;--el-fill-color-blank:#fff;--el-bg-color:#fff;--el-bg-color-page:#f2f3f5;
  --el-border-radius-base:4px;--el-font-size-base:13px;
  --el-font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'PingFang SC','Microsoft YaHei',sans-serif;
  --dm:#909399;--pr:#409eff;--bg:#f5f7fa;--sf:#fff;--bd:#e4e7ed;--tx:#303133;
}
body.dark{
  --el-text-color-primary:#e5eaf3;--el-text-color-regular:#cfd3dc;--el-text-color-secondary:#a3a6ad;--el-text-color-placeholder:#8d9095;
  --el-border-color:#4c4d4f;--el-border-color-light:#414243;--el-fill-color:#262727;--el-fill-color-blank:#1d1e1f;
  --el-bg-color:#1d1e1f;--el-bg-color-page:#141414;
  --dm:#7a7d90;--pr:#6c5ce7;--bg:#1a1a2e;--sf:#16213e;--bd:#2a2a4a;--tx:#e4e6ef;
}
*{box-sizing:border-box}html,body{margin:0;padding:0;height:100%}
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow:auto}
.tool-main{padding:16px}
.ep-card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px;margin-bottom:12px}
.ep-card__header{font-weight:600;font-size:15px;margin-bottom:12px}
.ep-form-item{margin-bottom:12px}
.ep-form-item>label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.ep-input,.ep-textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:inherit;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.ep-input:focus,.ep-textarea:focus{border-color:var(--pr)}
.ep-textarea{resize:vertical;min-height:60px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.ep-flags{display:flex;flex-wrap:wrap;gap:4px 14px;margin:6px 0}
.ep-check{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer}
.ep-btn{border:1px solid var(--el-border-color);background:var(--el-fill-color-blank);color:var(--el-text-color-primary);border-radius:var(--el-border-radius-base);padding:7px 14px;font-size:13px;cursor:pointer}
.ep-btn:hover{border-color:var(--pr);color:var(--pr)}
.ep-btn--link{background:none;border:none;color:var(--el-color-primary);padding:4px 6px;cursor:pointer}
.ep-btn--link:hover{text-decoration:underline}
.ep-btn--small{padding:4px 10px;font-size:12px}
.ep-alert{border-radius:var(--el-border-radius-base);padding:10px 12px;font-size:13px;margin-bottom:10px}
.ep-alert--warning{background:rgba(230,162,60,.12);color:var(--el-color-warning)}
.ep-alert--danger{background:rgba(245,108,108,.12);color:var(--el-color-danger)}
.ep-table{width:100%;border-collapse:collapse;font-size:13px}
.ep-table th,.ep-table td{border:1px solid var(--el-border-color);padding:6px 10px;text-align:left;vertical-align:top}
.ep-table th{background:var(--el-fill-color);font-weight:600}
.ep-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.muted{color:var(--el-text-color-secondary);font-size:13px}
</style></head>
<body class="dark"><div class="tool-main">

<div class="ep-card">
  <div class="ep-card__header">正则测试 Regex Tester</div>
  <div class="ep-form-item"><label>正则表达式 Pattern</label>
    <textarea id="rxP" class="ep-textarea" rows="3" placeholder="输入正则" oninput="rxCompute()">[a-z]+</textarea>
  </div>
  <div class="ep-flags">
    <label class="ep-check"><input type="checkbox" id="rx_g" checked onchange="rxCompute()"> g 全局</label>
    <label class="ep-check"><input type="checkbox" id="rx_i" onchange="rxCompute()"> i 忽略大小写</label>
    <label class="ep-check"><input type="checkbox" id="rx_m" onchange="rxCompute()"> m 多行</label>
    <label class="ep-check"><input type="checkbox" id="rx_s" checked onchange="rxCompute()"> s 单行(dotAll)</label>
    <label class="ep-check"><input type="checkbox" id="rx_u" onchange="rxCompute()"> u Unicode</label>
    <label class="ep-check"><input type="checkbox" id="rx_v" onchange="rxCompute()"> v Unicode 集合</label>
  </div>
  <div class="ep-form-item"><label>测试文本 Subject</label>
    <textarea id="rxT" class="ep-textarea" rows="5" placeholder="输入待匹配文本" oninput="rxCompute()">Hello World</textarea>
  </div>
</div>

<div class="ep-card">
  <div class="ep-card__header">匹配结果 Matches</div>
  <div id="rxMatches"></div>
</div>

<script>
function g(id){return document.getElementById(id);}
function esc(s){return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function rxMatch(regex,text,flags){
  var re=new RegExp(regex,flags);var results=[];var lastIndex=-1;var m=re.exec(text);
  while(m!==null){
    if(re.lastIndex===lastIndex||m[0]==='')break;
    var idx=m.indices;var caps=[];
    Object.keys(m).forEach(function(k){
      if(k!=='0'&&/^\d+$/.test(k)&&idx&&idx[+k])caps.push({name:k,value:m[k],start:idx[+k][0],end:idx[+k][1]});
    });
    var groups=[];
    Object.keys(m.groups||{}).forEach(function(k){
      if(idx&&idx.groups&&idx.groups[k])groups.push({name:k,value:m.groups[k],start:idx.groups[k][0],end:idx.groups[k][1]});
    });
    results.push({index:m.index,value:m[0],captures:caps,groups:groups});
    lastIndex=re.lastIndex;m=re.exec(text);
  }
  return results;
}
function rxCompute(){
  var p=g('rxP').value,t=g('rxT').value,out=g('rxMatches');
  if(!p){out.innerHTML='<div class="ep-alert ep-alert--warning">请输入正则表达式</div>';return;}
  var flags='d';
  if(g('rx_g').checked)flags+='g';
  if(g('rx_i').checked)flags+='i';
  if(g('rx_m').checked)flags+='m';
  if(g('rx_s').checked)flags+='s';
  if(g('rx_u').checked)flags+='u';else if(g('rx_v').checked)flags+='v';
  var results;
  try{results=rxMatch(p,t,flags);}catch(e){out.innerHTML='<div class="ep-alert ep-alert--danger">无效的正则: '+esc(e.message)+'</div>';return;}
  if(!results.length){out.innerHTML='<div class="ep-alert ep-alert--warning">无匹配</div>';return;}
  var rows=results.map(function(r){
    var caps=r.captures.length?r.captures.map(function(c){return '"'+c.name+'" = '+esc(c.value)+' ['+c.start+'-'+c.end+']';}).join('<br>'):'-';
    var grps=r.groups.length?r.groups.map(function(c){return '"'+c.name+'" = '+esc(c.value)+' ['+c.start+'-'+c.end+']';}).join('<br>'):'-';
    return '<tr><td>'+r.index+'</td><td class="ep-mono">'+esc(r.value)+'</td><td class="ep-mono">'+caps+'</td><td class="ep-mono">'+grps+'</td></tr>';
  });
  out.innerHTML='<table class="ep-table"><thead><tr><th>位置</th><th>匹配</th><th>捕获组</th><th>命名组</th></tr></thead><tbody>'+rows.join('')+'</tbody></table>';
}
rxCompute();
</script>
</body></html>
HTML;
    }
}
