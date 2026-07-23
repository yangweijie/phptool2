<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class Base64Panel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('Base64 编解码', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('🔄', size: 16.0), width: 24.0, height: 36.0));

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
  --el-color-primary:#409eff;--el-color-success:#67c23a;--el-color-danger:#f56c6c;
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
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:800px){.grid2{grid-template-columns:1fr}}
.card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px}
.card h3{font-size:15px;margin-bottom:12px;font-weight:600}
.form-item{margin-bottom:12px}
.form-item label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none;resize:vertical;min-height:80px}
.textarea:focus{border-color:var(--el-color-primary)}
.switch-row{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.switch{position:relative;width:40px;height:22px;background:var(--el-border-color);border-radius:11px;cursor:pointer;transition:background 0.3s}
.switch.active{background:var(--el-color-primary)}
.switch::after{content:'';position:absolute;top:2px;left:2px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform 0.3s}
.switch.active::after{transform:translateX(18px)}
.btn{border:none;border-radius:var(--el-border-radius-base);padding:6px 14px;font-size:12px;cursor:pointer;font-weight:500}
.btn-primary{background:var(--el-color-primary);color:#fff}
.btn-primary:hover{opacity:0.9}
.alert{border-radius:var(--el-border-radius-base);padding:10px 12px;font-size:13px;margin:8px 0}
.alert-danger{background:rgba(245,108,108,.12);color:var(--el-color-danger)}
.center{text-align:center;margin-top:8px}
</style></head>
<body class="dark">
<div class="grid2">
  <!-- String to Base64 -->
  <div class="card">
    <h3>字符串 → Base64</h3>
    <div class="switch-row">
      <div class="switch" id="encSafe" onclick="this.classList.toggle('active');encodeLive()"></div>
      <span>URL-safe</span>
    </div>
    <div class="form-item"><label>输入字符串:</label>
      <textarea id="encIn" class="textarea" rows="5" placeholder="输入要编码的字符串" oninput="encodeLive()">Hello FlyEnv!</textarea>
    </div>
    <div class="form-item"><label>Base64 输出:</label>
      <textarea id="encOut" class="textarea" rows="5" readonly></textarea>
    </div>
    <div class="center">
      <button class="btn btn-primary" onclick="copyText('encOut')">📋 复制</button>
    </div>
  </div>

  <!-- Base64 to String -->
  <div class="card">
    <h3>Base64 → 字符串</h3>
    <div class="switch-row">
      <div class="switch" id="decSafe" onclick="this.classList.toggle('active');decodeLive()"></div>
      <span>URL-safe</span>
    </div>
    <div class="form-item"><label>输入 Base64:</label>
      <textarea id="decIn" class="textarea" rows="5" placeholder="输入 Base64 编码" oninput="decodeLive()"></textarea>
    </div>
    <div class="form-item"><label>解码输出:</label>
      <textarea id="decOut" class="textarea" rows="5" readonly></textarea>
    </div>
    <div id="decErr" class="alert alert-danger" style="display:none">无效的 Base64 编码</div>
    <div class="center">
      <button class="btn btn-primary" onclick="copyText('decOut')">📋 复制</button>
    </div>
  </div>
</div>

<script>
function encodeLive() {
  var input = document.getElementById('encIn').value;
  var safe = document.getElementById('encSafe').classList.contains('active');
  var output = '';
  try {
    if (safe) {
      output = btoa(unescape(encodeURIComponent(input)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/,'');
    } else {
      output = btoa(unescape(encodeURIComponent(input)));
    }
  } catch(e) {
    output = 'Error: ' + e.message;
  }
  document.getElementById('encOut').value = output;
}

function decodeLive() {
  var input = document.getElementById('decIn').value.trim();
  var safe = document.getElementById('decSafe').classList.contains('active');
  var errEl = document.getElementById('decErr');
  var outEl = document.getElementById('decOut');

  if (!input) { outEl.value = ''; errEl.style.display = 'none'; return; }

  try {
    var norm = safe ? input.replace(/-/g, '+').replace(/_/g, '/') : input;
    // Add padding if needed
    while (norm.length % 4 !== 0) { norm += '='; }
    outEl.value = decodeURIComponent(escape(atob(norm)));
    errEl.style.display = 'none';
  } catch(e) {
    outEl.value = '';
    errEl.style.display = 'block';
  }
}

function copyText(id) {
  var text = document.getElementById(id).value;
  if (!text) return;
  navigator.clipboard.writeText(text).then(function() {
    // Visual feedback could be added here
  });
}

// Initial encode
encodeLive();
</script>
</body></html>
HTML;
    }
}
