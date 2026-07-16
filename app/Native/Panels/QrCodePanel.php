<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use App\Native\WindowHolder;
use Libui\Color;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Pickers\ColorPickerDialog;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * QR Code Generator with fg/bg color pickers, ECC level, scale, PNG download.
 */
final class QrCodePanel implements Panel
{
    private static string $fgColor = '#000000';
    private static string $bgColor = '#ffffff';
    private static string $eccLevel = 'M';
    private static int $scale = 8;
    private static ?TextAreaControl $inputControl = null;

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 80);
        $in->bind($surface);
        $in->setValue('https://github.com');
        self::$inputControl = $in;

        // ── ECC Level ───────────────────────────────────────────────────
        $eccOptions = ['L', 'M', 'Q', 'H'];
        $eccIdx = array_search(self::$eccLevel, $eccOptions, true) ?: 1;
        $eccBtn = LayoutNode::leaf("{$key}:ecc", new ButtonSpec("ECC: " . self::$eccLevel, 'soft'), width: 70, height: 24);
        $surface->onClick("{$key}:ecc", function () use ($surface, $key): void {
            $eccOptions = ['L', 'M', 'Q', 'H'];
            $idx = (array_search(self::$eccLevel, $eccOptions, true) + 1) % 4;
            self::$eccLevel = $eccOptions[$idx];
            $btn = LayoutNode::find($surface->rootLayout(), "{$key}:ecc");
            if ($btn !== null) $btn->spec = new ButtonSpec("ECC: " . self::$eccLevel, 'soft');
            self::renderQr($surface, $key);
        });

        // ── Scale / Size ────────────────────────────────────────────
        $sizes = [4, 6, 8, 10, 12, 16];
        $sizeBtn = LayoutNode::leaf("{$key}:size", new ButtonSpec(self::$scale . 'x', 'soft'), width: 60, height: 24);
        $surface->onClick("{$key}:size", function () use ($surface, $key): void {
            $sizes = [4, 6, 8, 10, 12, 16];
            $idx = array_search(self::$scale, $sizes, true);
            $idx = ($idx === false || $idx >= count($sizes) - 1) ? 0 : $idx + 1;
            self::$scale = $sizes[$idx];
            $btn = LayoutNode::find($surface->rootLayout(), "{$key}:size");
            if ($btn !== null) $btn->spec = new ButtonSpec(self::$scale . 'x', 'soft');
            self::renderQr($surface, $key);
        });

        // ── Color pickers ─────────────────────────────────────────────
        $fgBtn = LayoutNode::leaf("{$key}:fg", new ButtonSpec('⬛ ' . self::$fgColor, 'soft'), width: 110, height: 24);
        $surface->onClick("{$key}:fg", function () use ($surface, $key): void {
            $color = ColorPickerDialog::pick(WindowHolder::get());
            if ($color !== null) {
                self::$fgColor = self::colorToHex($color);
                $btn = LayoutNode::find($surface->rootLayout(), "{$key}:fg");
                if ($btn !== null) $btn->spec = new ButtonSpec('⬛ ' . self::$fgColor, 'soft');
                self::renderQr($surface, $key);
            }
        });

        $bgBtn = LayoutNode::leaf("{$key}:bg", new ButtonSpec('⬜ ' . self::$bgColor, 'soft'), width: 110, height: 24);
        $surface->onClick("{$key}:bg", function () use ($surface, $key): void {
            $color = ColorPickerDialog::pick(WindowHolder::get());
            if ($color !== null) {
                self::$bgColor = self::colorToHex($color);
                $btn = LayoutNode::find($surface->rootLayout(), "{$key}:bg");
                if ($btn !== null) $btn->spec = new ButtonSpec('⬜ ' . self::$bgColor, 'soft');
                self::renderQr($surface, $key);
            }
        });

        // ── Control row ────────────────────────────────────────────
        $controls = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $controls->child(LayoutNode::leaf(null, new LabelSpec('ECC:', size: 12), width: 40, height: 24))->child($eccBtn);
        $controls->child(LayoutNode::leaf(null, new LabelSpec('Size:', size: 12), width: 40, height: 24))->child($sizeBtn);
        $controls->child(LayoutNode::leaf(null, new LabelSpec('FG:', size: 12), width: 28, height: 24))->child($fgBtn);
        $controls->child(LayoutNode::leaf(null, new LabelSpec('BG:', size: 12), width: 28, height: 24))->child($bgBtn);

        // ── Action buttons ─────────────────────────────────────────────
        $genBtn = Ui::button("{$key}:gen", '▶ Generate', 'filled', 120);
        $surface->onClick("{$key}:gen", fn() => self::renderQr($surface, $key));

        $dlBtn = Ui::button("{$key}:dl", '💾 Save PNG', 'soft', 120);
        $surface->onClick("{$key}:dl", function () use ($in): void {
            $path = sys_get_temp_dir() . '/qrcode_' . time() . '.png';
            Backend::qrCodeSavePng($in->getValue(), $path, self::$eccLevel, self::$scale, self::$fgColor, self::$bgColor);
        });

        // ── WebView for QR display ────────────────────────────────────
        $webId = "{$key}:web";
        $initialSvg = Backend::qrCodeGenerate($in->getValue(), self::$eccLevel, self::$scale, self::$fgColor, self::$bgColor);
        $webLeaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::wrap($initialSvg)), width: $width);
        $webLeaf->style->grow = 1.0;

        // ── Assembly ───────────────────────────────────────────────────
        $inputH = 28 + 12 + 22 + 12 + 80 + 12 + 30 + 12 + 30 + 36;
        $inputRows = [
            Ui::title('QR Code Generator', $w),
            Ui::label('Text to encode', $w), $in->root(),
            $controls,
            Ui::row([$genBtn, $dlBtn]),
        ];
        $inputScroll = new ScrollViewControl("p:{$key}", $inputRows, width: $width, height: $inputH,
            gap: 10.0, padding: 18.0, contentHeight: $inputH);
        $inputScroll->bind($surface);
        $inputScroll = $inputScroll->root();

        $outer = LayoutNode::column(id: "{$key}:col", align: LayoutStyle::ALIGN_STRETCH, width: $width, height: $height);
        $outer->child($inputScroll);
        $outer->child($webLeaf);
        return $outer;
    }

    private static function renderQr(Surface $surface, string $key): void
    {
        $webNode = LayoutNode::find($surface->rootLayout(), "{$key}:web");
        $text = self::$inputControl?->getValue() ?? '';
        $svg = Backend::qrCodeGenerate($text, self::$eccLevel, self::$scale, self::$fgColor, self::$bgColor);
        if ($webNode !== null && $webNode->spec instanceof WebViewSpec) {
            $webNode->spec = new WebViewSpec(html: self::wrap($svg));
        }
        $surface->redraw();
    }

    private static function wrap(string $svg): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>body{font-family:-apple-system,sans-serif;margin:0;padding:12px;'
            . 'display:flex;align-items:center;justify-content:center;min-height:100vh;background:#fff}'
            . 'svg{width:auto;height:auto;max-width:100%;max-height:90vh}'
            . '</style></head><body>' . ($svg ?: '<div style="color:#888">Click <b>Generate</b> to render the QR code</div>') . '</body></html>';
    }

    private static function colorToHex(Color $c): string
    {
        return '#' . str_pad(dechex((int)round($c->r * 255)), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex((int)round($c->g * 255)), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex((int)round($c->b * 255)), 2, '0', STR_PAD_LEFT);
    }
}
