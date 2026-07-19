<?php declare(strict_types=1);
namespace App\Native\Panels;

use App\Native\Backend;
use App\Native\Panel;
use App\Native\Ui;
use App\Native\WindowHolder;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Pickers\FilePickerDialog;
use Yangweijie\Ui2\Widgets\ComboboxControl;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TabControl;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * Multi-language Code Playground — 1:1 match with original FlyEnv webview.
 *
 * Layout (matching original):
 *  Row 1: title "代码演练场 ⭐"
 *  Row 2: tab bar (TabControl, closable ×, no +)
 *  Row 3: toolbar row [lang ▼] [bin ▼] [📂] [▶] ... [Format:] [fmt ▼] [💾] ... [+]
 *  Row 4: split pane — left textarea | divider | right textarea
 */
final class CodePlayPanel implements Panel
{
    // ── Per-tab state (persists across rebuilds) ─────────────────────────────

    /** @var list<array{name:string,input:string,output:string,rawOutput:string,lang:string,format:string,binary:string}> */
    private static array $tabs = [
        ['name' => 'tab-1', 'input' => "<?php\necho \"Hello, World!\\n\";\n\$data = ['name' => 'FlyEnv', 'version' => 2.0];\nprint_r(\$data);", 'output' => '', 'rawOutput' => '', 'lang' => 'PHP', 'format' => 'Raw', 'binary' => ''],
    ];
    private static int $activeTab = 0;
    private static float $splitRatio = 0.55;

    /** @var list<string> */
    private static array $langs = ['PHP', 'Python', 'Node.js', 'Go', 'Rust', 'Java'];

    /** @var list<string> 17 output format transforms */
    private static array $formats = [
        'Raw', 'JSON', 'JSON Minify', 'PHP Array', 'JavaScript', 'TypeScript',
        'YAML', 'XML', 'PList', 'TOML', 'Go Struct', 'Go Bson',
        'Rust Serde', 'Java', 'Kotlin', 'MySQL', 'JSDoc',
    ];

    /** @var array<string, string> Default code samples per language */
    private static array $defaultCode = [
        'PHP'     => "<?php\necho \"Hello, World!\\n\";\n\$data = ['name' => 'FlyEnv', 'version' => 2.0];\nprint_r(\$data);",
        'Python'  => "print('Hello, World!')\nimport sys\nprint(f'Python {sys.version}')",
        'Node.js' => "console.log('Hello, World!');\nconsole.log(process.version);",
        'Go'      => 'fmt.Println("Hello, World!");' . "\n" . 'var x = 42' . "\n" . 'fmt.Printf("x = %d\\n", x)',
        'Rust'    => 'fn main() {' . "\n" . '    println!("Hello, World!");' . "\n" . '    let x = 42;' . "\n" . '    println!("x = {}", x);' . "\n" . '}',
        'Java'    => 'public class Main {' . "\n" . '    public static void main(String[] args) {' . "\n" . '        System.out.println("Hello, World!");' . "\n" . '    }' . "\n" . '}',
    ];

    /** @var array<string, list<string>> Per-language binary overrides */
    private static array $langBinaries = [
        'PHP'     => ['—', 'php'],
        'Python'  => ['—', 'python3'],
        'Node.js' => ['—', 'node'],
        'Go'      => ['—'],
        'Rust'    => ['—'],
        'Java'    => ['—'],
    ];

    // ── Stashed refs for cross-turn access ──────────────────────────────────
    private static ?TextAreaControl $inRef = null;
    private static ?TextAreaControl $outRef = null;
    private static ?TabControl $tabControlRef = null;
    private static ?ComboboxControl $langComboRef = null;
    private static ?ComboboxControl $binComboRef = null;
    private static ?LayoutNode $toolbarRef = null;

    // ═══════════════════════════════════════════════════════════════════════
    //  build()
    // ═══════════════════════════════════════════════════════════════════════

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w        = $width - 48;
        $svPad    = 18.0 * 2;  // top + bottom padding
        $titleH   = 28.0;
        $tabbarH  = 28.0;
        $toolbarH = 30.0;
        $gaps     = 6.0 * 3;   // title→tabBar, tabBar→toolbar, toolbar→splitRow
        $paneH    = max(150, $height - $svPad - $titleH - $tabbarH - $toolbarH - $gaps);

