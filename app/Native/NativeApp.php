<?php

declare(strict_types=1);

namespace App\Native;

use App\Native\Panels\Base64Panel;
use App\Native\Panels\BomCleanPanel;
use App\Native\Panels\CapturerPanel;
use App\Native\Panels\ChmodPanel;
use App\Native\Panels\CodeLibraryPanel;
use App\Native\Panels\CodePlayPanel;
use App\Native\Panels\PhpObfuscatorPanel;
use App\Native\Panels\CronParserPanel;
use App\Native\Panels\DiffPanel;
use App\Native\Panels\EncryptPanel;
use App\Native\Panels\EscapeHtmlPanel;
use App\Native\Panels\FileInfoPanel;
use App\Native\Panels\ImageCompressPanel;
use App\Native\Panels\GitMemoPanel;
use App\Native\Panels\HashPanel;
use App\Native\Panels\HttpStatusPanel;
use App\Native\Panels\JwtPanel;
use App\Native\Panels\JsonPanel;
use App\Native\Panels\KeycodeInfoPanel;
use App\Native\Panels\MarkdownPanel;
use App\Native\Panels\MimeTypesPanel;
use App\Native\Panels\PortKillPanel;
use App\Native\Panels\ProcessKillPanel;
use App\Native\Panels\QrCodePanel;
use App\Native\Panels\RegexCheatsheetPanel;
use App\Native\Panels\RegexTesterPanel;
use App\Native\Panels\RequestTimePanel;
use App\Native\Panels\RsaKeyPanel;
use App\Native\Panels\SiteSuckerPanel;
use App\Native\Panels\SslMakePanel;
use App\Native\Panels\SystemEnvPanel;
use App\Native\Panels\TimestampPanel;
use App\Native\Panels\TokenGeneratorPanel;
use App\Native\Panels\UrlPanel;
use App\Native\Panels\UrlParsePanel;
use App\Native\Panels\WifiQrPanel;
use App\Native\Panels\WsSsePanel;
use App\Native\Panels\HomePanel;
use Libui\App;
use Libui\Ffi;
use Libui\Window;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ListRowSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Rendering\DesignTokens;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

/**
 * Self-drawn (Surface) native app shell with full UX: search, favorites,
 * i18n, home page, collapsible sidebar.
 */
final class NativeApp
{
    private const SIDEBAR_W = 240.0;
    private const WIN_W = 1100.0;
    private const WIN_H = 760.0;
    private const TOPBAR_H = 44.0;
    private const CONTENT_PADDING = 20.0;

    private Catalog $catalog;
    private Surface $surface;
    private LayoutNode $contentCol;
    private LayoutNode $mainRoot;
    private LayoutNode $toastNode;
    private LayoutNode $sidebarMain;
    private ScrollViewControl $sidebarScroll;
    private LayoutNode $searchNode;
    private LayoutNode $langBtnNode;
    private float $contentW;
    private float $contentH;
    private float $paddedContentW;
    private float $paddedContentH;
    private bool $dark = false;
    private bool $chinese = true;
    private bool $sidebarCollapsed = false;
    private bool $automation;
    private ?string $currentTool = null;
    private string $searchQuery = '';
    /** @var array<string, LayoutNode> */
    private array $navNodes = [];

    /** @var array<string,Panel> */
    private array $panels;
    private HomePanel $homePanel;

