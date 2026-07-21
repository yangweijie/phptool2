<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Libui\Color;
use Libui\Draw\DrawContext;
use Libui\Text\FontDescriptor;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CanvasSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CheckboxSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class ProcessKillPanel implements Panel
{
    /** @var list<array{pid:int,user:string,command:string}> */
    private static array $procs = [];
    /** @var array<int,bool> pid => selected */
    private static array $selected = [];
    private static bool $killing = false;

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // Title row with icon
        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('进程查杀', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('⚡', size: 16.0), width: 24.0, height: 36.0));

        // Search input with search icon button
        $kwField = Ui::textField("{$key}:kw", 'Please Input Process Name', $w - 44.0);
        $searchBtn = Ui::button("{$key}:search", '🔍', 'outline', 36.0, 30.0);
        $searchRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $searchRow->child($kwField);
        $searchRow->child($searchBtn);

        // Action buttons
        $clearSelBtn = Ui::button("{$key}:clearsel", '清除选择', 'outline', 90.0, 30.0);
        $clearAllBtn = Ui::button("{$key}:clearall", '消除全部', 'danger', 90.0, 30.0);
        $actionRow = LayoutNode::row(gap: 8.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_START);
        $actionRow->child($clearSelBtn);
        $actionRow->child($clearAllBtn);

        // Table container
        $tableCol = LayoutNode::column(id: "{$key}:table", gap: 0.0, align: LayoutStyle::ALIGN_STRETCH, width: $w);
        foreach ($this->renderTableChildren($surface, $key, $w) as $child) {
            $tableCol->child($child);
        }
        $tableCol->style->height = self::computeTableHeight($tableCol);

        // Search handler
        $surface->onClick("{$key}:search", function () use ($surface, $key, $kwField, $w): void {
            $kw = $kwField->spec instanceof TextFieldSpec ? $kwField->spec->value : '';
            self::$killing = false;
            self::$procs = Backend::processKillSearch($kw);
            self::$selected = [];
            self::renderTable($surface, $key, $w);
        });

        // Clear selection handler
        $surface->onClick("{$key}:clearsel", function () use ($surface, $key, $w): void {
            self::$selected = [];
            self::renderTable($surface, $key, $w);
        });

        // Clear all handler
        $surface->onClick("{$key}:clearall", function () use ($surface, $key, $w): void {
            if (empty(self::$procs)) return;
            $pids = array_column(self::$procs, 'pid');
            self::$killing = true;
            Backend::processKillPids($pids);
            self::$killing = false;
            self::$procs = [];
            self::$selected = [];
            self::renderTable($surface, $key, $w);
        });

        // Flat structure
        $children = [
            $titleRow,
            $searchRow,
            $actionRow,
            $tableCol,
        ];

        $totalH = 400.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 8.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private static function computeTableHeight(LayoutNode $col): float
    {
        $total = 0.0;
        $count = count($col->children);
        $gap = $col->style->gap;
        foreach ($col->children as $i => $child) {
            $childH = $child->style->height ?? $child->style->basis ?? 0.0;
            $total += $childH;
            if ($i < $count - 1) {
                $total += $gap;
            }
        }
        return max($total, 200.0);
    }

    private function renderTable(Surface $surface, string $key, float $w): void
    {
        $tableNode = LayoutNode::find($surface->rootLayout(), "{$key}:table");
        if ($tableNode === null) return;
        $tableNode->children = [];
        foreach ($this->renderTableChildren($surface, $key, $w) as $child) {
            $tableNode->child($child);
        }
        $tableNode->style->height = self::computeTableHeight($tableNode);
        $surface->redraw();
    }

    /** @return list<LayoutNode> */
    private function renderTableChildren(Surface $surface, string $key, float $w): array
    {
        $children = [];

        // Header row
        $header = LayoutNode::row(gap: 0.0, height: 32.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $header->child(LayoutNode::leaf(null, new LabelSpec('☐', size: 13.0), width: 40.0, height: 32.0));
        $header->child(LayoutNode::leaf(null, new LabelSpec('PID', size: 13.0, opacity: 0.65), width: 100.0, height: 32.0));
        $header->child(LayoutNode::leaf(null, new LabelSpec('User', size: 13.0, opacity: 0.65), width: 150.0, height: 32.0));
        $header->child(LayoutNode::leaf(null, new LabelSpec('COMMAND', size: 13.0, opacity: 0.65), width: max(0, $w - 300.0), height: 32.0));
        $children[] = $header;

        if (empty(self::$procs)) {
            $emptyCanvas = new CanvasSpec(function (DrawContext $ctx, float $cw, float $ch): void {
                $font = new FontDescriptor('Arial', 13.0);
                $text = '暂无数据';
                $textW = strlen($text) * 13.0;
                $ctx->drawString($text, $font, Color::rgb(0x909399), ($cw - $textW) / 2, ($ch - 13) / 2);
            });
            $children[] = LayoutNode::leaf(null, $emptyCanvas, width: $w, height: 200.0);
            return $children;
        }

        foreach (self::$procs as $p) {
            $pid = (int) $p['pid'];
            $checked = !empty(self::$selected[$pid]);
            $row = LayoutNode::row(gap: 0.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
            $cbId = "{$key}:cb:{$pid}";
            $row->child(LayoutNode::leaf($cbId, new CheckboxSpec(checked: $checked), width: 40.0, height: 28.0));
            $row->child(LayoutNode::leaf(null, new LabelSpec((string) $pid, size: 13.0), width: 100.0, height: 28.0));
            $row->child(LayoutNode::leaf(null, new LabelSpec($p['user'], size: 13.0), width: 150.0, height: 28.0));
            $row->child(LayoutNode::leaf(null, new LabelSpec($p['command'], size: 13.0), width: max(0, $w - 300.0), height: 28.0));
            $children[] = $row;

            $surface->onClick($cbId, function () use ($pid, $surface, $key, $w): void {
                self::$selected[$pid] = empty(self::$selected[$pid]);
                self::renderTable($surface, $key, $w);
            });
        }

        return $children;
    }
}
