<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class TimestampPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: \Yangweijie\Ui2\Layout\LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('时间戳', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('🕐', size: 16.0), width: 24.0, height: 36.0));

        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $w, height: $height - 50);
        $leaf->style->grow = 1.0;

        $children = [$titleRow, $leaf];
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
  --el-color-primary:#409eff;--el-color-success:#67c23a;
  --el-text-color-primary:#303133;--el-text-color-regular:#606266;
  --el-border-color:#dcdfe6;--el-fill-color-blank:#fff;--el-bg-color:#fff;
  --el-border-radius-base:4px;--el-font-size-base:13px;
  --el-font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  --bg:#f5f7fa;--sf:#fff;--tx:#303133;
}
body.dark{
  --el-text-color-primary:#e5eaf3;--el-text-color-regular:#cfd3dc;
  --el-border-color:#4c4d4f;--el-fill-color-blank:#1d1e1f;--el-bg-color:#1d1e1f;
  --bg:#1a1a2e;--sf:#16213e;--tx:#e4e6ef;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow:auto;padding:16px}
.current{text-align:center;margin:20px 0}
.current-label{font-size:14px;color:var(--el-text-color-regular);margin-bottom:8px}
.current-value{font-size:48px;font-weight:700;color:var(--el-color-success);cursor:pointer;user-select:none}
.current-value:hover{opacity:0.8}
.card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px;margin-bottom:16px}
.card h3{font-size:15px;margin-bottom:12px;font-weight:600}
.row{display:flex;gap:8px;align-items:center}
.input{border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none;flex:1}
.input:focus{border-color:var(--el-color-primary)}
select{border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.arrow{font-size:20px;color:var(--el-text-color-regular)}
.result{font-size:14px;color:var(--el-color-success);margin-top:8px;font-weight:500}
.datetime-input{position:relative}
.datetime-input input{width:100%}
</style></head>
<body class="dark">

<div class="current">
  <div class="current-label">Current Unix Timestamp</div>
  <div class="current-value" id="tsNow" title="点击复制" onclick="copyText(this.textContent)"></div>
</div>

<div class="card">
  <div class="row">
    <input type="number" id="unixInput" class="input" placeholder="Unix Timestamp">
    <select id="unixUnit"><option value="s">秒</option><option value="ms">毫秒</option></select>
    <span class="arrow">→</span>
    <input type="text" id="dateOutput" class="input" readonly placeholder="日期时间">
  </div>
</div>

<div class="card">
  <div class="row">
    <input type="datetime-local" id="dateInput" class="input">
    <span class="arrow">→</span>
    <input type="number" id="unixOutput" class="input" readonly placeholder="Unix Timestamp">
    <select id="dateUnit"><option value="s">秒</option><option value="ms">毫秒</option></select>
  </div>
</div>

<script>
function updateNow() {
  document.getElementById('tsNow').textContent = Math.floor(Date.now() / 1000);
}
updateNow();
setInterval(updateNow, 1000);

function copyText(text) {
  navigator.clipboard.writeText(text).then(function() {
    var el = document.getElementById('tsNow');
    var orig = el.textContent;
    el.textContent = '已复制!';
    setTimeout(function() { el.textContent = orig; }, 1000);
  });
}

// Unix → Date
document.getElementById('unixInput').addEventListener('input', function() {
  var val = parseInt(this.value);
  if (isNaN(val)) { document.getElementById('dateOutput').value = ''; return; }
  var unit = document.getElementById('unixUnit').value;
  var ts = unit === 'ms' ? Math.floor(val / 1000) : val;
  var d = new Date(ts * 1000);
  document.getElementById('dateOutput').value = formatDateTime(d);
});

document.getElementById('unixUnit').addEventListener('change', function() {
  document.getElementById('unixInput').dispatchEvent(new Event('input'));
});

// Date → Unix
document.getElementById('dateInput').addEventListener('input', function() {
  if (!this.value) { document.getElementById('unixOutput').value = ''; return; }
  var d = new Date(this.value);
  var ts = Math.floor(d.getTime() / 1000);
  var unit = document.getElementById('dateUnit').value;
  document.getElementById('unixOutput').value = unit === 'ms' ? ts * 1000 : ts;
});

document.getElementById('dateUnit').addEventListener('change', function() {
  document.getElementById('dateInput').dispatchEvent(new Event('input'));
});

function formatDateTime(d) {
  var y = d.getFullYear();
  var m = String(d.getMonth() + 1).padStart(2, '0');
  var day = String(d.getDate()).padStart(2, '0');
  var h = String(d.getHours()).padStart(2, '0');
  var min = String(d.getMinutes()).padStart(2, '0');
  var sec = String(d.getSeconds()).padStart(2, '0');
  return y + '/' + m + '/' + day + ' ' + h + ':' + min + ':' + sec;
}
</script>
</body></html>
HTML;
    }
}