    public function __construct(bool $automation = false)
    {
        $this->automation = $automation;
        $this->catalog = Catalog::getInstance();
        Catalog::setLocale('zh');
        $this->homePanel = new HomePanel();
        $this->panels = [
            'json' => new JsonPanel(),
            'b64' => new Base64Panel(),
            'hash' => new HashPanel(),
            'ts' => new TimestampPanel(),
            'jwt' => new JwtPanel(),
            'chmod' => new ChmodPanel(),
            'url' => new UrlPanel(),
            'diff' => new DiffPanel(),
            'file' => new FileInfoPanel(),
            'md' => new MarkdownPanel(),
            'escape' => new EscapeHtmlPanel(),
            'urlparse' => new UrlParsePanel(),
            'regex' => new RegexTesterPanel(),
            'token' => new TokenGeneratorPanel(),
            'sysenv' => new SystemEnvPanel(),
            'http' => new HttpStatusPanel(),
            'mime' => new MimeTypesPanel(),
            'encrypt' => new EncryptPanel(),
            'rsa' => new RsaKeyPanel(),
            'qr' => new QrCodePanel(),
            'sucker' => new SiteSuckerPanel(),
            'bom' => new BomCleanPanel(),
            'cron' => new CronParserPanel(),
            'regexref' => new RegexCheatsheetPanel(),
            'git' => new GitMemoPanel(),
            'wssse' => new WsSsePanel(),
            'keycode' => new KeycodeInfoPanel(),
            'wifiqr' => new WifiQrPanel(),
            'sslmk' => new SslMakePanel(),
            'timing' => new RequestTimePanel(),
            'codeplay' => new CodePlayPanel(),
            'obf' => new PhpObfuscatorPanel(),
            'cap' => new CapturerPanel(),
            'codelib' => new CodeLibraryPanel(),
            'portkill' => new PortKillPanel(),
            'prockill' => new ProcessKillPanel(),
            'imgcompress' => new ImageCompressPanel(),
        ];
        $this->panels['imgcompress']->onRebuild = function (): void {
            if ($this->currentTool === 'ImageCompress') {
                $this->openTool('ImageCompress');
            }
        };
        $this->panels['sysenv']->onRebuild = function (): void {
            if ($this->currentTool === 'SystemEnv') {
                $this->openTool('SystemEnv');
            }
        };
        $this->panels['codelib']->onRebuild = function (): void {
            if ($this->currentTool === 'CodeLibrary') {
                $this->openTool('CodeLibrary');
            }
        };
    }

    private function t(string $toolId): string
    {
        return $this->chinese ? $this->catalog->nameOf($toolId) : $this->catalog->nameEnOf($toolId);
    }

    private function tLabel(string $key): string
    {
        return match ($key) {
            'search' => $this->chinese ? '搜索工具...' : 'Search tools...',
            'tools' => $this->chinese ? '工具箱' : 'TOOLS',
            'lang' => $this->chinese ? 'EN' : '中',
            'no_results' => $this->chinese ? '无匹配工具' : 'No matching tools',
            default => $key,
        };
    }

