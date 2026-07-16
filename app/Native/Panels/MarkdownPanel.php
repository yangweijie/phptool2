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
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * Markdown Preview with split pane, drag divider, file open, WebView rendering.
 */
final class MarkdownPanel implements Panel
{
    private static float $splitRatio = 0.45;

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $halfW = max(100, ($w * self::$splitRatio) - 4);
        $otherW = max(100, $w - $halfW - 12);

        // ── Tab bar ───────────────────────────────────────────────────────
        $tabBar = LayoutNode::row(gap: 4, height: 28, align: LayoutStyle::ALIGN_CENTER);

        // Tab buttons
        $tabs = $this->getTabs();
        foreach ($tabs as $ti => $tab) {
            $tabId = "{$key}:mtab:{$ti}";
            $isActive = $ti === self::$activeTab;
            $btn = LayoutNode::leaf($tabId, new ButtonSpec($tab['name'], $isActive ? 'filled' : 'soft'), width: 80, height: 24);
            $surface->onClick($tabId, function () use ($key, $ti, $surface): void {
                self::$activeTab = $ti;
            });
            $tabBar->child($btn);
        }
        // Add tab
        $addBtn = LayoutNode::leaf("{$key}:mtabadd", new ButtonSpec('+', 'soft'), width: 24, height: 24);
        $surface->onClick("{$key}:mtabadd", function () use ($key): void {
            $n = count(self::$tabs) + 1;
            self::$tabs[] = ['name' => "Doc {$n}", 'input' => "# New Document\n\nWrite your markdown here.", 'output' => ''];
            self::$activeTab = count(self::$tabs) - 1;
        });
        $tabBar->child($addBtn);

        $spacer = LayoutNode::leaf(null, null, height: 1.0);
        $spacer->style->grow = 1.0;
        $tabBar->child($spacer);

        // ── Left pane (source) ────────────────────────────────────────────
        $currentTab = self::$tabs[self::$activeTab] ?? ['input' => '', 'output' => ''];
        $inputH = max(200, $height - 100);
        $in = new TextAreaControl("{$key}:in", '', width: $halfW - 8, height: $inputH);
        $in->bind($surface);
        $in->setValue($currentTab['input']);

        $leftCol = LayoutNode::column(gap: 4, width: $halfW, height: $inputH, align: LayoutStyle::ALIGN_STRETCH);
        $leftCol->style->grow = 0;
        $leftBtnRow = Ui::row([
            Ui::button("{$key}:open", '📂 Open', 'soft', 80, 26),
            Ui::button("{$key}:run", '▶ Render', 'filled', 100, 26),
        ], gap: 4, height: 30);
        $leftCol->child($leftBtnRow);
        $leftCol->child($in->root());
        $in->root()->style->grow = 1.0;

        // ── Divider ───────────────────────────────────────────────────────
        $divider = LayoutNode::leaf("{$key}:divider", null, width: 8.0, height: $inputH);
        $surface->onDrag("{$key}:divider", function () use ($surface, $key, $w): void {
            $div = LayoutNode::find($surface->rootLayout(), "{$key}:divider");
            if ($div === null) return;
            $avail = $w - 12;
            $leftW = max(100, min($div->x, $avail - 100));
            self::$splitRatio = $leftW / $avail;
            $splitRow = LayoutNode::find($surface->rootLayout(), "{$key}:split");
            if ($splitRow !== null && count($splitRow->children) >= 3) {
                $splitRow->children[0]->style->width = $leftW;
                $splitRow->children[2]->style->width = $avail - $leftW;
            }
            $surface->redraw();
        });

        // ── Right pane (WebView preview) ──────────────────────────────────
        $webId = "{$key}:web";
        $webH = $inputH;
        $initialHtml = $currentTab['output'] !== ''
            ? $currentTab['output']
            : Backend::markdownPreview($in->getValue());
        $webLeaf = LayoutNode::leaf($webId, new WebViewSpec(html: $initialHtml), width: $otherW, height: $webH);
        $webLeaf->style->grow = 1.0;

        // ── Wire handlers ─────────────────────────────────────────────────
        $surface->onClick("{$key}:run", function () use ($in, $surface, $key): void {
            $md = $in->getValue();
            self::$tabs[self::$activeTab]['input'] = $md;
            self::$tabs[self::$activeTab]['output'] = Backend::markdownPreview($md);
            $webNode = LayoutNode::find($surface->rootLayout(), "{$key}:web");
            if ($webNode !== null && $webNode->spec instanceof WebViewSpec) {
                $webNode->spec = new WebViewSpec(html: self::$tabs[self::$activeTab]['output']);
            }
            $surface->redraw();
        });

        $surface->onClick("{$key}:open", function () use ($in): void {
            $w = WindowHolder::get();
            if ($w === null) return;
            $path = FilePickerDialog::pick($w);
            if ($path !== null) {
                $in->setValue(Backend::fileRead($path));
            }
        });

        // ── Assembly ──────────────────────────────────────────────────────
        $splitRow = LayoutNode::row(id: "{$key}:split", gap: 0, height: $inputH, align: LayoutStyle::ALIGN_CENTER);
        $splitRow->child($leftCol);
        $splitRow->child($divider);
        $splitRow->child($webLeaf);
        $splitRow->style->grow = 1.0;

        $mainCol = LayoutNode::column(id: "{$key}:root", gap: 6, align: LayoutStyle::ALIGN_STRETCH, width: $w, height: $height);
        $mainCol->child(Ui::title('Markdown Preview', $w));
        $mainCol->child($tabBar);
        $mainCol->child($splitRow);

        return $mainCol;
    }

    /** @var list<array{name:string, input:string, output:string}> */
    private static array $tabs = [
        ['name' => 'Doc 1', 'input' => "# Heading\n\nSome **markdown** text.", 'output' => ''],
    ];
    private static int $activeTab = 0;

    /** @return list<array{name:string, input:string, output:string}> */
    private function getTabs(): array { return self::$tabs; }
}
