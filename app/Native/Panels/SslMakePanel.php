<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend; use App\Native\Panel; use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class SslMakePanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        // Title row with Generate button on the right
        $titleRow = LayoutNode::row(gap: 0.0, height: 36.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $titleRow->child(LayoutNode::leaf(null, new LabelSpec('SSL证书', size: 16.0, opacity: 0.85), width: $w - 100.0, height: 36.0));
        $titleRow->child(LayoutNode::leaf("{$key}:gen", new \Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec('生成', 'filled'), width: 80.0, height: 30.0));

        // Domains textarea
        $domainsLabel = Ui::label('域名列表', $w, 12.0, 18.0);
        $domains = new TextAreaControl("{$key}:domains", '', width: $w, height: 120);
        $domains->bind($surface);

        // Root CA path
        $caLabel = Ui::label('CA证书路径（可选，不选则自动生成）', $w, 12.0, 18.0);
        $caPath = Ui::textField("{$key}:capath", 'Root CA certificate path, if not choose, will create new in SSL certificate save path', $w - 40.0);
        $caRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $caRow->child($caPath);
        $caRow->child(Ui::button("{$key}:cafile", "📁", 'outline', 32.0, 30.0));

        // Save path
        $saveLabel = Ui::label('证书保存路径', $w, 12.0, 18.0);
        $savePath = Ui::textField("{$key}:savepath", 'SSL certificate save path', $w - 40.0);
        $saveRow = LayoutNode::row(gap: 4.0, height: 34.0, width: $w, align: LayoutStyle::ALIGN_CENTER);
        $saveRow->child($savePath);
        $saveRow->child(Ui::button("{$key}:savefile", "📁", 'outline', 32.0, 30.0));

        // Click handler for Generate
        $surface->onClick("{$key}:gen", function () use ($domains, $caPath, $savePath, $surface, $key) {
            $domainsText = '';
            if ($domains->spec instanceof \Yangweijie\Ui2\Rendering\WidgetRenderer\TextAreaSpec) {
                $domainsText = $domains->spec->value;
            }
            $lines = array_filter(array_map('trim', explode("\n", $domainsText)));
            if (empty($lines)) return;

            $ca = ($caPath->spec instanceof TextFieldSpec) ? trim($caPath->spec->value) : '';
            $save = ($savePath->spec instanceof TextFieldSpec) ? trim($savePath->spec->value) : '';

            $firstDomain = ltrim(array_values($lines)[0], '*.');
            $dn = ['commonName' => $firstDomain];

            $result = Backend::sslMake($dn);
            if (isset($result['error'])) return;

            // Save to file if path specified
            if ($save !== '') {
                $dir = rtrim($save, '/');
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                file_put_contents($dir . '/server.crt', $result['cert']);
                file_put_contents($dir . '/server.key', $result['key']);
            }
        });

        // File picker handlers (open native dialog)
        $surface->onClick("{$key}:cafile", function () use ($caPath, $surface, $key) {
            // Placeholder - would need WindowHolder for native dialog
        });
        $surface->onClick("{$key}:savefile", function () use ($savePath, $surface, $key) {
            // Placeholder - would need WindowHolder for native dialog
        });

        // Flat structure for ScrollViewControl
        $children = [
            $titleRow,
            $domainsLabel,
            $domains->root(),
            $caLabel,
            $caRow,
            $saveLabel,
            $saveRow,
        ];

        $totalH = 300.0;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: max($totalH, $height));
        $sv->bind($surface);
        return $sv->root();
    }
}
