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

        // [text, level] — 0=section header, 1=item, 2=sub-item/description
        $items = [
            ['Normal characters', 0],
            ['. or [^\\n\\r]  any character excluding newline or carriage return', 1],
            ['[A-Za-z]  alphabet', 1],
            ['[a-z]  lowercase alphabet', 1],
            ['[A-Z]  uppercase alphabet', 1],
            ['\\d or [0-9]  digit', 1],
            ['\\D or [^0-9]  non-digit', 1],
            ['_  underscore', 1],
            ['\\w or [A-Za-z0-9_]  alphabet, digit or underscore', 1],
            ['\\W or [^A-Za-z0-9_]  inverse of \\w', 1],
            ['\\S  inverse of \\s', 1],

            ['Whitespace characters', 0],
            ['  (space)', 1],
            ['\\t  tab', 1],
            ['\\n  newline', 1],
            ['\\r  carriage return', 1],
            ['\\s  space, tab, newline or carriage return', 1],

            ['Character set', 0],
            ['[xyz]  either x, y or z', 1],
            ['[^xyz]  neither x, y nor z', 1],
            ['[1-3]  either 1, 2 or 3', 1],
            ['[^1-3]  neither 1, 2 nor 3', 1],
            ['Think of a character set as an OR operation on the single characters enclosed in square brackets.', 2],
            ['Use ^ after the opening [ to "negate" the character set.', 2],
            ['Within a character set, . means a literal period.', 2],

            ['Characters that require escaping', 0],
            ['Outside a character set', 1],
            ['\\.  period', 2],
            ['\\^  caret', 2],
            ['\\$  dollar sign', 2],
            ['|  pipe', 2],
            ['\\\\  back slash', 2],
            ['\\/  forward slash', 2],
            ['\\(  opening bracket', 2],
            ['\\)  closing bracket', 2],
            ['\\[  opening square bracket', 2],
            ['\\]  closing square bracket', 2],
            ['\\{  opening curly bracket', 2],
            ['\\}  closing curly bracket', 2],
            ['Inside a character set', 1],
            ['\\\\  back slash', 2],
            ['\\]  closing square bracket', 2],
            ['A ^ must be escaped only if it occurs immediately after the opening [ of the character set.', 2],
            ['A - must be escaped only if it occurs between two alphabets or two digits.', 2],

            ['Quantifiers', 0],
            ['{2}  exactly 2', 1],
            ['{2,}  at least 2', 1],
            ['{2,7}  at least 2 but no more than 7', 1],
            ['*  0 or more', 1],
            ['+  1 or more', 1],
            ['?  exactly 0 or 1', 1],
            ['The quantifier goes after the expression to be quantified.', 2],

            ['Boundaries', 0],
            ['^  start of string', 1],
            ['$  end of string', 1],
            ['\\b  word boundary', 1],
            ['How word boundary matching works:', 2],
            ['At the beginning of the string if the first character is \\w.', 2],
            ['Between two adjacent characters, if the first is \\w and the second is \\W.', 2],
            ['At the end of the string if the last character is \\w.', 2],

            ['Matching', 0],
            ['foo|bar  match either foo or bar', 1],
            ['foo(?=bar)  match foo if it\'s before bar', 1],
            ['foo(?!bar)  match foo if it\'s not before bar', 1],
            ['(?<=bar)foo  match foo if it\'s after bar', 1],
            ['(?<!bar)foo  match foo if it\'s not after bar', 1],

            ['Grouping and capturing', 0],
            ['(foo)  capturing group; match and capture foo', 1],
            ['(?:foo)  non-capturing group; match foo without capturing', 1],
            ['(foo)bar\\1  backreference to 1st capturing group; match foobarfoo', 1],
            ['Capturing groups are only relevant in:', 2],
            ['  string.match(regexp)', 2],
            ['  string.matchAll(regexp)', 2],
            ['  string.replace(regexp, callback)', 2],
            ['\\N is a backreference to the Nth capturing group. Numbered from 1.', 2],

            ['References', 0],
            ['MDN: Regular Expressions', 1],
            ['RegExr: regex101.com', 1],
        ];

        $children = [];
        $children[] = LayoutNode::leaf(null, new LabelSpec('Regex Cheatsheet', size: 16, opacity: 0.85), width: $w, height: 28);
        $totalH = 36;
        foreach ($items as [$text, $level]) {
            $opacity = match ($level) {
                0 => 0.55,   // section header
                1 => 1.0,    // item
                2 => 0.65,   // sub-item/description
                default => 1.0,
            };
            $pad = match ($level) {
                1 => '    ',
                2 => '        ',
                default => '',
            };
            $fontSize = $level === 0 ? 14.0 : 13.0;
            $children[] = LayoutNode::leaf(null, new LabelSpec($pad . $text, size: $fontSize, opacity: $opacity), width: $w, height: 18);
            $totalH += 20;
        }
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 1, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }
}
