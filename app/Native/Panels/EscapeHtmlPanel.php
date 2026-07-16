<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class EscapeHtmlPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 160);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 160);
        $in->bind($surface); $out->bind($surface);
        $surface->onClick("{$key}:enc", static fn () => $out->setValue(Backend::escapeHtml($in->getValue())));
        $surface->onClick("{$key}:dec", static fn () => $out->setValue(Backend::escapeHtmlDecode($in->getValue())));
        $rows = [
            Ui::title('Escape / Unescape HTML', $w), Ui::label('HTML input', $w), $in->root(),
            Ui::row([Ui::button("{$key}:enc", 'Escape', 'filled', 120), Ui::button("{$key}:dec", 'Unescape', 'soft', 120)]),
            Ui::label('Result', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 560);
        $sv->bind($surface);
        return $sv->root();
    }
}
