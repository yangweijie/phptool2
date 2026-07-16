<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * Diff Compare with split pane inputs, statistics, diff navigation, WebView output.
 */
final class DiffPanel implements Panel
{
    private const PLACEHOLDER_HTML = '<!DOCTYPE html><html><head><meta charset="utf-8">'
        . '<style>body{font-family:-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;'
        . 'height:100vh;margin:0;color:#888;font-size:14px;background:#fafafa}</style></head>'
        . '<body><div>Enter text above and click <b>Compare</b> to see the diff</div></body></html>';

    private static float $splitRatio = 0.5;
    private static string $lastDiffA = '';
    private static string $lastDiffB = '';
    private static int $diffIndex = -1;
    /** @var list<array{type:string,lineA:int,lineB:int,content:string}> */
    private static array $diffBlocks = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $halfW = max(100, ($w * self::$splitRatio) - 4);
        $otherW = max(100, $w - $halfW - 12);
        $inputH = max(60, ($height - 380) * 0.4);

        // ── Left input ────────────────────────────────────────────────────
        $a = new TextAreaControl("{$key}:a", '', width: $halfW - 4, height: $inputH);
        $a->bind($surface);
        $a->setValue("# Hello World\n\nThis is the **original** text.\n\n- item 1\n- item 2\n");
        $leftCol = LayoutNode::column(gap: 4, width: $halfW, height: $inputH + 24, align: LayoutStyle::ALIGN_STRETCH);
        $leftCol->style->grow = 0;
        $leftCol->child(Ui::label('Original', $halfW - 4, 12, 20));
        $leftCol->child($a->root());
        $a->root()->style->grow = 1.0;

        // ── Right input ────────────────────────────────────────────────────
        $b = new TextAreaControl("{$key}:b", '', width: $otherW - 4, height: $inputH);
        $b->bind($surface);
        $b->setValue("# Hello World\n\nThis is the **modified** text.\n\n- item 1\n- item 2 (updated)\n- item 3\n");
        $rightCol = LayoutNode::column(gap: 4, width: $otherW, height: $inputH + 24, align: LayoutStyle::ALIGN_STRETCH);
        $rightCol->style->grow = 0;
        $rightCol->child(Ui::label('Modified', $otherW - 4, 12, 20));
        $rightCol->child($b->root());
        $b->root()->style->grow = 1.0;

        // ── Divider ───────────────────────────────────────────────────────
        $divider = LayoutNode::leaf("{$key}:divider", null, width: 8.0, height: $inputH);
        $surface->onDrag("{$key}:divider", function () use ($surface, $key, $w): void {
            $div = LayoutNode::find($surface->rootLayout(), "{$key}:divider");
            if ($div === null) return;
            $avail = $w - 12;
            $leftW = max(100, min($div->x, $avail - 100));
            self::$splitRatio = $leftW / $avail;
            $splitRow = LayoutNode::find($surface->rootLayout(), "{$key}:inputs");
            if ($splitRow !== null && count($splitRow->children) >= 3) {
                $splitRow->children[0]->style->width = $leftW;
                $splitRow->children[2]->style->width = $avail - $leftW;
            }
            $surface->redraw();
        });

        $inputRow = LayoutNode::row(id: "{$key}:inputs", gap: 0, height: $inputH + 24, align: LayoutStyle::ALIGN_CENTER);
        $inputRow->child($leftCol);
        $inputRow->child($divider);
        $inputRow->child($rightCol);

        // ── Toolbar ───────────────────────────────────────────────────────
        $toolbarRow = Ui::row([
            Ui::button("{$key}:run", 'Compare', 'filled', 100),
            Ui::button("{$key}:sample", 'Sample', 'soft', 80),
            Ui::button("{$key}:swap", '⇄ Swap', 'soft', 80),
            Ui::button("{$key}:clear", 'Clear', 'soft', 70),
            Ui::button("{$key}:prev", '◀ Prev', 'soft', 80),
            Ui::button("{$key}:next", 'Next ▶', 'soft', 80),
            Ui::button("{$key}:copy", '📋 Copy', 'soft', 80),
        ], gap: 6, height: 32);

        // ── Stats bar ─────────────────────────────────────────────────────
        $statsRow = LayoutNode::row(id: "{$key}:stats", gap: 8, height: 24, align: LayoutStyle::ALIGN_CENTER);

