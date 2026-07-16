<?php

declare(strict_types=1);

namespace App\Native;

use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\AlertSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * Shown for tools that do not have a native panel yet.
 */
final class PlaceholderPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $col = LayoutNode::column(gap: 14, padding: 24, align: LayoutStyle::ALIGN_START, width: $width, height: $height);

        $col->child(LayoutNode::leaf(null, new AlertSpec(variant: 'info', title: 'Coming soon'),
            width: $width - 48, height: 44));
        $col->child(LayoutNode::leaf(null, new LabelSpec('This tool has not been ported to the native (self-drawn) version yet.', size: 14),
            width: $width - 48, height: 22));

        return (new ScrollViewControl(
            "p:{$key}",
            [$col],
            width: $width,
            height: $height,
            contentHeight: $height,
        ))->root();
    }
}
