<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Catalog;
use App\Native\Panel;
use App\Native\Ui;
use App\Native\WindowHolder;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Pickers\FilePickerDialog;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * Screenshot tool (macOS `screencapture -s` interactive region capture).
 * Mirrors the original webview Capturer: shortcut card, save dir + naming,
 * 3 action buttons (保存设置 / 截图 / 隐藏并截图).
 */
final class CapturerPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $zh = Catalog::chinese();
        $title = $zh ? '屏幕截图' : 'Screen Capture';
        $subtitle = $zh ? '截图快捷键' : 'Capture Shortcut';
        $noneText = $zh ? '无' : 'None';
        $tipText = $zh ? '请按下你想唤起截图功能的按键，例如 Ctrl+Shift+A' : 'Press the keys you want to invoke capture, e.g. Ctrl+Shift+A';

        // ── Shortcut display card (large empty card with placeholder) ────
        $shortcutCard = LayoutNode::leaf(
            "{$key}:shortcut",
            null,
            width: $w,
            height: 100,
        );
        $shortcutContent = LayoutNode::column(
            gap: 6,
            align: LayoutStyle::ALIGN_CENTER,
            width: $w,
            height: 100,
        );
        $shortcutContent->style->grow = 0;
        $shortcutContent->child(LayoutNode::leaf(null, new LabelSpec($noneText, size: 22, opacity: 0.55), width: $w, height: 36));
        $shortcutContent->child(LayoutNode::leaf(null, new LabelSpec($tipText, size: 11, opacity: 0.5), width: $w, height: 18));
        $shortcutCard->child($shortcutContent);

        $clearSelBtn = LayoutNode::leaf("{$key}:clearsel", new ButtonSpec($zh ? '清除' : 'Clear', 'soft'), width: 80, height: 28);
        $shortcutRow = LayoutNode::row(gap: 8, height: 32, align: LayoutStyle::ALIGN_CENTER);
        $shortcutRow->child(LayoutNode::leaf(null, null, height: 1.0)); // spacer
        $spacer = LayoutNode::leaf(null, null, height: 1.0);
        $spacer->style->grow = 1.0;
        $shortcutRow->child($spacer);
        $shortcutRow->child($clearSelBtn);

        $surface->onClick("{$key}:clearsel", function () use ($surface, $key): void {
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:shortcut");
            // Reset to "no shortcut recorded" — empty content
        });

        // ── Save directory ───────────────────────────────────────────────
        $dirField = LayoutNode::leaf("{$key}:dir", new TextFieldSpec(value: Backend::captureDefaultDir(), placeholder: $zh ? '保存位置' : 'Save location'), width: $w - 100, height: 32);
        $dirPickBtn = LayoutNode::leaf("{$key}:dirpick", new ButtonSpec('📂 ' . ($zh ? '浏览' : 'Browse'), 'soft'), width: 90, height: 32);
        $surface->onClick("{$key}:dirpick", function () use ($dirField): void {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $dirField->spec = new TextFieldSpec(value: $path, placeholder: 'Save location');
            }
        });
        $dirRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $dirRow->child(LayoutNode::leaf(null, new LabelSpec($zh ? '截图保存位置' : 'Save location', size: 12, opacity: 0.65), width: 90, height: 22));
        $dirRow->child($dirField);
        $dirRow->child($dirPickBtn);

        // ── Name template + helper buttons (with + prefix) ──────────────
        $nameField = LayoutNode::leaf("{$key}:name", new TextFieldSpec(value: '{timestamp}{index}{datetime}{uuid}', placeholder: 'Name template'), width: $w, height: 32);
        $nameRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $nameRow->child(LayoutNode::leaf(null, new LabelSpec($zh ? '截图命名规则' : 'Naming rules', size: 12, opacity: 0.65), width: 90, height: 22));
        $nameRow->child($nameField);

        $placeholderLabels = [
            '{index}' => '+ 序号',
            '{timestamp}' => '+ 时间戳',
            '{datetime}' => '+ 时间',
            '{uuid}' => '+ UUID',
        ];
        $placeholderRow = LayoutNode::row(gap: 6, height: 32, align: LayoutStyle::ALIGN_CENTER);
        foreach ($placeholderLabels as $ph => $label) {
            $id = "{$key}:ph:" . trim($ph, '{}');
            $pBtn = LayoutNode::leaf($id, new ButtonSpec($label, 'soft'), width: 90, height: 28);
            $surface->onClick($id, function () use ($nameField, $ph): void {
                $spec = $nameField->spec instanceof TextFieldSpec ? $nameField->spec : null;
                $cur = $spec?->value ?? '';
                $nameField->spec = new TextFieldSpec(value: $cur . $ph, placeholder: 'Name template');
            });
            $placeholderRow->child($pBtn);
        }

        // ── 3 action buttons (Save Settings / Capture / Capture & Hide) ─
        $saveBtn = LayoutNode::leaf("{$key}:save", new ButtonSpec('💾 ' . ($zh ? '保存' : 'Save'), 'soft'), width: 100, height: 32);
        $capBtn = LayoutNode::leaf("{$key}:cap", new ButtonSpec('📷 ' . ($zh ? '截图' : 'Capture'), 'filled'), width: 100, height: 32);
        $hideBtn = LayoutNode::leaf("{$key}:caphide", new ButtonSpec('🎯 ' . ($zh ? '隐藏此窗口截图' : 'Hide & Capture'), 'soft'), width: 160, height: 32);

        // ── Output ────────────────────────────────────────────────────────
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 80);
        $out->bind($surface);

        $surface->onClick("{$key}:save", function () use ($surface, $key, $out): void {
            $out->setValue('Settings saved: dir=' . $this->fieldVal($surface, $key, 'dir') . ' name=' . $this->fieldVal($surface, $key, 'name'));
        });

        $actionRow = LayoutNode::row(gap: 6, height: 40, align: LayoutStyle::ALIGN_CENTER);
        $actionRow->child($saveBtn);
        $actionRow->child($capBtn);
        $actionRow->child($hideBtn);

        $captureHandler = function (bool $hide) use ($surface, $key, $out): void {
            $dir = $this->fieldVal($surface, $key, 'dir') ?: Backend::captureDefaultDir();
            $name = $this->fieldVal($surface, $key, 'name') ?: 'fly_ss_{datetime}';
            $result = Backend::capture($dir, $name, $hide);
            $out->setValue($result);
        };
        $surface->onClick("{$key}:cap", fn() => $captureHandler(false));
        $surface->onClick("{$key}:caphide", fn() => $captureHandler(true));

        // ── Assembly ─────────────────────────────────────────────────────
        $rows = [
            Ui::title($title, $w),
            Ui::label($subtitle, $w),
            $shortcutCard,
            $shortcutRow,
            Ui::label($zh ? '保存位置' : 'Save location', $w),
            $dirRow,
            Ui::label($zh ? '命名规则' : 'Naming rules', $w),
            $nameRow,
            $placeholderRow,
            $actionRow,
            Ui::label($zh ? '输出' : 'Output', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 8.0, padding: 24.0, contentHeight: 760);
        $sv->bind($surface);
        return $sv->root();
    }

    private function fieldVal(Surface $surface, string $key, string $id): string
    {
        $n = LayoutNode::find($surface->rootLayout(), "{$key}:{$id}");
        if ($n === null || !($n->spec instanceof TextFieldSpec)) return '';
        return $n->spec->value;
    }
}
