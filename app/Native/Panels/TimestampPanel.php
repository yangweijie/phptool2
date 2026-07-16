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

final class TimestampPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $unix = '';
        $date = '';

        $unixLeaf = Ui::textField("{$key}:unix", 'unix timestamp', $w);
        $dateLeaf = Ui::textField("{$key}:date", 'YYYY-MM-DD HH:MM:SS', $w);

        $surface->onText("{$key}:unix", static function (string $c, bool $bs) use ($unixLeaf, $surface, &$unix): void {
            $cur = $unixLeaf->spec instanceof TextFieldSpec ? $unixLeaf->spec->value : '';
            $unix = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
            $unixLeaf->spec = new TextFieldSpec(value: $unix, placeholder: 'unix timestamp');
            $surface->redraw();
        });
        $surface->onText("{$key}:date", static function (string $c, bool $bs) use ($dateLeaf, $surface, &$date): void {
            $cur = $dateLeaf->spec instanceof TextFieldSpec ? $dateLeaf->spec->value : '';
            $date = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
            $dateLeaf->spec = new TextFieldSpec(value: $date, placeholder: 'YYYY-MM-DD HH:MM:SS');
            $surface->redraw();
        });

        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 160);
        $out->bind($surface);

        $now = Ui::button("{$key}:now", 'Now', 'filled', 120);
        $t2d = Ui::button("{$key}:t2d", 'Unix → Date', 'soft', 150);
        $d2t = Ui::button("{$key}:d2t", 'Date → Unix', 'soft', 150);

        $surface->onClick("{$key}:now", static fn () => $out->setValue(self::fmt(Backend::tsNow())));
        $surface->onClick("{$key}:t2d", static fn () => $out->setValue(self::fmt(Backend::tsToDate($unix))));
        $surface->onClick("{$key}:d2t", static fn () => $out->setValue(self::fmt(Backend::dateToTs($date))));

        $rows = [
            Ui::title('Timestamp', $w),
            Ui::row([$now]),
            Ui::label('Unix timestamp', $w),
            $unixLeaf,
            Ui::row([$t2d]),
            Ui::label('Date string', $w),
            $dateLeaf,
            Ui::row([$d2t]),
            Ui::label('Result', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 640);
        $sv->bind($surface);
        return $sv->root();
    }

    /** @param array<string,mixed> $data */
    private static function fmt(array $data): string
    {
        if (isset($data['error'])) {
            return 'Error: ' . $data['error'];
        }
        $lines = [];
        foreach ($data as $k => $v) {
            $lines[] = ucfirst($k) . ': ' . $v;
        }
        return implode("\n", $lines);
    }
}
