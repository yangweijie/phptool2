<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Libui\Color;
use Libui\Draw\DrawContext;
use Libui\Draw\StrokeParams;
use Libui\Text\FontDescriptor;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CanvasSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class FileInfoPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $dropH = max(200, $height - 300);

        // Result area
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 200);
        $out->bind($surface);

        // Drop zone canvas
        $dropZone = new CanvasSpec(function (DrawContext $ctx, float $cw, float $ch): void {
            $bg = 0xF5F7FA;
            $border = 0xCCCCCC;
            $icon = 0x303133;
            $text = 0x909399;

            $ctx->fillRect(0, 0, $cw, $ch, Color::rgb($bg));
            $ctx->strokeRect(8, 8, $cw - 16, $ch - 16, Color::rgb($border), StrokeParams::solid(2.0));

            $cx = $cw / 2;
            $cy = $ch / 2 - 15;

            // Cloud
            $ctx->fillCircle($cx, $cy, 25, Color::rgb($icon));
            $ctx->fillCircle($cx - 18, $cy + 8, 16, Color::rgb($icon));
            $ctx->fillCircle($cx + 18, $cy + 8, 16, Color::rgb($icon));
            $ctx->fillRect($cx - 25, $cy + 4, 50, 16, Color::rgb($icon));

            // Arrow
            $ctx->fillRect($cx - 3, $cy - 12, 6, 20, Color::rgb(0xFFFFFF));

            $font = new FontDescriptor('Arial', 13.0);
            $ctx->drawString('点击选择文件', $font, Color::rgb($text), $cx - 40, $cy + 45);
        }, background: (float) 0xF5F7FA);

        $dropNode = LayoutNode::leaf("{$key}:drop", $dropZone, width: $w, height: $dropH);

        // File path display
        $pathLabel = Ui::label('未选择文件', $w, 12.0, 18.0);

        // Select file button
        $selectBtn = Ui::button("{$key}:select", '选择文件', 'filled', 120);

        // Button row
        $btnRow = LayoutNode::row(gap: 10.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_START);
        $btnRow->child($selectBtn);

        // Click handlers
        $surface->onClick("{$key}:select", function () use ($out, $pathLabel, $surface) {
            $this->openFileAndShowInfo($out, $pathLabel, $surface);
        });

        $surface->onClick("{$key}:drop", function () use ($out, $pathLabel, $surface) {
            $this->openFileAndShowInfo($out, $pathLabel, $surface);
        });

        // Flat structure
        $children = [
            LayoutNode::leaf(null, new LabelSpec('文件信息', size: 16.0, opacity: 0.85), width: $w, height: 28.0),
            $pathLabel,
            $btnRow,
            $dropNode,
            $out->root(),
        ];

        $totalH = $dropH + 300.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 8.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private function openFileAndShowInfo(TextAreaControl $out, LayoutNode $pathLabel, Surface $surface): void
    {
        $win = \App\Native\WindowHolder::get();
        if ($win === null) return;
        $path = $win->dialogs()->openFile();
        if ($path === null || $path === '') return;

        // Update path label
        $pathLabel->spec = new LabelSpec($path, size: 12.0, opacity: 1.0);
        $surface->redraw();

        $info = Backend::fileInfo($path);
        if (isset($info['error'])) {
            $out->setValue('Error: ' . $info['error']);
            return;
        }
        $lines = [];
        foreach ($info as $k => $v) {
            $lines[] = $k . ': ' . $v;
        }
        $out->setValue(implode("\n", $lines));
    }
}
