<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class Base64FilePanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('Base64 文件转换', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('📁', size: 16.0), width: 24.0, height: 36.0));

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
.textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:12px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none;resize:vertical;min-height:100px}
.textarea:focus{border-color:var(--el-color-primary)}
.btn{border:none;border-radius:var(--el-border-radius-base);padding:8px 16px;font-size:13px;cursor:pointer;font-weight:500;margin:4px}
.btn-primary{background:var(--el-color-primary);color:#fff}
.btn-primary:hover{opacity:0.9}
.btn-success{background:var(--el-color-success);color:#fff}
.btn-outline{border:1px solid var(--el-border-color);background:var(--el-fill-color-blank);color:var(--el-text-color-primary)}
.btn-outline:hover{border-color:var(--el-color-primary);color:var(--el-color-primary)}
.file-drop{border:2px dashed var(--el-border-color);border-radius:8px;padding:40px 20px;text-align:center;cursor:pointer;transition:border-color 0.3s}
.file-drop:hover,.file-drop.dragover{border-color:var(--el-color-primary)}
.file-drop-icon{font-size:48px;margin-bottom:8px}
.file-drop-text{color:var(--el-text-color-regular);font-size:14px}
.file-info{background:var(--el-fill-color);border-radius:4px;padding:8px 12px;margin:8px 0;font-size:12px}
.alert{border-radius:var(--el-border-radius-base);padding:10px 12px;font-size:13px;margin:8px 0}
.alert-success{background:rgba(103,194,58,.12);color:var(--el-color-success)}
.alert-danger{background:rgba(245,108,108,.12);color:var(--el-color-danger)}
.center{text-align:center;margin-top:12px}
.actions{display:flex;gap:8px;justify-content:center;margin-top:12px}
</style></head>
<body class="dark">
<div class="grid2">
  <!-- File to Base64 -->
  <div class="card">
    <h3>文件 → Base64</h3>
    <div class="file-drop" id="dropZone" onclick="document.getElementById('fileInput').click()">
      <div class="file-drop-icon">📄</div>
      <div class="file-drop-text">点击选择文件或拖拽到此处</div>
    </div>
    <input type="file" id="fileInput" style="display:none" onchange="handleFileSelect(event)">
    <div id="fileInfo" class="file-info" style="display:none"></div>
    <div class="form-item"><label>Base64 输出:</label>
      <textarea id="b64Output" class="textarea" rows="6" readonly placeholder="选择文件后显示 Base64"></textarea>
    </div>
    <div class="actions">
      <button class="btn btn-primary" onclick="copyB64()">📋 复制</button>
      <button class="btn btn-outline" onclick="downloadB64()">⬇ 下载 .txt</button>
    </div>
  </div>

  <!-- Base64 to File -->
  <div class="card">
    <h3>Base64 → 文件</h3>
    <div class="form-item"><label>输入 Base64:</label>
      <textarea id="b64Input" class="textarea" rows="6" placeholder="粘贴 Base64 编码内容" oninput="previewDecode()"></textarea>
    </div>
    <div id="imagePreview" style="display:none;margin:12px 0;text-align:center;background:var(--el-fill-color);border-radius:8px;padding:12px">
      <img id="previewImg" style="max-width:100%;max-height:200px;border-radius:4px">
    </div>
    <div class="form-item"><label>文件名:</label>
      <input type="text" id="fileName" class="input" placeholder="output.txt" style="width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none">
    </div>
    <div id="decodeInfo" class="file-info" style="display:none"></div>
    <div id="decodeErr" class="alert alert-danger" style="display:none">无效的 Base64 编码</div>
    <div class="actions">
      <button class="btn btn-primary" onclick="previewImage()">🖼 预览图片</button>
      <button class="btn btn-success" onclick="downloadFile()">⬇ 下载文件</button>
    </div>
  </div>
</div>

<script>
var currentB64 = '';
var currentFileName = '';

// Drag and drop
var dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.classList.add('dragover');
});
dropZone.addEventListener('dragleave', function() {
  this.classList.remove('dragover');
});
dropZone.addEventListener('drop', function(e) {
  e.preventDefault();
  this.classList.remove('dragover');
  if (e.dataTransfer.files.length > 0) {
    processFile(e.dataTransfer.files[0]);
  }
});

function handleFileSelect(e) {
  if (e.target.files.length > 0) {
    processFile(e.target.files[0]);
  }
}