    public function run(): void
    {
        $this->contentW = self::WIN_W - self::SIDEBAR_W;
        $this->contentH = self::WIN_H - self::TOPBAR_H;
        $this->paddedContentW = $this->contentW - 2 * self::CONTENT_PADDING;
        $this->paddedContentH = $this->contentH - 2 * self::CONTENT_PADDING;

        // Content column — swapped per tool
        $this->contentCol = LayoutNode::column(
            padding: self::CONTENT_PADDING,
            id: 'content-root',
            align: LayoutStyle::ALIGN_STRETCH,
        );
        $this->contentCol->style->grow = 1.0;

        // Topbar can be built before Surface exists (no handlers that touch surface)
        $topbar = $this->buildTopbar();
        $mainRow = LayoutNode::row(id: 'main', align: LayoutStyle::ALIGN_STRETCH);
        $mainRow->style->grow = 1.0;
        $mainRow->child(LayoutNode::leaf(null, null, width: self::SIDEBAR_W)); // placeholder for sidebar
        $mainRow->child($this->contentCol);

        $this->mainRoot = LayoutNode::column(id: 'main-root', align: LayoutStyle::ALIGN_STRETCH);
        // Persistent toast label — always present, content empty by default;
        // toast() updates its label for N ms then clears the content.
        $this->toastNode = LayoutNode::leaf(
            '_toast',
            new LabelSpec('', size: 13, opacity: 0.95),
            width: 380,
            height: 28,
        );
        $this->toastNode->style->grow = 0;
        $this->mainRoot->child($this->toastNode);
        $this->mainRoot->child($topbar);
        $this->mainRoot->child($mainRow);

        // Single Surface — MUST exist before buildSidebar (which needs $this->surface for handlers)
        $this->surface = new Surface($this->mainRoot);

        $this->sidebarScroll = $this->buildSidebar();
        $this->sidebarMain = LayoutNode::leaf(null, null, width: self::SIDEBAR_W);
        $this->sidebarMain->style->grow = 0.0;
        $this->sidebarMain->child($this->sidebarScroll->root());
        $mainRow->children[0] = $this->sidebarMain; // replace placeholder
        $this->sidebarScroll->bind($this->surface);

        // Wire up all handlers
        $this->rebuildNavHandlers();

        $this->surface->onClick('topbar:theme', fn() => $this->toggleTheme());
        $this->surface->onClick('topbar:lang', fn() => $this->toggleLang());
        $this->surface->onClick('topbar:home', fn() => $this->goHome());

        // Search onText: track query string char by char to avoid TextFieldSpec readonly.
        $this->surface->onText('topbar:search', function (string $char, bool $backspace): void {
            if ($backspace) {
                $this->searchQuery = $this->searchQuery === '' ? '' : mb_substr($this->searchQuery, 0, -1);
            } else {
                $this->searchQuery .= $char;
            }
            // Update the search node spec so the drawn field shows the text
            $this->searchNode->spec = new TextFieldSpec(
                value: $this->searchQuery,
                placeholder: $this->tLabel('search'),
            );
            $this->onSearchChanged($this->searchQuery);
            $this->surface->redraw();
        });

        $this->homePanel->onToolClick = function (string $toolId): void {
            $this->openTool($toolId);
        };

        $this->homePanel->onToggleFavorite = function (string $toolId): void {
            $this->catalog->toggleFavorite($toolId);
            $this->goHome();
        };

        $this->homePanel->onReorderFavorite = function (string $toolId, int $direction): void {
            $this->catalog->reorderFavorite($toolId, $direction);
            $this->goHome();
        };

        $this->surface->onResize(fn(float $a, float $b) => $this->onAreaResize($a, $b));

        $window = new Window('FlyEnv Toolbox', (int)self::WIN_W, (int)self::WIN_H, false);
        $window->setMargined(true);
        $window->centered();
        $window->setChild($this->surface->root());

        // Let panels use the native file picker.
        WindowHolder::set($window);

        $this->goHome();

        $appLife = App::new()->window($window)->onShouldQuit(static fn(): bool => true);

        if ($this->automation) {
            $appLife->enableAutomation(
                port: 18765,
                stateProvider: fn(): array => [
                    'currentTool' => $this->currentTool,
                    'dark' => $this->dark,
                    'chinese' => $this->chinese,
                    'sidebarCollapsed' => $this->sidebarCollapsed,
                ],
                driveHandler: function (string $nodeId, array $payload): array {
                    $action = $payload['action'] ?? 'click';

                    if ($action === 'drag') {
                        $h = $this->surface->dragHandlerFor($nodeId);
                        if ($h === null) {
                            return ['ok' => false, 'error' => "no drag handler for {$nodeId}"];
                        }
                        $rect = $this->surface->screenRectOf($nodeId);
                        if ($rect === null) {
                            return ['ok' => false, 'error' => "no rect for {$nodeId}"];
                        }
                        [$sx, $sy, $sw, $sh] = $rect;
                        $from = $payload['from'] ?? null;
                        $to = $payload['to'] ?? null;
                        if (! is_array($from) || ! is_array($to) || count($from) < 2 || count($to) < 2) {
                            return ['ok' => false, 'error' => 'drag requires from:[x,y] and to:[x,y] in viewport coords'];
                        }
                        $rx0 = $from[0] - $sx; $ry0 = $from[1] - $sy;
                        $rx1 = $to[0] - $sx;   $ry1 = $to[1] - $sy;
                        $h($rx0, $ry0, $sw, $sh);   // press (gutter → page or grab thumb)
                        $h($rx1, $ry1, $sw, $sh);   // drag
                        $end = $this->surface->dragEndHandlerFor($nodeId);
                        if ($end !== null) {
                            $end();
                        }
                        $node = LayoutNode::find($this->surface->rootLayout(), $nodeId);

                        return ['ok' => true, 'nodeId' => $nodeId, 'scrollY' => $node?->scrollY ?? null,
                            'rect' => $rect, 'rel' => [$rx0, $ry0, $rx1, $ry1]];
                    }

                    if ($action === 'inspect') {
                        $node = LayoutNode::find($this->surface->rootLayout(), $nodeId);
                        if ($node === null) {
                            return ['ok' => false, 'error' => "no node {$nodeId}"];
                        }
                        $spec = $node->spec;
                        $val = null;
                        if ($spec !== null) {
                            $val = property_exists($spec, 'value') ? $spec->value
                                : (property_exists($spec, 'text') ? $spec->text : null);
                        }
                        return ['ok' => true, 'nodeId' => $nodeId, 'type' => $spec?->type(), 'value' => $val];
                    }

                    if ($action === 'rect') {
                        $node = LayoutNode::find($this->surface->rootLayout(), $nodeId);
                        if ($node === null) {
                            return ['ok' => false, 'error' => "no node {$nodeId}"]
                            ;
                        }
                        return ['ok' => true, 'nodeId' => $nodeId, 'rect' => [$node->x, $node->y, $node->w, $node->h]];
                    }

                    if ($action === 'tree') {
                        $node = $nodeId === 'root' || $nodeId === ''
                            ? $this->surface->rootLayout()
                            : LayoutNode::find($this->surface->rootLayout(), $nodeId);
                        if ($node === null) {
                            return ['ok' => false, 'error' => "no node {$nodeId}"];
                        }
                        $out = [];
                        $walk = function (LayoutNode $n) use (&$out, &$walk): void {
                            if ($n->id !== null) {
                                $out[] = [$n->id, round($n->x, 1), round($n->y, 1), round($n->w, 1), round($n->h, 1)];
                            }
                            foreach ($n->children as $c) {
                                $walk($c);
                            }
                        };
                        $walk($node);
                        return ['ok' => true, 'nodeId' => $nodeId, 'tree' => $out];
                    }

                    $handler = $this->surface->handlerFor($nodeId);
                    if ($handler !== null) {
                        $handler();
                        return ['ok' => true, 'nodeId' => $nodeId];
                    }
                    return ['ok' => false, 'error' => "no handler for {$nodeId}"];
                },
                mcp: true,
            );
        }

        $appLife->run();
    }

