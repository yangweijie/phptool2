<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use Libui\Color;
use Libui\Draw\DrawContext;
use Libui\Text\FontDescriptor;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\CanvasSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class GitMemoPanel implements Panel
{
    private const SUBCOMMANDS = [
        'add', 'am', 'apply', 'archive', 'bisect', 'blame', 'branch', 'bundle',
        'checkout', 'cherry-pick', 'clean', 'clone', 'commit', 'config', 'count-objects',
        'credential', 'describe', 'diff', 'difftool', 'fetch', 'for-each-ref', 'format-patch',
        'grep', 'hash-object', 'help', 'init', 'instaweb', 'log', 'merge', 'mergetool',
        'mv', 'name-rev', 'notes', 'pull', 'push', 'rebase', 'reflog', 'remote',
        'request-pull', 'rerere', 'reset', 'rev-list', 'rev-parse', 'revert', 'rm',
        'send-email', 'shortlog', 'show', 'stash', 'status', 'submodule', 'subtree',
        'switch', 'tag', 'whatchanged', 'worktree',
    ];

    private const CODE_BG = 0x1A1B26;

    private const TOKEN_COLORS = [
        'cmd'  => 0x7AA2F7,
        'sub'  => 0x9ECE6A,
        'flag' => 0x565F89,
        'str'  => 0x9ECE6A,
        'ph'   => 0xBB9AF7,
        'op'   => 0x89DDFF,
        'text' => 0xC0CAF5,
    ];

    private static function parseMd(string $md): array
    {
        $sections = [];
        $codeBuf = null;
        $idx = -1;

        foreach (explode("\n", $md) as $line) {
            if ($codeBuf !== null) {
                if (preg_match('/^\s*```/', $line)) {
                    $sections[$idx]['blocks'][] = ['code', $codeBuf];
                    $codeBuf = null;
                } else {
                    $codeBuf[] = $line;
                }
                continue;
            }
            if (preg_match('/^\s*```/', $line)) {
                $codeBuf = [];
                continue;
            }
            if (preg_match('/^##\s+(.+)$/', $line, $m)) {
                $sections[] = ['header' => trim($m[1]), 'blocks' => []];
                $idx = count($sections) - 1;
                continue;
            }
            if (preg_match('/^#{3,}\s+(.+)$/', $line, $m)) {
                if ($idx >= 0) {
                    $sections[$idx]['blocks'][] = ['desc', trim($m[1])];
                }
                continue;
            }
            $trimmed = trim($line);
            if ($trimmed !== '' && $idx >= 0) {
                $clean = preg_replace('/[*_`]/', '', $trimmed);
                $sections[$idx]['blocks'][] = ['desc', $clean];
            }
        }

        return $sections;
    }

    private static function tokenize(string $line): array
    {
        $tokens = [];
        $len = strlen($line);
        $i = 0;

        while ($i < $len) {
            if ($line[$i] === ' ' || $line[$i] === "\t") {
                $start = $i;
                while ($i < $len && ($line[$i] === ' ' || $line[$i] === "\t")) { $i++; }
                $tokens[] = [substr($line, $start, $i - $start), 'text'];
                continue;
            }
            if ($line[$i] === '"' || $line[$i] === "'") {
                $quote = $line[$i];
                $start = $i;
                $i++;
                while ($i < $len && $line[$i] !== $quote) {
                    if ($line[$i] === '\\') { $i++; }
                    $i++;
                }
                if ($i < $len) { $i++; }
                $tokens[] = [substr($line, $start, $i - $start), 'str'];
                continue;
            }
            if ($line[$i] === '[') {
                $start = $i;
                $i++;
                while ($i < $len && $line[$i] !== ']') { $i++; }
                if ($i < $len) { $i++; }
                $tokens[] = [substr($line, $start, $i - $start), 'ph'];
                continue;
            }
            if ($line[$i] === '-' && $i + 1 < $len && ($line[$i + 1] === '-' || ctype_alnum($line[$i + 1]))) {
                $start = $i;
                $i++;
                if ($line[$i] === '-') { $i++; }
                while ($i < $len && (ctype_alnum($line[$i]) || $line[$i] === '-' || $line[$i] === '_')) { $i++; }
                $tokens[] = [substr($line, $start, $i - $start), 'flag'];
                continue;
            }
            if (str_contains('|&;>', $line[$i])) {
                $start = $i;
                $ch = $line[$i];
                $i++;
                if ($i < $len && $line[$i] === $ch && ($ch === '|' || $ch === '&')) { $i++; }
                $tokens[] = [substr($line, $start, $i - $start), 'op'];
                continue;
            }
            if (ctype_alnum($line[$i]) || $line[$i] === '_' || $line[$i] === '/' || $line[$i] === '.' || $line[$i] === '~') {
                $start = $i;
                while ($i < $len && !preg_match('/[\s"\'\[\]|&;>]/', $line[$i])) { $i++; }
                $word = substr($line, $start, $i - $start);
                $bare = ltrim($word, './~');
                $type = match (true) {
                    $bare === 'git' => 'cmd',
                    in_array($bare, self::SUBCOMMANDS, true) => 'sub',
                    default => 'text',
                };
                $tokens[] = [$word, $type];
                continue;
            }
            $tokens[] = [$line[$i], 'text'];
            $i++;
        }

        return $tokens;
    }

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;

        $mdPath = dirname(__DIR__, 3) . '/assets/md/git-memo.en.md';
        $md = @file_get_contents($mdPath) ?: '';
        $sections = self::parseMd($md);

        $children = [];
        $copyIdx = 0;

        foreach ($sections as $sec) {
            $children[] = LayoutNode::leaf(
                null,
                new LabelSpec($sec['header'], size: 16.0, opacity: 0.85),
                width: $w,
                height: 30.0
            );

            $codeLines = [];
            foreach ($sec['blocks'] as $block) {
                [$type, $content] = $block;

                if ($type === 'desc') {
                    self::flushCode($children, $codeLines, $surface, $key, $w, $copyIdx);
                    $children[] = LayoutNode::leaf(
                        null,
                        new LabelSpec($content, size: 13.0, opacity: 1.0),
                        width: $w,
                        height: 20.0
                    );
                } else {
                    foreach ($content as $line) {
                        $codeLines[] = $line;
                    }
                }
            }
            self::flushCode($children, $codeLines, $surface, $key, $w, $copyIdx);
        }

        $totalH = 20.0;
        foreach ($children as $child) {
            $h = $child->style->height ?? 22.0;
            $totalH += $h + 4.0;
        }

        $sv = new ScrollViewControl(
            "p:{$key}",
            $children,
            width: $width,
            height: $height,
            gap: 4.0,
            padding: 18.0,
            contentHeight: max($totalH, $height),
        );
        $sv->bind($surface);
        return $sv->root();
    }

    private static function flushCode(array &$children, array &$lines, Surface $surface, string $key, float $w, int &$idx): void
    {
        if ($lines === []) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $isComment = str_starts_with($trimmed, '#');

            if ($isComment) {
                // Comment: CanvasSpec with dark bg
                $bg = self::CODE_BG;
                $commentColor = 0x565F89;
                $text = '  ' . $trimmed;
                $canvas = new CanvasSpec(function (DrawContext $ctx, float $cw, float $ch) use ($bg, $commentColor, $text): void {
                    $ctx->fillRect(0, 0, $cw, $ch, Color::rgb($bg));
                    $font = new FontDescriptor('Menlo', 12.0);
                    $ctx->drawString($text, $font, Color::rgb($commentColor), 12, ($ch - 12) / 2);
                }, background: (float) $bg);
                $children[] = LayoutNode::leaf(null, $canvas, width: $w, height: 24.0);
            } else {
                // Command: CanvasSpec (syntax highlighting) + Copy button in a row
                $tokens = self::tokenize($trimmed);
                $tokRef = $tokens;
                $bg = self::CODE_BG;

                $canvas = new CanvasSpec(function (DrawContext $ctx, float $cw, float $ch) use ($bg, $tokRef): void {
                    $ctx->fillRect(0, 0, $cw, $ch, Color::rgb($bg));
                    $x = 12.0;
                    $font = new FontDescriptor('Menlo', 13.0);
                    $y = ($ch - 13) / 2;
                    foreach ($tokRef as [$text, $type]) {
                        $colorHex = GitMemoPanel::TOKEN_COLORS[$type] ?? GitMemoPanel::TOKEN_COLORS['text'];
                        $ctx->drawString($text, $font, Color::rgb($colorHex), $x, $y);
                        $x += strlen($text) * 7.8;
                    }
                }, background: (float) $bg);

                $codeW = $w - 44.0;
                $codeNode = LayoutNode::leaf(null, $canvas, width: $codeW, height: 30.0);

                $copyBtn = Ui::button("{$key}:gm{$idx}", "Copy", 'outline', 36.0, 20.0);

                $cmdText = $trimmed;
                $copyId = "{$key}:gm{$idx}";
                $surface->onClick($copyId, function () use ($cmdText) {
                    Backend::copyText($cmdText);
                });

                $row = Ui::row([$codeNode, $copyBtn], gap: 4.0, height: 30.0);
                $children[] = $row;
                $idx++;
            }
        }

        $lines = [];
    }
}
