<?php

declare(strict_types=1);

namespace App;

/**
 * Markdown rendering — shared by the WebView "md" bind and the test suite.
 * Uses Parsedown (already a project dependency) with safe mode enabled.
 */
final class MarkdownHelper
{
    public static function render(string $md): string
    {
        if (trim($md) === '') {
            return '';
        }
        if (!class_exists(\Parsedown::class)) {
            return '';
        }
        $pd = new \Parsedown();
        $pd->setSafeMode(true);
        $pd->setBreaksEnabled(true);

        return $pd->text($md);
    }
}
