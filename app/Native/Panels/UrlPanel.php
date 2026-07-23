<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class UrlPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('URL 编解码', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('🔗', size: 16.0), width: 24.0, height: 36.0));

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
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:800px){.grid2{grid-template-columns:1fr}}
.card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px}
.card h3{font-size:15px;margin-bottom:12px;font-weight:600}
.form-item{margin-bottom:12px}
.form-item label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none;resize:vertical;min-height:60px}
.textarea:focus{border-color:var(--el-color-primary)}
.btn{border:none;border-radius:var(--el-border-radius-base);padding:6px 14px;font-size:12px;cursor:pointer;font-weight:500}
.btn-primary{background:var(--el-color-primary);color:#fff}
.btn-primary:hover{opacity:0.9}
.center{text-align:center;margin-top:8px}
</style></head>
<body class="dark">
<div class="grid2">
  <!-- URL Encode -->
  <div class="card">
    <h3>URL 编码</h3>
    <div class="form-item"><label>输入字符串:</label>
      <textarea id="encIn" class="textarea" rows="3" placeholder="输入要编码的字符串" oninput="urlEncode()">https://example.com/path?q=FlyEnv</textarea>
    </div>
    <div class="form-item"><label>编码结果:</label>
      <textarea id="encOut" class="textarea" rows="3" readonly></textarea>
    </div>
    <div class="center">
      <button class="btn btn-primary" onclick="copyText('encOut')">📋 复制</button>
    </div>
  </div>

  <!-- URL Decode -->
  <div class="card">
    <h3>URL 解码</h3>
    <div class="form-item"><label>输入字符串:</label>
      <textarea id="decIn" class="textarea" rows="3" placeholder="输入要解码的字符串" oninput="urlDecode()"></textarea>
    </div>
    <div class="form-item"><label>解码结果:</label>
      <textarea id="decOut" class="textarea" rows="3" readonly></textarea>
    </div>
    <div class="center">
      <button class="btn btn-primary" onclick="copyText('decOut')">📋 复制</button>
    </div>
  </div>
</div>

<script>
function urlEncode() {
  var input = document.getElementById('encIn').value;
  document.getElementById('encOut').value = encodeURIComponent(input);
}

function urlDecode() {
  var input = document.getElementById('decIn').value;
  try {
    document.getElementById('decOut').value = decodeURIComponent(input);
  } catch(e) {
    document.getElementById('decOut').value = 'Error: Invalid URL encoding';
  }
}

function copyText(id) {
  var text = document.getElementById(id).value;
  if (!text) return;
  navigator.clipboard.writeText(text);
}

// Initial encode
urlEncode();
</script>
</body></html>
HTML;
    }
}
