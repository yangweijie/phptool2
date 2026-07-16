<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Catalog;
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
 * Image Compress with 6 tabs mirroring the original webview:
 * - batch:     file list table + add/compress all
 * - basic:     single-file path + quality + max-width
 * - compress:  quality + max-width + format
 * - effects:   brightness + contrast + blur
 * - watermark: text + position + opacity
 * - texture:   preset
 */
final class ImageCompressPanel implements Panel
{
    private static int $activeTab = 0;
    private static array $files = [];
    /** @var array<string, string> filePath => status (waiting/processing/done/error) */
    private static array $fileStatus = [];
    private static int $quality = 75;
    private static int $maxWidth = 0;
    private static int $maxHeight = 0;
    private static string $format = 'jpg';
    private static int $brightness = 0;
    private static int $contrast = 0;
    private static int $saturation = 0;
    private static int $sharpen = 0;
    private static int $noise = 0;
    private static int $blur = 0;
    private static string $watermarkText = '';
    private static string $watermarkPos = 'center';
    private static int $watermarkOpacity = 50;
    private static int $wmFontSize = 12;
    private static int $wmRotation = 0;
    private static string $texture = 'none';
    private static string $singlePath = '';
    private static string $outText = '';

    /** Set by NativeApp; called when a tab/setting change requires a full rebuild. */
    public \Closure $onRebuild;

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $zh = Catalog::chinese();
        $title = $zh ? '图片压缩' : 'Image Compress';

        // ── Tab bar ──────────────────────────────────────────────────────
        $tabLabels = [
            0 => $zh ? '批量处理' : 'Batch',
            1 => $zh ? '基础设置' : 'Basic',
            2 => $zh ? '压缩配置' : 'Compress',
            3 => $zh ? '图片效果' : 'Effects',
            4 => $zh ? '水印配置' : 'Watermark',
            5 => $zh ? '纹理配置' : 'Texture',
        ];
        $tabBar = LayoutNode::row(gap: 0, height: 36, align: LayoutStyle::ALIGN_CENTER, id: "{$key}:tabbar");
        foreach ($tabLabels as $ti => $label) {
            $isActive = $ti === self::$activeTab;
            $tabId = "{$key}:tab:{$ti}";
            $btn = LayoutNode::leaf($tabId, new ButtonSpec($label, $isActive ? 'filled' : 'soft'), width: 90, height: 32);
            $surface->onClick($tabId, function () use ($ti, $surface, $key): void {
                self::$activeTab = $ti;
                // Tab content is rebuilt on the next build() call (see
                // NativeApp::onRebuild). But NativeApp owns the rebuild, so
                // dispatch through a callback the host set up.
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            });
            $tabBar->child($btn);
        }

        // ── Tab content ─────────────────────────────────────────────────
        $paneContent = $this->buildTabContent($surface, $key, $w, $zh);
        $paneContent->id = "{$key}:pane";

        // ── Output ────────────────────────────────────────────────────────
        $out = new TextAreaControl("{$key}:out", self::$outText, width: $w, height: 80);
        $out->bind($surface);

        // ── Assembly ─────────────────────────────────────────────────────
        $rows = [
            Ui::title($title, $w),
            $tabBar,
            $paneContent,
            Ui::label($zh ? '输出' : 'Output', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 24.0, contentHeight: 720);
        $sv->bind($surface);
        return $sv->root();
    }

    private function buildTabContent(Surface $surface, string $key, float $w, bool $zh): LayoutNode
    {
        // Compute pane height per tab so the parent column never shrinks rows.
        $paneH = match (self::$activeTab) {
            0 => 340,
            1 => 240,
            2 => 140,
            3 => 240,
            4 => 200,
            5 => 80,
            default => 100,
        };
        $col = LayoutNode::column(gap: 10, width: $w, height: $paneH, align: LayoutStyle::ALIGN_STRETCH);
        $col->style->grow = 0;
        return match (self::$activeTab) {
            0 => $this->buildBatchTab($surface, $key, $w, $zh, $col),
            1 => $this->buildBasicTab($surface, $key, $w, $zh, $col),
            2 => $this->buildCompressTab($surface, $key, $w, $zh, $col),
            3 => $this->buildEffectsTab($surface, $key, $w, $zh, $col),
            4 => $this->buildWatermarkTab($surface, $key, $w, $zh, $col),
            5 => $this->buildTextureTab($surface, $key, $w, $zh, $col),
            default => $col,
        };
    }

