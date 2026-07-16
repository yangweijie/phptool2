#!/usr/bin/env php
<?php
/**
 * FlyEnv Toolbox — native (self-drawn) version.
 *
 * Rebuilt from the original webview FlyEnv (4.15.4) using the Yangweijie\Ui2
 * Surface + widget framework: the whole UI is drawn on a canvas (no WebView,
 * no async JS bridge). The tool catalog and panel framework live under
 * app/Native/; backend computations are pure PHP in App\Native\Backend.
 *
 * Usage: php flyenv-web.php   (needs PHP 8.2+, the LibUI FFI extension, and the
 * native pebview/libui libs — same runtime as the old webview entry).
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Native\NativeApp;
use Libui\Ffi;

Ffi::init();

$app = new NativeApp(getenv('UI2_AUTOMATION') === 'true');
$app->run();

// Periodic temp-file cleanup (older than 1 hour).
register_shutdown_function(static function (): void {
    $files = glob(sys_get_temp_dir() . '/fly_*');
    if ($files) {
        foreach ($files as $f) {
            if (filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
    }
});
