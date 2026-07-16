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
 * Process Killer — matches original FlyEnv webview 1:1.
 * Search → checkbox list → Kill Selected / Kill All → structured result + Retry failed.
 */
final class ProcessKillPanel implements Panel
{
    /** @var list<array{pid:int,user:string,command:string}> */
    private static array $procs = [];
    /** @var array<int,bool> pid => selected */
    private static array $selected = [];
    private static bool $killing = false;
    private static ?array $lastKillResult = null;
    private static array $failedPids = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $kwField = Ui::textField("{$key}:kw", 'process name (空 = 全部)', 280);
        $searchBtn = Ui::button("{$key}:search", '🔍 查找', 'filled', 80, 30);
        $killSelBtn = Ui::button("{$key}:killsel", '⚡ 结束选中', 'soft', 110, 30);
        $killAllBtn = Ui::button("{$key}:killall", '💀 结束全部', 'danger', 100, 30);

        // ── Search handler ────────────────────────────────────────────────
        $surface->onClick("{$key}:search", function () use ($surface, $key, $kwField): void {
            $kw = $kwField->spec instanceof TextFieldSpec ? $kwField->spec->value : '';
            self::$killing = false;
            self::$lastKillResult = null;
            self::$failedPids = [];
            self::showStatus($surface, $key, '⏳ Searching...');
            self::$procs = Backend::processKillSearch($kw);
            self::$selected = [];
            self::renderTable($surface, $key);
        });

        // ── Kill Selected handler ─────────────────────────────────────────
        $surface->onClick("{$key}:killsel", function () use ($surface, $key): void {
            if (self::$killing) return;
            $pids = array_keys(array_filter(self::$selected));
            if (empty($pids)) return;
            self::doKill($surface, $key, $pids);
        });

        // ── Kill All handler ──────────────────────────────────────────────
        $surface->onClick("{$key}:killall", function () use ($surface, $key): void {
            if (self::$killing) return;
            if (empty(self::$procs)) return;
            $pids = array_column(self::$procs, 'pid');
            self::doKill($surface, $key, $pids);
        });

        // ── Layout ────────────────────────────────────────────────────────
        $searchRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $searchRow->child(LayoutNode::leaf(null, new LabelSpec('关键词:', size: 12), width: 60, height: 22));
        $searchRow->child($kwField);
        $searchRow->child($searchBtn);

        $actionRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $actionRow->child($killSelBtn);
        $actionRow->child($killAllBtn);

        $statusNode = LayoutNode::leaf("{$key}:status", null, width: $w, height: 0);

        // This column IS the table container — renderTable() will find it by id
        $tableCol = LayoutNode::column(id: "{$key}:table", gap: 1, align: LayoutStyle::ALIGN_STRETCH, width: $w);
        // Seed with initial content
        foreach ($this->renderTableChildren($surface, $key, $w) as $child) {
            $tableCol->child($child);
        }
        $tableCol->style->height = self::computeTableHeight($tableCol);

        $tableContent = [
            $searchRow,
            $actionRow,
            LayoutNode::leaf(null, null, height: 6),
            $statusNode,
            $tableCol,
        ];