    // ── Tab 0: Batch ──────────────────────────────────────────────────
    private function buildBatchTab(Surface $surface, string $key, float $w, bool $zh, LayoutNode $col): LayoutNode
    {
        $col->child(LayoutNode::leaf(null, new LabelSpec($zh ? '批量处理' : 'Batch Process', size: 16, opacity: 0.85), width: $w, height: 28));

        $addBtn = Ui::button("{$key}:addfiles", '➕ ' . ($zh ? '选择文件' : 'Select Files'), 'filled', 130, 32);
        $startBtn = Ui::button("{$key}:startall", '▶ ' . ($zh ? '全部开始' : 'Start All'), 'filled', 100, 32);
        $stopBtn = Ui::button("{$key}:stopall", '⏹ ' . ($zh ? '停止' : 'Stop'), 'soft', 80, 32);
        $retryBtn = Ui::button("{$key}:retry", '🔄 ' . ($zh ? '重试' : 'Retry'), 'soft', 80, 32);
        $clearBtn = Ui::button("{$key}:clearfiles", '🗑 ' . ($zh ? '清除全部' : 'Clear All'), 'soft', 100, 32);

        $btnRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $btnRow->child($addBtn);
        $btnRow->child($startBtn);
        $btnRow->child($stopBtn);
        $btnRow->child($retryBtn);
        $btnRow->child($clearBtn);
        $col->child($btnRow);

        $surface->onClick("{$key}:addfiles", function () use ($surface, $key): void {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                self::$files[] = $path;
                self::$fileStatus[$path] = 'waiting';
                $this->renderFileTable($surface, $key);
            }
        });
        $surface->onClick("{$key}:clearfiles", function () use ($surface, $key): void {
            self::$files = [];
            self::$fileStatus = [];
            $this->renderFileTable($surface, $key);
        });
        $surface->onClick("{$key}:startall", function () use ($surface, $key): void {
            if (empty(self::$files)) return;
            $results = [];
            foreach (self::$files as $file) {
                self::$fileStatus[$file] = 'processing';
                $r = Backend::imageCompressAdvanced(
                    $file,
                    quality: self::$quality,
                    maxWidth: self::$maxWidth,
                    maxHeight: self::$maxHeight,
                    format: self::$format,
                    brightness: self::$brightness,
                    contrast: self::$contrast,
                    saturation: self::$saturation,
                    sharpen: self::$sharpen,
                    noise: self::$noise,
                    blur: self::$blur,
                    watermarkText: self::$watermarkText,
                    watermarkPos: self::$watermarkPos,
                    watermarkOpacity: self::$watermarkOpacity,
                    wmFontSize: self::$wmFontSize,
                    wmRotation: self::$wmRotation,
                    texture: self::$texture,
                );
                self::$fileStatus[$file] = isset($r['error']) ? 'error' : 'done';
                $results[] = $r;
                $this->renderFileTable($surface, $key, $results);
            }
            $out = '';
            $totalOrig = 0; $totalNew = 0;
            foreach ($results as $r) {
                if (isset($r['error'])) {
                    $out .= "❌ " . $r['error'] . "\n";
                } else {
                    $out .= "✅ " . basename($r['output']) . " (" . round($r['original'] / 1024, 1) . "KB → " . round($r['compressed'] / 1024, 1) . "KB, -{$r['ratio']})\n";
                    $totalOrig += $r['original']; $totalNew += $r['compressed'];
                }
            }
            if ($totalOrig > 0) {
                $totalRatio = round((1 - $totalNew / $totalOrig) * 100, 1);
                $out .= "\nTotal: " . round(($totalOrig - $totalNew) / 1024, 1) . "KB saved ({$totalRatio}%)";
            }
            self::$outText = $out;
            $this->renderFileTable($surface, $key, $results);
            $this->refreshOut($surface, $key, $out);
        });
        $surface->onClick("{$key}:stopall", function () use ($surface, $key): void {
            foreach (self::$files as $f) {
                if (self::$fileStatus[$f] === 'processing') {
                    self::$fileStatus[$f] = 'waiting';
                }
            }
            $this->renderFileTable($surface, $key);
        });
        $surface->onClick("{$key}:retry", function () use ($surface, $key): void {
            foreach (self::$files as $f) {
                if (self::$fileStatus[$f] === 'error') {
                    self::$fileStatus[$f] = 'waiting';
                }
            }
            $this->renderFileTable($surface, $key);
        });

