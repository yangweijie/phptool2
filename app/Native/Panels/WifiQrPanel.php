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
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * WiFi QR Generator — encryption type, EAP methods, password toggle, hidden, colors.
 *
 * Mirrors the original FlyEnv JS panel (toolbox-panels.js wifi section).
 * Conditional visibility via height=0 (no display:none in native UI).
 */
final class WifiQrPanel implements Panel
{
    private static string $encryption    = 'WPA';
    private static string $eccLevel      = 'M';
    private static int    $scale         = 8;
    private static string $fgColor       = '#000000';
    private static string $bgColor       = '#ffffff';
    private static string $eapMethod     = 'PEAP';
    private static string $phase2        = 'None';
    private static bool   $hidden        = false;
    private static bool   $useAnon       = false;
    private static string $identityValue = '';

    private const ENC_TYPES   = ['WPA', 'WPA2-EAP', 'WEP', 'nopass'];
    private const EAP_METHODS = ['PEAP', 'TLS', 'TTLS', 'SIM', 'AKA', "AKA'", 'PWD', 'FAST', 'LEAP', 'EKE', 'WFA-DPP'];
    private const PHASE2_OPTS = ['None', 'MSCHAPV2', 'GTC', 'MD5', 'OTP', 'Token'];

    /** Helper: find-and-set spec on a node (avoids nullsafe write context). */
    private static function setSpec(Surface $surface, string $id, object $spec): void
    {
        $n = LayoutNode::find($surface->rootLayout(), $id);
        if ($n !== null) { $n->spec = $spec; }
    }

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w      = $width - 48;
        $noPass = self::$encryption === 'nopass';
        $isEap  = self::$encryption === 'WPA2-EAP';

        // ── SSID ────────────────────────────────────────────────────
        $ssid = new TextAreaControl("{$key}:ssid", '', width: $w, height: 30);
        $ssid->bind($surface);
        $ssid->setValue('My WiFi');

        // ── Password ────────────────────────────────────────────────
        $pass      = new TextAreaControl("{$key}:pass", '', width: $w, height: $noPass ? 0.0 : 30.0);
        $pass->bind($surface);
        $passLabel = Ui::label('Password', $w);
        if ($noPass) { $passLabel->style->height = 0.0; }

        // ── Encryption cycle ────────────────────────────────────────
        $encBtn = LayoutNode::leaf("{$key}:enc", new ButtonSpec('Enc: ' . self::$encryption, 'soft'), width: 120, height: 26);
        $surface->onClick("{$key}:enc", function () use ($surface, $key): void {
            $idx = (array_search(self::$encryption, self::ENC_TYPES, true) + 1) % count(self::ENC_TYPES);
            self::$encryption = self::ENC_TYPES[$idx];
            self::setSpec($surface, "{$key}:enc", new ButtonSpec('Enc: ' . self::$encryption, 'soft'));
            self::refreshConditional($surface, $key);
            self::renderQr($surface, $key);
        });

        // ── ECC cycle ───────────────────────────────────────────────
        $eccBtn = LayoutNode::leaf("{$key}:ecc", new ButtonSpec('ECC: ' . self::$eccLevel, 'soft'), width: 70, height: 26);
        $surface->onClick("{$key}:ecc", function () use ($surface, $key): void {
            $opts = ['L', 'M', 'Q', 'H'];
            $idx = (array_search(self::$eccLevel, $opts, true) + 1) % count($opts);
            self::$eccLevel = $opts[$idx];
            self::setSpec($surface, "{$key}:ecc", new ButtonSpec('ECC: ' . self::$eccLevel, 'soft'));
            self::renderQr($surface, $key);
        });

        // ── Colors ──────────────────────────────────────────────────
        $fgBtn = LayoutNode::leaf("{$key}:fg", new ButtonSpec('⬛ ' . self::$fgColor, 'soft'), width: 110, height: 26);
        $surface->onClick("{$key}:fg", function () use ($surface, $key): void {
            $color = ColorPickerDialog::pick(WindowHolder::get());
            if ($color !== null) {
                self::$fgColor = self::colorToHex($color);
                self::setSpec($surface, "{$key}:fg", new ButtonSpec('⬛ ' . self::$fgColor, 'soft'));
                self::renderQr($surface, $key);
            }
        });

