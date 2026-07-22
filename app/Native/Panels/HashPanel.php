<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\DropdownMenuControl;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class HashPanel implements Panel
{
    private static string $digest = 'Hex';
    private static array $hashes = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $nameW = 80.0;
        $hashW = $w - $nameW - 44.0;
        $algorithms = ['MD5', 'SHA1', 'SHA256', 'SHA224', 'SHA512', 'SHA384', 'SHA3', 'RIPEMD160'];

        // Title
        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('Hash 文本', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('☆', size: 16.0), width: 24.0, height: 36.0));

        // Input section
        $inputLabel = Ui::label('Your text to hash:', $w, 13.0, 18.0);
        $input = new TextAreaControl("{$key}:in", '', width: $w, height: 60);
        $input->bind($surface);

        // Digest encoding section
        $digestLabel = Ui::label('Digest encoding', $w, 13.0, 18.0);
        $digestOptions = ['Hexadecimal (base 16)', 'Binary (base 2)', 'Base64', 'Base64url'];
        $digestMenu = new DropdownMenuControl("{$key}:digest", $digestOptions, selected: 0, width: $w);
        $digestMenu->bind($surface)->onSelect(function (int $i, string $label) {
            self::$digest = match ($i) {
                0 => 'Hex',
                1 => 'Bin',
                2 => 'Base64',
                3 => 'Base64url',
                default => 'Hex',
            };
        });

        // Hash results - each row has name, truncated hash, and copy button
        $hashRows = [];
        foreach ($algorithms as $algo) {
            $row = LayoutNode::row(gap: 0.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
            $row->child(LayoutNode::leaf(null, new LabelSpec($algo, size: 12.0, opacity: 0.85), width: $nameW, height: 28.0));
            // Hash value - will be truncated to fit
            $row->child(LayoutNode::leaf("{$key}:h_" . strtolower($algo), new LabelSpec('-', size: 11.0), width: $hashW, height: 28.0));
            $row->child(Ui::button("{$key}:c_" . strtolower($algo), '📋', 'outline', 28.0, 24.0));
            $hashRows[] = $row;
        }

        // Compute button
        $computeBtn = Ui::button("{$key}:compute", '计算', 'filled', 80.0, 32.0);

        // Compute handler
        $surface->onClick("{$key}:compute", function () use ($surface, $key, $algorithms) {
            $inNode = LayoutNode::find($surface->rootLayout(), "{$key}:in");
            $text = '';
            if ($inNode !== null && $inNode->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\TextAreaSpec) {
                $text = $inNode->spec->value;
            }

            $result = Backend::hashAll($text, self::$digest);

            foreach ($algorithms as $algo) {
                $nodeId = "{$key}:h_" . strtolower($algo);
                $node = LayoutNode::find($surface->rootLayout(), $nodeId);
                if ($node !== null && $node->spec instanceof LabelSpec) {
                    $hash = $result[$algo] ?? '-';
                    self::$hashes[strtolower($algo)] = $hash;
                    // Truncate for display if too long
                    $display = strlen($hash) > 60 ? substr($hash, 0, 57) . '...' : $hash;
                    $node->spec = new LabelSpec($display, size: 11.0);
                }
            }
            $surface->redraw();
        });

        // Copy handlers for each algorithm
        foreach ($algorithms as $algo) {
            $lower = strtolower($algo);
            $surface->onClick("{$key}:c_{$lower}", function () use ($lower) {
                Backend::copyText(self::$hashes[$lower] ?? '');
            });
        }

        // Auto-compute on input change
        $surface->onText("{$key}:in", function () use ($surface, $key, $algorithms) {
            $inNode = LayoutNode::find($surface->rootLayout(), "{$key}:in");
            $text = '';
            if ($inNode !== null && $inNode->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\TextAreaSpec) {
                $text = $inNode->spec->value;
            }

            $result = Backend::hashAll($text, self::$digest);

            foreach ($algorithms as $algo) {
                $nodeId = "{$key}:h_" . strtolower($algo);
                $node = LayoutNode::find($surface->rootLayout(), $nodeId);
                if ($node !== null && $node->spec instanceof LabelSpec) {
                    $hash = $result[$algo] ?? '-';
                    self::$hashes[strtolower($algo)] = $hash;
                    // Truncate for display if too long
                    $display = strlen($hash) > 60 ? substr($hash, 0, 57) . '...' : $hash;
                    $node->spec = new LabelSpec($display, size: 11.0);
                }
            }
            $surface->redraw();
        });

        // Flat structure
        $children = [
            $titleRow,
            $inputLabel,
            $input->root(),
            $digestLabel,
            $digestMenu->root(),
            $computeBtn,
        ];
        $children = array_merge($children, $hashRows);

        $totalH = 500.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 4.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }
}
