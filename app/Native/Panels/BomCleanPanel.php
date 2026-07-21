<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use App\Native\WindowHolder;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class BomCleanPanel implements Panel
{
    /** @var list<string> */
    private static array $fileTypes = [];
    /** @var string Last scanned directory */
    private static string $lastPath = '';
    /** @var string Formatted file type display text */
    private static string $fileTypeText = '';

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // Title row
        $titleRow = LayoutNode::row(gap: 0.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('文件Bom清理', size: 16.0, opacity: 0.85), width: $w - 100.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('☆', size: 16.0), width: 20.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf("{$key}:clean", new ButtonSpec('清理', 'filled'), width: 80.0, height: 30.0));

        // Directory input
        $dirField = Ui::textField("{$key}:dir", 'Directory or file', $w - 40.0);
        $dirPickBtn = Ui::button("{$key}:dirpick", '📁', 'outline', 32.0, 30.0);
        $dirRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $dirRow->child($dirField);
        $dirRow->child($dirPickBtn);

        // Exclude list
        $excludeLabel = Ui::label('排除目录', $w, 12.0, 18.0);
        $excludeId = "{$key}:exclude";
        $excludeNode = LayoutNode::leaf($excludeId, null, width: $w, height: 100);

        // File type display
        $fileTypeLabel = Ui::label('File Type', $w, 13.0, 24.0);
        $fileTypeId = "{$key}:filetypes";
        $fileTypeText = self::$fileTypeText !== '' ? self::$fileTypeText : '选择目录后显示文件类型';
        $fileTypeOpacity = self::$fileTypeText !== '' ? 1.0 : 0.55;
        $fileTypeNode = LayoutNode::leaf($fileTypeId, new LabelSpec($fileTypeText, size: 12.0, opacity: $fileTypeOpacity), width: $w, height: 200.0);

        // Result
        $resultLabel = Ui::label('结果', $w, 13.0, 24.0);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 120);
        $out->bind($surface);

        // Directory picker
        $surface->onClick("{$key}:dirpick", function () use ($surface, $key, $w) {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = $win->dialogs()->openFolder();
            if ($path !== null && $path !== '') {
                // Update dir field
                $dirNode = LayoutNode::find($surface->rootLayout(), "{$key}:dir");
                if ($dirNode !== null && $dirNode->spec instanceof TextFieldSpec) {
                    $dirNode->spec = new TextFieldSpec(value: $path, placeholder: 'Directory or file');
                }
                // Scan and display file types
                self::scanAndDisplay($path, $surface, $key, $w);
                $surface->redraw();
            }
        });

        // Clean handler
        $surface->onClick("{$key}:clean", function () use ($surface, $key, $out) {
            $dirNode = LayoutNode::find($surface->rootLayout(), "{$key}:dir");
            $dir = ($dirNode !== null && $dirNode->spec instanceof TextFieldSpec) ? $dirNode->spec->value : '';
            if ($dir === '') {
                $out->setValue('请选择目录或文件');
                return;
            }
            $exclude = self::getExcludeList($surface, $key);
            $result = Backend::bomCleanDir($dir, $exclude);
            if (isset($result['error'])) {
                $out->setValue('错误: ' . $result['error']);
                return;
            }
            $lines = [
                "总计: {$result['total']} 个文件",
                "已检查: {$result['checked']} 个文件",
                "成功清理: {$result['success']} 个文件",
                "失败: {$result['fail']} 个文件",
            ];
            if (!empty($result['successFiles'])) {
                $lines[] = '';
                $lines[] = '成功清理:';
                foreach ($result['successFiles'] as $f) $lines[] = '  ' . $f;
            }
            if (!empty($result['failFiles'])) {
                $lines[] = '';
                $lines[] = '失败:';
                foreach ($result['failFiles'] as $f) $lines[] = '  ' . $f['path'] . ': ' . $f['msg'];
            }
            $out->setValue(implode("\n", $lines));
        });

        // Flat structure
        $children = [
            $titleRow, $dirRow, $excludeLabel, $excludeNode,
            $fileTypeLabel, $fileTypeNode, $resultLabel, $out->root(),
        ];

        $totalH = 480.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);

        // Initialize exclude textarea after bind
        $excludeLeaf = LayoutNode::find($sv->root(), $excludeId);
        if ($excludeLeaf !== null) {
            $excludeLeaf->spec = new \Yangweijie\Ui2\Rendering\WidgetRenderer\TextAreaSpec(
                value: ".idea\n.git\n.svn\n.vscode\nnode_modules",
                placeholder: '排除目录（每行一个）'
            );
        }

        return $sv->root();
    }

    private static function getExcludeList(Surface $surface, string $key): array
    {
        $node = LayoutNode::find($surface->rootLayout(), "{$key}:exclude");
        if ($node !== null && $node->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\TextAreaSpec) {
            return array_filter(array_map('trim', explode("\n", $node->spec->value)));
        }
        return [];
    }

    private static function scanAndDisplay(string $path, Surface $surface, string $key, float $w): void
    {
        self::$fileTypes = [];
        self::$lastPath = $path;

        if (!is_dir($path)) {
            self::$fileTypeText = '';
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $extCounts = [];
        $totalFiles = 0;
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $totalFiles++;
            $ext = $file->getExtension();
            if ($ext === '') $ext = '(no ext)';
            $extCounts[$ext] = ($extCounts[$ext] ?? 0) + 1;
        }
        arsort($extCounts);

        // Format output: show top 10 types + summary
        $lines = [];
        $lines[] = "共 {$totalFiles} 个文件, " . count($extCounts) . " 种类型";
        $lines[] = '';
        $i = 0;
        foreach ($extCounts as $ext => $count) {
            if ($i >= 10) {
                $lines[] = '... 还有 ' . (count($extCounts) - 10) . ' 种类型';
                break;
            }
            $lines[] = "☑ .{$ext}  ({$count})";
            self::$fileTypes[] = $ext;
            $i++;
        }

        self::$fileTypeText = implode("\n", $lines);

        $node = LayoutNode::find($surface->rootLayout(), "{$key}:filetypes");
        if ($node !== null) {
            $node->spec = new LabelSpec(self::$fileTypeText, size: 12.0, opacity: 1.0);
        }
    }
}
