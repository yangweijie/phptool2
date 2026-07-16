<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use App\Native\WindowHolder;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Pickers\FilePickerDialog;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\DropdownMenuSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * JSON Parser with split pane, drag divider, 16 formats, tabs, file ops.
 */
final class JsonPanel implements Panel
{
    /** Current left-pane width ratio (stored across rebuilds). */
    private static float $splitRatio = 0.5;

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $toolbarH = 36.0;
        $tabbarH = 28.0;
        $titleH = 28.0;
        $paneH = max(200, $height - $titleH - $tabbarH - $toolbarH - 40);
        $halfW = ($width - 48) * self::$splitRatio;
        $halfW = max(100, min($halfW, $width - 48 - 100 - 8));
        $otherW = max(100, $width - 48 - $halfW - 8);

        // ── Tab bar ───────────────────────────────────────────────────────
        $tabBar = LayoutNode::row(gap: 4, height: 28, align: LayoutStyle::ALIGN_CENTER, id: "{$key}:tabbar");
        $currentTab = count($this->getTabs()) - 1;
        foreach ($this->getTabs() as $ti => $tab) {
            $tabId = "{$key}:tab:{$ti}";
            $btn = LayoutNode::leaf($tabId, new ButtonSpec($tab['name'], $ti === $currentTab ? 'filled' : 'soft'), width: 80, height: 24);
            $surface->onClick($tabId, function () use ($key, $ti): void {
                $this->switchTab($key, $ti);
                // rebuild
            });
            $tabBar->child($btn);
        }
        // Add tab button
        $addTabBtn = LayoutNode::leaf("{$key}:tabadd", new ButtonSpec('+', 'soft'), width: 24, height: 24);
        $surface->onClick("{$key}:tabadd", function () use ($surface, $key): void {
            $this->addTab($key);
        });
        $tabBar->child($addTabBtn);

        $spacer = LayoutNode::leaf(null, null, height: 1.0);
        $spacer->style->grow = 1.0;
        $tabBar->child($spacer);

        // Input type tag
        $typeTag = LayoutNode::leaf("{$key}:typetag", new ButtonSpec('(JSON)', 'soft'), width: 60, height: 22);
        $tabBar->child($typeTag);

        // Format selector (simulate with buttons for now)
        $fmtLabel = LayoutNode::leaf(null, new LabelSpec('Format:', size: 12), width: 50, height: 22);
        $tabBar->child($fmtLabel);

        $formats = ['json', 'json-minify', 'php', 'js', 'ts', 'yaml', 'xml', 'toml', 'goStruct', 'rustSerde', 'Java', 'Kotlin', 'MySQL'];
        $curFmt = 0;
        $fmtBtn = LayoutNode::leaf("{$key}:fmtbtn", new ButtonSpec($formats[$curFmt], 'soft'), width: 100, height: 24);
        $surface->onClick("{$key}:fmtbtn", function () use ($surface, $key): void {
            // Cycle format
            $fmtNode = LayoutNode::find($surface->rootLayout(), "{$key}:fmtbtn");
            $this->cycleFormat($surface, $key);
        });
        $tabBar->child($fmtBtn);

        // ── Left pane (input) ─────────────────────────────────────────────
        $in = new TextAreaControl("{$key}:in", '', width: $halfW - 8, height: $paneH);
        $in->bind($surface);

        $leftCol = LayoutNode::column(gap: 4, width: $halfW, height: $paneH, align: LayoutStyle::ALIGN_STRETCH);
        $leftCol->style->grow = 0;
        $leftRow = Ui::row([
            Ui::button("{$key}:open", '📂 Open', 'soft', 80, 26),
            Ui::button("{$key}:detect", '🔍 Auto', 'soft', 80, 26),
        ], gap: 4, height: 30);
        $leftCol->child($leftRow);
        $leftCol->child($in->root());
        $in->root()->style->grow = 1.0;

        // ── Divider (drag handle) ─────────────────────────────────────────
        $divider = LayoutNode::leaf("{$key}:divider", null, width: 8.0, height: $paneH);
        $surface->onDrag("{$key}:divider", function () use ($surface, $key, $width): void {
            $this->onDragDivider($surface, $key, $width);
        });

        // ── Right pane (output) ───────────────────────────────────────────
        $out = new TextAreaControl("{$key}:out", '', width: $otherW - 8, height: $paneH);
        $out->bind($surface);

        $rightCol = LayoutNode::column(gap: 4, width: $otherW, height: $paneH, align: LayoutStyle::ALIGN_STRETCH);
        $rightCol->style->grow = 0;
        $rightBtnRow = Ui::row([
            Ui::button("{$key}:fmt", 'Format', 'filled', 80, 26),
            Ui::button("{$key}:val", 'Validate', 'soft', 80, 26),
            Ui::button("{$key}:sortasc", 'A→Z', 'soft', 60, 26),
            Ui::button("{$key}:sortdesc", 'Z→A', 'soft', 60, 26),
            Ui::button("{$key}:sortnone", '∅', 'soft', 40, 26),
            Ui::button("{$key}:save", '💾 Save', 'soft', 80, 26),
        ], gap: 4, height: 30);
        $rightCol->child($rightBtnRow);
        $rightCol->child($out->root());
        $out->root()->style->grow = 1.0;