        // ── WebView output ───────────────────────────────────────────────
        $webId = "{$key}:web";
        $webH = max(100, $height - $inputH - 130);
        $webLeaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::PLACEHOLDER_HTML), width: $w, height: $webH);
        $webLeaf->style->grow = 1.0;

        // ── Wire handlers ─────────────────────────────────────────────────
        $surface->onClick("{$key}:run", function () use ($a, $b, $surface, $key): void {
            $ta = $a->getValue();
            $tb = $b->getValue();
            self::$lastDiffA = $ta;
            self::$lastDiffB = $tb;
            self::$diffBlocks = self::computeDiffBlocks(explode("\n", $ta), explode("\n", $tb));
            self::$diffIndex = -1;
            $this->renderDiff($surface, $key, $ta, $tb);
        });

        $surface->onClick("{$key}:sample", function () use ($a, $b, $surface, $key): void {
            $sampleA = "# Hello World\n\nThis is the **original** text.\n\n- item 1\n- item 2\n";
            $sampleB = "# Hello World\n\nThis is the **modified** text.\n\n- item 1\n- item 3\n- item 2 (updated)\n";
            $a->setValue($sampleA);
            $b->setValue($sampleB);
        });

        $surface->onClick("{$key}:swap", function () use ($a, $b): void {
            $tmp = $a->getValue();
            $a->setValue($b->getValue());
            $b->setValue($tmp);
        });

        $surface->onClick("{$key}:clear", function () use ($a, $b): void {
            $a->setValue('');
            $b->setValue('');
        });

        $surface->onClick("{$key}:prev", function () use ($surface, $key): void {
            if (self::$diffIndex > 0) {
                self::$diffIndex--;
                $this->scrollToDiff($surface, $key);
            }
        });

        $surface->onClick("{$key}:next", function () use ($surface, $key): void {
            if (self::$diffIndex < count(self::$diffBlocks) - 1) {
                self::$diffIndex++;
                $this->scrollToDiff($surface, $key);
            }
        });

        $surface->onClick("{$key}:copy", function () use ($surface, $key): void {
            $result = Backend::diffLines(self::$lastDiffA, self::$lastDiffB);
            $tmp = sys_get_temp_dir() . '/flyenv_diff_copy.txt';
            file_put_contents($tmp, $result);
        });

        // ── Assembly ──────────────────────────────────────────────────────
        $rows = LayoutNode::column(id: "{$key}:root", gap: 12, align: LayoutStyle::ALIGN_STRETCH, width: $w, height: $height);
        $rows->child(LayoutNode::leaf(null, null, width: $w, height: 16));
        $rows->child(Ui::title('Diff Compare', $w));
        $rows->child($inputRow);
        $rows->child($toolbarRow);
        $rows->child($statsRow);
        $rows->child($webLeaf);

        return $rows;
    }

    private function renderDiff(Surface $surface, string $key, string $a, string $b): void
    {
        $html = Backend::diffHtml($a, $b);
        $webNode = LayoutNode::find($surface->rootLayout(), "{$key}:web");
        if ($webNode !== null && $webNode->spec instanceof WebViewSpec) {
            $webNode->spec = new WebViewSpec(html: $html);
        }

        // Update stats
        $statsNode = LayoutNode::find($surface->rootLayout(), "{$key}:stats");
        if ($statsNode !== null) {
            $added = 0; $removed = 0; $changed = 0; $unchanged = 0;
            foreach (self::$diffBlocks as $blk) {
                match ($blk['type']) {
                    'add' => $added++,
                    'remove' => $removed++,
                    'change' => $changed++,
                    'same' => $unchanged++,
                    default => null,
                };
            }
            $statsNode->children = [];
            $statsNode->child(LayoutNode::leaf(null, new LabelSpec("+{$added}", size: 12, opacity: 0.9), width: 40, height: 22));
            $statsNode->child(LayoutNode::leaf(null, new LabelSpec("-{$removed}", size: 12, opacity: 0.9), width: 40, height: 22));
            $statsNode->child(LayoutNode::leaf(null, new LabelSpec("~{$changed}", size: 12, opacity: 0.9), width: 40, height: 22));
            $statsNode->child(LayoutNode::leaf(null, new LabelSpec("={$unchanged}", size: 12, opacity: 0.9), width: 50, height: 22));
        }
        $surface->redraw();
    }

    private function scrollToDiff(Surface $surface, string $key): void
    {
        // Highlight current diff block in the WebView by scrolling to it
        // For simplicity, just re-render with highlighting
        $this->renderDiff($surface, $key, self::$lastDiffA, self::$lastDiffB);
    }

    /**
     * Compute diff blocks (LCS-style) between two line arrays.
     * @param list<string> $aLines
     * @param list<string> $bLines
     * @return list<array{type:string,lineA:int,lineB:int,content:string}>
     */
    private static function computeDiffBlocks(array $aLines, array $bLines): array
    {
        // Simple LCS-based diff
        $lcs = self::longestCommonSubsequence($aLines, $bLines);
        $blocks = [];
        $ai = 0; $bi = 0;
        foreach ($lcs as [$la, $lb]) {
            // Lines before the LCS match in a (removed)
            while ($ai < $la) {
                $blocks[] = ['type' => 'remove', 'lineA' => $ai, 'lineB' => -1, 'content' => $aLines[$ai]];
                $ai++;
            }
            // Lines before the LCS match in b (added)
            while ($bi < $lb) {
                $blocks[] = ['type' => 'add', 'lineA' => -1, 'lineB' => $bi, 'content' => $bLines[$bi]];
                $bi++;
            }
            // Matching line
            $blocks[] = ['type' => 'same', 'lineA' => $ai, 'lineB' => $bi, 'content' => $aLines[$ai]];
            $ai++; $bi++;
        }
        // Remaining lines in a
        while ($ai < count($aLines)) {
            $blocks[] = ['type' => 'remove', 'lineA' => $ai, 'lineB' => -1, 'content' => $aLines[$ai]];
            $ai++;
        }
        while ($bi < count($bLines)) {
            $blocks[] = ['type' => 'add', 'lineA' => -1, 'lineB' => $bi, 'content' => $bLines[$bi]];
            $bi++;
        }
        return $blocks;
    }

    /**
     * @param list<string> $a
     * @param list<string> $b
     * @return list<array{int,int}>
     */
    private static function longestCommonSubsequence(array $a, array $b): array
    {
        $m = count($a); $n = count($b);
        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $dp[$i][$j] = $a[$i - 1] === $b[$j - 1]
                    ? $dp[$i - 1][$j - 1] + 1
                    : max($dp[$i - 1][$j], $dp[$i][$j - 1]);
            }
        }
        $result = [];
        $i = $m; $j = $n;
        while ($i > 0 && $j > 0) {
            if ($a[$i - 1] === $b[$j - 1]) {
                array_unshift($result, [$i - 1, $j - 1]);
                $i--; $j--;
            } elseif ($dp[$i - 1][$j] > $dp[$i][$j - 1]) {
                $i--;
            } else {
                $j--;
            }
        }
        return $result;
    }
}
