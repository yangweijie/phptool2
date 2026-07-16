<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class EncryptPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $in = new TextAreaControl("{$key}:in", '', width: $w, height: 120);
        $keyField = Ui::textField("{$key}:key", 'Encryption key (16+ chars)', $w);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 120);
        $in->bind($surface); $out->bind($surface);
        $surface->onClick("{$key}:enc", static function () use ($in, $keyField, $out): void {
            $k = $keyField->spec instanceof TextFieldSpec ? $keyField->spec->value : '';
            $out->setValue(Backend::encrypt($in->getValue(), str_pad($k, 32, 'x')));
        });
        $surface->onClick("{$key}:dec", static function () use ($in, $keyField, $out): void {
            $k = $keyField->spec instanceof TextFieldSpec ? $keyField->spec->value : '';
            $out->setValue(Backend::decrypt($in->getValue(), str_pad($k, 32, 'x')));
        });
        $rows = [
            Ui::title('AES-256 Encryption', $w), Ui::label('Plaintext / Ciphertext', $w), $in->root(),
            Ui::label('Encryption key', $w), $keyField,
            Ui::row([Ui::button("{$key}:enc", 'Encrypt', 'filled', 120), Ui::button("{$key}:dec", 'Decrypt', 'soft', 120)]),
            Ui::label('Result', $w), $out->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 620);
        $sv->bind($surface);
        return $sv->root();
    }
}