        // ── Wire handlers ─────────────────────────────────────────────────
        $surface->onClick("{$key}:fmt", fn() => $out->setValue(self::convertFormat($in->getValue(), $key, $surface)));
        $surface->onClick("{$key}:val", fn() => $out->setValue(Backend::jsonValidate($in->getValue())));
        $surface->onClick("{$key}:sortasc", fn() => $this->setSort($surface, $key, 'asc'));
        $surface->onClick("{$key}:sortdesc", fn() => $this->setSort($surface, $key, 'desc'));
        $surface->onClick("{$key}:sortnone", fn() => $this->setSort($surface, $key, 'none'));
        $surface->onClick("{$key}:open", function () use ($in): void {
            $w = WindowHolder::get();
            if ($w === null) return;
            $path = FilePickerDialog::pick($w);
            if ($path !== null) {
                $in->setValue(Backend::fileRead($path));
            }
        });
        $surface->onClick("{$key}:detect", function () use ($in, $surface, $key): void {
            $raw = $in->getValue();
            $detected = $this->detectType($raw);
            $tagNode = LayoutNode::find($surface->rootLayout(), "{$key}:typetag");
            if ($tagNode !== null) {
                $tagNode->spec = new ButtonSpec("({$detected})", 'soft');
            }
        });
        $surface->onClick("{$key}:save", function () use ($out): void {
            $w = WindowHolder::get();
            if ($w === null) return;
            $path = FilePickerDialog::pick($w);
            if ($path !== null) {
                $out->setValue(Backend::fileSave($path, $out->getValue()));
            }
        });
        // Auto-trigger detect on input change (via onText)
        // We'll re-detect when Format is clicked (already calls convertFormat)

        // ── Root ──────────────────────────────────────────────────────────
        $splitRow = LayoutNode::row(id: "{$key}:split", gap: 0, height: $paneH, align: LayoutStyle::ALIGN_CENTER);
        $splitRow->child($leftCol);
        $splitRow->child($divider);
        $splitRow->child($rightCol);

        $rows = [
            Ui::title('JSON Parser', $width - 48),
            $tabBar,
            $splitRow,
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: $height);
        $sv->bind($surface);
        return $sv->root();
    }

    // ── Tab management ──────────────────────────────────────────────────────
    /** @var list<array{name:string, input:string, output:string, format:string, sort:string}> */
    private static array $tabs = [
        ['name' => 'Tab 1', 'input' => '', 'output' => '', 'format' => 'json', 'sort' => 'none'],
    ];
    private static int $activeTab = 0;

    /** @return list<array{name:string, input:string, output:string, format:string, sort:string}> */
    private function getTabs(): array { return self::$tabs; }

    private function switchTab(string $key, int $ti): void
    {
        self::$activeTab = $ti;
    }

    private function addTab(string $key): void
    {
        $n = count(self::$tabs) + 1;
        self::$tabs[] = ['name' => "Tab {$n}", 'input' => '', 'output' => '', 'format' => 'json', 'sort' => 'none'];
        self::$activeTab = count(self::$tabs) - 1;
    }

    // ── Drag divider ────────────────────────────────────────────────────────
    private function onDragDivider(Surface $surface, string $key, float $totalW): void
    {
        // Read mouse x from the drag event; update left column width
        $divNode = LayoutNode::find($surface->rootLayout(), "{$key}:divider");
        if ($divNode === null) return;
        $avail = $totalW - 48 - 8; // minus padding + divider width
        $leftW = max(100, min($divNode->x, $avail - 100));
        self::$splitRatio = $leftW / $avail;

        // Update children widths
        $splitRow = LayoutNode::find($surface->rootLayout(), "{$key}:split");
        if ($splitRow === null || count($splitRow->children) < 3) return;
        $splitRow->children[0]->style->width = $leftW;
        $splitRow->children[2]->style->width = $avail - $leftW;
        $surface->redraw();
    }

    // ── Format / sort ───────────────────────────────────────────────────────
    private static string $currentSort = 'none';
    private static int $currentFormatIdx = 0;
    /** @var list<string> */
    private static array $formatList = ['json', 'json-minify', 'php', 'js', 'ts', 'yaml', 'xml', 'toml', 'goStruct', 'rustSerde', 'Java', 'Kotlin', 'MySQL'];

    private function setSort(Surface $surface, string $key, string $sort): void
    {
        self::$currentSort = $sort;
    }

    private function cycleFormat(Surface $surface, string $key): void
    {
        self::$currentFormatIdx = (self::$currentFormatIdx + 1) % count(self::$formatList);
    }

    private function currentFormat(): string
    {
        return self::$formatList[self::$currentFormatIdx];
    }

    private function convertFormat(string $input, string $key, Surface $surface): string
    {
        return Backend::jsonConvert($input, self::$formatList[self::$currentFormatIdx], self::$currentSort);
    }

    private function detectType(string $raw): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') return 'JSON';
        if (str_starts_with($trimmed, '<?php') || str_starts_with($trimmed, '[') && str_contains($trimmed, '=>')) return 'PHP';
        if (str_starts_with($trimmed, '<')) return 'XML';
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) return 'JSON';
        if (str_starts_with($trimmed, 'http') || str_starts_with($trimmed, 'www')) return 'URL';
        if (preg_match('/^\w+:/', $trimmed)) return 'YAML';
        return 'JSON';
    }
}
