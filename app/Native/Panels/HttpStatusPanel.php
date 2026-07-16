<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Catalog;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class HttpStatusPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        // Width leaves 20px right margin so the scrollbar gutter is visible
        // (the scrollbar is 12px wide and overlaps the right edge of the
        // content if the content extends to the viewport edge).
        $w = $width - 76;
        $zh = Catalog::chinese();
        $children = [];
        $children[] = LayoutNode::leaf("{$key}:title", new LabelSpec($zh ? 'HTTP 状态码' : 'HTTP Status Codes', size: 18, opacity: 0.85), width: $w, height: 32);
        $totalH = 40;

        // Sections with clickable code entries
        $sections = [
            [$zh ? '信息响应 (1xx)' : 'Informational (1xx)', [
                ['100', $zh ? '继续' : 'Continue'],
                ['101', $zh ? '切换协议' : 'Switching Protocols'],
                ['102', $zh ? '处理中' : 'Processing'],
            ]],
            [$zh ? '成功 (2xx)' : 'Success (2xx)', [
                ['200', $zh ? '请求成功' : 'OK'],
                ['201', $zh ? '已创建' : 'Created'],
                ['202', $zh ? '已接受' : 'Accepted'],
                ['204', $zh ? '无内容' : 'No Content'],
                ['206', $zh ? '部分内容' : 'Partial Content'],
            ]],
            [$zh ? '重定向 (3xx)' : 'Redirection (3xx)', [
                ['301', $zh ? '永久移动' : 'Moved Permanently'],
                ['302', $zh ? '临时移动' : 'Found'],
                ['304', $zh ? '未修改' : 'Not Modified'],
                ['307', $zh ? '临时重定向' : 'Temporary Redirect'],
                ['308', $zh ? '永久重定向' : 'Permanent Redirect'],
            ]],
            [$zh ? '客户端错误 (4xx)' : 'Client Errors (4xx)', [
                ['400', $zh ? '错误请求' : 'Bad Request'],
                ['401', $zh ? '未授权' : 'Unauthorized'],
                ['403', $zh ? '禁止访问' : 'Forbidden'],
                ['404', $zh ? '未找到' : 'Not Found'],
                ['405', $zh ? '方法不允许' : 'Method Not Allowed'],
                ['408', $zh ? '请求超时' : 'Request Timeout'],
                ['409', $zh ? '冲突' : 'Conflict'],
                ['413', $zh ? '请求体过大' : 'Payload Too Large'],
                ['415', $zh ? '不支持的媒体类型' : 'Unsupported Media Type'],
                ['422', $zh ? '不可处理' : 'Unprocessable Entity'],
                ['429', $zh ? '请求过多' : 'Too Many Requests'],
            ]],
            [$zh ? '服务端错误 (5xx)' : 'Server Errors (5xx)', [
                ['500', $zh ? '服务器错误' : 'Internal Server Error'],
                ['501', $zh ? '未实现' : 'Not Implemented'],
                ['502', $zh ? '网关错误' : 'Bad Gateway'],
                ['503', $zh ? '服务不可用' : 'Service Unavailable'],
                ['504', $zh ? '网关超时' : 'Gateway Timeout'],
            ]],
        ];

        $secIdx = 0;
        foreach ($sections as [$sectionName, $codes]) {
            // Section header — give it an id so findAt() finds a node.
            $children[] = LayoutNode::leaf("{$key}:hdr:{$secIdx}", new LabelSpec($sectionName, size: 14, opacity: 0.65), width: $w, height: 24);
            $totalH += 28;

            $rowIdx = 0;
            $row = LayoutNode::row(id: "{$key}:row:{$secIdx}:{$rowIdx}", gap: 8, height: 28, align: LayoutStyle::ALIGN_CENTER);
            $colItems = 0;
            foreach ($codes as [$code, $desc]) {
                $text = "{$code}  {$desc}";
                $entry = LayoutNode::leaf("{$key}:e:{$code}", new LabelSpec($text, size: 12), width: ($w - 8) / 2, height: 24);
                $row->child($entry);
                $colItems++;
                if ($colItems === 2) {
                    $children[] = $row;
                    $rowIdx++;
                    $row = LayoutNode::row(id: "{$key}:row:{$secIdx}:{$rowIdx}", gap: 8, height: 28, align: LayoutStyle::ALIGN_CENTER);
                    $colItems = 0;
                    $totalH += 30;
                }
            }
            if ($colItems > 0) {
                $row->id = "{$key}:row:{$secIdx}:last";
                $children[] = $row;
                $totalH += 30;
            }
            $totalH += 8; // spacing between sections
            $secIdx++;
        }

        $children[] = LayoutNode::leaf("{$key}:tail", null, height: 20);
        $totalH += 20;

        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 2, padding: 28.0, contentHeight: $totalH);
        $sv->bind($surface);
        return $sv->root();
    }
}
