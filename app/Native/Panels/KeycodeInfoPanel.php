<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * Keycode Info — 1:1 with the legacy webview tool (`__p.keycode`).
 *
 * Live key capture needs a real `keydown` event, which only a browser runtime
 * can provide, so the whole tool is rendered as a self-contained HTML document
 * inside a WebView, reusing the legacy `kcCb`/`kcClear` behaviour verbatim.
 * The native layer only hosts the WebView and fills the content area.
 */
final class KeycodeInfoPanel implements Panel
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
  --el-color-primary:#409eff;--el-color-danger:#f56c6c;
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
.flex{display:flex}.gap-2{gap:8px}.items-center{align-items:center}
.ep-form-item{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.ep-form-item>label{width:90px;flex-shrink:0;margin:0;font-size:13px;color:var(--el-text-color-regular)}
.ep-input{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:inherit;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.ep-textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.ep-textarea{resize:vertical}
.ep-btn{border:1px solid var(--el-border-color);background:var(--el-fill-color-blank);color:var(--el-text-color-primary);border-radius:var(--el-border-radius-base);padding:7px 14px;font-size:13px;cursor:pointer}
.ep-btn:hover{border-color:var(--pr);color:var(--pr)}
.ep-btn--link{background:none;border:none;color:var(--el-color-primary);padding:4px 6px;cursor:pointer}
.ep-btn--link:hover{text-decoration:underline}
.ep-btn--small{padding:4px 10px;font-size:12px}
.ep-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.muted{color:var(--el-text-color-secondary);font-size:13px}
</style></head>
<body class="dark"><div class="tool-main">

<div class="ep-card" style="text-align:center;padding:40px 0;margin-bottom:16px">
  <div id="kcKeyDisplay" style="font-size:42px;font-weight:800;color:var(--dm);margin-bottom:8px">⌨</div>
  <div id="kcHint" style="font-size:13px;opacity:.7">按下任意键查看键码信息</div>
</div>

<div class="ep-card">
  <div class="ep-form-item"><label>Key</label><input id="kcV_key" class="ep-input ep-mono" readonly><button class="ep-btn ep-btn--link ep-btn--small" onclick="kcCopy('kcV_key')">复制</button></div>
  <div class="ep-form-item"><label>KeyCode</label><input id="kcV_keyCode" class="ep-input ep-mono" readonly><button class="ep-btn ep-btn--link ep-btn--small" onclick="kcCopy('kcV_keyCode')">复制</button></div>
  <div class="ep-form-item"><label>Code</label><input id="kcV_code" class="ep-input ep-mono" readonly><button class="ep-btn ep-btn--link ep-btn--small" onclick="kcCopy('kcV_code')">复制</button></div>
  <div class="ep-form-item"><label>Location</label><input id="kcV_loc" class="ep-input ep-mono" readonly></div>
  <div class="ep-form-item"><label>Modifiers</label><input id="kcV_mod" class="ep-input ep-mono" readonly></div>
  <div class="flex gap-2" style="margin-top:8px"><button class="ep-btn" onclick="kcClear()">清空</button></div>
</div>

<div class="ep-card">
  <div class="flex gap-2" style="margin-bottom:8px"><span class="muted">历史记录 (最近 50)</span></div>
  <textarea id="kcHist" class="ep-textarea ep-mono" rows="8" readonly style="min-height:140px"></textarea>
</div>

<script>
var kcHist=[];
var kcCb=null;
function kcSet(id,val){var el=document.getElementById(id);if(el)el.value=val;}
function kcCopy(id){var v=document.getElementById(id);if(v&&v.value&&navigator.clipboard)navigator.clipboard.writeText(v.value);}
function kcClear(){
  kcHist=[];
  var kh=document.getElementById('kcHist');if(kh)kh.value='';
  kcSet('kcV_key','');kcSet('kcV_code','');kcSet('kcV_keyCode','');kcSet('kcV_loc','');kcSet('kcV_mod','');
  var hint=document.getElementById('kcHint');if(hint)hint.textContent='按下任意键查看键码信息';
  var kd=document.getElementById('kcKeyDisplay');if(kd)kd.textContent='⌨';
}
function kcInit(){
  if(kcCb)return;
  var hint=document.getElementById('kcHint');if(hint)hint.textContent='';
  var kd=document.getElementById('kcKeyDisplay');if(kd)kd.textContent='⌨';
  kcCb=function(e){
    e.preventDefault();
    var k=e.key.length===1?e.key:'('+e.key+')';
    if(kd)kd.textContent=k;
    kcSet('kcV_key',e.key||'');
    kcSet('kcV_code',e.code||'');
    kcSet('kcV_keyCode',e.keyCode||'');
    var locs=['','Left','Right','Numpad','Mobile','Joystick'];
    kcSet('kcV_loc',(e.location<locs.length?locs[e.location]:e.location)||'');
    var mods=[];if(e.shiftKey)mods.push('Shift');if(e.ctrlKey)mods.push('Ctrl');if(e.altKey)mods.push('Alt');if(e.metaKey)mods.push('Meta');
    kcSet('kcV_mod',mods.join(' + ')||'—');
    kcHist.unshift(e.code+' ('+e.key+')');
    if(kcHist.length>50)kcHist.length=50;
    var hist=document.getElementById('kcHist');if(hist)hist.value=kcHist.join('\n');
  };
  document.addEventListener('keydown',kcCb);
}
kcInit();
</script>
</body></html>
HTML;
    }
}