    private function buildTopbar(): LayoutNode
    {
        $home = LayoutNode::leaf('topbar:home', new ButtonSpec('🏠', 'soft'), width: 36.0, height: 30.0);

        $this->searchNode = LayoutNode::leaf(
            'topbar:search',
            new TextFieldSpec(value: '', placeholder: $this->tLabel('search')),
            width: 200.0,
            height: 30.0,
        );

        $spacer = LayoutNode::leaf(null, null, height: 1.0);
        $spacer->style->grow = 1.0;

        $this->langBtnNode = LayoutNode::leaf('topbar:lang', new ButtonSpec($this->tLabel('lang'), 'soft'), width: 42.0, height: 30.0);
        $theme = LayoutNode::leaf('topbar:theme', new ButtonSpec('🎨', 'soft'), width: 42.0, height: 30.0);

        return LayoutNode::row(
            padding: 4.0, gap: 4.0,
            align: LayoutStyle::ALIGN_CENTER,
            id: 'topbar',
            height: self::TOPBAR_H,
        )->child($home)->child($this->searchNode)->child($spacer)->child($this->langBtnNode)->child($theme);
    }

    /**
     * @param list<array{id:string,cat:string,name:string,nameEn:string,icon:string}> $tools
     */
    private function buildSidebarWith(array $tools): ScrollViewControl
    {
        $children = [];
        $sidebarW = self::SIDEBAR_W - 16;
        $children[] = LayoutNode::leaf(null, new LabelSpec($this->tLabel('tools'), size: 13, opacity: 0.6), width: $sidebarW, height: 22);
        $totalH = 22 + 8;

        $prevCat = '';
        $this->navNodes = [];
        foreach ($tools as $t) {
            if ($t['cat'] !== $prevCat) {
                $children[] = LayoutNode::leaf(null, new LabelSpec($t['cat'], size: 12, opacity: 0.55), width: $sidebarW, height: 20);
                $totalH += 24;
                $prevCat = $t['cat'];
            }
            $isFav = $this->catalog->isFavorite($t['id']);
            $line = "{$t['icon']}  " . $this->t($t['id']);
            $navNode = LayoutNode::leaf(
                "nav:{$t['id']}",
                new ListRowSpec(label: $line, radius: 6.0),
                width: $sidebarW,
                height: 30,
            );
            $children[] = $navNode;
            $this->navNodes[$t['id']] = $navNode;
            $totalH += 34;
        }
        if (empty($tools)) {
            $children[] = LayoutNode::leaf(null, new LabelSpec($this->tLabel('no_results'), size: 13, opacity: 0.5), width: $sidebarW, height: 30);
            $totalH += 34;
        }
        // ── Import/Export footer ────────────────────────────────────────
        $children[] = LayoutNode::leaf(null, null, height: 4);
        $totalH += 4;
        $expBtn = LayoutNode::leaf('sidebar:export', new ButtonSpec('📤 Export Favs', 'soft'), width: $sidebarW, height: 26);
        $this->surface->onClick('sidebar:export', fn() => $this->exportFavorites());
        $children[] = $expBtn;
        $totalH += 30;
        $impBtn = LayoutNode::leaf('sidebar:import', new ButtonSpec('📥 Import Favs', 'soft'), width: $sidebarW, height: 26);
        $this->surface->onClick('sidebar:import', fn() => $this->importFavorites());
        $children[] = $impBtn;
        $totalH += 30;
        $totalH += 16;

        $sv = new ScrollViewControl('sidebar', $children, width: self::SIDEBAR_W, height: self::WIN_H - self::TOPBAR_H, contentHeight: $totalH, gap: 2, padding: 4);
        return $sv;
    }

