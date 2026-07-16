<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class TokenGeneratorPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 100);
        $out->bind($surface);
        $surface->onClick("{$key}:gen64", static fn () => $out->setValue(Backend::tokenGenerate(64)));
        $surface->onClick("{$key}:gen32", static fn () => $out->setValue(Backend::tokenGenerate(32)));
        $rows = [
            Ui::title('Token Generator', $w),
            Ui::row([Ui::button("{$key}:gen64", '128-char token', 'filled', 160), Ui::button("{$key}:gen32", '64-char token', 'soft', 160)]),
            Ui::label('Generated token', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 400);
        $sv->bind($surface);
        return $sv->root();
    }
}
