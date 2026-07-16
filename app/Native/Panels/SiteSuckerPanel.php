<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel; use App\Native\Ui;
use App\SiteSucker;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class SiteSuckerPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $urlField = Ui::textField("{$key}:url", 'https://example.com', $w);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 260);
        $out->bind($surface);
        $surface->onClick("{$key}:run", static function () use ($urlField, $out, $surface): void {
            $url = $urlField->spec instanceof TextFieldSpec ? $urlField->spec->value : '';
            if ($url === '') { $out->setValue('Enter a URL'); return; }
            // Simple mirror engine — delegates to App\SiteSucker
            try {
                $sucker = new SiteSucker($url, sys_get_temp_dir() . '/fly_site_' . time(), exclude: ['analytics', 'ads', 'track']);
                $summary = [];
                $sucker->on('page', fn (string $page) => $summary[] = "  fetched: {$page}");
                $sucker->run();
                $result = "Site Sucker completed.\n\nTarget: {$url}\n" . implode("\n", $summary);
                $result .= "\n\nCheck the output directory for downloaded files.";
                $out->setValue($result);
            } catch (\Throwable $e) {
                $out->setValue('Error: ' . $e->getMessage());
            }
        });
        $rows = [
            Ui::title('Site Sucker', $w), Ui::label('Website URL', $w), $urlField,
            Ui::row([Ui::button("{$key}:run", 'Download', 'filled', 140)]),
            Ui::label('Status', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 580);
        $sv->bind($surface);
        return $sv->root();
    }
}
