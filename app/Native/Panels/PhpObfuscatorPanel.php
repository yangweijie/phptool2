<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use App\Native\WindowHolder;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Pickers\FilePickerDialog;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * PHP Obfuscator (yakpro-po). Source file/dir → obfuscated output.
 */
final class PhpObfuscatorPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // ── Source picker ────────────────────────────────────────────────
        $srcField = Ui::textField("{$key}:src", '/path/to/Source.php', $w - 100, 32);
        $srcPickBtn = LayoutNode::leaf("{$key}:srcpick", new ButtonSpec('📂 Source', 'soft'), width: 90, height: 32);
        $surface->onClick("{$key}:srcpick", function () use ($surface, $key): void {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $node = LayoutNode::find($surface->rootLayout(), "{$key}:src");
                if ($node !== null) $node->spec = new TextFieldSpec(value: $path, placeholder: 'Source .php file');
            }
        });
        $rowSrc = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $rowSrc->child(LayoutNode::leaf(null, new LabelSpec('源文件:', size: 12, opacity: 0.65), width: 70, height: 22));
        $rowSrc->child($srcField);
        $rowSrc->child($srcPickBtn);

        // ── Output directory picker ──────────────────────────────────────
        $descField = Ui::textField("{$key}:desc", sys_get_temp_dir() . '/flyenv_obf', $w - 100, 32);
        $descPickBtn = LayoutNode::leaf("{$key}:descpick", new ButtonSpec('📂 Output', 'soft'), width: 90, height: 32);
        $surface->onClick("{$key}:descpick", function () use ($surface, $key): void {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $node = LayoutNode::find($surface->rootLayout(), "{$key}:desc");
                if ($node !== null) $node->spec = new TextFieldSpec(value: $path, placeholder: 'Output dir');
            }
        });
        $rowDesc = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $rowDesc->child(LayoutNode::leaf(null, new LabelSpec('输出到:', size: 12, opacity: 0.65), width: 70, height: 22));
        $rowDesc->child($descField);
        $rowDesc->child($descPickBtn);

        // ── Config (default yakpro-po CNF) ───────────────────────────────
        $cfgField = Ui::textField("{$key}:cfg", '', $w, 32);
        $rowCfg = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $rowCfg->child(LayoutNode::leaf(null, new LabelSpec('配置文件:', size: 12, opacity: 0.65), width: 70, height: 22));
        $rowCfg->child($cfgField);

        // ── Result ───────────────────────────────────────────────────────
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 140);
        $out->bind($surface);

        // ── Generate button ──────────────────────────────────────────────
        $genBtn = Ui::button("{$key}:gen", '🔒 Generate', 'filled', 150);
        $surface->onClick("{$key}:gen", function () use ($surface, $key, $out): void {
            $srcNode = LayoutNode::find($surface->rootLayout(), "{$key}:src");
            $descNode = LayoutNode::find($surface->rootLayout(), "{$key}:desc");
            $cfgNode = LayoutNode::find($surface->rootLayout(), "{$key}:cfg");
            $src = ($srcNode !== null && $srcNode->spec instanceof TextFieldSpec) ? $srcNode->spec->value : '';
            $desc = ($descNode !== null && $descNode->spec instanceof TextFieldSpec) ? $descNode->spec->value : '';
            $cfg = ($cfgNode !== null && $cfgNode->spec instanceof TextFieldSpec) ? $cfgNode->spec->value : '';
            $out->setValue(Backend::phpObfuscate($src, $desc, 'php', $cfg));
        });

        // ── Assembly ─────────────────────────────────────────────────────
        $rows = [
            Ui::title('PHP Obfuscator', $w),
            Ui::label('使用 yakpro-po 进行 PHP 代码混淆', $w),
            $rowSrc,
            $rowDesc,
            $rowCfg,
            Ui::row([$genBtn]),
            Ui::label('结果', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 10.0, padding: 18.0, contentHeight: 600);
        $sv->bind($surface);
        return $sv->root();
    }
}
