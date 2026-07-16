<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class CronParserPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 60);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 280);
        $in->bind($surface); $out->bind($surface);
        $surface->onClick("{$key}:run", static fn () => $out->setValue(implode("\n", Backend::cronParse($in->getValue()))));
        $rows = [
            Ui::title('Cron Parser', $w),
            Ui::label('Cron expression (5 fields: min hour dom month dow)', $w),
            $in->root(),
            Ui::row([Ui::button("{$key}:run", 'Parse', 'filled', 120)]),
            Ui::label('Next runs', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 560);
        $sv->bind($surface);
        return $sv->root();
    }
}
