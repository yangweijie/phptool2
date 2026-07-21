<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ComboboxControl;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class CronParserPanel implements Panel
{
    private static string $mode = 'auto';
    private static string $genMode = 'everyMinute';
    private static string $expr = '* * * * *';
    private static int $count = 10;

    private const SCHEDULE_LABELS = ['每分钟', '每N分钟', '每小时', '每天', '每周', '每月'];
    private const SCHEDULE_MODES = ['everyMinute', 'everyNMinutes', 'hourly', 'daily', 'weekly', 'monthly'];
    private const DOW_LABELS = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];
    private const PRESETS = [
        '每分钟' => '* * * * *',
        '每5分钟' => '*/5 * * * *',
        '每天午夜' => '0 0 * * *',
        '工作日9点' => '0 9 * * 1-5',
        '每月1号' => '0 0 1 * *',
    ];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // ── Generate card inputs ──────────────────────────────────────────
        $intervalField = LayoutNode::leaf("{$key}:intv", new TextFieldSpec(value: '5', placeholder: '5'), width: 60, height: 28);
        $hourField = LayoutNode::leaf("{$key}:hour", new TextFieldSpec(value: '0', placeholder: '0'), width: 50, height: 28);
        $minuteField = LayoutNode::leaf("{$key}:min", new TextFieldSpec(value: '0', placeholder: '0'), width: 50, height: 28);
        $domField = LayoutNode::leaf("{$key}:dom", new TextFieldSpec(value: '1', placeholder: '1-31'), width: 60, height: 28);

        $dowCombo = new ComboboxControl("{$key}:dow", self::DOW_LABELS, value: '星期日', width: 110, height: 28, readonly: true);
        $scheduleCombo = new ComboboxControl("{$key}:sched", self::SCHEDULE_LABELS, value: '每分钟', width: 200, height: 28, readonly: true);

        $genExprLabel = LayoutNode::leaf("{$key}:gexpr", new LabelSpec(self::$expr, size: 13), width: $w - 200, height: 26);

        // Mode buttons
        $modeBtns = [
            LayoutNode::leaf("{$key}:m0", new ButtonSpec('自动', 'filled'), width: 60, height: 28),
            LayoutNode::leaf("{$key}:m1", new ButtonSpec('Linux 5', 'soft'), width: 72, height: 28),
            LayoutNode::leaf("{$key}:m2", new ButtonSpec('秒级 6', 'soft'), width: 72, height: 28),
        ];
        $modeMap = ['auto', 'linux', 'seconds'];

        // ── Parse card refs ───────────────────────────────────────────────
        $exprInput = LayoutNode::leaf("{$key}:expr", new TextFieldSpec(value: self::$expr, placeholder: '*/5 * * * *'), width: $w - 60, height: 28);
        $modeTagLabel = LayoutNode::leaf("{$key}:mtag", new LabelSpec('', size: 11), width: 60, height: 18);
        $hintTagLabel = LayoutNode::leaf("{$key}:htag", new LabelSpec('', size: 11), width: $w - 60, height: 18);
        $errorLabel = LayoutNode::leaf("{$key}:err", new LabelSpec('', size: 12), width: $w, height: 18);
        $resultsLabel = LayoutNode::leaf("{$key}:res", new LabelSpec('', size: 12), width: $w, height: 180);
        $countLabel = LayoutNode::leaf("{$key}:cnt", new LabelSpec((string) self::$count, size: 13), width: 40, height: 24);

        // ── Helper: read TextField value ──────────────────────────────────
        $getVal = static fn (LayoutNode $n, string $d = ''): string => $n->spec instanceof TextFieldSpec ? $n->spec->value : $d;

        // ── doParse: read expr + count, show results ─────────────────────
        $doParse = static function () use ($exprInput, $modeTagLabel, $hintTagLabel, $errorLabel, $resultsLabel, $surface): void {
            $expr = self::$expr;
            $count = self::$count;
            $exprInput->spec = new TextFieldSpec(value: $expr, placeholder: '*/5 * * * *');

            if ($expr === '') {
                $modeTagLabel->spec = new LabelSpec('', size: 11);
                $hintTagLabel->spec = new LabelSpec('', size: 11);
                $errorLabel->spec = new LabelSpec('', size: 12);
                $resultsLabel->spec = new LabelSpec('', size: 12);
                $surface->redraw();
                return;
            }

            try {
                $res = Backend::cronGetNextRuns($expr, $count);
                $modeTagLabel->spec = new LabelSpec($res['detectedMode'], size: 11);
                $hintTagLabel->spec = new LabelSpec($res['fieldHint'], size: 11);
                $errorLabel->spec = new LabelSpec('', size: 12);
                $text = '';
                foreach ($res['runs'] as $i => $run) {
                    $text .= ($i + 1) . ". {$run}\n";
                }
                $resultsLabel->spec = new LabelSpec(rtrim($text), size: 12);
            } catch (\Throwable $e) {
                $modeTagLabel->spec = new LabelSpec('', size: 11);
                $hintTagLabel->spec = new LabelSpec('', size: 11);
                $errorLabel->spec = new LabelSpec("⚠ {$e->getMessage()}", size: 12);
                $resultsLabel->spec = new LabelSpec('', size: 12);
            }
            $surface->redraw();
        };

        // ── doGenerate: read inputs, build expr, store, parse ────────────
        $doGenerate = static function () use ($intervalField, $hourField, $minuteField, $domField, $dowCombo, $genExprLabel, $getVal, $doParse, $surface): void {
            self::$expr = Backend::cronBuildExpr([
                'mode' => self::$mode,
                'genMode' => self::$genMode,
                'intMin' => (int) ($getVal($intervalField) ?: 5),
                'minute' => (int) ($getVal($minuteField) ?: 0),
                'hour' => (int) ($getVal($hourField) ?: 0),
                'dow' => array_search($dowCombo->value(), self::DOW_LABELS, true) ?: 0,
                'dom' => (int) ($getVal($domField) ?: 1),
            ]);
            $genExprLabel->spec = new LabelSpec(self::$expr, size: 13);
            $surface->redraw();
            $doParse();
        };

        // ── Wire mode buttons ─────────────────────────────────────────────
        foreach ($modeBtns as $idx => $btn) {
            $surface->onClick($btn->id, static function () use ($idx, $modeBtns, $modeMap, $doGenerate): void {
                self::$mode = $modeMap[$idx];
                foreach ($modeBtns as $i => $b) {
                    $label = $b->spec instanceof ButtonSpec ? $b->spec->label : '';
                    $b->spec = new ButtonSpec($label, $i === $idx ? 'filled' : 'soft');
                }
                $doGenerate();
            });
        }

        // ── Wire schedule combo ───────────────────────────────────────────
        $scheduleCombo->onChange(static function (string $label) use ($doGenerate): void {
            $idx = array_search($label, self::SCHEDULE_LABELS, true);
            self::$genMode = self::SCHEDULE_MODES[$idx] ?? 'everyMinute';
            $doGenerate();
        });
        $scheduleCombo->bind($surface);

        // ── Wire dow combo ────────────────────────────────────────────────
        $dowCombo->onChange(static function () use ($doGenerate): void {
            $doGenerate();
        });
        $dowCombo->bind($surface);

        // ── Wire generate text inputs ─────────────────────────────────────
        foreach ([$intervalField, $hourField, $minuteField, $domField] as $field) {
            $surface->onText($field->id, static function (string $c, bool $bs) use ($field, $surface, $doGenerate): void {
                $cur = $field->spec instanceof TextFieldSpec ? $field->spec->value : '';
                $val = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
                $field->spec = new TextFieldSpec(value: $val, placeholder: $field->spec instanceof TextFieldSpec ? $field->spec->placeholder : '');
                $surface->redraw();
                $doGenerate();
            });
        }

        // ── Wire "使用此表达式" ──────────────────────────────────────────
        $useBtn = Ui::button("{$key}:use", '使用此表达式', 'soft', 110, 28);
        $surface->onClick("{$key}:use", static function () use ($genExprLabel, $exprInput, $doParse, $surface): void {
            self::$expr = $genExprLabel->spec instanceof LabelSpec ? $genExprLabel->spec->text : '';
            $exprInput->spec = new TextFieldSpec(value: self::$expr, placeholder: '*/5 * * * *');
            $surface->redraw();
            $doParse();
        });

        // ── Wire expression field (Card 2) ────────────────────────────────
        $surface->onText("{$key}:expr", static function (string $c, bool $bs) use ($exprInput, $doParse, $surface): void {
            $cur = $exprInput->spec instanceof TextFieldSpec ? $exprInput->spec->value : '';
            $val = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
            self::$expr = $val;
            $exprInput->spec = new TextFieldSpec(value: $val, placeholder: '*/5 * * * *');
            $surface->redraw();
            $doParse();
        });

        // ── Wire preset buttons ───────────────────────────────────────────
        $presetRow1 = LayoutNode::row(gap: 6, align: LayoutStyle::ALIGN_START, height: 30, width: $w);
        $presetRow2 = LayoutNode::row(gap: 6, align: LayoutStyle::ALIGN_START, height: 30, width: $w);
        $pi = 0;
        foreach (self::PRESETS as $label => $expr) {
            $pid = "{$key}:pre:{$pi}";
            $btn = Ui::button($pid, $label, 'soft', 80, 26);
            $surface->onClick($pid, static function () use ($expr, $exprInput, $doParse, $surface): void {
                self::$expr = $expr;
                $exprInput->spec = new TextFieldSpec(value: $expr, placeholder: '*/5 * * * *');
                $surface->redraw();
                $doParse();
            });
            ($pi < 3 ? $presetRow1 : $presetRow2)->child($btn);
            $pi++;
        }

        // ── Wire count buttons ────────────────────────────────────────────
        $decBtn = Ui::button("{$key}:cntd", '−', 'soft', 28, 24);
        $incBtn = Ui::button("{$key}:cnti", '+', 'soft', 28, 24);
        $surface->onClick("{$key}:cntd", static function () use ($countLabel, $doParse, $surface): void {
            self::$count = max(1, self::$count - 1);
            $countLabel->spec = new LabelSpec((string) self::$count, size: 13);
            $surface->redraw();
            $doParse();
        });
        $surface->onClick("{$key}:cnti", static function () use ($countLabel, $doParse, $surface): void {
            self::$count = min(30, self::$count + 1);
            $countLabel->spec = new LabelSpec((string) self::$count, size: 13);
            $surface->redraw();
            $doParse();
        });

        // ── Assemble: flat rows (no nested columns — FlexLayout gives column children h=0) ──
        $rows = [
            Ui::title('⏰ Cron 解析', $w),
            Ui::label('📝 生成', $w),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('模式:', 60, 12, 18))
                ->child($modeBtns[0])->child($modeBtns[1])->child($modeBtns[2]),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('调度:', 60, 12, 18))
                ->child($scheduleCombo->root()),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('间隔:', 60, 12, 18))
                ->child($intervalField)
                ->child(Ui::label('分钟', 40, 12, 18)),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('时间:', 60, 12, 18))
                ->child($hourField)
                ->child(Ui::label(':', 10, 12, 18))
                ->child($minuteField),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('星期:', 60, 12, 18))
                ->child($dowCombo->root()),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('日期:', 60, 12, 18))
                ->child($domField),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('表达式:', 60, 12, 18))
                ->child($genExprLabel)
                ->child($useBtn),
            Ui::label('🔍 解析', $w),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 28, width: $w)
                ->child(Ui::label('表达式:', 60, 12, 18))
                ->child($exprInput),
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 18, width: $w)
                ->child($modeTagLabel)
                ->child($hintTagLabel),
            $presetRow1,
            $presetRow2,
            LayoutNode::row(gap: 8, align: LayoutStyle::ALIGN_CENTER, height: 24, width: $w)
                ->child(Ui::label('显示 ', 40, 12, 18))
                ->child($decBtn)
                ->child($countLabel)
                ->child($incBtn)
                ->child(Ui::label(' 条', 30, 12, 18)),
            Ui::label('📅 下次执行', $w),
            $errorLabel,
            $resultsLabel,
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, contentHeight: max($height, 1000.0), gap: 8.0, padding: 12.0);
        $sv->bind($surface);
        // Auto-parse initial expression so results show on first load
        $doParse();
        return $sv->root();
    }
}
