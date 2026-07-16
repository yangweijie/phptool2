<?php

declare(strict_types=1);

namespace App\Native;

use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * A native tool panel.
 *
 * build() returns the root node of the panel (typically a ScrollViewControl
 * sized to the content area) and wires all of its own event handlers on the
 * content $surface. Every interactive node id must be prefixed with $key so
 * handlers from a previously-opened panel never collide.
 */
interface Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode;
}