        $bgBtn = LayoutNode::leaf("{$key}:bg", new ButtonSpec('⬜ ' . self::$bgColor, 'soft'), width: 110, height: 26);
        $surface->onClick("{$key}:bg", function () use ($surface, $key): void {
            $color = ColorPickerDialog::pick(WindowHolder::get());
            if ($color !== null) {
                self::$bgColor = self::colorToHex($color);
                self::setSpec($surface, "{$key}:bg", new ButtonSpec('⬜ ' . self::$bgColor, 'soft'));
                self::renderQr($surface, $key);
            }
        });

        // ── Controls row 1: enc / ecc / fg / bg ────────────────────
        $controls1 = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $controls1->child($encBtn);
        $controls1->child($eccBtn);
        $controls1->child($fgBtn);
        $controls1->child($bgBtn);

        // ── Hidden network toggle ───────────────────────────────────
        $hiddenBtn = LayoutNode::leaf("{$key}:hid",
            new ButtonSpec(self::$hidden ? '📡 Hidden ✓' : '📡 Hidden', 'soft'), width: 100, height: 26);
        $surface->onClick("{$key}:hid", function () use ($surface, $key): void {
            self::$hidden = !self::$hidden;
            self::setSpec($surface, "{$key}:hid", new ButtonSpec(self::$hidden ? '📡 Hidden ✓' : '📡 Hidden', 'soft'));
            self::renderQr($surface, $key);
        });

        // ── Controls row 2: hidden ──────────────────────────────────
        $controls2 = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $controls2->child($hiddenBtn);

        // ── EAP box (conditionally visible via height) ──────────────
        $eapBox = LayoutNode::column(gap: 4, width: $w, height: $isEap ? 120.0 : 0.0);
        $eapBox->style->id = "{$key}:eapbox";

        $eapBtn = LayoutNode::leaf("{$key}:eap",
            new ButtonSpec('EAP: ' . self::$eapMethod, 'soft'), width: 140, height: 26);
        $surface->onClick("{$key}:eap", function () use ($surface, $key): void {
            $idx = (array_search(self::$eapMethod, self::EAP_METHODS, true) + 1) % count(self::EAP_METHODS);
            self::$eapMethod = self::EAP_METHODS[$idx];
            self::setSpec($surface, "{$key}:eap", new ButtonSpec('EAP: ' . self::$eapMethod, 'soft'));
            self::renderQr($surface, $key);
        });

        $ph2Btn = LayoutNode::leaf("{$key}:ph2",
            new ButtonSpec('Phase2: ' . self::$phase2, 'soft'), width: 120, height: 26);
        $surface->onClick("{$key}:ph2", function () use ($surface, $key): void {
            $idx = (array_search(self::$phase2, self::PHASE2_OPTS, true) + 1) % count(self::PHASE2_OPTS);
            self::$phase2 = self::PHASE2_OPTS[$idx];
            self::setSpec($surface, "{$key}:ph2", new ButtonSpec('Phase2: ' . self::$phase2, 'soft'));
            self::renderQr($surface, $key);
        });

        $identity = new TextAreaControl("{$key}:identity", '', width: $w - 96, height: 28);
        $identity->bind($surface);

        $anonBtn = LayoutNode::leaf("{$key}:anon",
            new ButtonSpec(self::$useAnon ? 'Anon ID ✓' : 'Anon ID', 'soft'), width: 90, height: 26);
        $surface->onClick("{$key}:anon", function () use ($surface, $key): void {
            self::$useAnon = !self::$useAnon;
            self::setSpec($surface, "{$key}:anon", new ButtonSpec(self::$useAnon ? 'Anon ID ✓' : 'Anon ID', 'soft'));
            self::renderQr($surface, $key);
        });

        $eapRow1 = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $eapRow1->child($eapBtn);
        $eapRow1->child($ph2Btn);

        $eapRow2 = LayoutNode::row(gap: 6, height: 30, align: LayoutStyle::ALIGN_CENTER);
        $eapRow2->child($identity->root());
        $eapRow2->child($anonBtn);

        $eapBox->child(Ui::label('EAP Configuration', $w));
        $eapBox->child($eapRow1);
        $eapBox->child($eapRow2);

        // ── Action buttons ──────────────────────────────────────────
        $genBtn = Ui::button("{$key}:gen", '▶ Generate', 'filled', 120);
        $surface->onClick("{$key}:gen", fn() => self::renderQr($surface, $key));

        $dlBtn = Ui::button("{$key}:dl", '💾 Save PNG', 'soft', 120);
        $surface->onClick("{$key}:dl", function () use ($ssid, $pass): void {
            $path = WindowHolder::saveFile('wifi_qr_' . time() . '.png', 'PNG Image (*.png)|*.png');
            if ($path === '' || $path === null) { return; }
            Backend::wifiQrSavePng(
                $ssid->getValue(), $pass->getValue(), self::$encryption,
                $path, self::$eccLevel, self::$scale, self::$fgColor, self::$bgColor,
                self::buildExtra(),
            );
        });

