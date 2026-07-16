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

final class FileInfoPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $path = '';
        $pathLeaf = Ui::textField("{$key}:path", '/path/to/file', $w);
        $surface->onText("{$key}:path", static function (string $c, bool $bs) use ($pathLeaf, $surface, &$path): void {
            $cur = $pathLeaf->spec instanceof TextFieldSpec ? $pathLeaf->spec->value : '';
            $path = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
            $pathLeaf->spec = new TextFieldSpec(value: $path, placeholder: '/path/to/file');
            $surface->redraw();
        });

        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 260);
        $out->bind($surface);

        $run = Ui::button("{$key}:run", 'Get Info', 'filled', 140);
        $surface->onClick("{$key}:run", static function () use (&$path, $out): void {
            $info = Backend::fileInfo($path);
            if (isset($info['error'])) {
                $out->setValue('Error: ' . $info['error']);
                return;
            }
            $lines = [];
            foreach ($info as $k => $v) {
                $lines[] = $k . ': ' . $v;
            }
            $out->setValue(implode("\n", $lines));
        });

        $rows = [
            Ui::title('File Info', $w),
            Ui::label('File path', $w),
            $pathLeaf,
            Ui::row([$run]),
            Ui::label('Result', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 560);
        $sv->bind($surface);
        return $sv->root();
    }
}
