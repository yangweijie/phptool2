<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class BomCleanPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $path = Ui::textField("{$key}:path", '/path/to/file.php', $w);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 160);
        $out->bind($surface);
        $surface->onClick("{$key}:detect", static function () use ($path, $out): void {
            $p = $path->spec instanceof TextFieldSpec ? $path->spec->value : '';
            $r = Backend::bomDetect($p);
            if (isset($r['error'])) { $out->setValue($r['error']); return; }
            $out->setValue($r['found'] ? "BOM detected! ({$r['size']} bytes)\nType: {$r['mime']}" : "No BOM found.\nType: {$r['mime']}");
        });
        $surface->onClick("{$key}:clean", static function () use ($path, $out): void {
            $p = $path->spec instanceof TextFieldSpec ? $path->spec->value : '';
            $r = Backend::bomClean($p);
            if (isset($r['error'])) { $out->setValue($r['error']); return; }
            $out->setValue($r['cleaned'] ? "BOM removed: {$r['removed']}" : $r['msg']);
        });
        $rows = [
            Ui::title('BOM Detector / Cleaner', $w), Ui::label('File path', $w), $path,
            Ui::row([Ui::button("{$key}:detect", 'Detect', 'filled', 110), Ui::button("{$key}:clean", 'Clean', 'soft', 110)]),
            Ui::label('Result', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 500);
        $sv->bind($surface);
        return $sv->root();
    }
}
