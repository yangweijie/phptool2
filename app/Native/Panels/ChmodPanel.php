<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Libui\Color;
use Libui\Draw\DrawContext;
use Libui\Text\FontDescriptor;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CanvasSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CheckboxSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class ChmodPanel implements Panel
{
    /** @var list<bool> */
    private static array $bits = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        if (empty(self::$bits)) {
            self::$bits = Backend::chmodToBits('000');
        }

        $groups = ['Owner (u)', 'Group (g)', 'Public (o)'];
        $perms = ['Read (4)', 'Write (2)', 'Execute (1)'];
        $colW = ($w - 80) / 3;

        // Header row
        $headerRow = LayoutNode::row(gap: 0.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $headerRow->child(LayoutNode::leaf(null, new LabelSpec('', size: 12.0), width: 80.0, height: 28.0));
        foreach ($groups as $g) {
            $headerRow->child(LayoutNode::leaf(null, new LabelSpec($g, size: 12.0, opacity: 0.65), width: $colW, height: 28.0));
        }

        // Permission rows
        $permRows = [];
        foreach ($perms as $pi => $permLabel) {
            $row = LayoutNode::row(gap: 0.0, height: 32.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
            $row->child(LayoutNode::leaf(null, new LabelSpec($permLabel, size: 12.0), width: 80.0, height: 32.0));

            foreach ($groups as $gi => $gName) {
                $idx = $gi * 3 + $pi;
                $cbId = "{$key}:b{$idx}";
                $cb = LayoutNode::leaf($cbId, new CheckboxSpec(checked: self::$bits[$idx]), width: $colW, height: 32.0);
                $row->child($cb);

                $surface->onClick($cbId, function () use ($idx, $cbId, $surface, $key) {
                    self::$bits[$idx] = !self::$bits[$idx];
                    // Update checkbox visual state
                    $cbNode = LayoutNode::find($surface->rootLayout(), $cbId);
                    if ($cbNode !== null && $cbNode->spec instanceof CheckboxSpec) {
                        $cbNode->spec = new CheckboxSpec(checked: self::$bits[$idx]);
                    }
                    self::updateDisplay($surface, $key);
                });
            }
            $permRows[] = $row;
        }

        // Result display (CanvasSpec for large centered text)
        $resultCanvas = new CanvasSpec(function (DrawContext $ctx, float $cw, float $ch) {
            $oct = Backend::chmodFromBits(self::$bits)['octal'];
            $sym = Backend::chmodFromBits(self::$bits)['symbolic'];
            $font = new FontDescriptor('Arial', 36.0);
            $fontSm = new FontDescriptor('Arial', 24.0);
            // Octal number centered
            $ctx->drawString($oct, $font, Color::rgb(0x67C23A), ($cw - strlen($oct) * 22) / 2, 20);
            // Symbolic string centered
            $ctx->drawString($sym, $fontSm, Color::rgb(0x67C23A), ($cw - strlen($sym) * 15) / 2, 70);
        });
        $resultNode = LayoutNode::leaf(null, $resultCanvas, width: $w, height: 120.0);

        // Command display
        $cmd = Backend::chmodFromBits(self::$bits)['octal'];
        $cmdLabel = LayoutNode::leaf("{$key}:cmd", new LabelSpec("chmod {$cmd} path", size: 13.0), width: $w - 40.0, height: 24.0);
        $copyBtn = Ui::button("{$key}:copy", '📋', 'outline', 32.0, 24.0);
        $cmdRow = LayoutNode::row(gap: 4.0, height: 32.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $cmdRow->child($cmdLabel);
        $cmdRow->child($copyBtn);

        $surface->onClick("{$key}:copy", function () use ($surface, $key) {
            $node = LayoutNode::find($surface->rootLayout(), "{$key}:cmd");
            if ($node !== null && $node->spec instanceof LabelSpec) {
                Backend::copyText($node->spec->text);
            }
        });

        // Flat structure
        $children = array_merge([
            LayoutNode::leaf(null, new LabelSpec('Chmod计算器', size: 16.0, opacity: 0.85), width: $w, height: 36.0),
            $headerRow,
        ], $permRows, [
            $resultNode,
            $cmdRow,
        ]);

        $totalH = 400.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 4.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private static function updateDisplay(Surface $surface, string $key): void
    {
        $r = Backend::chmodFromBits(self::$bits);
        $node = LayoutNode::find($surface->rootLayout(), "{$key}:cmd");
        if ($node !== null && $node->spec instanceof LabelSpec) {
            $node->spec = new LabelSpec("chmod {$r['octal']} path", size: 13.0);
        }
        $surface->redraw();
    }
}
