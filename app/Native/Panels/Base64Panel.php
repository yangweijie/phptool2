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

final class Base64Panel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 160);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 160);
        $in->bind($surface);
        $out->bind($surface);

        $enc = Ui::button("{$key}:enc", 'Encode', 'filled', 120);
        $dec = Ui::button("{$key}:dec", 'Decode', 'soft', 120);

        $surface->onClick("{$key}:enc", static fn () => $out->setValue(Backend::base64Encode($in->getValue())));
        $surface->onClick("{$key}:dec", static fn () => $out->setValue(Backend::base64Decode($in->getValue())));

        $rows = [
            Ui::title('Base64', $w),
            Ui::label('Input', $w),
            $in->root(),
            Ui::row([$enc, $dec]),
            Ui::label('Output', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 540);
        $sv->bind($surface);
        return $sv->root();
    }
}
