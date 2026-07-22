<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class EncryptPanel implements Panel
{
    private static string $algo = 'AES';
    /** @var array<string,TextAreaControl> */
    private static array $controls = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $halfW = ($w - 12) / 2;

        // Title
        $titleRow = LayoutNode::row(gap: 6.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('加密解密', size: 16.0, opacity: 0.85), width: $w - 40.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('🔐', size: 16.0), width: 24.0, height: 36.0));

        // ===== Encryption Section =====
        $encTitle = LayoutNode::leaf(null, new LabelSpec('加密', size: 14.0, opacity: 0.85), width: $halfW, height: 24.0);

        self::$controls['encin'] = new TextAreaControl("{$key}:encin", 'Lorem ipsum dolor sit amet', width: $halfW, height: 100);
        self::$controls['encin']->bind($surface);

        $encKey = Ui::textField("{$key}:enckey", 'my secret key', $halfW);

        // Enc algorithm buttons
        $encAlgoRow = LayoutNode::row(gap: 4.0, height: 28.0, width: $halfW, align: LayoutStyle::ALIGN_CENTER);
        foreach (['AES', 'TripleDES', 'Rabbit', 'RC4'] as $algo) {
            $encAlgoRow->child(Ui::button("{$key}:ea_" . strtolower($algo), $algo, $algo === 'AES' ? 'filled' : 'outline', 60.0, 24.0));
        }

        $encOutput = LayoutNode::leaf("{$key}:encout", new LabelSpec('', size: 11.0), width: $halfW, height: 80.0);

        // ===== Decryption Section =====
        $decTitle = LayoutNode::leaf(null, new LabelSpec('解密', size: 14.0, opacity: 0.85), width: $halfW, height: 24.0);

        self::$controls['decin'] = new TextAreaControl("{$key}:decin", '', width: $halfW, height: 100);
        self::$controls['decin']->bind($surface);

        $decKey = Ui::textField("{$key}:deckey", 'my secret key', $halfW);

        // Dec algorithm buttons
        $decAlgoRow = LayoutNode::row(gap: 4.0, height: 28.0, width: $halfW, align: LayoutStyle::ALIGN_CENTER);
        foreach (['AES', 'TripleDES', 'Rabbit', 'RC4'] as $algo) {
            $decAlgoRow->child(Ui::button("{$key}:da_" . strtolower($algo), $algo, $algo === 'AES' ? 'filled' : 'outline', 60.0, 24.0));
        }

        $decOutput = LayoutNode::leaf("{$key}:decout", new LabelSpec('', size: 11.0), width: $halfW, height: 80.0);

        // ===== Handlers =====
        // Enc algorithm handlers
        foreach (['AES', 'TripleDES', 'Rabbit', 'RC4'] as $algo) {
            $surface->onClick("{$key}:ea_" . strtolower($algo), function () use ($algo, $surface, $key) {
                self::$algo = $algo;
                foreach (['AES', 'TripleDES', 'Rabbit', 'RC4'] as $a) {
                    $node = LayoutNode::find($surface->rootLayout(), "{$key}:ea_" . strtolower($a));
                    if ($node !== null && $node->spec instanceof ButtonSpec) {
                        $node->spec = new ButtonSpec($a, $a === $algo ? 'filled' : 'outline');
                    }
                }
                self::encrypt($surface, $key);
            });
        }

        // Dec algorithm handlers
        foreach (['AES', 'TripleDES', 'Rabbit', 'RC4'] as $algo) {
            $surface->onClick("{$key}:da_" . strtolower($algo), function () use ($algo, $surface, $key) {
                self::$algo = $algo;
                foreach (['AES', 'TripleDES', 'Rabbit', 'RC4'] as $a) {
                    $node = LayoutNode::find($surface->rootLayout(), "{$key}:da_" . strtolower($a));
                    if ($node !== null && $node->spec instanceof ButtonSpec) {
                        $node->spec = new ButtonSpec($a, $a === $algo ? 'filled' : 'outline');
                    }
                }
                self::decrypt($surface, $key);
            });
        }

        // Enc/Dec buttons
        $surface->onClick("{$key}:enc", function () use ($surface, $key) {
            self::encrypt($surface, $key);
        });

        $surface->onClick("{$key}:dec", function () use ($surface, $key) {
            self::decrypt($surface, $key);
        });

        // ===== Layout - flat structure =====
        $row1 = LayoutNode::row(gap: 12.0, height: 24.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $row1->child(LayoutNode::leaf(null, new LabelSpec('加密', size: 14.0, opacity: 0.85), width: $halfW, height: 24.0));
        $row1->child(LayoutNode::leaf(null, new LabelSpec('解密', size: 14.0, opacity: 0.85), width: $halfW, height: 24.0));

        $row2 = LayoutNode::row(gap: 12.0, height: 100.0, width: $w, align: LayoutStyle::ALIGN_START);
        $row2->child(self::$controls['encin']->root());
        $row2->child(self::$controls['decin']->root());

        $row3 = LayoutNode::row(gap: 12.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $row3->child($encKey);
        $row3->child($decKey);

        $row4 = LayoutNode::row(gap: 12.0, height: 28.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $row4->child($encAlgoRow);
        $row4->child($decAlgoRow);

        $row5 = LayoutNode::row(gap: 12.0, height: 80.0, width: $w, align: LayoutStyle::ALIGN_START);
        $row5->child($encOutput);
        $row5->child($decOutput);

        $row6 = LayoutNode::row(gap: 12.0, height: 32.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $row6->child(Ui::button("{$key}:enc", '加密', 'filled', 80.0, 32.0));
        $row6->child(Ui::button("{$key}:dec", '解密', 'filled', 80.0, 32.0));

        $children = [
            $titleRow,
            $row1, $row2, $row3, $row4, $row5, $row6,
        ];

        $totalH = 450.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }

    private static function encrypt(Surface $surface, string $key): void
    {
        $text = self::$controls['encin']?->getValue() ?? '';
        $secret = '';
        $keyNode = LayoutNode::find($surface->rootLayout(), "{$key}:enckey");
        if ($keyNode !== null && $keyNode->spec instanceof TextFieldSpec) {
            $secret = $keyNode->spec->value;
        }

        $result = Backend::encrypt($text, $secret);

        $outNode = LayoutNode::find($surface->rootLayout(), "{$key}:encout");
        if ($outNode !== null && $outNode->spec instanceof LabelSpec) {
            $outNode->spec = new LabelSpec($result, size: 11.0);
        }
        $surface->redraw();
    }

    private static function decrypt(Surface $surface, string $key): void
    {
        $text = self::$controls['decin']?->getValue() ?? '';
        $secret = '';
        $keyNode = LayoutNode::find($surface->rootLayout(), "{$key}:deckey");
        if ($keyNode !== null && $keyNode->spec instanceof TextFieldSpec) {
            $secret = $keyNode->spec->value;
        }

        $result = Backend::decrypt($text, $secret);

        $outNode = LayoutNode::find($surface->rootLayout(), "{$key}:decout");
        if ($outNode !== null && $outNode->spec instanceof LabelSpec) {
            $outNode->spec = new LabelSpec($result, size: 11.0);
        }
        $surface->redraw();
    }
}
