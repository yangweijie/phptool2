<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class RequestTimePanel implements Panel
{
    private const METRICS = [
        'DNS查询', 'TCP连接', '请求处理', '首字节时间(TTFB)',
        '内容下载', '下载速度', '数据大小', 'HTTP版本',
        '状态码', '使用代理', '总耗时',
    ];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $halfW = $w / 2;

        $children = [];

        // Title
        $children[] = Ui::title('URL请求计时分析', $w);

        // URL input
        $children[] = Ui::textField("{$key}:url", 'https://example.com', $w);

        // Button
        $children[] = Ui::row([Ui::button("{$key}:run", '测量', 'filled', 120)], height: 36.0);

        // Table header
        $children[] = Ui::row([
            LayoutNode::leaf(null, new LabelSpec('指标', size: 13.0, opacity: 0.65), width: $halfW, height: 28.0),
            LayoutNode::leaf(null, new LabelSpec('数值', size: 13.0, opacity: 0.65), width: $halfW, height: 28.0),
        ], height: 28.0);

        // Table rows
        foreach (self::METRICS as $metric) {
            $children[] = Ui::row([
                LayoutNode::leaf(null, new LabelSpec($metric, size: 13.0), width: $halfW, height: 28.0),
                LayoutNode::leaf("{$key}:v_" . md5($metric), new LabelSpec('-', size: 13.0), width: $halfW, height: 28.0),
            ], height: 28.0);
        }

        // Click handler
        $surface->onClick("{$key}:run", function () use ($surface, $key) {
            $root = $surface->rootLayout();
            $urlNode = LayoutNode::find($root, "{$key}:url");
            $url = ($urlNode !== null && $urlNode->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec) ? $urlNode->spec->value : '';
            $result = Backend::requestTime($url);
            if (isset($result['error'])) return;
            foreach ($result as $row) {
                $nodeId = "{$key}:v_" . md5($row['指标']);
                $node = LayoutNode::find($root, $nodeId);
                if ($node !== null && $node->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec) {
                    $node->spec = new LabelSpec($row['数值'], size: 13.0);
                }
            }
            $surface->redraw();
        });

        $totalH = count($children) * 30.0 + 20.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 4.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }
}
