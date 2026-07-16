<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class CodeLibraryPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 56;
        $sections = [
            ['Array helpers', [
                'array_map(fn($v) => ..., $arr)',
                'array_filter($arr, fn($v) => ...)',
                'array_reduce($arr, fn($c, $v) => ..., $init)',
                'array_chunk($arr, $n)',
                'array_column($arr, $key)',
            ]],
            ['String helpers', [
                'str_starts_with($s, $prefix)',
                'str_contains($s, $needle)',
                'str_getcsv($line)',
                'sscanf($str, $fmt, ...)',
            ]],
            ['File / Path', [
                'basename($path), dirname($path)',
                'pathinfo($path, PATHINFO_EXTENSION)',
                'glob($pattern)',
                'file_get_contents / file_put_contents',
            ]],
            ['I/O', [
                'php://input (POST body)',
                'php://output (write directly)',
                'php://temp (in-memory stream)',
                'php://memory',
            ]],
            ['JSON', [
                'json_decode($str, true)',
                'json_encode($val, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)',
            ]],
            ['Date / Time', [
                'date($fmt, $ts), strtotime($str)',
                'DateTime / DateInterval / DatePeriod',
                '(new DateTime())->modify("+1 day")->format("c")',
            ]],
            ['Misc', [
                'debug_backtrace(), debug_print_backtrace()',
                'var_dump(...), print_r(...)',
                'compact(...), extract(...)',
            ]],
        ];
        $children = [];
        $children[] = LayoutNode::leaf(null, new LabelSpec('PHP Code Snippets', size: 18, opacity: 0.85), width: $w, height: 32);
        $children[] = LayoutNode::leaf(null, null, height: 4.0);
        $totalH = 40;
        foreach ($sections as [$sectionName, $lines]) {
            // Section header
            $children[] = LayoutNode::leaf(null, new LabelSpec($sectionName, size: 14, opacity: 0.65), width: $w, height: 22);
            $totalH += 26;
            foreach ($lines as $line) {
                $children[] = LayoutNode::leaf(null, new LabelSpec('  ' . $line, size: 13), width: $w, height: 22);
                $totalH += 24;
            }
            $totalH += 12; // spacing between sections
        }
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 2, padding: 28.0, contentHeight: $totalH);
        $sv->bind($surface);
        return $sv->root();
    }
}