function processFile(file) {
  currentFileName = file.name;
  var reader = new FileReader();
  reader.onload = function(e) {
    var base64 = e.target.result;
    // Remove data URL prefix if present
    if (base64.indexOf(',') !== -1) {
      base64 = base64.split(',')[1];
    }
    currentB64 = base64;

    // Show file info
    var info = document.getElementById('fileInfo');
    info.style.display = 'block';
    info.innerHTML = '📄 ' + file.name + ' (' + formatSize(file.size) + ')';

    // Show base64
    document.getElementById('b64Output').value = base64;
  };
  reader.readAsDataURL(file);
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function copyB64() {
  var text = document.getElementById('b64Output').value;
  if (!text) return;
  navigator.clipboard.writeText(text);
}

function downloadB64() {
  var text = document.getElementById('b64Output').value;
  if (!text) return;
  var blob = new Blob([text], {type: 'text/plain'});
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = currentFileName ? currentFileName + '.txt' : 'base64.txt';
  a.click();
}

function previewDecode() {
  var input = document.getElementById('b64Input').value.trim();
  var info = document.getElementById('decodeInfo');
  var err = document.getElementById('decodeErr');

  if (!input) {
    info.style.display = 'none';
    err.style.display = 'none';
    return;
  }

  try {
    var decoded = atob(input);
    var bytes = new Uint8Array(decoded.length);
    for (var i = 0; i < decoded.length; i++) {
      bytes[i] = decoded.charCodeAt(i);
    }
    // Try to detect file type
    var mimeType = detectMimeType(bytes);
    var ext = getExtension(mimeType);

    info.style.display = 'block';
    info.innerHTML = '📊 大小: ' + formatSize(bytes.length) + '<br>📋 类型: ' + (mimeType || '未知');

    // Update filename suggestion if empty
    if (!document.getElementById('fileName').value) {
      document.getElementById('fileName').value = 'decoded' + ext;
    }

    err.style.display = 'none';
    currentB64 = input;
  } catch(e) {
    info.style.display = 'none';
    err.style.display = 'block';
  }
}

function detectMimeType(bytes) {
  if (bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47) return 'image/png';
  if (bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF) return 'image/jpeg';
  if (bytes[0] === 0x47 && bytes[1] === 0x49 && bytes[2] === 0x46) return 'image/gif';
  if (bytes[0] === 0x25 && bytes[1] === 0x50 && bytes[2] === 0x44 && bytes[3] === 0x46) return 'application/pdf';
  if (bytes[0] === 0x50 && bytes[1] === 0x4B) return 'application/zip';
  if (bytes[0] === 0x1F && bytes[1] === 0x8B) return 'application/gzip';
  if (bytes[0] === 0x7F && bytes[1] === 0x45 && bytes[2] === 0x4C && bytes[3] === 0x46) return 'application/x-executable';
  if (bytes[0] === 0xCF && bytes[1] === 0xFA && bytes[2] === 0xED && bytes[3] === 0xFE) return 'application/x-executable';
  return 'application/octet-stream';
}

function getExtension(mimeType) {
  var map = {
    'image/png': '.png', 'image/jpeg': '.jpg', 'image/gif': '.gif',
    'application/pdf': '.pdf', 'application/zip': '.zip',
    'application/gzip': '.gz', 'application/x-executable': ''
  };
  return map[mimeType] || '.bin';
}

function downloadFile() {
  var input = document.getElementById('b64Input').value.trim();
  var fileName = document.getElementById('fileName').value || 'decoded.bin';
  if (!input) return;

  try {
    var decoded = atob(input);
    var bytes = new Uint8Array(decoded.length);
    for (var i = 0; i < decoded.length; i++) {
      bytes[i] = decoded.charCodeAt(i);
    }
    var mimeType = detectMimeType(bytes);
    var blob = new Blob([bytes], {type: mimeType});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = fileName;
    a.click();
  } catch(e) {
    document.getElementById('decodeErr').style.display = 'block';
  }
}

function previewImage() {
  var input = document.getElementById('b64Input').value.trim();
  if (!input) return;

  try {
    var decoded = atob(input);
    var bytes = new Uint8Array(decoded.length);
    for (var i = 0; i < decoded.length; i++) {
      bytes[i] = decoded.charCodeAt(i);
    }
    var mimeType = detectMimeType(bytes);

    // Check if it's an image
    if (mimeType.startsWith('image/')) {
      var base64WithPrefix = 'data:' + mimeType + ';base64,' + input;
      document.getElementById('previewImg').src = base64WithPrefix;
      document.getElementById('imagePreview').style.display = 'block';
      document.getElementById('decodeErr').style.display = 'none';
    } else {
      document.getElementById('imagePreview').style.display = 'none';
      document.getElementById('decodeErr').textContent = '该文件不是图片类型，无法预览';
      document.getElementById('decodeErr').style.display = 'block';
    }
  } catch(e) {
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('decodeErr').textContent = '无效的 Base64 编码';
    document.getElementById('decodeErr').style.display = 'block';
  }
}
</script>
</body></html>
HTML;
    }
}