        // ── Split geometry ──────────────────────────────────────────────
        $halfW  = max(100, ($w * self::$splitRatio) - 4);
        $otherW = max(100, $w - $halfW - 12);

        $tab    = self::currentTab();
        $lang   = $tab['lang'];
        $fmt    = $tab['format'];
        $binary = $tab['binary'];

        // ═══════════════════════════════════════════════════════════════
        //  Tab bar — TabControl (closable only, + is in toolbar)
        // ═══════════════════════════════════════════════════════════════

        $tcTabs = [];
        foreach (self::$tabs as $t) {
            $tcTabs[] = ['id' => $t['name'], 'label' => $t['name'], 'content' => LayoutNode::leaf(null, null)];
        }
        $tabControl = new TabControl("{$key}:tabs", $tcTabs, self::$activeTab,
            tabHeight: $tabbarH, panelHeight: 0, closable: true, addable: true);
        $tabControl->bind($surface)
            ->onChange(fn (int $i) => $this->switchTab($i))
            ->onCloseTab(fn (int $i) => $this->closeTab($surface, $i))
            ->onAddTab(function () use ($surface): void { $this->addTab($surface); });
        self::$tabControlRef = $tabControl;

        $tabBar = $tabControl->root();

        // Tab bar spacer — fill remaining width
        $tabSpacer = LayoutNode::leaf(null, null, height: 1.0);
        $tabSpacer->style->grow = 1.0;
        $tabBar->child($tabSpacer);

        // ═══════════════════════════════════════════════════════════════
        //  Toolbar — single row spanning full width
        // ═══════════════════════════════════════════════════════════════

        // Binary override combobox — width scales with window so field shows more chars
        // Toolbar fixed elements: lang(110) + open(26) + run(26) + formatLabel(48) + fmt(130) + save(26) = 366 + gaps
        // Leave at least 40 px for midSpacer so toolbar doesn't look cramped
        $binW = min(max(100.0, $w * 0.25), 450.0);
        $binCombo = new ComboboxControl("{$key}:bin", ['—'],
            value: '—', width: $binW, height: $toolbarH, readonly: true);
        $binCombo->bind($surface)->onChange(function (string $v) use ($surface, $key): void {
            self::$tabs[self::$activeTab]['binary'] = $v === '—' ? '' : $v;
            $surface->redraw();
        });
        self::$binComboRef = $binCombo;

        // Language combobox
        $langCombo = new ComboboxControl("{$key}:lang", self::$langs,
            value: $lang, width: 110, height: $toolbarH, readonly: true);
        $langCombo->bind($surface)->onChange(function (string $v) use ($surface, $key, $binCombo): void {
            self::$tabs[self::$activeTab]['lang'] = $v;
            $scanned = Backend::scanBinaries($v);
            $newBinOpts = array_merge(["—"], $scanned);
            $binCombo->setOptions($newBinOpts);
            $binCombo->setMinPanelWidth(self::calculateBinPanelWidth($newBinOpts));
            $binCombo->setValue($newBinOpts[0], true);
            self::$tabs[self::$activeTab]['binary'] = '';
            $surface->redraw();
        });
        self::$langComboRef = $langCombo;

        // Populate binaries for the current language (and on every language change)
        $scanned = Backend::scanBinaries($lang);
        $binOpts = array_merge(["—"], $scanned);
        $binCombo->setOptions($binOpts);
        $binCombo->setMinPanelWidth(self::calculateBinPanelWidth($binOpts));
        $binCombo->setValue($binary ?: '—', true);

        // Format combobox
        $fmtCombo = new ComboboxControl("{$key}:fmt", self::$formats,
            value: $fmt, width: 130, height: $toolbarH);
        $fmtCombo->bind($surface)->onChange(function (string $v) use ($surface, $key): void {
            self::$tabs[self::$activeTab]['format'] = $v;
            $raw = self::$tabs[self::$activeTab]['rawOutput'];
            $out = self::$outRef;
            if ($out !== null && $raw !== '') {
                $out->setValue(Backend::codeTransform($raw, $v));
            }
            $surface->redraw();
        });
        // Spacer between run and Format (grows to fill space)
        $midSpacer = LayoutNode::leaf(null, null, height: 1.0);
        $midSpacer->style->grow = 1.0;
        // Single toolbar row matching original layout
        $toolbar = Ui::row([
            $langCombo->root(),
            $binCombo->root(),
            Ui::button("{$key}:open", '📂', 'soft', 26, 26),
            Ui::button("{$key}:run",  '▶',  'filled', 26, 26),
            $midSpacer,
            Ui::label('Format:', 48, 12, 26),
            $fmtCombo->root(),
            Ui::button("{$key}:save", '💾', 'soft', 26, 26),
        ], gap: 4, height: $toolbarH);
        self::$toolbarRef = $toolbar;