        // ── WebView ─────────────────────────────────────────────────
        $webId      = "{$key}:web";
        $initialSvg = Backend::wifiQrGenerate(
            $ssid->getValue(), $pass->getValue(), self::$encryption,
            self::$eccLevel, self::$scale, self::$fgColor, self::$bgColor,
            self::buildExtra(),
        );
        $webLeaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::wrap($initialSvg)), width: $width);
        $webLeaf->style->grow = 1.0;

        // ── Assembly ────────────────────────────────────────────────
        $inputRows = [
            Ui::title('WiFi QR Generator', $w),
            Ui::label('SSID', $w), $ssid->root(),
            $passLabel, $pass->root(),
            $controls1,
            $controls2,
            $eapBox,
            Ui::row([$genBtn, $dlBtn]),
        ];

        $inputH = 308.0;
        if ($noPass) { $inputH -= 52.0; }
        if ($isEap)  { $inputH += 120.0; }

        $inputScroll = new ScrollViewControl("p:{$key}", $inputRows, width: $width, height: $inputH,
            gap: 8.0, padding: 18.0, contentHeight: $inputH);
        $inputScroll->bind($surface);

        $outer = LayoutNode::column(id: "{$key}:col", align: LayoutStyle::ALIGN_STRETCH, width: $width, height: $height);
        $outer->child($inputScroll->root());
        $outer->child($webLeaf);
        return $outer;
    }

    private static function buildExtra(): array
    {
        $extra = ['hidden' => self::$hidden];
        if (self::$encryption === 'WPA2-EAP') {
            $extra['eap'] = self::$eapMethod;
            if (self::$phase2 !== 'None') { $extra['phase2'] = self::$phase2; }
            $extra[self::$useAnon ? 'anonymous' : 'identity'] = self::$identityValue;
        }
        return $extra;
    }

    private static function refreshConditional(Surface $surface, string $key): void
    {
        $noPass = self::$encryption === 'nopass';
        $isEap  = self::$encryption === 'WPA2-EAP';

        $passNode = LayoutNode::find($surface->rootLayout(), "{$key}:pass");
        if ($passNode !== null) { $passNode->style->height = $noPass ? 0.0 : 30.0; }

        $passLbl = LayoutNode::find($surface->rootLayout(), "{$key}:passLabel");
        if ($passLbl !== null) { $passLbl->style->height = $noPass ? 0.0 : 22.0; }

        $eapBox = LayoutNode::find($surface->rootLayout(), "{$key}:eapbox");
        if ($eapBox !== null) { $eapBox->style->height = $isEap ? 120.0 : 0.0; }
    }

    private static function renderQr(Surface $surface, string $key): void
    {
        $webNode     = LayoutNode::find($surface->rootLayout(), "{$key}:web");
        $ssidText    = '';
        $passText    = '';
        $identityText = '';

        $ssidNode = LayoutNode::find($surface->rootLayout(), "{$key}:ssid");
        if ($ssidNode?->spec !== null && property_exists($ssidNode->spec, 'value')) {
            $ssidText = $ssidNode->spec->value;
        }
        $passNode = LayoutNode::find($surface->rootLayout(), "{$key}:pass");
        if ($passNode?->spec !== null && property_exists($passNode->spec, 'value')) {
            $passText = $passNode->spec->value;
        }
        $idNode = LayoutNode::find($surface->rootLayout(), "{$key}:identity");
        if ($idNode?->spec !== null && property_exists($idNode->spec, 'value')) {
            $identityText = $idNode->spec->value;
        }
        self::$identityValue = $identityText;

        $svg = Backend::wifiQrGenerate(
            $ssidText, $passText, self::$encryption,
            self::$eccLevel, self::$scale, self::$fgColor, self::$bgColor,
            self::buildExtra(),
        );
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
            . '</style></head><body>'
            . ($svg ?: '<div style="color:#888">Click <b>Generate</b> to render the WiFi QR code</div>')
            . '</body></html>';
    }

    private static function colorToHex(Color $c): string
    {
        return '#' . str_pad(dechex((int)round($c->r * 255)), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex((int)round($c->g * 255)), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex((int)round($c->b * 255)), 2, '0', STR_PAD_LEFT);
    }
}
