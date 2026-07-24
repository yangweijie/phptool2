<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class HttpStatusPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('HTTP 状态码', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('📡', size: 16.0), width: 24.0, height: 36.0));

        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $w, height: min($height - 60, 450));
        $leaf->style->grow = 1.0;

        $children = [$titleRow, $leaf];
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 4.0, padding: 18.0, contentHeight: 500.0);
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
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow-y:auto;padding:16px;height:100%}
.card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px;margin-bottom:16px}
.card h3{font-size:15px;margin-bottom:12px;font-weight:600}
.form-item{margin-bottom:12px}
.form-item label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.input{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.input:focus{border-color:var(--el-color-primary)}
.subtitle{font-size:14px;font-weight:600;margin:16px 0 8px;padding-bottom:4px;border-bottom:1px solid var(--el-border-color)}
.entry{padding:8px 0;border-bottom:1px solid var(--el-border-color)}
.entry:last-child{border-bottom:none}
.entry-code{font-size:16px;font-weight:700;margin-bottom:2px}
.entry-desc{font-size:13px;color:var(--el-text-color-regular)}
.entry-type{font-size:11px;color:var(--el-color-primary);margin-left:8px}
.no-results{padding:20px;text-align:center;color:var(--el-text-color-regular)}
</style></head>
<body class="dark">
<div class="card">
  <div class="form-item"><label>搜索状态码:</label>
    <input type="text" id="httpQ" class="input" placeholder="输入状态码或描述..." oninput="filterHTTP()">
  </div>
  <div id="httpResults"></div>
</div>
<script>
var HTTP_DATA = [
  {code:100,name:'Continue',desc:'等待客户端发送请求体',type:'HTTP'},
  {code:101,name:'Switching Protocols',desc:'服务器已同意切换协议',type:'HTTP'},
  {code:102,name:'Processing',desc:'服务器正在处理请求，但尚无响应',type:'WebDAV'},
  {code:103,name:'Early Hints',desc:'服务器在最终HTTP消息前返回一些响应头',type:'HTTP'},
  {code:200,name:'OK',desc:'标准的成功HTTP请求响应',type:'HTTP'},
  {code:201,name:'Created',desc:'请求已成功，创建了新资源',type:'HTTP'},
  {code:202,name:'Accepted',desc:'请求已接受处理，但处理尚未完成',type:'HTTP'},
  {code:203,name:'Non-Authoritative Information',desc:'请求成功，但内容已被代理修改',type:'HTTP'},
  {code:204,name:'No Content',desc:'服务器成功处理请求，未返回内容',type:'HTTP'},
  {code:205,name:'Reset Content',desc:'服务器指示重新初始化文档视图',type:'HTTP'},
  {code:206,name:'Partial Content',desc:'服务器仅返回资源的一部分',type:'HTTP'},
  {code:207,name:'Multi-Status',desc:'消息体是XML，可包含单独的响应码',type:'WebDAV'},
  {code:208,name:'Already Reported',desc:'DAV成员已在响应的前面部分列举',type:'WebDAV'},
  {code:226,name:'IM Used',desc:'服务器已 fulfilled 请求，响应是结果的表示',type:'HTTP'},
  {code:300,name:'Multiple Choices',desc:'资源有多个选项，客户端可跟随',type:'HTTP'},
  {code:301,name:'Moved Permanently',desc:'此请求及未来所有请求应重定向到给定URI',type:'HTTP'},
  {code:302,name:'Found',desc:'重定向到另一个URL',type:'HTTP'},
  {code:303,name:'See Other',desc:'响应可在另一个URI下找到',type:'HTTP'},
  {code:304,name:'Not Modified',desc:'资源未被修改',type:'HTTP'},
  {code:305,name:'Use Proxy',desc:'请求的资源只能通过代理访问',type:'HTTP'},
  {code:306,name:'Switch Proxy',desc:'不再使用，后续请求应使用指定代理',type:'HTTP'},
  {code:307,name:'Temporary Redirect',desc:'请求应使用另一个URI重复',type:'HTTP'},
  {code:308,name:'Permanent Redirect',desc:'请求和未来所有请求应使用另一个URI',type:'HTTP'},
  {code:400,name:'Bad Request',desc:'服务器无法处理请求，客户端错误',type:'HTTP'},
  {code:401,name:'Unauthorized',desc:'需要认证且已失败或未提供',type:'HTTP'},
  {code:402,name:'Payment Required',desc:'保留供将来使用',type:'HTTP'},
  {code:403,name:'Forbidden',desc:'请求有效，但服务器拒绝操作',type:'HTTP'},
  {code:404,name:'Not Found',desc:'请求的资源未找到',type:'HTTP'},
  {code:405,name:'Method Not Allowed',desc:'请求方法不支持',type:'HTTP'},
  {code:406,name:'Not Acceptable',desc:'资源只能生成不被接受的内容',type:'HTTP'},
  {code:407,name:'Proxy Authentication Required',desc:'客户端必须先向代理认证',type:'HTTP'},
  {code:408,name:'Request Timeout',desc:'服务器等待请求超时',type:'HTTP'},
  {code:409,name:'Conflict',desc:'请求因冲突无法处理',type:'HTTP'},
  {code:410,name:'Gone',desc:'请求的资源不再可用',type:'HTTP'},
  {code:411,name:'Length Required',desc:'请求未指定内容长度',type:'HTTP'},
  {code:412,name:'Precondition Failed',desc:'服务器不满足请求的前置条件',type:'HTTP'},
  {code:413,name:'Payload Too Large',desc:'请求体过大',type:'HTTP'},
  {code:414,name:'URI Too Long',desc:'URI过长',type:'HTTP'},
  {code:415,name:'Unsupported Media Type',desc:'不支持的媒体类型',type:'HTTP'},
  {code:416,name:'Range Not Satisfiable',desc:'客户端请求的部分无法提供',type:'HTTP'},
  {code:417,name:'Expectation Failed',desc:'服务器无法满足Expect头字段',type:'HTTP'},
  {code:418,name:"I'm a teapot",desc:'服务器拒绝用茶壶煮咖啡',type:'HTTP'},
  {code:421,name:'Misdirected Request',desc:'请求被定向到无法响应的服务器',type:'HTTP'},
  {code:422,name:'Unprocessable Entity',desc:'请求格式正确但因语义错误无法处理',type:'HTTP'},
  {code:423,name:'Locked',desc:'正在访问的资源被锁定',type:'HTTP'},
  {code:424,name:'Failed Dependency',desc:'请求因前一个请求失败而失败',type:'HTTP'},
  {code:425,name:'Too Early',desc:'服务器不愿处理可能被重放的请求',type:'HTTP'},
  {code:426,name:'Upgrade Required',desc:'客户端应切换到不同协议',type:'HTTP'},
  {code:428,name:'Precondition Required',desc:'源服务器要求请求是条件性的',type:'HTTP'},
  {code:429,name:'Too Many Requests',desc:'请求过多',type:'HTTP'},
  {code:431,name:'Request Header Fields Too Large',desc:'请求头字段过大',type:'HTTP'},
  {code:451,name:'Unavailable For Legal Reasons',desc:'因法律原因不可用',type:'HTTP'},
  {code:500,name:'Internal Server Error',desc:'服务器遇到意外情况',type:'HTTP'},
  {code:501,name:'Not Implemented',desc:'服务器不支持请求的功能',type:'HTTP'},
  {code:502,name:'Bad Gateway',desc:'网关或代理从上游服务器收到无效响应',type:'HTTP'},
  {code:503,name:'Service Unavailable',desc:'服务器暂时过载或维护中',type:'HTTP'},
  {code:504,name:'Gateway Timeout',desc:'网关或代理等待上游服务器响应超时',type:'HTTP'},
  {code:505,name:'HTTP Version Not Supported',desc:'服务器不支持HTTP协议版本',type:'HTTP'},
  {code:506,name:'Variant Also Negotiates',desc:'透明内容协商导致循环引用',type:'HTTP'},
  {code:507,name:'Insufficient Storage',desc:'服务器无法存储表示',type:'HTTP'},
  {code:510,name:'Not Extended',desc:'进一步的扩展才能满足请求',type:'HTTP'},
  {code:511,name:'Network Authentication Required',desc:'需要网络认证',type:'HTTP'}
];
var HTTP_CATS = ['1xx informational response','2xx success','3xx redirection','4xx client error','5xx server error'];
function renderHTTP(query) {
  var out = document.getElementById('httpResults');
  var q = (query || '').toLowerCase();
  var data = HTTP_DATA;
  if (q) {
    data = data.filter(function(c) {
      return c.code.toString().includes(q) || c.name.toLowerCase().includes(q) || c.desc.toLowerCase().includes(q);
    });
  }
  if (data.length === 0) {
    out.innerHTML = '<div class="no-results">未找到匹配的状态码</div>';
    return;
  }
  if (q) {
    out.innerHTML = '<div class="subtitle">搜索结果</div>' + data.map(function(c) {
      return '<div class="entry"><div class="entry-code">' + c.code + ' ' + c.name + '<span class="entry-type">' + c.type + '</span></div><div class="entry-desc">' + c.desc + '</div></div>';
    }).join('');
  } else {
    var html = '';
    HTTP_CATS.forEach(function(cat) {
      var cs = data.filter(function(c) { return c.cat === cat; });
      if (!cs.length) return;
      html += '<div class="subtitle">' + cat + '</div>';
      cs.forEach(function(c) {
        html += '<div class="entry"><div class="entry-code">' + c.code + ' ' + c.name + '<span class="entry-type">' + c.type + '</span></div><div class="entry-desc">' + c.desc + '</div></div>';
      });
    });
    out.innerHTML = html;
  }
}
function filterHTTP() {
  var q = document.getElementById('httpQ').value;
  renderHTTP(q);
}
renderHTTP();
</script>
</body></html>
HTML;
    }
}
