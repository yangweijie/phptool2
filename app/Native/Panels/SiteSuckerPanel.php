<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel; use App\Native\Ui;
use App\Native\WindowHolder;
use App\SiteSucker;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class SiteSuckerPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // Title
        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('网站抓取', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('☆', size: 16.0), width: 24.0, height: 36.0));

        // URL input
        $urlField = Ui::textField("{$key}:url", 'URL', $w);

        // URL search
        $urlSearchRow = LayoutNode::row(gap: 4.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $urlSearchRow->child(LayoutNode::leaf(null, new LabelSpec('url(0/0)', size: 12.0, opacity: 0.65), width: 80.0, height: 28.0));
        $urlSearchRow->child(Ui::textField("{$key}:urlsearch", '搜索', 120.0));

        // Host search
        $hostSearchRow = LayoutNode::row(gap: 4.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $hostSearchRow->child(LayoutNode::leaf(null, new LabelSpec('host', size: 12.0, opacity: 0.65), width: 40.0, height: 28.0));
        $hostSearchRow->child(Ui::textField("{$key}:hostsearch", '搜索', 120.0));

        // Settings section - always visible
        $settingsLabel = LayoutNode::leaf(null, new LabelSpec('───── 设置 ─────', size: 12.0, opacity: 0.5), width: $w, height: 24.0);

        // Save path
        $saveRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $saveRow->child(Ui::textField("{$key}:savepath", '文件保存为: 保存路径/域名', $w - 40.0));
        $saveRow->child(Ui::button("{$key}:savepick", '📁', 'outline', 32.0, 30.0));

        // Proxy
        $proxyField = Ui::textField("{$key}:proxy", '网络代理: http://127.0.0.1:1087', $w);

        // Timeout
        $timeoutField = Ui::textField("{$key}:timeout", '超时时间: 默认值5000', $w);

        // Image limit
        $imgField = Ui::textField("{$key}:imglimit", '图片文件大小限制 (M)', $w);

        // Video limit
        $videoField = Ui::textField("{$key}:videolimit", '音视频文件大小限制 (M)', $w);

        // Page limit
        $pageField = Ui::textField("{$key}:pagelimit", '页面限制', $w);

        // Domain exclusion
        $excludeLabel = Ui::label('域名排除', $w, 12.0, 18.0);
        $exclude = new TextAreaControl("{$key}:exclude", '过滤包含此处域名的网址, 每行一个', width: $w, height: 80);
        $exclude->bind($surface);

        // Result
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 150);
        $out->bind($surface);

        // Handlers
        $surface->onClick("{$key}:savepick", function () use ($surface, $key) {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = $win->dialogs()->openFolder();
            if ($path !== null && $path !== '') {
                $node = LayoutNode::find($surface->rootLayout(), "{$key}:savepath");
                if ($node !== null && $node->spec instanceof TextFieldSpec) {
                    $node->spec = new TextFieldSpec(value: $path, placeholder: '文件保存为: 保存路径/域名');
                }
                $surface->redraw();
            }
        });

        $surface->onClick("{$key}:run", static function () use ($urlField, $out, $surface): void {
            $url = $urlField->spec instanceof TextFieldSpec ? $urlField->spec->value : '';
            if ($url === '') { $out->setValue('请输入 URL'); return; }
            try {
                $sucker = new SiteSucker($url, sys_get_temp_dir() . '/fly_site_' . time(), exclude: ['analytics', 'ads', 'track']);
                $summary = [];
                $sucker->on('page', fn (string $page) => $summary[] = "  fetched: {$page}");
                $sucker->run();
                $result = "下载完成\n\n目标: {$url}\n" . implode("\n", $summary);
                $out->setValue($result);
            } catch (\Throwable $e) {
                $out->setValue('错误: ' . $e->getMessage());
            }
        });

        // Flat structure - settings always visible
        $children = [
            $titleRow, $urlField, $urlSearchRow,
            Ui::label('暂无数据', $w, 12.0, 18.0),
            $hostSearchRow,
            Ui::label('暂无数据', $w, 12.0, 18.0),
            $settingsLabel, $saveRow, $proxyField, $timeoutField,
            $imgField, $videoField, $pageField, $excludeLabel, $exclude->root(),
            $out->root(),
        ];

        $totalH = 800.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }
}
