<?php

declare(strict_types=1);

namespace App\Native;

use Libui\Window;

/**
 * Global holder for the main Window reference.
 *
 * The Window is created in NativeApp::run() but panels need it for file
 * picker dialogs.  NativeApp sets the ref; panels read it in their onClick
 * handlers.
 */
final class WindowHolder
{
    private static ?Window $window = null;

    public static function set(Window $w): void
    {
        self::$window = $w;
    }

    public static function get(): ?Window
    {
        return self::$window;
    }
}
