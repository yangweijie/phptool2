<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class UrlPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 140);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 140);
        $in->bind($surface);
        $out->bind($surface);

        $enc = Ui::button("{$key}:enc", 'URL Encode', 'filled', 130);
        $dec = Ui::button("{$key}:dec", 'URL Decode', 'soft', 130);
        $esc = Ui::button("{$key}:esc", 'Escape HTML', 'outline', 130);

        $surface->onClick("{$key}:enc", static fn () => $out->setValue(Backend::urlEncode($in->getValue())));
        $surface->onClick("{$key}:dec", static fn () => $out->setValue(Backend::urlDecode($in->getValue())));
        $surface->onClick("{$key}:esc", static fn () => $out->setValue(Backend::escapeHtml($in->getValue())));

        $rows = [
            Ui::title('URL Encode', $w),
            Ui::label('Input', $w),
            $in->root(),
            Ui::row([$enc, $dec, $esc]),
            Ui::label('Output', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 480);
        $sv->bind($surface);
        return $sv->root();
    }
}