    private function buildSidebar(): ScrollViewControl
    {
        $allTools = $this->searchQuery === '' ? $this->catalog->tools() : $this->catalog->search($this->searchQuery);
        return $this->buildSidebarWith($allTools);
    }

    private function rebuildSidebar(): void
    {
        $newScroll = $this->buildSidebar();
        $newScroll->bind($this->surface);
        $this->sidebarMain->children = [$newScroll->root()];
        $this->sidebarScroll = $newScroll;
        $this->rebuildNavHandlers();
        $this->surface->refreshFocusables();
        $this->surface->redraw();
    }

    private function rebuildNavHandlers(): void
    {
        foreach ($this->catalog->tools() as $t) {
            $id = $t['id'];
            $this->surface->onClick("nav:{$id}", function () use ($id): void {
                $this->openTool($id);
            });
        }
    }

    private function goHome(): void
    {
        $this->currentTool = null;
        $this->rebuildSidebar();

        $node = $this->homePanel->build($this->surface, 'home', $this->paddedContentW, $this->paddedContentH);
        $this->contentCol->children = [$node];
        $this->surface->refreshFocusables();
        $this->surface->redraw();
    }

    private function onSearchChanged(string $query): void
    {
        $this->rebuildSidebar();
    }

    private function openTool(string $toolId): void
    {
        // Update sidebar selection
        if (isset($this->navNodes[$toolId]) && $this->navNodes[$toolId]->spec instanceof ListRowSpec) {
            $n = $this->navNodes[$toolId]->spec;
            $this->navNodes[$toolId]->spec = new ListRowSpec(label: $n->label, selected: true, hovered: $n->hovered, radius: 6.0);
        }
        if ($this->currentTool !== null && $this->currentTool !== $toolId && isset($this->navNodes[$this->currentTool]) && $this->navNodes[$this->currentTool]->spec instanceof ListRowSpec) {
            $n = $this->navNodes[$this->currentTool]->spec;
            $this->navNodes[$this->currentTool]->spec = new ListRowSpec(label: $n->label, selected: false, hovered: $n->hovered, radius: 6.0);
        }
        $this->currentTool = $toolId;
        $this->toast($this->t($toolId), 'info', 1000);

        $key = $this->catalog->panelKey($toolId);
        $panel = $this->panels[$key] ?? new PlaceholderPanel();
        $node = $panel->build($this->surface, $key, $this->paddedContentW, $this->paddedContentH);

        $this->contentCol->children = [$node];
        $this->surface->refreshFocusables();
        $this->surface->redraw();
    }