        $sv = new ScrollViewControl("p:{$key}", $tableContent, width: $width, height: $height, gap: 10.0, padding: 18.0, contentHeight: 800.0);
        $sv->bind($surface);
        return $sv->root();
    }

    private static function showStatus(Surface $surface, string $key, string $msg): void
    {
        $node = LayoutNode::find($surface->rootLayout(), "{$key}:status");
        if ($node === null) return;
        if ($msg === '') {
            $node->spec = null;
            $node->style->height = 0.0;
        } else {
            $node->spec = new LabelSpec($msg, size: 12);
            $node->style->height = 22.0;
        }
    }

    private static function doKill(Surface $surface, string $key, array $pids): void
    {
        self::$killing = true;
        $n = count($pids);
        self::showStatus($surface, $key, "⏳ Killing {$n} process(es): " . implode(', ', $pids));
        $surface->redraw();

        $result = Backend::processKillPids($pids);
        self::$killing = false;
        self::$lastKillResult = $result;
        self::$failedPids = [];

        $killed = $result['killed'];
        $attempted = $result['attempted'];
        $status = "✓ Killed {$killed}/{$attempted} process(es)";

        foreach ($result['details'] as $d) {
            if (!$d['killed']) {
                self::$failedPids[] = $d['pid'];
            }
        }

        $errors = [];
        foreach ($result['details'] as $d) {
            if (!$d['killed'] && $d['error'] !== '') {
                $errors[] = "PID {$d['pid']}: {$d['error']}";
            }
        }
        if (!empty($errors)) {
            $status .= "\n" . implode("\n", $errors);
        }

        self::$procs = [];
        self::$selected = [];
        self::renderTable($surface, $key, $status);
    }

    private static function retryFailed(Surface $surface, string $key): void
    {
        if (self::$killing || empty(self::$failedPids)) return;
        $pids = self::$failedPids;
        self::$failedPids = [];
        self::$lastKillResult = null;
        self::doKill($surface, $key, $pids);
    }

    private function renderTable(Surface $surface, string $key, string $resultMsg = ''): void
    {
        $tableNode = LayoutNode::find($surface->rootLayout(), "{$key}:table");
        if ($tableNode === null) return;
        $w = ($tableNode->w ?? 800) - 8;
        $tableNode->children = [];
        foreach ($this->renderTableChildren($surface, $key, $w, $resultMsg) as $child) {
            $tableNode->child($child);
        }
        $tableNode->style->height = self::computeTableHeight($tableNode);
        $surface->redraw();
    }

    /**
     * Compute total height for a column by summing children heights + gaps.
     * Prevents FlexLayout from collapsing column-in-column to h=0.
     */
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
        return $total;
    }

    /**
     * Build table children (result message, header, rows) as flat array.
     * @return list<LayoutNode>
     */
    private function renderTableChildren(Surface $surface, string $key, float $w, string $resultMsg = ''): array
    {
        $children = [];

        if ($resultMsg !== '') {
            $lines = explode("\n", $resultMsg);
            foreach ($lines as $line) {
                $children[] = LayoutNode::leaf(null, new LabelSpec($line, size: 12), width: $w, height: 20);
            }

            if (!empty(self::$failedPids)) {
                $retryBtn = Ui::button("{$key}:retry", '🔄 Retry failed (' . count(self::$failedPids) . ')', 'soft', 180, 26);
                $children[] = $retryBtn;
                $children[] = LayoutNode::leaf(null, null, height: 6);
                $surface->onClick("{$key}:retry", function () use ($surface, $key): void {
                    self::retryFailed($surface, $key);
                });
            } else {
                $children[] = LayoutNode::leaf(null, null, height: 6);
            }
        }

        if (empty(self::$procs)) {
            $children[] = LayoutNode::leaf(null, new LabelSpec('暂无数据 — 输入关键词后点击 查找', size: 12, opacity: 0.55), width: $w, height: 24);
            return $children;
        }

        // Header row
        $header = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $header->child(LayoutNode::leaf(null, new LabelSpec('☐', size: 13), width: 24, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec('PID', size: 12, opacity: 0.65), width: 70, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec('User', size: 12, opacity: 0.65), width: 100, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec('Command', size: 12, opacity: 0.65), width: max(0, $w - 200), height: 22));
        $children[] = $header;

        // Data rows
        foreach (self::$procs as $p) {
            $pid = (int) $p['pid'];
            $checked = !empty(self::$selected[$pid]);
            $row = LayoutNode::row(gap: 6, height: 26, align: LayoutStyle::ALIGN_CENTER);
            $cbId = "{$key}:cb:{$pid}";
            $cbLeaf = LayoutNode::leaf($cbId, new CheckboxSpec(checked: $checked), width: 24, height: 22);
            $row->child($cbLeaf);
            $row->child(LayoutNode::leaf(null, new LabelSpec((string) $pid, size: 12), width: 70, height: 22));
            $row->child(LayoutNode::leaf(null, new LabelSpec($p['user'], size: 12), width: 100, height: 22));
            $row->child(LayoutNode::leaf(null, new LabelSpec($p['command'], size: 12), width: max(0, $w - 200), height: 22));
            $children[] = $row;
            $surface->onClick($cbId, function () use ($cbLeaf, $pid, $surface): void {
                self::$selected[$pid] = empty(self::$selected[$pid]);
                $cbLeaf->spec = new CheckboxSpec(checked: !empty(self::$selected[$pid]));
                $surface->redraw();
            });
        }
        return $children;
    }
}
