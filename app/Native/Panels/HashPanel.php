<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\DropdownMenuControl;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class HashPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $digest = 'Hex';
        $digestMenu = new DropdownMenuControl("{$key}:dig", ['Hex', 'Bin', 'Base64', 'Base64url'], selected: 0, width: 200);
        $digestMenu->bind($surface)->onSelect(static function (int $i, string $label) use (&$digest): void {
            $digest = $label;
        });

        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 120);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 220);
        $in->bind($surface);
        $out->bind($surface);

        $run = Ui::button("{$key}:run", 'Hash', 'filled', 120);
        $surface->onClick("{$key}:run", static function () use ($in, $out, &$digest): void {
            $res = Backend::hashAll($in->getValue(), $digest);
            $lines = [];
            foreach ($res as $name => $val) {
                $lines[] = $name . ': ' . $val;
            }
            $out->setValue(implode("\n", $lines));
        });

        $rows = [
            Ui::title('Hash Text', $w),
            Ui::label('Digest encoding', $w),
            $digestMenu->root(),
            Ui::label('Input', $w),
            $in->root(),
            Ui::row([$run]),
            Ui::label('Output', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 640);
        $sv->bind($surface);
        return $sv->root();
    }
}
