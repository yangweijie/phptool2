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
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class PhpObfuscatorPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // Title row
        $titleRow = LayoutNode::row(gap: 0.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('PHP混淆', size: 16.0, opacity: 0.85), width: $w - 100.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('☆', size: 16.0), width: 20.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf("{$key}:gen", new ButtonSpec('生成', 'filled'), width: 80.0, height: 30.0));

        // PHP version
        $versionField = Ui::textField("{$key}:version", '混淆用PHP版本', $w);

        // Source file
        $srcField = Ui::textField("{$key}:src", '待混淆PHP文件或文件夹', $w - 40.0);
        $srcPickBtn = Ui::button("{$key}:srcpick", '📁', 'outline', 32.0, 30.0);
        $srcRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $srcRow->child($srcField);
        $srcRow->child($srcPickBtn);

        // Output file
        $outField = Ui::textField("{$key}:outpath", '保存文件或文件夹, 不能和源文件或原文件夹重复', $w - 40.0);
        $outPickBtn = Ui::button("{$key}:outpick", '📁', 'outline', 32.0, 30.0);
        $outRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $outRow->child($outField);
        $outRow->child($outPickBtn);

        // Advanced settings label
        $advLabel = LayoutNode::leaf(null, new LabelSpec('高级设置', size: 13.0, opacity: 0.85), width: $w, height: 24.0);

        // Config editor (WebView) - always visible
        $editor = LayoutNode::leaf("{$key}:editor", new WebViewSpec(html: self::editorHtml()), width: $w, height: 300.0);

        // Handlers
        $surface->onClick("{$key}:srcpick", function () use ($surface, $key) {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $node = LayoutNode::find($surface->rootLayout(), "{$key}:src");
                if ($node !== null && $node->spec instanceof TextFieldSpec) {
                    $node->spec = new TextFieldSpec(value: $path, placeholder: '待混淆PHP文件或文件夹');
                }
                $surface->redraw();
            }
        });

        $surface->onClick("{$key}:outpick", function () use ($surface, $key) {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $node = LayoutNode::find($surface->rootLayout(), "{$key}:outpath");
                if ($node !== null && $node->spec instanceof TextFieldSpec) {
                    $node->spec = new TextFieldSpec(value: $path, placeholder: '保存文件或文件夹');
                }
                $surface->redraw();
            }
        });

        $surface->onClick("{$key}:gen", function () use ($surface, $key) {
            $srcNode = LayoutNode::find($surface->rootLayout(), "{$key}:src");
            $outNode = LayoutNode::find($surface->rootLayout(), "{$key}:outpath");
            $src = ($srcNode !== null && $srcNode->spec instanceof TextFieldSpec) ? $srcNode->spec->value : '';
            $outPath = ($outNode !== null && $outNode->spec instanceof TextFieldSpec) ? $outNode->spec->value : '';
            if ($src === '') return;
            Backend::phpObfuscate($src, $outPath, 'php', '');
        });

        // Flat structure
        $children = [
            $titleRow,
            $versionField,
            $srcRow,
            $outRow,
            $advLabel,
            $editor,
        ];

        $totalH = 550.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 8.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private static function editorHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;background:#1e1e2e;color:#cdd6f4;padding:12px;height:100vh;display:flex;flex-direction:column}
.toolbar{display:flex;gap:8px;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #45475a}
.btn{padding:4px 12px;border:1px solid #585b70;border-radius:4px;background:#313244;color:#cdd6f4;cursor:pointer;font-size:12px}
.btn:hover{background:#45475a}
.btn.primary{background:#89b4fa;color:#1e1e2e;border-color:#89b4fa}
.btn.danger{background:#f38ba8;color:#1e1e2e;border-color:#f38ba8}
textarea{flex:1;width:100%;background:#181825;color:#cdd6f4;border:1px solid #45475a;border-radius:4px;padding:12px;font-family:inherit;font-size:13px;resize:none;line-height:1.5;tab-size:4}
textarea:focus{outline:none;border-color:#89b4fa}
.status{margin-top:8px;font-size:12px;color:#a6adc8}
</style></head>
<body>
<div class="toolbar">
  <button class="btn primary" onclick="saveConfig()">保存配置</button>
  <button class="btn" onclick="resetConfig()">重置默认</button>
  <button class="btn danger" onclick="clearConfig()">清空</button>
  <span class="status" id="status"></span>
</div>
<textarea id="editor" spellcheck="false">PHP Obfuscator Configuration (yakpro-po)

=== General ===
obfuscate = true
strip_indentation = true
strip_comments = true
strip_blanklines = true

=== Strings ===
obfuscate_strings = true
shuffle_strings = true

=== Variables ===
obfuscate_variables = true
obfuscate_variables_kind = prefix

=== Functions ===
obfuscate_functions = true
obfuscate_functions_kind = prefix

=== Classes ===
obfuscate_classes = true
obfuscate_classes_kind = prefix

=== Namespaces ===
obfuscate_namespaces = true</textarea>
<script>
function saveConfig(){
  var content=document.getElementById('editor').value;
  document.getElementById('status').textContent='已保存 ('+content.length+' chars)';
  document.getElementById('status').style.color='#a6e3a1';
}
function resetConfig(){
  document.getElementById('editor').value='PHP Obfuscator Configuration (yakpro-po)\n\n=== General ===\nobfuscate = true\nstrip_indentation = true\nstrip_comments = true\nstrip_blanklines = true\n\n=== Strings ===\nobfuscate_strings = true\nshuffle_strings = true\n\n=== Variables ===\nobfuscate_variables = true\nobfuscate_variables_kind = prefix\n\n=== Functions ===\nobfuscate_functions = true\nobfuscate_functions_kind = prefix\n\n=== Classes ===\nobfuscate_classes = true\nobfuscate_classes_kind = prefix\n\n=== Namespaces ===\nobfuscate_namespaces = true';
  document.getElementById('status').textContent='已重置为默认配置';
}
function clearConfig(){
  document.getElementById('editor').value='';
  document.getElementById('status').textContent='已清空';
}
document.getElementById('editor').addEventListener('keydown',function(e){
  if(e.key==='Tab'){
    e.preventDefault();
    var s=this.selectionStart,ev=this.selectionEnd;
    this.value=this.value.substring(0,s)+'    '+this.value.substring(ev);
    this.selectionStart=this.selectionEnd=s+4;
  }
});
</script>
</body></html>
HTML;
    }
}
