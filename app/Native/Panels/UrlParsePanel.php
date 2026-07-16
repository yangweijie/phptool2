<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * URL Parse — 1:1 with the legacy webview tool (`__p.urlparse`):
 * a single URL input parsed live on every keystroke, eight read-only
 * component fields (protocol / username / password / hostname / port /
 * path / query / hash) each with a Copy button, and a Params section
 * listing every query parameter as a key/value row with Copy buttons.
 */
final class UrlParsePanel implements Panel
{
    /** Parsed component values, kept for the Copy handlers. */
    private array $comp = [];

    /** Parsed query parameters [ [k, v], ... ]. */
    private array $params = [];

    /** Scroll container, kept so we can grow its content height with params. */
    private ?ScrollViewControl $sv = null;

    private const DEFAULT_URL = 'https://me:pwd@www.macphpstudy.com:3000/sponsor.html?key1=value&key2=value2#thanks';

    private const COMPS = [
        'protocol' => 'Protocol',
        'username' => 'Username',
        'password' => 'Password',
        'hostname' => 'Hostname',
        'port'     => 'Port',
        'pathname' => 'Path',
        'search'   => 'Query',
        'hash'     => 'Hash',
    ];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // ── URL input (live-parsed) ──────────────────────────────────────
        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 60);
        $in->setValue(self::DEFAULT_URL);
        $in->bind($surface);
        $in->onChange(fn (string $v) => $this->render($surface, $key, $w, $v));

        // ── Eight component rows ─────────────────────────────────────────
        $rows = [
            Ui::title('URL Parser', $w),
            Ui::label('URL', $w),
            $in->root(),
        ];
        $fieldW = $w - 90 - 60 - 16; // label 90 + copy 60 + 2 gaps
        foreach (self::COMPS as $k => $label) {
            $field = LayoutNode::leaf("{$key}:f_{$k}", new TextFieldSpec(value: '', radius: 6.0), width: $fieldW, height: 34);
            $copy = Ui::button("{$key}:cp_{$k}", 'Copy', 'soft', 60, 34);
            $row = LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 34);
            $row->child(Ui::label($label, 90, 13, 34));
            $row->child($field);
            $row->child($copy);
            $rows[] = $row;

            $surface->onClick("{$key}:cp_{$k}", function () use ($k): void {
                Backend::copyText($this->comp[$k] ?? '');
            });
        }

        // ── Params section (rebuilt on every parse) ──────────────────────
        $rows[] = Ui::label('Params', $w, 14, 28);
        $rows[] = LayoutNode::column(id: "{$key}:params", gap: 6, align: LayoutStyle::ALIGN_STRETCH, width: $w, height: 0);

        // Initial parse (default URL) so the panel is populated on open.
        $this->parseInto($key, self::DEFAULT_URL);
        $contentH = 600.0; // provisional; replaced by measureContentHeight() below

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 8, padding: 16.0, contentHeight: $contentH);
        $sv->bind($surface);
        $this->sv = $sv;

        // Populate the nodes (component fields + params container).
        $this->renderTree($sv->root(), $key, $w, $surface);

        // Size the scroll area to the real content height (not a formula guess)
        // so every param row is reachable by scrolling.
        $sv->setContentHeight($this->measureContentHeight());

        return $sv->root();
    }

    /**
     * Parse $url, store results in $this->comp / $this->params, and return
     * the number of query parameters (used to size the scroll content).
     */
    private function parseInto(string $key, string $url): int
    {
        $parts = @parse_url($url);
        if ($parts === false) {
            $parts = [];
        }
        $this->comp = [
            'protocol' => isset($parts['scheme']) ? $parts['scheme'] . ':' : '',
            'username' => $parts['user'] ?? '',
            'password' => $parts['pass'] ?? '',
            'hostname' => $parts['host'] ?? '',
            'port'     => isset($parts['port']) ? (string) $parts['port'] : '',
            'pathname' => $parts['path'] ?? '',
            'search'   => isset($parts['query']) ? '?' . $parts['query'] : '',
            'hash'     => isset($parts['fragment']) ? '#' . $parts['fragment'] : '',
        ];

        $params = [];
        if (isset($parts['query']) && $parts['query'] !== '') {
            foreach (explode('&', $parts['query']) as $pair) {
                if ($pair === '') {
                    continue;
                }
                $kv = explode('=', $pair, 2);
                $params[] = [$kv[0], $kv[1] ?? ''];
            }
        }
        $this->params = $params;

        return count($params);
    }

    /** Update the component fields and rebuild the params rows in $root. */
    private function renderTree(LayoutNode $root, string $key, float $w, Surface $surface): void
    {
        foreach (self::COMPS as $k => $_) {
            $node = LayoutNode::find($root, "{$key}:f_{$k}");
            if ($node !== null) {
                $node->spec = new TextFieldSpec(value: $this->comp[$k] ?? '', radius: 6.0);
            }
        }

        $pcol = LayoutNode::find($root, "{$key}:params");
        if ($pcol !== null) {
            $pcol->children = [];
            $half = ($w - 60 - 16) / 2; // two fields + copy 60 + 2 gaps
            foreach ($this->params as $i => [$pk, $pv]) {
                $kf = LayoutNode::leaf("{$key}:pqk_{$i}", new TextFieldSpec(value: $pk, radius: 6.0), width: $half, height: 34);
                $vf = LayoutNode::leaf("{$key}:pqv_{$i}", new TextFieldSpec(value: $pv, radius: 6.0), width: $half, height: 34);
                $cb = Ui::button("{$key}:cpq_{$i}", 'Copy', 'soft', 60, 34);
                $row = LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 34);
                $row->child($kf);
                $row->child($vf);
                $row->child($cb);
                $pcol->child($row);
            }
            // The layout engine has no content measurement (v1): a column with
            // height 0 shrinks its children into zero space, overlapping the
            // param rows. Give the params column its real height so rows stack.
            $pcol->style->height = count($this->params) > 0 ? count($this->params) * 40.0 : 0.0;
        }

        $this->registerParamCopies($key, $surface);
    }

    /** Wire onClick for every rendered param Copy button. */
    private function registerParamCopies(string $key, Surface $surface): void
    {
        foreach ($this->params as $i => [$pk, $pv]) {
            $id = "{$key}:cpq_{$i}";
            $value = $pv;
            // Surface handler keyed by id; closure captures the value snapshot.
            $surface->onClick($id, function () use ($value): void {
                Backend::copyText($value);
            });
        }
    }

    /** Called on every keystroke: re-parse, refresh the tree, redraw. */
    private function render(Surface $surface, string $key, float $w, string $url): void
    {
        $this->parseInto($key, $url);
        $this->renderTree($surface->rootLayout(), $key, $w, $surface);
        $this->sv?->setContentHeight($this->measureContentHeight());
        $surface->redraw();
    }

    /**
     * Compute the panel's real content height from the fixed heights of its
     * direct children (every child — title, label, text area, component rows
     * and the params column — declares an explicit height). This is exact and
     * never depends on a prior (stale) content height that would otherwise
     * cap and shrink the layout.
     */
    private function measureContentHeight(): float
    {
        $viewport = $this->sv?->root();
        if ($viewport === null) {
            return 600.0;
        }
        $content = $viewport->children[0] ?? null;
        if ($content === null) {
            return 600.0;
        }

        $sum = 0.0;
        foreach ($content->children as $c) {
            $sum += (float) ($c->style->height ?? 0.0);
        }
        $childCount = count($content->children);
        $natural = $content->style->padding * 2
            + $sum
            + max(0.0, $childCount - 1) * $content->style->gap;

        // Always at least the viewport so the container stays valid.
        return max($natural, $this->sv->viewportHeight());
    }
}