        // ═══════════════════════════════════════════════════════════════
        //  Left pane — input
        // ═══════════════════════════════════════════════════════════════

        $in = new TextAreaControl("{$key}:in", '', width: $halfW - 8, height: $paneH);
        $in->bind($surface);
        $in->setValue($tab['input']);
        $in->onChange(function (string $v): void { self::$tabs[self::$activeTab]['input'] = $v; });

        // ═══════════════════════════════════════════════════════════════
        //  Divider
        // ═══════════════════════════════════════════════════════════════

        $divider = LayoutNode::leaf("{$key}:divider", null, width: 8.0);
        $surface->onDrag("{$key}:divider", function () use ($surface, $key, $w): void {
            $this->onDragDivider($surface, $key, $w);
        });

        // ═══════════════════════════════════════════════════════════════
        //  Right pane — output
        // ═══════════════════════════════════════════════════════════════

        $out = new TextAreaControl("{$key}:out", '', width: $otherW - 8, height: $paneH);
        $out->bind($surface);
        $out->setValue($tab['output']);
        $out->onChange(function (string $v): void { self::$tabs[self::$activeTab]['output'] = $v; });

        // ═══════════════════════════════════════════════════════════════
        //  Action handlers
        // ═══════════════════════════════════════════════════════════════

        $surface->onClick("{$key}:run",  function () use ($surface): void { $this->runCode($surface); });
        $surface->onClick("{$key}:open", function () use ($in): void {
            $win = WindowHolder::get();
            if ($win === null) return;
            $path = FilePickerDialog::pick($win);
            if ($path !== null) {
                $in->setValue(Backend::fileRead($path));
            }
        });
        $surface->onClick("{$key}:save", function (): void { $this->saveOutput(); });

        // ── Stash refs ──────────────────────────────────────────────────
        self::$inRef  = $in;
        self::$outRef = $out;

        // ═══════════════════════════════════════════════════════════════
        //  Assembly
        // ═══════════════════════════════════════════════════════════════

        $splitRow = LayoutNode::row(id: "{$key}:split", gap: 0, height: $paneH);
        $splitRow->child($in->root());
        $in->root()->style->grow = 1.0;
        $in->root()->style->shrink = 1.0;
        $splitRow->child($divider);
        $divider->style->shrink = 1.0;
        $splitRow->child($out->root());
        $out->root()->style->grow = 1.0;
        $out->root()->style->shrink = 1.0;

        $rows = [
            Ui::title('代码演练场 ⭐', $w),
            $tabBar,
            $toolbar,
            $splitRow,
        ];

        $sv = new ScrollViewControl("p:{$key}", $rows, width: $width, height: $height, gap: 6.0, padding: 18.0, contentHeight: $height);
        $sv->bind($surface);

        return $sv->root();
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Tab helpers
    // ═══════════════════════════════════════════════════════════════════════

    /** @return array{name:string,input:string,output:string,rawOutput:string,lang:string,format:string,binary:string} */
    private static function currentTab(): array
    {
        return self::$tabs[self::$activeTab] ?? self::$tabs[0];
    }

    /** Calculate minimum panel width (in px) to fit the longest option.
     *  Rough estimate: ~7.5px per char at 12px font, plus 16px padding.
     */
    private static function calculateBinPanelWidth(array $options): float
    {
        $longest = '';
        foreach ($options as $opt) {
            if (mb_strlen($opt) > mb_strlen($longest)) {
                $longest = $opt;
            }
        }
        return max(80.0, mb_strlen($longest) * 7.5 + 16);
    }

    // ── Tab operations ──────────────────────────────────────────────────────

