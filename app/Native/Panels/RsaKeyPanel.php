<?php
declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class RsaKeyPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $pub = new TextAreaControl("{$key}:pub", '', width: $w, height: 160);
        $priv = new TextAreaControl("{$key}:priv", '', width: $w, height: 200);
        $pub->bind($surface); $priv->bind($surface);
        $surface->onClick("{$key}:gen", static function () use ($pub, $priv): void {
            $result = Backend::rsaKeyGenerate(2048);
            if (isset($result['error'])) { $pub->setValue($result['error']); $priv->setValue(''); return; }
            $pub->setValue($result['public']);
            $priv->setValue($result['private']);
        });
        $rows = [
            Ui::title('RSA Key Generator', $w),
            Ui::row([Ui::button("{$key}:gen", 'Generate 2048-bit', 'filled', 180)]),
            Ui::label('Public Key', $w), $pub->root(),
            Ui::label('Private Key', $w), $priv->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 660);
        $sv->bind($surface);
        return $sv->root();
    }
}
