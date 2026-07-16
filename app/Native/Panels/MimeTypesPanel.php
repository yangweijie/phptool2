<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class MimeTypesPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $mimes = [
            'text/plain' => 'txt, log, ini', 'text/html' => 'html, htm', 'text/css' => 'css',
            'text/javascript' => 'js, mjs', 'application/json' => 'json',
            'application/xml' => 'xml, xsd', 'application/pdf' => 'pdf',
            'application/zip' => 'zip', 'application/gzip' => 'gz, gzip',
            'image/jpeg' => 'jpg, jpeg', 'image/png' => 'png', 'image/gif' => 'gif',
            'image/webp' => 'webp', 'image/svg+xml' => 'svg',
            'video/mp4' => 'mp4', 'audio/mpeg' => 'mp3',
            'application/octet-stream' => 'bin, exe',
        ];
        $children = [];
        $children[] = LayoutNode::leaf(null, new LabelSpec('Common MIME Types', size: 16), width: $w, height: 28);
        $totalH = 36;
        foreach ($mimes as $mime => $exts) {
            $children[] = LayoutNode::leaf(null, new LabelSpec("{$mime}  →  .{$exts}", size: 13), width: $w, height: 20);
            $totalH += 22;
        }
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 2, padding: 18.0, contentHeight: $totalH);
        $sv->bind($surface);
        return $sv->root();
    }
}