    private function switchTab(int $ti): void
    {
        $this->syncTabFromWidgets();
        self::$activeTab = $ti;
        $this->syncWidgetsFromTab();
        $this->syncCombosFromTab();
    }

    private function closeTab(Surface $surface, int $ti): void
    {
        if (count(self::$tabs) <= 1) return;

        $this->syncTabFromWidgets();
        array_splice(self::$tabs, $ti, 1);
        self::$activeTab = self::$tabControlRef?->activeIndex() ?? max(0, self::$activeTab - 1);

        $this->syncWidgetsFromTab();
        $this->syncCombosFromTab();
        $surface->redraw();
    }

    private function addTab(Surface $surface): void
    {
        $n      = count(self::$tabs) + 1;
        $label  = "tab-{$n}";
        $lang   = self::$tabs[self::$activeTab]['lang'];
        $src    = self::$defaultCode[$lang] ?? '';

        self::$tabs[] = ['name' => $label, 'input' => $src, 'output' => '', 'rawOutput' => '', 'lang' => $lang, 'format' => 'Raw', 'binary' => ''];
        self::$activeTab = count(self::$tabs) - 1;

        self::$tabControlRef?->addTab($label);

        $this->syncWidgetsFromTab();
        $this->syncCombosFromTab();
        $surface->redraw();
    }

    // ── Widget ↔ tab state sync ─────────────────────────────────────────────

    private function syncTabFromWidgets(): void
    {
        $in  = self::$inRef;
        $out = self::$outRef;
        if ($in === null || $out === null) return;
        self::$tabs[self::$activeTab]['input']  = $in->getValue();
        self::$tabs[self::$activeTab]['output'] = $out->getValue();
    }

    private function syncWidgetsFromTab(): void
    {
        $in  = self::$inRef;
        $out = self::$outRef;
        if ($in === null || $out === null) return;
        $tab = self::currentTab();
        $in->setValue($tab['input']);
        $out->setValue($tab['output']);
    }

    private function syncCombosFromTab(): void
    {
        $tab = self::currentTab();

        $langCombo = self::$langComboRef;
        if ($langCombo !== null) {
            $langCombo->setValue($tab['lang'], false);
        }

        $binCombo = self::$binComboRef;
        if ($binCombo !== null) {
            $scanned = Backend::scanBinaries($tab['lang']);
            $binOpts = array_merge(['—'], $scanned);
            $binCombo->setOptions($binOpts);
            $binCombo->setValue($tab['binary'] ?: '—', false);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Actions
    // ═══════════════════════════════════════════════════════════════════════

    private function runCode(Surface $surface): void
    {
        $tab = self::currentTab();
        $in  = self::$inRef;
        $out = self::$outRef;
        if ($in === null || $out === null) return;

        $code      = $in->getValue();
        $lang      = $tab['lang'];
        $binary    = $tab['binary'] !== '—' ? $tab['binary'] : '';
        $rawOutput = Backend::codeRun($code, $lang, $binary);

        self::$tabs[self::$activeTab]['rawOutput'] = $rawOutput;
        $fmt     = $tab['format'];
        $display = Backend::codeTransform($rawOutput, $fmt);
        self::$tabs[self::$activeTab]['output'] = $display;
        $out->setValue($display);
        $surface->redraw();
    }

    private function saveOutput(): void
    {
        $win = WindowHolder::get();
        if ($win === null) return;
        $path = FilePickerDialog::pick($win);
        if ($path === null) return;

        $out = self::$outRef;
        if ($out === null) return;
        Backend::fileSave($path, $out->getValue());
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Drag divider
    // ═══════════════════════════════════════════════════════════════════════

    private function onDragDivider(Surface $surface, string $key, float $totalW): void
    {
        $divNode = LayoutNode::find($surface->rootLayout(), "{$key}:divider");
        if ($divNode === null) return;

        $avail  = $totalW - 12;
        $leftW  = max(100, min($divNode->x, $avail - 100));
        self::$splitRatio = $leftW / $avail;

        $splitRow = LayoutNode::find($surface->rootLayout(), "{$key}:split");
        if ($splitRow === null || count($splitRow->children) < 3) return;

        $splitRow->children[0]->style->width = $leftW;
        $splitRow->children[2]->style->width = $avail - $leftW;
        $surface->redraw();
    }
}
