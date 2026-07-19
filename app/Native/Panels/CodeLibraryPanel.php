<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\CodeLibraryData;
use App\Native\Panel;
use App\Native\Ui;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Layout\LayoutStyle;
use Yangweijie\Ui2\Rendering\WidgetRenderer\ButtonSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\DialogCardSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Rendering\WidgetRenderer\TextFieldSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;
use Yangweijie\Ui2\Widgets\TextAreaControl;

/**
 * Code Library — 1:1 with the legacy webview tool.
 *
 * Language tabs → Groups sidebar + Codes sidebar → Main detail area.
 * Full CRUD via inline forms; data persisted to JSON via CodeLibraryData.
 */
final class CodeLibraryPanel implements Panel
{
    /** Set by NativeApp; called when a view change requires a full rebuild. */
    public ?\Closure $onRebuild = null;

    // ── UI state (persists across rebuilds via static) ────────────────
    private static string $langType = 'php';
    private static string $selectedGroupId = '';
    private static string $selectedItemId = '';
    private static bool $batchMode = false;
    /** @var list<string> */
    private static array $batchIds = [];
    private static string $editMode = '';        // '' | 'group_add' | 'group_edit' | 'code_add' | 'code_edit'
    private static string $editGroupId = '';
    private static string $editItemId = '';
    private static string $searchQuery = '';

    private const TITLE_BAR_H = 36.0;
    private const TAB_H = 28.0;
    private const ROW_H = 24.0;
    private const GROUP_HEADER_H = 28.0;
    private const CODE_HEADER_H = 28.0;
    private const SIDEBAR_W = 220.0;

    /** @var array<string, TextAreaControl> */
    private static array $textAreas = [];

    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $data = CodeLibraryData::getInstance();
        self::$textAreas = [];

        // Auto-select first group if none selected (initial load)
        if (self::$selectedGroupId === '') {
            $groups = $data->getGroups(self::$langType);
            if ($groups !== []) {
                self::$selectedGroupId = $groups[0]['id'];
            }
        }

        // ── 1. Title bar ─────────────────────────────────────────────
        $titleBar = $this->buildTitleBar($surface, $key, $w, $data);

        // ── 2. Language tabs ─────────────────────────────────────────
        $tabBar = $this->buildLangTabs($surface, $key, $w, $data);

        // ── 3. Sidebar (groups + codes) ──────────────────────────────
        $sidebar = $this->buildSidebar($surface, $key, $w, $data);

        // ── 4. Main area ─────────────────────────────────────────────
        $main = $this->buildMainArea($surface, $key, $w, $data);

        // ── 5. Batch bar (conditional) ──────────────────────────────
        $batchBar = self::$batchMode
            ? $this->buildBatchBar($surface, $key, $data)
            : Ui::label('', $w, 1.0, 1.0);

        // ── Assembly — title bar + tabs OUTSIDE scroll, content scrolls ──
        $nonScrollH = self::TITLE_BAR_H + self::TAB_H + 12.0; // title + tabs + 2 gaps
        $scrollH = max(100.0, $height - $nonScrollH);
        $batchH = ($batchBar->style->height > 30) ? 34.0 : 0.0;
        $contentH = max(100.0, $scrollH - $batchH - 48.0);
        // Explicit height needed — row's default ALIGN_CENTER gives children h=0 without it
        $sidebar->style->height = $contentH;
        $main->style->height = $contentH;
        $contentRow = Ui::row([$sidebar, $main], gap: 12.0, height: $contentH);
        $contentRow->id = "{$key}:content";

        // Scrollable portion: content row + batch bar
        $scrollTotalH = $contentH + 6.0 + $batchH;
        $scrollCol = Ui::column([$contentRow, $batchBar], gap: 6.0, width: $w);
        $scrollCol->style->height = $scrollTotalH;

        $sv = new ScrollViewControl("p:{$key}", [$scrollCol], width: $width, height: $scrollH, gap: 0, padding: 24.0, contentHeight: max($scrollH, $scrollTotalH + 48));
        $sv->bind($surface);

