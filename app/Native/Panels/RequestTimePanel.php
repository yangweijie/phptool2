<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class RequestTimePanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $url = Ui::textField("{$key}:url", 'https://example.com', $w);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 260);
        $out->bind($surface);
        $surface->onClick("{$key}:run", static fn () => $out->setValue(Backend::requestTime($url->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec ? $url->spec->value : '')));
        $rows = [
            Ui::title('URL Timing', $w), Ui::label('URL', $w), $url,
            Ui::row([Ui::button("{$key}:run", 'Measure', 'filled', 120)]),
            Ui::label('Result', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 520);
        $sv->bind($surface);
        return $sv->root();
    }
}
