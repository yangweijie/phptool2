<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class JwtPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $w, height: $height - 40);
        $leaf->style->grow = 1.0;

        $children = [
            LayoutNode::leaf(null, new \Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec('JWT 编码/解码', size: 16.0, opacity: 0.85), width: $w, height: 36.0),
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
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:800px){.grid2{grid-template-columns:1fr}}
.card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px}
.card h3{font-size:15px;margin-bottom:12px;font-weight:600}
.form-item{margin-bottom:12px}
.form-item label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.input,.textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:inherit;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.input:focus,.textarea:focus{border-color:var(--el-color-primary)}
.textarea{resize:vertical;min-height:80px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
select{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.btn{border:none;border-radius:var(--el-border-radius-base);padding:8px 16px;font-size:13px;cursor:pointer;font-weight:500}
.btn-primary{background:var(--el-color-primary);color:#fff}
.btn-primary:hover{opacity:0.9}
.alert{border-radius:var(--el-border-radius-base);padding:10px 12px;font-size:13px;margin:8px 0}
.alert-success{background:rgba(103,194,58,.12);color:var(--el-color-success)}
.alert-danger{background:rgba(245,108,108,.12);color:var(--el-color-danger)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;white-space:pre-wrap;word-break:break-all}
.row{display:flex;gap:8px;margin-bottom:8px}
.row .form-item{flex:1;margin-bottom:0}
</style></head>
<body class="dark">
<div class="grid2">
  <!-- Encode -->
  <div class="card">
    <h3>Encode</h3>
    <div class="row">
      <div class="form-item"><label>Algorithm:</label>
        <select id="encAlg"><option>HS256</option><option>HS384</option><option>HS512</option><option>none</option></select>
      </div>
      <div class="form-item"><label>Secret:</label>
        <input type="text" id="encSecret" class="input" value="your-256-bit-secret">
      </div>
    </div>
    <div class="form-item"><label>Header:</label>
      <textarea id="encHeader" class="textarea" rows="4">{
  "alg": "HS256",
  "typ": "JWT"
}</textarea>
    </div>
    <div class="form-item"><label>Payload:</label>
      <textarea id="encPayload" class="textarea" rows="5">{
  "sub": "1234567890",
  "name": "FlyEnv",
  "iat": 1516239022
}</textarea>
    </div>
    <div class="form-item"><label>JWT:</label>
      <textarea id="encOutput" class="textarea" rows="3" readonly></textarea>
    </div>
    <button class="btn btn-primary" onclick="doEncode()">Encode</button>
  </div>

  <!-- Decode -->
  <div class="card">
    <h3>Decode</h3>
    <div class="row">
      <div class="form-item"><label>Algorithm:</label>
        <select id="decAlg"><option>HS256</option><option>HS384</option><option>HS512</option><option>none</option></select>
      </div>
      <div class="form-item"><label>Secret:</label>
        <input type="text" id="decSecret" class="input" value="your-256-bit-secret">
      </div>
    </div>
    <div class="form-item"><label>JWT:</label>
      <textarea id="decInput" class="textarea" rows="4" placeholder="Paste JWT token here"></textarea>
    </div>
    <div id="decStatus"></div>
    <div class="form-item"><label>Header:</label>
      <textarea id="decHeader" class="textarea" rows="3" readonly></textarea>
    </div>
    <div class="form-item"><label>Payload:</label>
      <textarea id="decPayload" class="textarea" rows="5" readonly></textarea>
    </div>
    <button class="btn btn-primary" onclick="doDecode()">Decode This Token</button>
  </div>
</div>

<script>
function doEncode() {
  try {
    var header = JSON.parse(document.getElementById('encHeader').value);
    var payload = JSON.parse(document.getElementById('encPayload').value);
    var secret = document.getElementById('encSecret').value;
    var alg = document.getElementById('encAlg').value;

    if (alg === 'none') {
      header.alg = 'none';
      var token = btoa(JSON.stringify(header)) + '.' + btoa(JSON.stringify(payload)) + '.';
      document.getElementById('encOutput').value = token;
      return;
    }

    header.alg = alg;
    var headerB64 = btoa(JSON.stringify(header)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
    var payloadB64 = btoa(JSON.stringify(payload)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');

    var enc = new TextEncoder();
    var keyData = enc.encode(secret);
    var algorithm = {name: 'HMAC', hash: alg === 'HS256' ? 'SHA-256' : alg === 'HS384' ? 'SHA-384' : 'SHA-512'};

    crypto.subtle.importKey('raw', keyData, algorithm, false, ['sign']).then(function(key) {
      return crypto.subtle.sign(algorithm.name, key, enc.encode(headerB64 + '.' + payloadB64));
    }).then(function(sig) {
      var sigB64 = btoa(String.fromCharCode.apply(null, new Uint8Array(sig))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
      document.getElementById('encOutput').value = headerB64 + '.' + payloadB64 + '.' + sigB64;
    });
  } catch(e) {
    document.getElementById('encOutput').value = 'Error: ' + e.message;
  }
}

function doDecode() {
  try {
    var token = document.getElementById('decInput').value.trim();
    var secret = document.getElementById('decSecret').value;
    var alg = document.getElementById('decAlg').value;

    if (!token) { alert('Please paste a JWT token'); return; }

    var parts = token.split('.');
    if (parts.length !== 3) { throw new Error('Invalid JWT format'); }

    var header = JSON.parse(atob(parts[0].replace(/-/g,'+').replace(/_/g,'/')));
    var payload = JSON.parse(atob(parts[1].replace(/-/g,'+').replace(/_/g,'/')));

    document.getElementById('decHeader').value = JSON.stringify(header, null, 2);
    document.getElementById('decPayload').value = JSON.stringify(payload, null, 2);

    if (alg === 'none' || header.alg === 'none') {
      document.getElementById('decStatus').innerHTML = '<div class="alert alert-success">No signature (alg: none)</div>';
      return;
    }

    // Verify signature
    var keyData = new TextEncoder().encode(secret);
    var algorithm = {name: 'HMAC', hash: header.alg === 'HS256' ? 'SHA-256' : header.alg === 'HS384' ? 'SHA-384' : 'SHA-512'};

    crypto.subtle.importKey('raw', keyData, algorithm, false, ['verify']).then(function(key) {
      var sig = Uint8Array.from(atob(parts[2].replace(/-/g,'+').replace(/_/g,'/')), function(c){return c.charCodeAt(0);});
      var data = new TextEncoder().encode(parts[0] + '.' + parts[1]);
      return crypto.subtle.verify(algorithm.name, key, sig, data);
    }).then(function(valid) {
      document.getElementById('decStatus').innerHTML = valid
        ? '<div class="alert alert-success">✅ Signature verified</div>'
        : '<div class="alert alert-danger">❌ Invalid signature</div>';
    }).catch(function() {
      document.getElementById('decStatus').innerHTML = '<div class="alert alert-danger">❌ Invalid signature</div>';
    });
  } catch(e) {
    document.getElementById('decStatus').innerHTML = '<div class="alert alert-danger">❌ Error: ' + e.message + '</div>';
  }
}
</script>
</body></html>
HTML;
    }
}