    private function toggleTheme(): void
    {
        $this->dark = !$this->dark;
        if ($this->dark) {
            $this->surface->setTheme(self::darkOverrides());
            return;
        }
        $this->surface->tokens = new DesignTokens();
        $this->surface->redraw();
    }

    private function toggleLang(): void
    {
        $this->chinese = !$this->chinese;
        Catalog::setLocale($this->chinese ? 'zh' : 'en');
        // Update search placeholder
        $this->searchNode->spec = new TextFieldSpec(
            value: $this->searchQuery,
            placeholder: $this->tLabel('search'),
        );
        // Update lang button
        $this->langBtnNode->spec = new ButtonSpec($this->tLabel('lang'), 'soft');
        // Rebuild sidebar with new locale
        $this->rebuildSidebar();
        // Rebuild content
        if ($this->currentTool !== null) {
            $this->openTool($this->currentTool);
        } else {
            $this->goHome();
        }
        $this->surface->redraw();
    }

    private function toast(string $message, string $type = 'info', int $durationMs = 2000): void
    {
        // The persistent toast node is always present in mainRoot; we just
        // update its label and clear it after the duration. No insert/remove,
        // so the layout height never changes.
        $this->toastNode->spec = new LabelSpec($message, size: 13, opacity: 0.95);
        $this->surface->redraw();

        Ffi::timer($durationMs, function (): bool {
            // Clear the toast content back to whitespace.
            $this->toastNode->spec = new LabelSpec(' ', size: 13, opacity: 0.0);
            $this->surface->redraw();
            return false;
        });
    }

    private function exportFavorites(): void
    {
        $favs = $this->catalog->favorites();
        $data = json_encode($favs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $path = sys_get_temp_dir() . '/flyenv_favorites_export.json';
        $bytes = file_put_contents($path, $data);
        $this->toast('Favorites exported: ' . count($favs) . ' items, ' . ($bytes ?: 0) . ' bytes', 'success', 2500);
    }

    private function importFavorites(): void
    {
        $win = WindowHolder::get();
        if ($win === null) return;
        $path = \Yangweijie\Ui2\Pickers\FilePickerDialog::pick($win);
        if ($path === null || !is_file($path)) return;
        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            $this->toast('Invalid favorites file', 'error');
            return;
        }
        foreach ($data as $toolId => $val) {
            if (is_bool($val) && $val) {
                $this->catalog->toggleFavorite($toolId);
            }
        }
        if ($this->currentTool === null) {
            $this->goHome();
        } else {
            $this->rebuildSidebar();
        }
        $this->toast('Favorites imported', 'success');
    }

    private function onAreaResize(float $areaW, float $areaH): void
    {
        $this->paddedContentW = max(100, $areaW - self::SIDEBAR_W - 2 * self::CONTENT_PADDING);
        $this->paddedContentH = max(100, $areaH - self::TOPBAR_H - 2 * self::CONTENT_PADDING);
        $this->sidebarScroll->root()->style->height = $areaH - self::TOPBAR_H;
        if ($this->currentTool !== null) {
            $this->openTool($this->currentTool);
        } else {
            // Rebuild the home page so its cards adapt to the new width.
            $this->goHome();
        }
    }

    /** @return array<string,mixed> */
    private static function darkOverrides(): array
    {
        return [
            'color' => [
                'primary' => [0.30, 0.65, 1.0, 1.0],
                'track' => [0.22, 0.22, 0.24, 1.0],
                'onSurface' => [0.92, 0.92, 0.92, 1.0],
                'surface' => [0.16, 0.16, 0.18, 1.0],
                'selection' => [0.30, 0.70, 1.0, 0.20],
                'scrim' => [0.0, 0.0, 0.0, 0.55],
            ],
        ];
    }
}