        // File list table
        $tableCol = LayoutNode::column(id: "{$key}:filetable", gap: 0, width: $w, align: LayoutStyle::ALIGN_STRETCH);
        $col->child($tableCol);
        $this->renderFileTable($surface, $key);

        return $col;
    }

    private function renderFileTable(Surface $surface, string $key, ?array $results = null): void
    {
        $tableCol = LayoutNode::find($surface->rootLayout(), "{$key}:filetable");
        if ($tableCol === null) return;
        $w = ($tableCol->parent?->w ?? 800) - 8;
        $tableCol->children = [];

        if (empty(self::$files)) {
            $tableCol->child(LayoutNode::leaf(null, new LabelSpec(Catalog::chinese() ? '暂无数据' : 'No data', size: 13, opacity: 0.5), width: $w, height: 30));
            $surface->redraw();
            return;
        }

        $zh = Catalog::chinese();
        // Header: 路径 | 处理前 | 状态 | 结果
        $header = LayoutNode::row(gap: 6, height: 28, align: LayoutStyle::ALIGN_CENTER);
        $header->child(LayoutNode::leaf(null, null, width: 10, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec($zh ? '路径' : 'Path', size: 11, opacity: 0.65), width: 300, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec($zh ? '处理前' : 'Before', size: 11, opacity: 0.65), width: 70, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec($zh ? '状态' : 'Status', size: 11, opacity: 0.65), width: 70, height: 22));
        $header->child(LayoutNode::leaf(null, new LabelSpec($zh ? '结果' : 'Result', size: 11, opacity: 0.65), width: 70, height: 22));
        $tableCol->child($header);

        foreach (self::$files as $i => $file) {
            $status = self::$fileStatus[$file] ?? 'waiting';
            $statusIcon = match ($status) {
                'processing' => '⏳',
                'done' => '✅',
                'error' => '❌',
                default => '⏸',
            };
            $row = LayoutNode::row(gap: 6, height: 24, align: LayoutStyle::ALIGN_CENTER);
            $row->child(LayoutNode::leaf(null, null, width: 10, height: 22));
            $row->child(LayoutNode::leaf(null, new LabelSpec(basename($file), size: 11), width: 300, height: 22));
            $origKB = is_file($file) ? round(filesize($file) / 1024, 1) : 0;
            $row->child(LayoutNode::leaf(null, new LabelSpec("{$origKB} KB", size: 11), width: 70, height: 22));
            $row->child(LayoutNode::leaf(null, new LabelSpec($statusIcon . $status, size: 11), width: 70, height: 22));
            $after = $results[$i] ?? null;
            $afterStr = $after && !isset($after['error']) ? round($after['compressed'] / 1024, 1) . ' KB' : ($status === 'waiting' ? '—' : '');
            $row->child(LayoutNode::leaf(null, new LabelSpec($afterStr, size: 11), width: 70, height: 22));
            $tableCol->child($row);
        }
        $surface->redraw();
    }

    private function refreshOut(Surface $surface, string $key, string $text): void
    {
        $out = LayoutNode::find($surface->rootLayout(), "textarea:{$key}:out");
        if ($out !== null) {
            // TextAreaControl exposes setValue; find the bound control via leaf id
            $panel = new \ReflectionClass(\Yangweijie\Ui2\Widgets\TextAreaControl::class);
            $controlsProp = $panel->getProperty('controls');
            $controlsProp->setAccessible(true);
            $controls = $controlsProp->getValue();
            if (isset($controls["{$key}:out"])) {
                $controls["{$key}:out"]->setValue($text);
            }
        }
        $surface->redraw();
    }

    // ── Tab 1: Basic ──────────────────────────────────────────────────
    private function buildBasicTab(Surface $surface, string $key, float $w, bool $zh, LayoutNode $col): LayoutNode
    {
        $pathField = LayoutNode::leaf("{$key}:path", new TextFieldSpec(value: self::$singlePath, placeholder: $zh ? '图片路径' : 'Image path'), width: $w - 100, height: 32);
        $pathPickBtn = LayoutNode::leaf("{$key}:pathpick", new ButtonSpec('📂', 'soft'), width: 40, height: 32);
        $surface->onClick("{$key}:pathpick", function () use ($pathField): void {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $pathField->spec = new TextFieldSpec(value: $path, placeholder: 'Image path');
            }
        });
        $row1 = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row1->child(LayoutNode::leaf(null, new LabelSpec($zh ? '图片路径' : 'Path', size: 12, opacity: 0.65), width: 80, height: 22));
        $row1->child($pathField);
        $row1->child($pathPickBtn);
        $col->child($row1);

        // Width / Height steppers
        $whRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $wLabel = LayoutNode::leaf(null, new LabelSpec($zh ? '宽度(px)' : 'Width(px)', size: 12, opacity: 0.65), width: 80, height: 22);
        $wMinus = LayoutNode::leaf("{$key}:wh1:-", new ButtonSpec('-', 'soft'), width: 30, height: 28);
        $wVal = LayoutNode::leaf("{$key}:wh1:val", new LabelSpec((string) self::$maxWidth, size: 13), width: 60, height: 22);
        $wPlus = LayoutNode::leaf("{$key}:wh1:+", new ButtonSpec('+', 'soft'), width: 30, height: 28);
        $hLabel = LayoutNode::leaf(null, new LabelSpec($zh ? '高度(px)' : 'Height(px)', size: 12, opacity: 0.65), width: 80, height: 22);
        $hMinus = LayoutNode::leaf("{$key}:wh2:-", new ButtonSpec('-', 'soft'), width: 30, height: 28);
        $hVal = LayoutNode::leaf("{$key}:wh2:val", new LabelSpec((string) self::$maxHeight, size: 13), width: 60, height: 22);
        $hPlus = LayoutNode::leaf("{$key}:wh2:+", new ButtonSpec('+', 'soft'), width: 30, height: 28);
        $whRow->child($wLabel); $whRow->child($wMinus); $whRow->child($wVal); $whRow->child($wPlus);
        $whRow->child($hLabel); $whRow->child($hMinus); $whRow->child($hVal); $whRow->child($hPlus);
        $col->child($whRow);
        $surface->onClick("{$key}:wh1:-", function () use ($surface, $key): void {
            self::$maxWidth = max(0, self::$maxWidth - 100);
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:wh1:val");
            if ($n !== null) $n->spec = new LabelSpec((string) self::$maxWidth, size: 13);
            $surface->redraw();
        });
        $surface->onClick("{$key}:wh1:+", function () use ($surface, $key): void {
            self::$maxWidth = min(9999, self::$maxWidth + 100);
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:wh1:val");
            if ($n !== null) $n->spec = new LabelSpec((string) self::$maxWidth, size: 13);
            $surface->redraw();
        });
        $surface->onClick("{$key}:wh2:-", function () use ($surface, $key): void {
            self::$maxHeight = max(0, self::$maxHeight - 100);
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:wh2:val");
            if ($n !== null) $n->spec = new LabelSpec((string) self::$maxHeight, size: 13);
            $surface->redraw();
        });
        $surface->onClick("{$key}:wh2:+", function () use ($surface, $key): void {
            self::$maxHeight = min(9999, self::$maxHeight + 100);
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:wh2:val");
            if ($n !== null) $n->spec = new LabelSpec((string) self::$maxHeight, size: 13);
            $surface->redraw();
        });

        $qualRow = $this->buildQualityRow($surface, $key, $w, $zh);
        $col->child($qualRow);

        $runBtn = Ui::button("{$key}:basicrun", '⚡ ' . ($zh ? '压缩' : 'Compress'), 'filled', 130, 32);
        $rowRun = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $rowRun->child($runBtn);
        $col->child($rowRun);

        $surface->onClick("{$key}:basicrun", function () use ($surface, $key): void {
            $pathNode = LayoutNode::find($surface->rootLayout(), "{$key}:path");
            $path = $pathNode?->spec instanceof TextFieldSpec ? $pathNode->spec->value : '';
            if ($path === '' || !is_file($path)) {
                self::$outText = 'Error: select a valid image file';
            } else {
                $r = Backend::imageCompressAdvanced($path, quality: self::$quality, maxWidth: self::$maxWidth, maxHeight: self::$maxHeight, format: self::$format);
                if (isset($r['error'])) {
                    self::$outText = 'Error: ' . $r['error'];
                } else {
                    self::$outText = "Compressed: " . $r['output'] . " ({$r['ratio']} smaller)";
                }
            }
            $this->refreshOut($surface, $key, self::$outText);
        });

        return $col;
    }

    private function buildQualityRow(Surface $surface, string $key, float $w, bool $zh): LayoutNode
    {
        $row = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row->child(LayoutNode::leaf(null, new LabelSpec($zh ? '质量' : 'Quality', size: 12, opacity: 0.65), width: 80, height: 22));
        foreach ([30, 50, 70, 90] as $q) {
            $isActive = $q === self::$quality;
            $qId = "{$key}:q:{$q}";
            $qBtn = LayoutNode::leaf($qId, new ButtonSpec((string) $q, $isActive ? 'filled' : 'soft'), width: 60, height: 32);
            $surface->onClick($qId, function () use ($q): void { self::$quality = $q; });
            $row->child($qBtn);
        }
        return $row;
    }

    private function buildMaxWidthRow(Surface $surface, string $key, float $w, bool $zh): LayoutNode
    {
        $row = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row->child(LayoutNode::leaf(null, new LabelSpec($zh ? '最大宽度' : 'Max Width', size: 12, opacity: 0.65), width: 80, height: 22));
        foreach ([0, 800, 1280, 1920, 3840] as $mw) {
            $label = $mw === 0 ? ($zh ? '原始' : 'Original') : (string) $mw;
            $isActive = $mw === self::$maxWidth;
            $mwId = "{$key}:mw:{$mw}";
            $mwBtn = LayoutNode::leaf($mwId, new ButtonSpec($label, $isActive ? 'filled' : 'soft'), width: 70, height: 32);
            $surface->onClick($mwId, function () use ($mw): void { self::$maxWidth = $mw; });
            $row->child($mwBtn);
        }
        return $row;
    }

    // ── Tab 2: Compress ──────────────────────────────────────────────
    private function buildCompressTab(Surface $surface, string $key, float $w, bool $zh, LayoutNode $col): LayoutNode
    {
        $col->child($this->buildQualityRow($surface, $key, $w, $zh));
        $col->child($this->buildMaxWidthRow($surface, $key, $w, $zh));

        // Format selector
        $row = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row->child(LayoutNode::leaf(null, new LabelSpec($zh ? '格式' : 'Format', size: 12, opacity: 0.65), width: 80, height: 22));
        foreach (['jpg' => 'JPEG', 'png' => 'PNG', 'webp' => 'WebP', 'gif' => 'GIF', 'avif' => 'AVIF'] as $fmt => $lbl) {
            $isActive = $fmt === self::$format;
            $fId = "{$key}:fmt:{$fmt}";
            $fBtn = LayoutNode::leaf($fId, new ButtonSpec($lbl, $isActive ? 'filled' : 'soft'), width: 60, height: 32);
            $surface->onClick($fId, function () use ($fmt): void { self::$format = $fmt; });
            $row->child($fBtn);
        }
        $col->child($row);

        return $col;
    }

    // ── Tab 3: Effects ───────────────────────────────────────────────
    private function buildEffectsTab(Surface $surface, string $key, float $w, bool $zh, LayoutNode $col): LayoutNode
    {
        $col->child($this->buildStepperRow($surface, $key, $w, $zh ? '亮度' : 'Brightness', 'brightness', -100, 100, 10));
        $col->child($this->buildStepperRow($surface, $key, $w, $zh ? '对比度' : 'Contrast', 'contrast', -100, 100, 10));
        $col->child($this->buildStepperRow($surface, $key, $w, $zh ? '饱和度' : 'Saturation', 'saturation', -100, 100, 10));
        $col->child($this->buildStepperRow($surface, $key, $w, $zh ? '锐化' : 'Sharpen', 'sharpen', 0, 10, 1));
        $col->child($this->buildStepperRow($surface, $key, $w, $zh ? '噪点' : 'Noise', 'noise', 0, 10, 1));
        $col->child($this->buildStepperRow($surface, $key, $w, $zh ? '模糊' : 'Blur', 'blur', 0, 10, 1));
        return $col;
    }

    private function buildStepperRow(Surface $surface, string $key, float $w, string $label, string $prop, int $min, int $max, int $step): LayoutNode
    {
        $current = match ($prop) {
            'brightness' => self::$brightness,
            'contrast' => self::$contrast,
            'blur' => self::$blur,
            default => 0,
        };
        $row = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row->child(LayoutNode::leaf(null, new LabelSpec($label, size: 12, opacity: 0.65), width: 80, height: 22));
        $minusId = "{$key}:{$prop}:-";
        $plusId = "{$key}:{$prop}:+";
        $valId = "{$key}:{$prop}:val";
        $minusBtn = LayoutNode::leaf($minusId, new ButtonSpec('-', 'soft'), width: 36, height: 28);
        $valLabel = LayoutNode::leaf($valId, new LabelSpec((string) $current, size: 14), width: 60, height: 22);
        $plusBtn = LayoutNode::leaf($plusId, new ButtonSpec('+', 'soft'), width: 36, height: 28);
        $row->child($minusBtn);
        $row->child($valLabel);
        $row->child($plusBtn);
        $surface->onClick($minusId, function () use ($prop, $valId, $min, $step, $surface, $key): void {
            self::setEffectValue($prop, max($min, self::getEffectValue($prop) - $step));
            $val = self::getEffectValue($prop);
            $node = LayoutNode::find($surface->rootLayout(), $valId);
            if ($node !== null) $node->spec = new LabelSpec((string) $val, size: 14);
            $surface->redraw();
        });
        $surface->onClick($plusId, function () use ($prop, $valId, $max, $step, $surface, $key): void {
            self::setEffectValue($prop, min($max, self::getEffectValue($prop) + $step));
            $val = self::getEffectValue($prop);
            $node = LayoutNode::find($surface->rootLayout(), $valId);
            if ($node !== null) $node->spec = new LabelSpec((string) $val, size: 14);
            $surface->redraw();
        });
        return $row;
    }

    private static function getEffectValue(string $prop): int
    {
        return match ($prop) {
            'brightness' => self::$brightness,
            'contrast' => self::$contrast,
            'saturation' => self::$saturation,
            'sharpen' => self::$sharpen,
            'noise' => self::$noise,
            'blur' => self::$blur,
            default => 0,
        };
    }

    private static function setEffectValue(string $prop, int $value): void
    {
        match ($prop) {
            'brightness' => self::$brightness = $value,
            'contrast' => self::$contrast = $value,
            'saturation' => self::$saturation = $value,
            'sharpen' => self::$sharpen = $value,
            'noise' => self::$noise = $value,
            'blur' => self::$blur = $value,
            default => null,
        };
    }

    // ── Tab 4: Watermark ─────────────────────────────────────────────
    private function buildWatermarkTab(Surface $surface, string $key, float $w, bool $zh, LayoutNode $col): LayoutNode
    {
        // Watermark text
        $wmField = LayoutNode::leaf("{$key}:wmtext", new TextFieldSpec(value: self::$watermarkText, placeholder: $zh ? '水印文字' : 'Watermark text'), width: $w, height: 32);
        $row1 = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row1->child(LayoutNode::leaf(null, new LabelSpec($zh ? '水印文字' : 'Text', size: 12, opacity: 0.65), width: 80, height: 22));
        $row1->child($wmField);
        $col->child($row1);

        // Position
        $row2 = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row2->child(LayoutNode::leaf(null, new LabelSpec($zh ? '位置' : 'Position', size: 12, opacity: 0.65), width: 80, height: 22));
        $positions = [
            'top-left' => $zh ? '左上' : 'TL',
            'top-right' => $zh ? '右上' : 'TR',
            'center' => $zh ? '居中' : 'Center',
            'bottom-left' => $zh ? '左下' : 'BL',
            'bottom-right' => $zh ? '右下' : 'BR',
        ];
        foreach ($positions as $p => $lbl) {
            $isActive = $p === self::$watermarkPos;
            $pId = "{$key}:wmpos:{$p}";
            $pBtn = LayoutNode::leaf($pId, new ButtonSpec($lbl, $isActive ? 'filled' : 'soft'), width: 60, height: 32);
            $surface->onClick($pId, function () use ($p): void { self::$watermarkPos = $p; });
            $row2->child($pBtn);
        }
        $col->child($row2);

        // Opacity
        $row3 = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row3->child(LayoutNode::leaf(null, new LabelSpec($zh ? '透明度' : 'Opacity', size: 12, opacity: 0.65), width: 80, height: 22));
        $opLabel = LayoutNode::leaf("{$key}:wmop:val", new LabelSpec((string) self::$watermarkOpacity, size: 14), width: 60, height: 22);
        $opMinus = LayoutNode::leaf("{$key}:wmop:-", new ButtonSpec('-', 'soft'), width: 36, height: 28);
        $opPlus = LayoutNode::leaf("{$key}:wmop:+", new ButtonSpec('+', 'soft'), width: 36, height: 28);
        $row3->child($opMinus);
        $row3->child($opLabel);
        $row3->child($opPlus);
        $col->child($row3);
        $surface->onClick("{$key}:wmop:-", function () use ($surface, $key): void {
            self::$watermarkOpacity = max(0, self::$watermarkOpacity - 10);
            $node = LayoutNode::find($surface->rootLayout(), "{$key}:wmop:val");
            if ($node !== null) $node->spec = new LabelSpec((string) self::$watermarkOpacity, size: 14);
            $surface->redraw();
        });
        $surface->onClick("{$key}:wmop:+", function () use ($surface, $key): void {
            self::$watermarkOpacity = min(100, self::$watermarkOpacity + 10);
            $node = LayoutNode::find($surface->rootLayout(), "{$key}:wmop:val");
            if ($node !== null) $node->spec = new LabelSpec((string) self::$watermarkOpacity, size: 14);
            $surface->redraw();
        });

        // Font size stepper (consistent with opacity stepper: 36px buttons, 14px label)
        $fsRow = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $fsRow->child(LayoutNode::leaf(null, new LabelSpec($zh ? '字号(px)' : 'Font(px)', size: 12, opacity: 0.65), width: 80, height: 22));
        $fsMinus = LayoutNode::leaf("{$key}:wmfs:-", new ButtonSpec('-', 'soft'), width: 36, height: 28);
        $fsVal = LayoutNode::leaf("{$key}:wmfs:val", new LabelSpec((string) self::$wmFontSize, size: 14), width: 60, height: 22);
        $fsPlus = LayoutNode::leaf("{$key}:wmfs:+", new ButtonSpec('+', 'soft'), width: 36, height: 28);
        $fsRow->child($fsMinus); $fsRow->child($fsVal); $fsRow->child($fsPlus);
        $col->child($fsRow);
        $surface->onClick("{$key}:wmfs:-", function () use ($surface, $key): void {
            self::$wmFontSize = max(6, self::$wmFontSize - 2);
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:wmfs:val");
            if ($n !== null) $n->spec = new LabelSpec((string) self::$wmFontSize, size: 14);
            $surface->redraw();
        });
        $surface->onClick("{$key}:wmfs:+", function () use ($surface, $key): void {
            self::$wmFontSize = min(72, self::$wmFontSize + 2);
            $n = LayoutNode::find($surface->rootLayout(), "{$key}:wmfs:val");
            if ($n !== null) $n->spec = new LabelSpec((string) self::$wmFontSize, size: 14);
            $surface->redraw();
        });

        return $col;
    }

    // ── Tab 5: Texture ───────────────────────────────────────────────
    private function buildTextureTab(Surface $surface, string $key, float $w, bool $zh, LayoutNode $col): LayoutNode
    {
        $row = LayoutNode::row(gap: 6, height: 36, align: LayoutStyle::ALIGN_CENTER);
        $row->child(LayoutNode::leaf(null, new LabelSpec($zh ? '纹理' : 'Texture', size: 12, opacity: 0.65), width: 80, height: 22));
        $textures = [
            'none' => $zh ? '无' : 'None',
            'paper' => $zh ? '纸张' : 'Paper',
            'canvas' => $zh ? '画布' : 'Canvas',
            'wood' => $zh ? '木纹' : 'Wood',
            'metal' => $zh ? '金属' : 'Metal',
            'concrete' => $zh ? '混凝土' : 'Concrete',
            'brick' => $zh ? '砖墙' : 'Brick',
            'stone' => $zh ? '石头' : 'Stone',
        ];
        foreach ($textures as $tex => $lbl) {
            $isActive = $tex === self::$texture;
            $tId = "{$key}:tex:{$tex}";
            $tBtn = LayoutNode::leaf($tId, new ButtonSpec($lbl, $isActive ? 'filled' : 'soft'), width: 80, height: 32);
            $surface->onClick($tId, function () use ($tex): void { self::$texture = $tex; });
            $row->child($tBtn);
        }
        $col->child($row);
        return $col;
    }
}