        // Outer column: title bar + tabs (fixed) + scroll area (fills rest)
        $outerCol = Ui::column([$titleBar, $tabBar, $sv->root()], gap: 6.0, width: $width);
        $outerCol->style->height = $height;
        return $outerCol;
    }

    // ── Title bar ──────────────────────────────────────────────────

    private function buildTitleBar(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $settingsId = "{$key}:settings";
        $favId = "{$key}:fav";

        $settingsBtn = LayoutNode::leaf($settingsId, new ButtonSpec('⚙', 'soft'), width: 28.0, height: 28.0);
        $surface->onClick($settingsId, function () use ($data, $surface, $key, $settingsBtn): void {
            $overlay = $this->buildSettingsOverlay($surface, $key, $data, $settingsBtn);
            $surface->setOverlay($overlay);
            $surface->onOverlayDismiss(function () use ($surface): void {
                $surface->setOverlay(null);
            });
        });

        $favBtn = LayoutNode::leaf($favId, new ButtonSpec('★', 'soft'), width: 28.0, height: 28.0);
        $surface->onClick($favId, function (): void {
            // Favorites toggle — no-op for now
        });

        return Ui::row([
            Ui::title('代码图书馆', 200.0),
            $settingsBtn,
            $favBtn,
        ], gap: 8.0, height: self::TITLE_BAR_H);
    }

    // ── Language tabs ───────────────────────────────────────────────

    private function buildLangTabs(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $langs = $data->getLangs();

        $visibleCount = 0;
        foreach ($langs as $lang) {
            if ($lang['show']) {
                $visibleCount++;
            }
        }

        $gap = 3.0;
        $totalGap = $gap * max(0, $visibleCount - 1);
        $tabW = max(40.0, min(70.0, ($w - $totalGap) / max(1, $visibleCount)));

        $nodes = [];

        foreach ($langs as $lang) {
            if (!$lang['show']) {
                continue;
            }
            $type = $lang['type'];
            $isActive = $type === self::$langType;
            $label = ucfirst($type);
            $btnId = "{$key}:lang:{$type}";
            $btn = LayoutNode::leaf($btnId, new ButtonSpec($label, $isActive ? 'filled' : 'outline', radius: 12.0), width: $tabW, height: self::TAB_H);
            $surface->onClick($btnId, function () use ($type): void {
                self::$langType = $type;
                self::$selectedGroupId = '';
                self::$selectedItemId = '';
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            });
            $nodes[] = $btn;
        }

        $row = LayoutNode::row(gap: $gap, height: self::TAB_H, align: LayoutStyle::ALIGN_CENTER);
        foreach ($nodes as $n) {
            $row->child($n);
        }
        return $row;
    }

    // ── Settings overlay ────────────────────────────────────────────

    private function buildSettingsOverlay(Surface $surface, string $key, CodeLibraryData $data, LayoutNode $anchorBtn): LayoutNode
    {
        $langs = $data->getLangs();
        $children = [];
        $children[] = Ui::title('语言显示', 200.0);

        foreach ($langs as $lang) {
            $type = $lang['type'];
            $show = $lang['show'];
            $toggleId = "{$key}:settoggle:{$type}";
            $row = Ui::row([
                Ui::label(ucfirst($type), 120.0),
                LayoutNode::leaf($toggleId, new ButtonSpec($show ? '✓' : '—', $show ? 'filled' : 'soft'), width: 40.0, height: 26.0),
            ], gap: 8.0, height: 30.0);
            $surface->onClick($toggleId, function () use ($type): void {
                CodeLibraryData::getInstance()->toggleLangShow($type);
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            });
            $children[] = $row;
        }

        $closeId = "{$key}:setclose";
        $children[] = LayoutNode::leaf($closeId, new ButtonSpec('关闭', 'filled'), width: 80.0, height: 30.0);
        $surface->onClick($closeId, function () use ($surface): void {
            $surface->setOverlay(null);
        });

        $langCount = count($langs);
        $innerH = 24.0 + $langCount * 30.0 + 30.0 + $langCount * 6.0;

        $col = Ui::column($children, gap: 6.0, width: 200.0);
        $col->style->height = $innerH;
        $form = Ui::column([$col], gap: 16.0, width: 240.0);
        $form->style->height = $innerH + 32.0;

        // Position below the ⚙ button using absolute positioning.
        $form->style->absolute = true;
        $form->style->left = $anchorBtn->x;
        $form->style->top = $anchorBtn->y + $anchorBtn->h + 4.0;

        $container = LayoutNode::column();
        $container->child($form);
        return $container;
    }

    // ── Sidebar ─────────────────────────────────────────────────────

    private function buildSidebar(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $sidebarW = min(self::SIDEBAR_W, $w * 0.25);
        $listH = 180.0;

        // ── Groups section ──────────────────────────────────────
        $addGroupId = "{$key}:addgroup";
        $groupsHeader = Ui::row([
            Ui::label('分组', $sidebarW - 40, 13.0, self::GROUP_HEADER_H),
            LayoutNode::leaf($addGroupId, new ButtonSpec('+', 'soft'), width: 24.0, height: 22.0),
        ], gap: 4.0, height: self::GROUP_HEADER_H);
        $surface->onClick($addGroupId, function () use ($surface, $key, $data): void {
            self::$editMode = 'group_add';
            $overlay = $this->buildGroupFormOverlay($surface, $key, $data);
            $surface->setOverlay($overlay);
            $surface->onOverlayDismiss(function () use ($surface): void {
                $surface->setOverlay(null);
                self::$editMode = '';
            });
        });

        $groups = $data->getGroups(self::$langType);
        $groupNodes = [];
        foreach ($groups as $g) {
            $gid = $g['id'];
            $isActive = $gid === self::$selectedGroupId;
            $btnId = "{$key}:grp:{$gid}";
            $btn = LayoutNode::leaf($btnId, new ButtonSpec($g['name'], $isActive ? 'filled' : 'soft'), width: $sidebarW - 10, height: self::ROW_H);
            $surface->onClick($btnId, function () use ($gid): void {
                self::$selectedGroupId = $gid;
                self::$selectedItemId = '';
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            });
            $groupNodes[] = $btn;

            // Context menu row: edit / move-to-top / delete — only for selected group
            if ($isActive) {
                $editId = "{$key}:grpedit:{$gid}";
                $moveId = "{$key}:grpmove:{$gid}";
                $delId = "{$key}:grpdel:{$gid}";
                $groupNodes[] = Ui::row([
                    LayoutNode::leaf($editId, new ButtonSpec('编辑', 'soft'), width: 40.0, height: 18.0),
                    LayoutNode::leaf($moveId, new ButtonSpec('置顶', 'soft'), width: 40.0, height: 18.0),
                    LayoutNode::leaf($delId, new ButtonSpec('删除', 'soft'), width: 40.0, height: 18.0),
                ], gap: 2.0, height: 20.0);

                $surface->onClick($editId, function () use ($gid, $surface, $key, $data): void {
                    self::$editMode = 'group_edit';
                    self::$editGroupId = $gid;
                    $overlay = $this->buildGroupFormOverlay($surface, $key, $data);
                    $surface->setOverlay($overlay);
                    $surface->onOverlayDismiss(function () use ($surface): void {
                        $surface->setOverlay(null);
                        self::$editMode = '';
                    });
                });
                $surface->onClick($moveId, function () use ($gid): void {
                    CodeLibraryData::getInstance()->moveGroupToTop($gid);
                    if (isset($this->onRebuild)) {
                        ($this->onRebuild)();
                    }
                });
                $surface->onClick($delId, function () use ($gid): void {
                    CodeLibraryData::getInstance()->deleteGroup($gid);
                    self::$selectedGroupId = '';
                    if (isset($this->onRebuild)) {
                        ($this->onRebuild)();
                    }
                });
            }
        }

        if ($groupNodes === []) {
            $emptyG = LayoutNode::column(gap: 4.0, width: $sidebarW, align: LayoutStyle::ALIGN_CENTER);
            $emptyG->child(Ui::label('📂', $sidebarW, 20.0, 30.0));
            $emptyG->child(Ui::label('暂无数据', $sidebarW, 11.0, 18.0));
            $groupNodes[] = $emptyG;
        }

        $groupsScroll = new ScrollViewControl("p:{$key}:glist", $groupNodes, width: $sidebarW, height: $listH, gap: 2.0, padding: 2.0, contentHeight: max($listH, count($groupNodes) * 26.0 + 4));
        $groupsScroll->bind($surface);

        // ── Codes section ───────────────────────────────────────
        $addCodeId = "{$key}:addcode";
        $codesHeader = Ui::row([
            Ui::label('代码', $sidebarW - 40, 13.0, self::CODE_HEADER_H),
            LayoutNode::leaf($addCodeId, new ButtonSpec('+', 'soft'), width: 24.0, height: 22.0),
        ], gap: 4.0, height: self::CODE_HEADER_H);
        $surface->onClick($addCodeId, function () use ($surface, $key, $data): void {
            self::$editMode = 'code_add';
            $overlay = $this->buildCodeFormOverlay($surface, $key, $data);
            $surface->setOverlay($overlay);
            $surface->onOverlayDismiss(function () use ($surface): void {
                $surface->setOverlay(null);
                self::$editMode = '';
            });
        });

        $ungrouped = $data->getUngroupedItems(self::$langType);
        $codeNodes = [];
        foreach ($ungrouped as $item) {
            $iid = $item['id'];
            $isActive = $iid === self::$selectedItemId;
            $btnId = "{$key}:code:{$iid}";
            $btn = LayoutNode::leaf($btnId, new ButtonSpec($item['name'], $isActive ? 'filled' : 'soft'), width: $sidebarW - 10, height: self::ROW_H);
            $surface->onClick($btnId, function () use ($iid): void {
                self::$selectedItemId = $iid;
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            });
            $codeNodes[] = $btn;

            // Context menu: edit / move / delete — only for selected code item
            if ($isActive) {
                $editId = "{$key}:codeedit:{$iid}";
                $moveId = "{$key}:codemove:{$iid}";
                $delId = "{$key}:codedel:{$iid}";
                $codeNodes[] = Ui::row([
                    LayoutNode::leaf($editId, new ButtonSpec('编辑', 'soft'), width: 40.0, height: 18.0),
                    LayoutNode::leaf($moveId, new ButtonSpec('置顶', 'soft'), width: 40.0, height: 18.0),
                    LayoutNode::leaf($delId, new ButtonSpec('删除', 'soft'), width: 40.0, height: 18.0),
                ], gap: 2.0, height: 20.0);

                $surface->onClick($editId, function () use ($iid, $surface, $key, $data): void {
                    self::$editMode = 'code_edit';
                    self::$editItemId = $iid;
                    $overlay = $this->buildCodeFormOverlay($surface, $key, $data);
                    $surface->setOverlay($overlay);
                    $surface->onOverlayDismiss(function () use ($surface): void {
                        $surface->setOverlay(null);
                        self::$editMode = '';
                    });
                });
                $surface->onClick($moveId, function () use ($iid): void {
                    CodeLibraryData::getInstance()->moveItemToTop($iid);
                    if (isset($this->onRebuild)) {
                        ($this->onRebuild)();
                    }
                });
                $surface->onClick($delId, function () use ($iid): void {
                    CodeLibraryData::getInstance()->deleteItem($iid);
                    self::$selectedItemId = '';
                    if (isset($this->onRebuild)) {
                        ($this->onRebuild)();
                    }
                });
            }
        }

        if ($codeNodes === []) {
            $emptyC = LayoutNode::column(gap: 4.0, width: $sidebarW, align: LayoutStyle::ALIGN_CENTER);
            $emptyC->child(Ui::label('📂', $sidebarW, 20.0, 30.0));
            $emptyC->child(Ui::label('暂无数据', $sidebarW, 11.0, 18.0));
            $codeNodes[] = $emptyC;
        }

        $codesScroll = new ScrollViewControl("p:{$key}:clist", $codeNodes, width: $sidebarW, height: $listH, gap: 2.0, padding: 2.0, contentHeight: max($listH, count($codeNodes) * 26.0 + 4));
        $codesScroll->bind($surface);

        $sidebarCol = Ui::column([
            $groupsHeader,
            $groupsScroll->root(),
            Ui::label('', $sidebarW, 1.0, 8.0),
            $codesHeader,
            $codesScroll->root(),
        ], gap: 0.0, width: $sidebarW);

        return $sidebarCol;
    }

    // ── Main area ───────────────────────────────────────────────────

    private function buildMainArea(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $sidebarW = min(self::SIDEBAR_W, $w * 0.25);
        $mainW = max(200.0, $w - $sidebarW - 12);

        // Inline edit form takes priority
        if (self::$editMode !== '') {
            return $this->buildInlineEdit($surface, $key, $mainW, $data);
        }

        // Code detail view
        if (self::$selectedItemId !== '') {
            return $this->buildCodeDetail($surface, $key, $mainW, $data);
        }

        // Group items view
        if (self::$selectedGroupId !== '') {
            return $this->buildGroupItems($surface, $key, $mainW, $data);
        }

        // Empty state — centered
        $emptyCol = LayoutNode::column(gap: 8.0, width: $mainW, align: LayoutStyle::ALIGN_CENTER);
        $emptyCol->child(Ui::label('📦', $mainW, 48.0, 60.0));
        $emptyCol->child(Ui::label('暂无数据', $mainW, 14.0, 24.0));
        return $emptyCol;
    }

    // ── Code detail ─────────────────────────────────────────────────

    private function buildCodeDetail(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $item = $data->getItem(self::$selectedItemId);
        if ($item === null) {
            return Ui::label('未找到项目', $w, 14.0, 40.0);
        }

        $nodes = [];
        $nodes[] = Ui::label($item['name'], $w, 16.0, 28.0);
        if ($item['comment'] !== '') {
            $nodes[] = Ui::label($item['comment'], $w, 12.0, 20.0);
        }

        // Code block
        $codeLines = explode("\n", $item['value']);
        $codeH = max(60, min(count($codeLines) * 18 + 20, 400));
        $codeNode = LayoutNode::leaf(null, new LabelSpec($item['value'], size: 13.0), width: $w, height: $codeH);
        $nodes[] = $codeNode;

        // Copy button
        $copyId = "{$key}:copy";
        $nodes[] = LayoutNode::leaf($copyId, new ButtonSpec('复制代码', 'filled'), width: 100.0, height: 30.0);
        $surface->onClick($copyId, function () use ($item): void {
            if (function_exists('libui_uiClipboardWriteText')) { // @phpstan-ignore-line
                libui_uiClipboardWriteText($item['value']); // @phpstan-ignore-line
            }
        });

        // Result
        if ($item['toValue'] !== '') {
            $nodes[] = Ui::label('结果:', $w, 13.0, 20.0);
            $resultLines = explode("\n", $item['toValue']);
            $resultH = max(40, min(count($resultLines) * 18 + 20, 200));
            $nodes[] = LayoutNode::leaf(null, new LabelSpec($item['toValue'], size: 13.0), width: $w, height: $resultH);
        }

        return Ui::column($nodes, gap: 8.0, width: $w);
    }

    // ── Group items ─────────────────────────────────────────────────

    private function buildGroupItems(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $group = $data->getGroup(self::$selectedGroupId);
        if ($group === null) {
            return Ui::label('未找到分组', $w, 14.0, 40.0);
        }

        $nodes = [];
        $nodes[] = Ui::label($group['name'], $w, 16.0, 28.0);

        // Search bar
        $searchId = "{$key}:search";
        $searchNode = Ui::textField($searchId, '搜索代码...', $w, 28.0);
        $nodes[] = $searchNode;

        // Batch toggle
        $batchId = "{$key}:batchtoggle";
        $nodes[] = LayoutNode::leaf($batchId, new ButtonSpec(self::$batchMode ? '取消批次' : '批量模式', 'soft'), width: 100.0, height: 28.0);
        $surface->onClick($batchId, function (): void {
            self::$batchMode = !self::$batchMode;
            self::$batchIds = [];
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        // Items in group
        $items = $data->getGroupItems(self::$selectedGroupId);

        if (self::$searchQuery !== '') {
            $q = mb_strtolower(self::$searchQuery);
            $items = array_values(array_filter($items, static function (array $i) use ($q): bool {
                return str_contains(mb_strtolower($i['name']), $q)
                    || str_contains(mb_strtolower($i['comment']), $q)
                    || str_contains(mb_strtolower($i['value']), $q);
            }));
        }

        foreach ($items as $item) {
            $iid = $item['id'];
            if (self::$batchMode) {
                $isChecked = in_array($iid, self::$batchIds, true);
                $toggleId = "{$key}:batch:{$iid}";
                $btnLabel = ($isChecked ? '[✓] ' : '[  ] ') . $item['name'];
                $nodes[] = LayoutNode::leaf($toggleId, new ButtonSpec($btnLabel, 'soft'), width: $w - 20, height: self::ROW_H);
                $surface->onClick($toggleId, function () use ($iid): void {
                    $idx = array_search($iid, self::$batchIds, true);
                    if ($idx !== false) {
                        array_splice(self::$batchIds, $idx, 1);
                    } else {
                        self::$batchIds[] = $iid;
                    }
                    if (isset($this->onRebuild)) {
                        ($this->onRebuild)();
                    }
                });
            } else {
                $btnId = "{$key}:gitem:{$iid}";
                $nodes[] = LayoutNode::leaf($btnId, new ButtonSpec($item['name'], 'soft'), width: $w - 20, height: self::ROW_H);
                $surface->onClick($btnId, function () use ($iid): void {
                    self::$selectedItemId = $iid;
                    if (isset($this->onRebuild)) {
                        ($this->onRebuild)();
                    }
                });
            }
        }

        // Add code button
        $addId = "{$key}:gaddcode";
        $nodes[] = LayoutNode::leaf($addId, new ButtonSpec('+ 添加代码', 'soft'), width: 110.0, height: 28.0);
        $surface->onClick($addId, function () use ($surface, $key, $data): void {
            self::$editMode = 'code_add';
            $overlay = $this->buildCodeFormOverlay($surface, $key, $data);
            $surface->setOverlay($overlay);
            $surface->onOverlayDismiss(function () use ($surface): void {
                $surface->setOverlay(null);
                self::$editMode = '';
            });
        });

        return Ui::column($nodes, gap: 4.0, width: $w);
    }

    // ── Overlay centering helper ────────────────────────────────────

    private function wrapOverlayCentered(LayoutNode $form): LayoutNode
    {
        // Add card background (white fill + rounded corners) — same as DialogControl.
        $form->spec = new DialogCardSpec(radius: 14.0);

        // Center the card in the overlay using the proven DialogControl pattern:
        // JUSTIFY_CENTER (main axis = vertical for column) + ALIGN_CENTER (cross axis = horizontal).
        $centerer = LayoutNode::column(
            justify: LayoutStyle::JUSTIFY_CENTER,
            align: LayoutStyle::ALIGN_CENTER,
        );
        $centerer->child($form);
        return $centerer;
    }

    // ── Overlay form (group add/edit) ──────────────────────────────

    private function buildGroupFormOverlay(Surface $surface, string $key, CodeLibraryData $data): LayoutNode
    {
        $isEdit = self::$editMode === 'group_edit';
        $existing = $isEdit ? $data->getGroup(self::$editGroupId) : null;
        $title = $isEdit ? '编辑分组' : '添加分组';
        $nameFieldId = "{$key}:grpform:name";

        $nameField = LayoutNode::leaf($nameFieldId, new TextFieldSpec(value: $existing['name'] ?? '', placeholder: '分组名称'), width: 250.0, height: 30.0);

        $saveId = "{$key}:grpform:save";
        $cancelId = "{$key}:grpform:cancel";

        $saveBtn = LayoutNode::leaf($saveId, new ButtonSpec('保存', 'filled'), width: 80.0, height: 30.0);
        $cancelBtn = LayoutNode::leaf($cancelId, new ButtonSpec('取消', 'soft'), width: 80.0, height: 30.0);

        $surface->onClick($saveId, function () use ($surface, $nameFieldId, $isEdit, $data): void {
            $node = LayoutNode::find($surface->overlay(), $nameFieldId);
            $name = ($node !== null && $node->spec instanceof TextFieldSpec) ? $node->spec->value : '';
            if ($name === '') {
                return;
            }
            if ($isEdit) {
                $data->updateGroup(self::$editGroupId, $name);
            } else {
                $data->addGroup(self::$langType, $name);
            }
            $surface->setOverlay(null);
            self::$editMode = '';
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        $surface->onClick($cancelId, function () use ($surface): void {
            $surface->setOverlay(null);
            self::$editMode = '';
        });

        $row = Ui::row([$saveBtn, $cancelBtn], gap: 8.0, height: 34.0);
        $form = Ui::column([
            Ui::label($title, 300.0, 16.0, 28.0),
            $nameField,
            $row,
        ], gap: 10.0, width: 320.0);
        $form->style->height = 112.0; // 28 + 30 + 34 + 10*2
        return $this->wrapOverlayCentered($form);
    }

    // ── Overlay form (code add/edit) ───────────────────────────────

    private function buildCodeFormOverlay(Surface $surface, string $key, CodeLibraryData $data): LayoutNode
    {
        $isEdit = self::$editMode === 'code_edit';
        $existing = $isEdit ? $data->getItem(self::$editItemId) : null;
        $title = $isEdit ? '编辑代码' : '添加代码';

        $nameId = "{$key}:codeform:name";
        $commentId = "{$key}:codeform:comment";
        $valueId = "{$key}:codeform:value";
        $saveId = "{$key}:codeform:save";
        $cancelId = "{$key}:codeform:cancel";

        $nameField = LayoutNode::leaf($nameId, new TextFieldSpec(value: $existing['name'] ?? '', placeholder: '代码名称'), width: 300.0, height: 30.0);
        $commentField = LayoutNode::leaf($commentId, new TextFieldSpec(value: $existing['comment'] ?? '', placeholder: '备注（可选）'), width: 300.0, height: 30.0);

        $codeValue = $existing['value'] ?? '';
        $codeLines = explode("\n", $codeValue);
        $codeH = max(80, min(count($codeLines) * 18 + 20, 300));
        $codeArea = new TextAreaControl($valueId, $codeValue, width: 300.0, height: $codeH);
        $codeArea->bind($surface);
        self::$textAreas[$valueId] = $codeArea;

        $saveBtn = LayoutNode::leaf($saveId, new ButtonSpec('保存', 'filled'), width: 80.0, height: 30.0);
        $cancelBtn = LayoutNode::leaf($cancelId, new ButtonSpec('取消', 'soft'), width: 80.0, height: 30.0);

        $surface->onClick($saveId, function () use ($surface, $nameId, $commentId, $valueId, $isEdit, $data): void {
            $nameNode = LayoutNode::find($surface->overlay(), $nameId);
            $name = ($nameNode !== null && $nameNode->spec instanceof TextFieldSpec) ? $nameNode->spec->value : '';
            $commentNode = LayoutNode::find($surface->overlay(), $commentId);
            $comment = ($commentNode !== null && $commentNode->spec instanceof TextFieldSpec) ? $commentNode->spec->value : '';
            $value = self::$textAreas[$valueId]?->getValue() ?? (($isEdit && $data->getItem(self::$editItemId)) ? $data->getItem(self::$editItemId)['value'] : '');

            if ($name === '') {
                return;
            }
            if ($isEdit) {
                $data->updateItem(self::$editItemId, [
                    'name' => $name, 'comment' => $comment, 'value' => $value,
                ]);
            } else {
                $groupId = self::$selectedGroupId;
                $data->addItem($groupId, self::$langType, $name, $comment, $value);
            }
            $surface->setOverlay(null);
            self::$editMode = '';
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        $surface->onClick($cancelId, function () use ($surface): void {
            $surface->setOverlay(null);
            self::$editMode = '';
        });

        $row = Ui::row([$saveBtn, $cancelBtn], gap: 8.0, height: 34.0);
        $form = Ui::column([
            Ui::label($title, 320.0, 16.0, 28.0),
            $nameField,
            $commentField,
            Ui::label('代码:', 300.0, 13.0, 20.0),
            $codeArea->root(),
            $row,
        ], gap: 8.0, width: 340.0);
        $form->style->height = 182.0 + $codeH; // 28+30+30+20+codeH+34 + 8*5
        return $this->wrapOverlayCentered($form);
    }

    // ── Batch bar ───────────────────────────────────────────────────

    private function buildBatchBar(Surface $surface, string $key, CodeLibraryData $data): LayoutNode
    {
        $count = count(self::$batchIds);

        $selectAllId = "{$key}:batch:all";
        $moveId = "{$key}:batch:move";
        $deleteId = "{$key}:batch:delete";
        $cancelId = "{$key}:batch:cancel";

        $selectAllBtn = LayoutNode::leaf($selectAllId, new ButtonSpec('全部选中', 'soft'), width: 80.0, height: 26.0);
        $moveBtn = LayoutNode::leaf($moveId, new ButtonSpec("移动({$count})", 'soft'), width: 80.0, height: 26.0);
        $deleteBtn = LayoutNode::leaf($deleteId, new ButtonSpec("删除({$count})", 'soft'), width: 80.0, height: 26.0);
        $cancelBtn = LayoutNode::leaf($cancelId, new ButtonSpec('取消', 'filled'), width: 70.0, height: 26.0);

        $surface->onClick($selectAllId, function () use ($data): void {
            $items = $data->getGroupItems(self::$selectedGroupId);
            self::$batchIds = array_column($items, 'id');
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        $surface->onClick($moveId, function () use ($data): void {
            if (self::$selectedGroupId !== '' && self::$batchIds !== []) {
                $data->moveItems(self::$batchIds, self::$selectedGroupId);
                self::$batchIds = [];
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            }
        });

        $surface->onClick($deleteId, function () use ($data): void {
            if (self::$batchIds !== []) {
                $data->deleteItems(self::$batchIds);
                self::$batchIds = [];
                self::$selectedItemId = '';
                if (isset($this->onRebuild)) {
                    ($this->onRebuild)();
                }
            }
        });

        $surface->onClick($cancelId, function (): void {
            self::$batchMode = false;
            self::$batchIds = [];
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        return Ui::row([$selectAllBtn, $moveBtn, $deleteBtn, $cancelBtn], gap: 8.0, height: 30.0);
    }

    // ── Inline edit ─────────────────────────────────────────────────

    private function buildInlineEdit(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        if (self::$editMode === 'group_add' || self::$editMode === 'group_edit') {
            return $this->buildGroupInlineForm($surface, $key, $w, $data);
        }
        if (self::$editMode === 'code_add' || self::$editMode === 'code_edit') {
            return $this->buildCodeInlineForm($surface, $key, $w, $data);
        }
        return Ui::label('', $w, 13.0, 1.0);
    }

    private function buildGroupInlineForm(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $isEdit = self::$editMode === 'group_edit';
        $existing = $isEdit ? $data->getGroup(self::$editGroupId) : null;
        $title = $isEdit ? '编辑分组' : '添加分组';

        $nameId = "{$key}:igname";
        $nameField = LayoutNode::leaf($nameId, new TextFieldSpec(value: $existing['name'] ?? '', placeholder: '分组名称'), width: min(300.0, $w), height: 30.0);

        $saveId = "{$key}:igsave";
        $cancelId = "{$key}:igcancel";

        $surface->onClick($saveId, function () use ($surface, $nameId, $isEdit, $data): void {
            $node = LayoutNode::find($surface->rootLayout(), $nameId);
            $name = ($node !== null && $node->spec instanceof TextFieldSpec) ? $node->spec->value : '';
            if ($name === '') {
                return;
            }
            if ($isEdit) {
                $data->updateGroup(self::$editGroupId, $name);
            } else {
                $data->addGroup(self::$langType, $name);
            }
            self::$editMode = '';
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        $surface->onClick($cancelId, function (): void {
            self::$editMode = '';
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        return Ui::column([
            Ui::label($title, $w, 16.0, 28.0),
            $nameField,
            Ui::row([
                LayoutNode::leaf($saveId, new ButtonSpec('保存', 'filled'), width: 80.0, height: 30.0),
                LayoutNode::leaf($cancelId, new ButtonSpec('取消', 'soft'), width: 80.0, height: 30.0),
            ], gap: 8.0, height: 34.0),
        ], gap: 10.0, width: $w);
    }

    private function buildCodeInlineForm(Surface $surface, string $key, float $w, CodeLibraryData $data): LayoutNode
    {
        $isEdit = self::$editMode === 'code_edit';
        $existing = $isEdit ? $data->getItem(self::$editItemId) : null;
        $title = $isEdit ? '编辑代码' : '添加代码';

        $nameId = "{$key}:icname";
        $commentId = "{$key}:iccomment";
        $valueId = "{$key}:icvalue";

        $nameField = LayoutNode::leaf($nameId, new TextFieldSpec(value: $existing['name'] ?? '', placeholder: '代码名称'), width: min(350.0, $w), height: 30.0);
        $commentField = LayoutNode::leaf($commentId, new TextFieldSpec(value: $existing['comment'] ?? '', placeholder: '备注（可选）'), width: min(350.0, $w), height: 30.0);

        $codeValue = $existing['value'] ?? '';
        $codeLines = explode("\n", $codeValue);
        $codeH = max(80, min(count($codeLines) * 18 + 20, 300));
        $codeArea = new TextAreaControl($valueId, $codeValue, width: min(350.0, $w), height: $codeH);
        $codeArea->bind($surface);
        self::$textAreas[$valueId] = $codeArea;

        $saveId = "{$key}:icsave";
        $cancelId = "{$key}:iccancel";

        $surface->onClick($saveId, function () use ($surface, $nameId, $commentId, $valueId, $isEdit, $data): void {
            $nameNode = LayoutNode::find($surface->rootLayout(), $nameId);
            $name = ($nameNode !== null && $nameNode->spec instanceof TextFieldSpec) ? $nameNode->spec->value : '';
            $commentNode = LayoutNode::find($surface->rootLayout(), $commentId);
            $comment = ($commentNode !== null && $commentNode->spec instanceof TextFieldSpec) ? $commentNode->spec->value : '';
            $value = self::$textAreas[$valueId]?->getValue() ?? (($isEdit && $data->getItem(self::$editItemId)) ? $data->getItem(self::$editItemId)['value'] : '');

            if ($name === '') {
                return;
            }
            if ($isEdit) {
                $data->updateItem(self::$editItemId, [
                    'name' => $name, 'comment' => $comment, 'value' => $value,
                ]);
            } else {
                $groupId = self::$selectedGroupId;
                $data->addItem($groupId, self::$langType, $name, $comment, $value);
            }
            self::$editMode = '';
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        $surface->onClick($cancelId, function (): void {
            self::$editMode = '';
            if (isset($this->onRebuild)) {
                ($this->onRebuild)();
            }
        });

        return Ui::column([
            Ui::label($title, $w, 16.0, 28.0),
            $nameField,
            $commentField,
            Ui::label('代码:', $w, 13.0, 20.0),
            $codeArea->root(),
            Ui::row([
                LayoutNode::leaf($saveId, new ButtonSpec('保存', 'filled'), width: 80.0, height: 30.0),
                LayoutNode::leaf($cancelId, new ButtonSpec('取消', 'soft'), width: 80.0, height: 30.0),
            ], gap: 8.0, height: 34.0),
        ], gap: 8.0, width: $w);
    }
}
