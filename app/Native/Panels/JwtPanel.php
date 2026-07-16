<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\DropdownMenuControl;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

final class JwtPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $alg = 'HS256';
        $mode = 'Encode';

        $algMenu = new DropdownMenuControl("{$key}:alg", ['HS256', 'HS384', 'HS512', 'none'], selected: 0, width: 160);
        $algMenu->bind($surface)->onSelect(static function (int $i, string $label) use (&$alg): void {
            $alg = $label;
        });

        $modeMenu = new DropdownMenuControl("{$key}:mode", ['Encode', 'Decode'], selected: 0, width: 160);
        $modeMenu->bind($surface)->onSelect(static function (int $i, string $label) use (&$mode): void {
            $mode = $label;
        });

        $secret = '';
        $secretLeaf = Ui::textField("{$key}:secret", 'secret / key', $w);
        $surface->onText("{$key}:secret", static function (string $c, bool $bs) use ($secretLeaf, $surface, &$secret): void {
            $cur = $secretLeaf->spec instanceof TextFieldSpec ? $secretLeaf->spec->value : '';
            $secret = $bs ? mb_substr($cur, 0, -1) : $cur . $c;
            $secretLeaf->spec = new TextFieldSpec(value: $secret, placeholder: 'secret / key');
            $surface->redraw();
        });

        $header = new TextAreaControl("{$key}:header", '{"typ":"JWT","alg":"HS256"}', width: $w, height: 80);
        $payload = new TextAreaControl("{$key}:payload", '{"sub":"123","name":"FlyEnv"}', width: $w, height: 100);
        $token = new TextAreaControl("{$key}:token", '', width: $w, height: 100);
        $out = new TextAreaControl("{$key}:out", '', width: $w, height: 200);
        $header->bind($surface);
        $payload->bind($surface);
        $token->bind($surface);
        $out->bind($surface);

        $run = Ui::button("{$key}:run", 'Run', 'filled', 120);
        $surface->onClick("{$key}:run", static function () use (&$alg, &$mode, $header, $payload, $token, $out, &$secret): void {
            try {
                if ($mode === 'Encode') {
                    $res = Backend::jwtEncode($header->getValue(), $payload->getValue(), $alg, $secret);
                    $out->setValue($res['token']);
                } else {
                    $res = Backend::jwtDecode($token->getValue(), $alg, $secret);
                    $text = 'Valid: ' . ($res['valid'] ? 'YES' : 'NO')
                        . "\n\nHeader:\n" . $res['header']
                        . "\n\nPayload:\n" . $res['payload'];
                    $out->setValue($text);
                }
            } catch (\Throwable $e) {
                $out->setValue('Error: ' . $e->getMessage());
            }
        });

        $rows = [
            Ui::title('JWT', $w),
            Ui::label('Algorithm', $w),
            $algMenu->root(),
            Ui::label('Mode', $w),
            $modeMenu->root(),
            Ui::label('Secret', $w),
            $secretLeaf,
            Ui::label('Header (JSON)', $w),
            $header->root(),
            Ui::label('Payload (JSON)', $w),
            $payload->root(),
            Ui::label('Token (decode input)', $w),
            $token->root(),
            Ui::row([$run]),
            Ui::label('Output', $w),
            $out->root(),
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 12.0, padding: 18.0, contentHeight: 1000);
        $sv->bind($surface);
        return $sv->root();
    }
}
