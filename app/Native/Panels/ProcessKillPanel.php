<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CheckboxSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * Process Killer — process table with checkboxes, Search/Kill Selected/Kill All.
 */
final class ProcessKillPanel implements Panel
{
    /** @var list<array{pid:int,user:string,command:string}> */
    private static array $procs = [];
    /** @var array<int,bool> pid => selected */
    private static array $selected = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $kwField = Ui::textField("{$key}:kw", 'process name (空 = 全部)', 280);
        $searchBtn = Ui::button("{$key}:search", '🔍 Lookup', 'filled', 110);
        $killSelBtn = Ui::button("{$key}:killsel", '⚡ Kill Selected', 'soft', 140);
        $killAllBtn = Ui::button("{$key}:killall", '💀 Kill All', 'soft', 110);
        $clearBtn = Ui::button("{$key}:clear", '🗑 Clear', 'soft', 80);

        $surface->onClick("{$key}:search", function () use ($kwField): void {
            $kw = $kwField->spec instanceof TextFieldSpec ? $kwField->spec->value : '';
            self::$procs = Backend::processKillSearch($kw);
            self::$selected = [];
        });

        $surface->onClick("{$key}:killsel", function () use ($surface, $key): void {
            $pids = array_keys(array_filter(self::$selected));
            if (empty($pids)) return;
            $result = Backend::processKillPids($pids);
            self::$procs = [];
            self::$selected = [];
            self::renderTable($surface, $key, $result);
        });

        $surface->onClick("{$key}:killall", function () use ($surface, $key): void {
            if (empty(self::$procs)) return;
            $pids = array_column(self::$procs, 'pid');
            $result = Backend::processKillPids($pids);
            self::$procs = [];
            self::$selected = [];
            self::renderTable($surface, $key, $result);
        });

        $surface->onClick("{$key}:clear", function () use ($surface, $key): void {
            self::$procs = [];
            self::$selected = [];
            self::renderTable($surface, $key);
        });

        $searchRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $searchRow->child(LayoutNode::leaf(null, new LabelSpec('关键词:', size: 12), width: 60, height: 22));
        $searchRow->child($kwField);
        $searchRow->child($searchBtn);

        $actionRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $actionRow->child($killSelBtn);
        $actionRow->child($killAllBtn);
        $actionRow->child($clearBtn);

        $tableCol = LayoutNode::column(id: "{$key}:table", gap: 1, align: LayoutStyle::ALIGN_STRETCH, width: $w);

        $tableContent = [
            $searchRow,
            $actionRow,
            LayoutNode::leaf(null, null, height: 6),
            $this->renderTableNodes($surface, $key, $w),
        ];

        $sv = new ScrollViewControl("p:{$key}", $tableContent, width: $width, height: $height, gap: 10.0, padding: 18.0, contentHeight: 800.0);
        $sv->bind($surface);
        return $sv->root();
    }

    private function renderTable(Surface $surface, string $key, string $result = ''): void
    {
        $tableNode = LayoutNode::find($surface->rootLayout(), "{$key}:table");
        if ($tableNode === null) return;
        $w = ($tableNode->w ?? 800) - 8;
        $tableNode->children = [];
        $tableNode->child($this->renderTableNodes($surface, $key, $w, $result));
        $surface->redraw();
    }

    private function renderTableNodes(Surface $surface, string $key, float $w, string $result = ''): LayoutNode
    {
        $col = LayoutNode::column(id: "{$key}:tablecol", gap: 1, align: LayoutStyle::ALIGN_STRETCH, width: $w);
        if ($result !== '') {
            $col->child(LayoutNode::leaf(null, new LabelSpec($result, size: 12), width: $w, height: 24));
            $col->child(LayoutNode::leaf(null, null, height: 6));
        }
        if (empty(self::$procs)) {
            $col->child(LayoutNode::leaf(null, new LabelSpec('暂无数据 — 输入关键词后点击 Lookup', size: 12, opacity: 0.55), width: $w, height: 24));
            return $col;
        }

        $header = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $header->child(LayoutNode::leaf(null, new LabelSpec('☐', size: 13), width: 24, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec('PID', size: 12, opacity: 0.65), width: 60, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec('User', size: 12, opacity: 0.65), width: 90, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec('Command', size: 12, opacity: 0.65), width: 200, height: 22));
        $col->child($header);

        foreach (self::$procs as $p) {
            $pid = (int) $p['pid'];
            $checked = !empty(self::$selected[$pid]);
            $row = LayoutNode::row(gap: 6, height: 26, align: LayoutStyle::ALIGN_CENTER);
            $cbId = "{$key}:cb:{$pid}";
            $cbLeaf = LayoutNode::leaf($cbId, new CheckboxSpec(checked: $checked), width: 24, height: 22);
            $row->child($cbLeaf);
            $row->child(LayoutNode::leaf(null, new LabelSpec((string) $pid, size: 12), width: 60, height: 22));
            $row->child(LayoutNode::leaf(null, new LabelSpec($p['user'], size: 12), width: 90, height: 22));
            $row->child(LayoutNode::leaf(null, new LabelSpec($p['command'], size: 12), width: 200, height: 22));
            $col->child($row);
            $surface->onClick($cbId, function () use ($cbLeaf, $pid, $surface): void {
                self::$selected[$pid] = empty(self::$selected[$pid]);
                $cbLeaf->spec = new CheckboxSpec(checked: !empty(self::$selected[$pid]));
                $surface->redraw();
            });
        }
        return $col;
    }
}
