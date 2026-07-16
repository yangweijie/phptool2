<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface; use Yangweijie\Ui2\Widgets\TextAreaControl;

final class SslMakePanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $cn = Ui::textField("{$key}:cn", 'Common Name (e.g. example.com)', $w);
        $org = Ui::textField("{$key}:org", 'Organization', $w);
        $cert = new TextAreaControl("{$key}:cert", '', width: $w, height: 140);
        $priv = new TextAreaControl("{$key}:priv", '', width: $w, height: 140);
        $cert->bind($surface); $priv->bind($surface);
        $surface->onClick("{$key}:gen", static function () use ($cn, $org, $cert, $priv): void {
            $cnVal = $cn->spec instanceof TextFieldSpec ? trim($cn->spec->value) : '';
            $orgVal = $org->spec instanceof TextFieldSpec ? trim($org->spec->value) : '';
            $dn = [];
            if ($cnVal !== '') $dn['commonName'] = $cnVal;
            if ($orgVal !== '') $dn['organizationName'] = $orgVal;
            $result = Backend::sslMake($dn);
            if (isset($result['error'])) { $cert->setValue($result['error']); $priv->setValue(''); return; }
            $cert->setValue($result['cert']); $priv->setValue($result['key']);
        });
        $rows = [
            Ui::title('SSL Certificate Generator', $w),
            Ui::label('Common Name', $w), $cn, Ui::label('Organization', $w), $org,
            Ui::row([Ui::button("{$key}:gen", 'Generate (365d)', 'filled', 170)]),
            Ui::label('Certificate', $w), $cert->root(),
            Ui::label('Private Key', $w), $priv->root(),
        ];
        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 660);
        $sv->bind($surface);
        return $sv->root();
    }
}
