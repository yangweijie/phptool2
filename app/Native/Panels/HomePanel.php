<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Catalog;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * Home panel: shows favorite tools (if any) + all tools as a clean 4-column
 * card grid. Cards show large icon + small name, no category dividers.
 */
final class HomePanel implements Panel
{
    private Surface $surface;
    private string $key;
    private float $w;
    private float $h;
    private Catalog $catalog;
    public \Closure $onToolClick;

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $this->surface = $surface;
        $this->key = $key;
        $this->w = $width - 48;
        $this->h = $height;
        $this->catalog = Catalog::getInstance();
        // onToolClick is set once by NativeApp. Do NOT reset it here.

        $rows = [];
        $rowGap = 10.0;
        $rowCellH = 88.0;
        $rowCardRowGap = 14.0;
        // Adaptive columns based on width: 2/3/4/5/6/7/8 cols
        $cols = $this->w < 500 ? 2 : ($this->w < 750 ? 3 : ($this->w < 1000 ? 4 : ($this->w < 1300 ? 5 : ($this->w < 1600 ? 6 : ($this->w < 1900 ? 7 : 8)))));
        $allTools = $this->catalog->tools();
        $allGridH = (int) ceil(count($allTools) / $cols) * $rowCellH
                  + max(0, (int) ceil(count($allTools) / $cols) - 1) * $rowCardRowGap;

        // ── Favorites section ───────────────────────────────────────────
        $favs = $this->catalog->favorites();
        $hasFavs = count($favs) > 0;
        $favGridH = (int) ceil(count($favs) / $cols) * $rowCellH
                  + max(0, (int) ceil(count($favs) / $cols) - 1) * $rowCardRowGap;

        if ($hasFavs) {
            $rows[] = LayoutNode::leaf(null, new LabelSpec($this->catalog->chinese() ? '我的收藏' : 'My Favorites', size: 16, opacity: 0.8), width: $this->w, height: 28);
            $rows[] = $this->buildCardGrid($favs, 0, true);
            $rows[] = LayoutNode::leaf(null, null, height: 16.0);
        }

        // ── All Tools — single unified grid (no category dividers) ─────
        $rows[] = LayoutNode::leaf(null, new LabelSpec($this->catalog->chinese() ? '全部工具' : 'All Tools', size: 16, opacity: 0.8), width: $this->w, height: 28);
        $rows[] = $this->buildCardGrid($allTools, count($favs), false);

        // Compute total content height exactly so the scroll view's column
        // does not shrink any sub-tree.
        $contentHeight = 28 + 16 + 28 + $allGridH
                       + ($hasFavs ? $favGridH + 16 : 0)
                       + 4 * $rowGap; // 4 gaps between the 5 children
        $contentHeight += 8 + 48; // top+bottom slack + 2*24 vertical padding

        $sv = new ScrollViewControl('home:sv', $rows, width: $width, height: $height, padding: 24.0, gap: $rowGap, contentHeight: (float) $contentHeight);
        $sv->bind($surface);
        return $sv->root();
    }

    /**
     * Build a card grid: 4 columns, each cell is icon (centered) + name (centered).
     * The whole cell is clickable via onClick.
     *
     * The grid column gets an EXPLICIT height = N rows × (cellH + gap) so
     * FlexLayout never shrinks it inside a too-tall ScrollView contentHeight.
     *
     * @param list<array{id:string,cat:string,name:string,nameEn:string,icon:string}> $tools
     */
    private function buildCardGrid(array $tools, int $idOffset, bool $isFav): LayoutNode
    {
        // Adapt column count to available width: 3/4/5/6/7/8 cols
        $cols = $this->w < 500 ? 2 : ($this->w < 750 ? 3 : ($this->w < 1000 ? 4 : ($this->w < 1300 ? 5 : ($this->w < 1600 ? 6 : ($this->w < 1900 ? 7 : 8)))));
        $gap = 12.0;
        $rowGap = 14.0;
        $cellW = ($this->w - ($cols - 1) * $gap) / $cols;
        $cellH = 88.0;

        // Count exact rows including the partial last row
        $rowCount = (int) ceil(count($tools) / $cols);
        if ($rowCount === 0) $rowCount = 1; // empty grid still has 1 placeholder row
        $gridH = $rowCount * $cellH + max(0, $rowCount - 1) * $rowGap;

        $grid = LayoutNode::column(gap: $rowGap, align: LayoutStyle::ALIGN_STRETCH, width: $this->w, height: $gridH);
        $row = null;

        if (count($tools) === 0) {
            $placeholder = LayoutNode::row(gap: $gap, height: $cellH, align: LayoutStyle::ALIGN_CENTER);
            $grid->child($placeholder);
            $placeholder->child(LayoutNode::leaf(null, null, width: $cellW, height: $cellH));
            return $grid;
        }

        foreach ($tools as $i => $t) {
            $colInRow = $i % $cols;
            if ($colInRow === 0) {
                $row = LayoutNode::row(gap: $gap, height: $cellH, align: LayoutStyle::ALIGN_CENTER);
                $grid->child($row);
            }

            $card = $this->buildCard($t, $cellW, $cellH, $isFav);
            if ($row !== null) {
                $row->child($card);
            }
        }
        // Pad the last row so cells don't get stretched
        $col = (count($tools) - 1) % $cols;
        if ($row !== null) {
            for ($k = $col + 1; $k < $cols; $k++) {
                $row->child(LayoutNode::leaf(null, null, width: $cellW, height: $cellH));
            }
        }
        return $grid;
    }

    /**
     * @param array{id:string,cat:string,name:string,nameEn:string,icon:string} $tool
     */
    private function buildCard(array $tool, float $cellW, float $cellH, bool $isFav): LayoutNode
    {
        $icon = $tool['icon'] ?? '🔧';
        $name = $this->catalog->chinese() ? $tool['name'] : $tool['nameEn'];

        // Clickable card leaf — uses ButtonSpec 'soft' (no heavy chrome).
        // The label is the centered icon + name, separated by newline so it
        // shows as 2 lines: icon on top, name on bottom.
        $label = "{$icon}\n{$name}";
        $btnId = $isFav ? "home:fav:{$tool['id']}" : "home:tool:{$tool['id']}";
        $card = LayoutNode::leaf(
            $btnId,
            new ButtonSpec($label, 'card'),
            width: $cellW,
            height: $cellH,
        );

        $toolId = $tool['id'];
        $this->surface->onClick($btnId, function () use ($toolId): void {
            ($this->onToolClick)($toolId);
        });

        return $card;
    }
}
