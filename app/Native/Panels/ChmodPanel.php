<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CheckboxSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class ChmodPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        /** @var list<bool> */
        $bits = Backend::chmodToBits('755');
        $letters = ['r', 'w', 'x'];
        $groups = ['Owner', 'Group', 'Other'];

        $octLabel = LayoutNode::leaf("{$key}:oct", new LabelSpec('755', size: 14), width: 120, height: 24);
        $symLabel = LayoutNode::leaf("{$key}:sym", new LabelSpec('rwxr-xr-x', size: 14), width: 200, height: 24);

        $recompute = static function () use (&$bits, $octLabel, $symLabel, $surface): void {
            $r = Backend::chmodFromBits($bits);
            $octLabel->spec = new LabelSpec($r['octal'], size: 14);
            $symLabel->spec = new LabelSpec($r['symbolic'], size: 14);
            $surface->redraw();
        };

        // 9 checkboxes, kept so "Apply" can refresh them.
        /** @var array<int,LayoutNode> $bLeaves */
        $bLeaves = [];
        $groupCols = [];
        for ($g = 0; $g < 3; $g++) {
            $col = LayoutNode::column(gap: 6, padding: 8, align: LayoutStyle::ALIGN_START, width: 150, height: 130);
            $col->child(Ui::label($groups[$g], 130, 12, 18));
            for ($i = 0; $i < 3; $i++) {
                $idx = $g * 3 + $i;
                $leaf = LayoutNode::leaf("{$key}:b{$idx}", new CheckboxSpec(label: $letters[$i], checked: $bits[$idx]), width: 120, height: 28);
                $bLeaves[$idx] = $leaf;
                $surface->onClick("{$key}:b{$idx}", static function () use ($idx, $leaf, $surface, &$bits, $recompute): void {
                    $bits[$idx] = !$bits[$idx];
                    $spec = $leaf->spec;
                    if ($spec instanceof CheckboxSpec) {
                        $leaf->spec = new CheckboxSpec(label: $spec->label, checked: $bits[$idx]);
                    }
                    $recompute();
                });
                $col->child($leaf);
            }
            $groupCols[] = $col;
        }
        $triadRow = LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_START, height: 130);
        foreach ($groupCols as $c) {
            $triadRow->child($c);
        }

        // Octal input + Apply
        $modeLeaf = Ui::textField("{$key}:mode", 'e.g. 755', 140);
        $surface->onText("{$key}:mode", static function (string $c, bool $bs) use ($modeLeaf, $surface, &$mode): void {
            $cur = $modeLeaf->spec instanceof TextFieldSpec ? $modeLeaf->spec->value : '';
            $mode = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
            $modeLeaf->spec = new TextFieldSpec(value: $mode, placeholder: 'e.g. 755');
            $surface->redraw();
        });
        $mode = '755';
        $apply = Ui::button("{$key}:apply", 'Apply', 'soft', 100);
        $surface->onClick("{$key}:apply", static function () use (&$mode, &$bits, $bLeaves, $recompute): void {
            $bits = Backend::chmodToBits($mode);
            foreach ($bLeaves as $idx => $leaf) {
                $leaf->spec = new CheckboxSpec(label: $leaf->spec instanceof CheckboxSpec ? $leaf->spec->label : '', checked: $bits[$idx]);
            }
            $recompute();
        });

        $rows = [
            Ui::title('Chmod Calculator', $w),
            $triadRow,
            Ui::label('Octal / Symbolic', $w),
            LayoutNode::row(gap: 16, align: LayoutStyle::ALIGN_CENTER, height: 24)
                ->child($octLabel)->child($symLabel),
            Ui::label('Set from octal', $w),
            LayoutNode::row(gap: 10, align: LayoutStyle::ALIGN_CENTER, height: 36)
                ->child($modeLeaf)->child($apply),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 460);
        $sv->bind($surface);
        return $sv->root();
    }
}
