<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class MimeTypesPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('MIME 类型', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('📋', size: 16.0), width: 24.0, height: 36.0));

        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $w, height: min($height, 400));
        $leaf->style->grow = 1.0;

        $children = [$titleRow, $leaf];
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 4.0, padding: 18.0, contentHeight: 600.0);
        $sv->bind($surface);
        return $sv->root();
    }

    private static function initialHtml(): string
    {
        $mimeList = [
            ['mime' => 'text/plain', 'ext' => 'txt'],
            ['mime' => 'text/html', 'ext' => 'html'],
            ['mime' => 'text/css', 'ext' => 'css'],
            ['mime' => 'text/javascript', 'ext' => 'js'],
            ['mime' => 'application/json', 'ext' => 'json'],
            ['mime' => 'application/xml', 'ext' => 'xml'],
            ['mime' => 'application/pdf', 'ext' => 'pdf'],
            ['mime' => 'application/zip', 'ext' => 'zip'],
            ['mime' => 'application/gzip', 'ext' => 'gz'],
            ['mime' => 'image/jpeg', 'ext' => 'jpg'],
            ['mime' => 'image/png', 'ext' => 'png'],
            ['mime' => 'image/gif', 'ext' => 'gif'],
            ['mime' => 'image/webp', 'ext' => 'webp'],
            ['mime' => 'image/svg+xml', 'ext' => 'svg'],
            ['mime' => 'video/mp4', 'ext' => 'mp4'],
            ['mime' => 'audio/mpeg', 'ext' => 'mp3'],
            ['mime' => 'application/octet-stream', 'ext' => 'bin'],
        ];

        $mimeOptions = '';
        $extOptions = '';
        $tableRows = '';
        foreach ($mimeList as $m) {
            $mimeOptions .= '<option value="' . $m['mime'] . '">' . $m['mime'] . '</option>';
            $extOptions .= '<option value=".' . $m['ext'] . '">.' . $m['ext'] . '</option>';
            $tableRows .= '<tr><td>' . $m['mime'] . '</td><td>.' . $m['ext'] . '</td></tr>';
        }

        return <<<HTML
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
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow-y:auto;padding:16px;height:100%}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:800px){.grid2{grid-template-columns:1fr}}
.card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px;margin-bottom:16px}
.card h3{font-size:15px;margin-bottom:12px;font-weight:600}
.form-item{margin-bottom:12px}
.form-item label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
select{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{border:1px solid var(--el-border-color);padding:6px 10px;text-align:left}
th{background:var(--el-fill-color);font-weight:600}
.result{margin-top:8px;padding:8px;background:var(--el-fill-color);border-radius:4px;font-weight:500}
</style></head>
<body class="dark">
<div class="grid2">
  <div class="card">
    <h3>MIME → 扩展名</h3>
    <div class="form-item"><label>选择 MIME 类型:</label>
      <select id="mMime" onchange="m2e()">{$mimeOptions}</select>
    </div>
    <div id="mExts" class="result"></div>
  </div>
  <div class="card">
    <h3>扩展名 → MIME</h3>
    <div class="form-item"><label>选择扩展名:</label>
      <select id="mExt" onchange="e2m()">{$extOptions}</select>
    </div>
    <div id="mMimeOut" class="result"></div>
  </div>
</div>
<div class="card">
  <h3>MIME 类型对照表</h3>
  <table>
    <thead><tr><th>MIME 类型</th><th>扩展名</th></tr></thead>
    <tbody>{$tableRows}</tbody>
  </table>
</div>
<script>
var MIME_LIST = {
  'text/plain': 'txt', 'text/html': 'html', 'text/css': 'css',
  'text/javascript': 'js', 'application/json': 'json',
  'application/xml': 'xml', 'application/pdf': 'pdf',
  'application/zip': 'zip', 'application/gzip': 'gz',
  'image/jpeg': 'jpg', 'image/png': 'png', 'image/gif': 'gif',
  'image/webp': 'webp', 'image/svg+xml': 'svg',
  'video/mp4': 'mp4', 'audio/mpeg': 'mp3', 'application/octet-stream': 'bin'
};

function m2e() {
  var mime = document.getElementById('mMime').value;
  var ext = MIME_LIST[mime] || '未知';
  document.getElementById('mExts').innerHTML = '扩展名: <strong>.' + ext + '</strong>';
}

function e2m() {
  var ext = document.getElementById('mExt').value.replace('.', '');
  var mime = '未知';
  for (var k in MIME_LIST) {
    if (MIME_LIST[k] === ext) { mime = k; break; }
  }
  document.getElementById('mMimeOut').innerHTML = 'MIME 类型: <strong>' + mime + '</strong>';
}

m2e();
e2m();
</script>
</body></html>
HTML;
    }
}
