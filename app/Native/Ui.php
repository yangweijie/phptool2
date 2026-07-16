<?php

declare(strict_types=1);

namespace App\Native;

use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;

/**
 * Tiny builders for the most common self-drawn widgets, so each panel stays
 * focused on its own logic instead of boilerplate leaf construction.
 */
final class Ui
{
    public static function label(string $text, float $width, float $size = 13.0, float $height = 22.0): LayoutNode
    {
        return LayoutNode::leaf(null, new LabelSpec($text, size: $size), width: $width, height: $height);
    }

    public static function title(string $text, float $width): LayoutNode
    {
        return LayoutNode::leaf(null, new LabelSpec($text, size: 16.0), width: $width, height: 28.0);
    }

    public static function button(string $id, string $label, string $variant = 'filled', float $width = 120.0, float $height = 36.0): LayoutNode
    {
        return LayoutNode::leaf($id, new ButtonSpec($label, $variant), width: $width, height: $height);
    }

    public static function textField(string $id, string $placeholder, float $width, float $height = 34.0): LayoutNode
    {
        return LayoutNode::leaf($id, new TextFieldSpec(value: '', placeholder: $placeholder), width: $width, height: $height);
    }

    /** A horizontal row of already-built nodes. */
    public static function row(array $children, float $gap = 10.0, float $height = 36.0): LayoutNode
    {
        $row = LayoutNode::row(gap: $gap, align: LayoutStyle::ALIGN_CENTER, height: $height);
        foreach ($children as $c) {
            $row->child($c);
        }
        return $row;
    }

    /** A vertical column of already-built nodes. */
    public static function column(array $children, float $gap = 12.0, float $width = 0.0): LayoutNode
    {
        $col = LayoutNode::column(gap: $gap, align: LayoutStyle::ALIGN_STRETCH, width: $width > 0 ? $width : null);
        foreach ($children as $c) {
            $col->child($c);
        }
        return $col;
    }
}
