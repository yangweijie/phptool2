<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class RegexCheatsheetPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $items = [
            ['Character classes', 0],
            ['.  Any character except newline', 1],
            ['\d  Digit (0-9)', 1], ['\w  Word char (a-z, A-Z, 0-9, _)', 1],
            ['\s  Whitespace', 1], ['\D  Non-digit', 1],
            ['Anchors', 0],
            ['^  Start of string', 1], ['$  End of string', 1],
            ['\b  Word boundary', 1],
            ['Quantifiers', 0],
            ['*  Zero or more', 1], ['+  One or more', 1],
            ['?  Zero or one', 1], ['{n}  Exactly n', 1],
            ['{n,}  n or more', 1], ['{n,m}  n to m', 1],
            ['Groups & Alternation', 0],
            ['(abc)  Capture group', 1], ['(?:abc)  Non-capture', 1],
            ['a|b  Alternation (a or b)', 1],
            ['Escapes', 0],
            ['\. \* \+ \? \( \) \[ \] \\', 1],
        ];
        $children = [];
        $children[] = LayoutNode::leaf(null, new LabelSpec('Regex Cheatsheet', size: 16), width: $w, height: 28);
        $totalH = 36;
        foreach ($items as [$text, $level]) {
            $opacity = $level === 0 ? 0.55 : 1.0;
            $pad = $level > 0 ? '    ' : '';
            $children[] = LayoutNode::leaf(null, new LabelSpec($pad . $text, size: 13, opacity: $opacity), width: $w, height: 18);
            $totalH += 20;
        }
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 1, padding: 18.0, contentHeight: $totalH);
        $sv->bind($surface);
        return $sv->root();
    }
}
