<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CheckboxSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class TokenGeneratorPanel implements Panel
{
    private static bool $upper = true;
    private static bool $lower = true;
    private static bool $numbers = true;
    private static bool $symbols = false;
    private static int $length = 64;
    private static string $token = '';

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // Generate initial token if empty
        if (self::$token === '') {
            self::generateToken();
        }

        // Title
        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('Token 生成器', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('🔑', size: 16.0), width: 24.0, height: 36.0));

        // Character type section
        $typeLabel = LayoutNode::leaf(null, new LabelSpec('字符类型', size: 13.0, opacity: 0.65), width: $w, height: 20.0);

        // Toggle rows with proper checkboxes
        $halfW = ($w - 8) / 2;
        $row1 = LayoutNode::row(gap: 8.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $row1->child($this->buildCheckbox($surface, $key, 'upper', '大写字母 (A-Z)', self::$upper, $halfW));
        $row1->child($this->buildCheckbox($surface, $key, 'lower', '小写字母 (a-z)', self::$lower, $halfW));

        $row2 = LayoutNode::row(gap: 8.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $row2->child($this->buildCheckbox($surface, $key, 'num', '数字 (0-9)', self::$numbers, $halfW));
        $row2->child($this->buildCheckbox($surface, $key, 'sym', '符号 (!@#$...)', self::$symbols, $halfW));

        // Length section
        $lenLabel = LayoutNode::leaf(null, new LabelSpec('Token 长度', size: 13.0, opacity: 0.65), width: $w, height: 20.0);
        $lenRow = LayoutNode::row(gap: 8.0, height: 32.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $lenRow->child(LayoutNode::leaf(null, new LabelSpec('长度:', size: 13.0), width: 50.0, height: 32.0));
        $lenRow->child(Ui::button("{$key}:lenminus", '−', 'outline', 32.0, 28.0));
        $lenRow->child(LayoutNode::leaf("{$key}:lendisplay", new LabelSpec((string) self::$length, size: 14.0), width: 60.0, height: 28.0));
        $lenRow->child(Ui::button("{$key}:lenplus", '+', 'outline', 32.0, 28.0));

        // Generate button
        $genBtn = Ui::button("{$key}:gen", '生成 Token', 'filled', 120.0, 32.0);

        // Result display (using Label instead of TextArea for reliability)
        $outLabel = LayoutNode::leaf("{$key}:out", new LabelSpec(self::$token, size: 12.0), width: $w, height: 80.0);

        // Copy button
        $copyBtn = Ui::button("{$key}:copy", '📋 复制', 'outline', 80.0, 28.0);

        // Length handlers
        $surface->onClick("{$key}:lenminus", function () use ($surface, $key) {
            self::$length = max(1, self::$length - 1);
            self::updateLength($surface, $key);
            self::generateToken();
            self::updateOutput($surface, $key);
        });

        $surface->onClick("{$key}:lenplus", function () use ($surface, $key) {
            self::$length = min(512, self::$length + 1);
            self::updateLength($surface, $key);
            self::generateToken();
            self::updateOutput($surface, $key);
        });

        // Generate handler
        $surface->onClick("{$key}:gen", function () use ($surface, $key) {
            self::generateToken();
            self::updateOutput($surface, $key);
        });

        // Copy handler
        $surface->onClick("{$key}:copy", function () {
            Backend::copyText(self::$token);
        });

        // Flat structure
        $children = [
            $titleRow,
            $typeLabel, $row1, $row2,
            $lenLabel, $lenRow,
            $genBtn,
            $outLabel,
            $copyBtn,
        ];

        $totalH = 400.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private function buildCheckbox(Surface $surface, string $key, string $opt, string $label, bool $checked, float $w): LayoutNode
    {
        $id = "{$key}:cb_{$opt}";
        $cb = LayoutNode::leaf($id, new CheckboxSpec(checked: $checked, label: $label), width: $w, height: 28.0);

        $surface->onClick($id, function () use ($opt, $id, $label, $surface, $key) {
            match ($opt) {
                'upper' => self::$upper = !self::$upper,
                'lower' => self::$lower = !self::$lower,
                'num' => self::$numbers = !self::$numbers,
                'sym' => self::$symbols = !self::$symbols,
            };
            $newChecked = match ($opt) {
                'upper' => self::$upper,
                'lower' => self::$lower,
                'num' => self::$numbers,
                'sym' => self::$symbols,
            };
            $cbNode = LayoutNode::find($surface->rootLayout(), $id);
            if ($cbNode !== null && $cbNode->spec instanceof CheckboxSpec) {
                $cbNode->spec = new CheckboxSpec(checked: $newChecked, label: $label);
            }
            self::generateToken();
            self::updateOutput($surface, $key);
        });

        return $cb;
    }

    private static function updateLength(Surface $surface, string $key): void
    {
        $node = LayoutNode::find($surface->rootLayout(), "{$key}:lendisplay");
        if ($node !== null && $node->spec instanceof LabelSpec) {
            $node->spec = new LabelSpec((string) self::$length, size: 14.0);
        }
    }

    private static function generateToken(): void
    {
        $chars = '';
        if (self::$upper) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if (self::$lower) $chars .= 'abcdefghijklmnopqrstuvwxyz';
        if (self::$numbers) $chars .= '0123456789';
        if (self::$symbols) $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        if ($chars === '') $chars = 'abcdefghijklmnopqrstuvwxyz';

        $token = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < self::$length; $i++) {
            $token .= $chars[random_int(0, $max)];
        }
        self::$token = $token;
    }

    private static function updateOutput(Surface $surface, string $key): void
    {
        $node = LayoutNode::find($surface->rootLayout(), "{$key}:out");
        if ($node !== null && $node->spec instanceof LabelSpec) {
            $node->spec = new LabelSpec(self::$token, size: 12.0);
        }
        $surface->redraw();
    }
}
