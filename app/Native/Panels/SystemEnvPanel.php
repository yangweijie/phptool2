<?php
declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Catalog;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * System Environment — 1:1 with the legacy webview tool:
 * lists the user's shell env config files (.bashrc/.zshrc/…), lets you
 * open the file in the OS file manager or edit + save it back.
 */
final class SystemEnvPanel implements Panel
{
    /** Set by NativeApp; called when a view change requires a full rebuild. */
    public \Closure $onRebuild;

    private const CANDIDATES = [
        '.bashrc', '.bash_profile', '.bash_logout',
        '.profile',
        '.zshrc', '.zsh_profile', '.zshenv', '.zlogin',
        '.config/fish/config.fish',
    ];

    private static string $mode = 'list';   // 'list' | 'edit'
    private static string $editFile = '';
    private static string $status = '';

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $zh = Catalog::chinese();

        return self::$mode === 'edit'
            ? $this->buildEditor($surface, $key, $w, $height, $zh)
            : $this->buildList($surface, $key, $w, $height, $zh);
    }

    // ── List view ─────────────────────────────────────────────────────
    private function buildList(Surface $surface, string $key, float $w, float $height, bool $zh): LayoutNode
    {
        $files = self::envFiles();

        $children = [];
        $children[] = Ui::title($zh ? '系统环境' : 'System Environment', $w);
        $children[] = LayoutNode::leaf(null, new LabelSpec(
            $zh ? '列出 Shell 环境配置文件，可编辑或在文件管理器中打开。'
                : 'List shell env config files — edit or reveal in file manager.',
            size: 12, opacity: 0.7), width: $w, height: 22);

        if (empty($files)) {
            $children[] = LayoutNode::leaf(null, new LabelSpec(
                $zh ? '未找到环境配置文件' : 'No env config files found',
                size: 13, opacity: 0.5), width: $w, height: 30);
        } else {
            foreach ($files as $f) {
                $id = md5($f);
                $row = LayoutNode::row(gap: 8, height: 36, align: LayoutStyle::ALIGN_CENTER);
                $row->child(LayoutNode::leaf(null, new LabelSpec(basename($f), size: 13), width: $w - 200, height: 22));
                $row->child(LayoutNode::leaf("{$key}:open:{$id}", new ButtonSpec($zh ? '打开' : 'Open', 'soft'), width: 70, height: 30));
                $row->child(LayoutNode::leaf("{$key}:edit:{$id}", new ButtonSpec($zh ? '编辑' : 'Edit', 'filled'), width: 70, height: 30));
                $children[] = $row;

                $surface->onClick("{$key}:open:{$id}", function () use ($f): void {
                    self::openInFolder($f);
                });
                $surface->onClick("{$key}:edit:{$id}", function () use ($f, $surface, $key): void {
                    self::$editFile = $f;
                    self::$mode = 'edit';
                    self::$status = '';
                    $this->rebuild($surface, $key);
                });
            }
        }

        $contentH = 80 + count($files) * 44 + 40;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $w, height: $height, gap: 12, padding: 24, contentHeight: $contentH);
        $sv->bind($surface);
        return $sv->root();
    }

    // ── Editor view ───────────────────────────────────────────────────
    private function buildEditor(Surface $surface, string $key, float $w, float $height, bool $zh): LayoutNode
    {
        $file = self::$editFile;
        $children = [];
        $children[] = Ui::title(basename($file), $w);

        if (self::$status !== '') {
            $children[] = LayoutNode::leaf(null, new LabelSpec(self::$status, size: 12, opacity: 0.8), width: $w, height: 20);
        }

        $toolbar = LayoutNode::row(gap: 8, height: 34, align: LayoutStyle::ALIGN_CENTER);
        $toolbar->child(LayoutNode::leaf("{$key}:save", new ButtonSpec($zh ? '保存' : 'Save', 'filled'), width: 90, height: 32));
        $toolbar->child(LayoutNode::leaf("{$key}:close", new ButtonSpec($zh ? '关闭' : 'Close', 'soft'), width: 90, height: 32));
        $children[] = $toolbar;

        $editH = max(200, $height - 200);
        $content = '';
        try {
            $content = self::envRead($file);
        } catch (\Throwable $e) {
            self::$status = ($zh ? '读取失败: ' : 'Read error: ') . $e->getMessage();
        }

        $ta = new TextAreaControl("{$key}:editor", $content, width: $w, height: $editH);
        $ta->bind($surface);
        $children[] = $ta->root();

        $surface->onClick("{$key}:save", function () use ($ta, $file, $surface, $key, $zh): void {
            try {
                self::envSave($file, $ta->getValue());
                self::$status = $zh ? '已保存' : 'Saved';
            } catch (\Throwable $e) {
                self::$status = ($zh ? '保存失败: ' : 'Save failed: ') . $e->getMessage();
            }
            $this->rebuild($surface, $key);
        });
        $surface->onClick("{$key}:close", function () use ($surface, $key): void {
            self::$mode = 'list';
            self::$editFile = '';
            self::$status = '';
            $this->rebuild($surface, $key);
        });

        $contentH = 28 + (self::$status !== '' ? 20 : 0) + 34 + $editH + 60;
        $sv = new ScrollViewControl("p:{$key}", $children, width: $w, height: $height, gap: 12, padding: 24, contentHeight: $contentH);
        $sv->bind($surface);
        return $sv->root();
    }

    private function rebuild(Surface $surface, string $key): void
    {
        if (isset($this->onRebuild)) {
            ($this->onRebuild)();
        }
    }

    // ── Backend (shell env files) ─────────────────────────────────────
    /** @return list<string> existing, readable shell config file paths */
    private static function envFiles(): array
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '/tmp';
        $out = [];
        foreach (self::CANDIDATES as $rel) {
            $p = $home . DIRECTORY_SEPARATOR . $rel;
            if (is_file($p) && is_readable($p)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    private static function envRead(string $file): string
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException('file not readable');
        }
        $c = @file_get_contents($file);
        if ($c === false) {
            throw new \RuntimeException('read failed');
        }
        return $c;
    }

    private static function envSave(string $file, string $content): void
    {
        if (is_file($file) && !is_writable($file)) {
            throw new \RuntimeException('file not writable');
        }
        if (!is_file($file) && !is_writable(dirname($file))) {
            throw new \RuntimeException('directory not writable');
        }
        $r = @file_put_contents($file, $content);
        if ($r === false) {
            throw new \RuntimeException('write failed');
        }
    }

    /** Best-effort: reveal the file in the OS file manager. */
    private static function openInFolder(string $file): void
    {
        $dir = dirname($file);
        $cmd = match (PHP_OS_FAMILY) {
            'Darwin'  => 'open -R ' . escapeshellarg($file),
            'Windows' => 'explorer /select,' . escapeshellarg($file),
            default   => 'xdg-open ' . escapeshellarg($dir),
        };
        @shell_exec($cmd);
    }
}
