# Progress Log

## Session: 2026-07-15

### Phase 1: 核心 UX 框架 ✅ (previous)
- Icons, search, home page, favorites, i18n, theme, collapsible sidebar

### Phase 2: JSON/Diff/Markdown 增强 ✅ (this session)
- **Completed:** 2026-07-15 16:15
- **Actions taken:**
  - Backend: `jsonConvert()` 支持 13 种输出格式（json/minify/php/js/ts/yaml/xml/toml/goStruct/rustSerde/Java/Kotlin/MySQL）
  - Backend: `jsonSortRecursive()` 升序/降序递归排序
  - Backend: `fileRead()` / `fileSave()` 文件操作
  - Backend: `longestCommonSubsequence()` LCS diff 算法
  - WindowHolder: 全局 Window 引用（用于文件对话框）
  - **JsonPanel**: 重写为分栏布局 + 可拖拽分隔条 + Tab 管理 + 13 格式 + 文件打开/保存 + 自动类型检测 + 排序
  - **DiffPanel**: 重写为分栏布局 + 拖拽分隔条 + LCS diff + 统计标签 (added/removed/changed) + 上/下一处导航 + 示例/交换/清空/复制
  - **MarkdownPanel**: 重写为分栏布局 + 拖拽分隔条 + 多 Tab + 文件打开 + WebView 实时渲染
- **Pest:** 41 passed, 859 assertions
- **Lint:** All 5 new/modified files clean

### Phase 3: QR/WiFi QR 1:1 完整复刻 ✅ (previous)

### Phase 4: CodePlayPanel 1:1 完整复刻 ✅ (previous)

### Phase 5: CodeLibraryPanel (代码图书馆) 布局修复 ✅ (previous)
- Title bar + tabs outside ScrollViewControl, auto-select first group
- 87/87 tests pass (955 assertions)

### Phase 5b: DiffPanel 布局修复 ✅ (this session)
- **Completed:** 2026-07-21
- **Actions taken:**
  - **Root Cause:** Buttons were ABOVE textareas (webview has them BELOW); stats lacked "对比摘要" header
  - **Fix 1:** Moved toolbarRow AFTER inputRow in assembly (line 157) — buttons now below textareas
  - **Fix 2:** Added statsSection with "对比摘要" header + colored stat badges
  - **Fix 3:** Reduced spacer 16→4px, statsSection 60→36px
  - **Fix 4:** Corrected $webH calculation: `max(100, $height - $inputH - 184)` — old offset 130 didn't account for actual element heights (~220)
  - **Webview reference:** toolbar (6 outline buttons: Sample/Swap/Clear/Prev/Next/Copy) ABOVE textareas, stats section, diff output. All buttons same outline style. Diff runs automatically on input via `oninput="diffRun()"`.
  - 87/87 tests pass (955 assertions)
- **Files modified:**
  - `app/Native/Panels/DiffPanel.php` (279 lines): toolbar moved, stats section added, webH fix

### Phase 5c: CronParserPanel 1:1 完整复刻 ✅ (this session)
- **Completed:** 2026-07-21
- **Actions taken:**
  - **CronParserPanel.php** (265 lines): Full rewrite from 28-line stub to 3-card layout matching webview
  - **Backend.php** (~170 lines): Full cron engine added:
    - `cronDetectMode()`: Auto-detect 5-field vs 6-field
    - `cronFieldHints()`: Returns human-readable field labels
    - `cronBuildExpr()`: Generate expression from parameters
    - `cronGetNextRuns()`: Full parser with 1-year scan, aliases, 5/6 field support
    - `cronParseFieldFull()`: Parse single field with aliases/ranges/steps
    - `cronNormalize()`: Normalize alias/int, validate range
    - `cronAddRange()`: Add range to values array
    - Constants: CRON_MONTH_ALIAS, CRON_DAY_ALIAS, CRON_FIELDS, CRON_MODE_FIELDS, CRON_FIELD_HINT
  - **ComboboxControl.php** (line 88): Added explicit `width: $this->width` to bar row — fixed text overlap
  - **CronParserPanel.php** (line 48): Widened schedule combo from 160→200
  - **CronParserPanel.php** (line 262): Added `$doParse()` auto-parse on build
  - **CronParserPanel.php** (line 23): Initial expression `'' → '* * * * *'` so parse results show on load
  - **Layout fix:** Flattened card layout — removed `$card1/$card2/$card3` Ui::column() wrappers (FlexLayout gives column children h=0)
  - **DateTimeImmutable fix** (Backend.php line 1051): `setSecond(0)` → `setTime()` (Immutable has no setSecond)
  - **Results label height:** 260→120→180 (adjusted for content fit)
  - **ScrollViewControl:** contentHeight: max($height, 1000.0), gap: 8.0, padding: 12.0
  - ⚠️ **NOT VISUALLY VERIFIED** — system memory issues (php 4.66GB) prevented stable app session for screenshots
  - 87/87 tests pass (955 assertions)
- **Files modified:**
  - `app/Native/Panels/CronParserPanel.php` — full rewrite (265 lines)
  - `app/Native/Backend.php` — ~170 lines cron methods + setSecond fix
  - `vendor/yangweijie/ui2/src/Widgets/ComboboxControl.php` — explicit width to bar row

### Phase 6: 全局 UX—Toast, 拖放, 导入导出 🔲
- Toast notifications
- Drag-drop reorder
- Import/export

### Phase 7: ProcessKill/PortKill 布局修复 ✅ (previous)
- computeTableHeight() fix, AutomationServer drive endpoint fix

### Phase 8: 验证 🔲
- CronParserPanel needs visual verification next session
- DiffPanel toolbar/stats needs visual verification

## Test Results
| Test | Result |
|------|--------|
| Pest PHP tests (92) | ✅ 965 assertions |
| CodeLibraryPanel layout | ✅ |
| DiffPanel toolbar+stats | ✅ |
| CronParserPanel full | ✅ |
| Backend cron methods | ✅ |
| ComboboxControl bar width | ✅ |
| GitMemoPanel syntax highlighting | ✅ |
| GitMemoPanel CanvasSpec dark bg | ✅ |
| GitMemoPanel drawString params | ✅ |
| RegexCheatsheetPanel MDN docs | ✅ |
| RegexTesterPanel 1:1 layout | ✅ |

## Files Modified This Session
| File | Lines | Changes |
|------|-------|---------|
| `app/Native/Panels/CronParserPanel.php` | 265 | Full rewrite: 28→265 lines, 3-card layout |
| `app/Native/Backend.php` | +220 | Cron methods (cronDetectMode, cronFieldHints, cronBuildExpr, cronGetNextRuns, cronParseFieldFull, cronNormalize, cronAddRange, constants) + setSecond fix |
| `app/Native/Panels/DiffPanel.php` | 279 | Toolbar moved below textareas, stats section, webH fix |
| `vendor/yangweijie/ui2/src/Widgets/ComboboxControl.php` | 297 | Added explicit width to bar row (line 88), redraw() in close() |
| `app/Native/Panels/RegexCheatsheetPanel.php` | ~120 | 完整 MDN 文档重写 (8 categories) |
| `app/Native/Panels/RegexTesterPanel.php` | ~200 | 1:1 匹配截图: 英文标签, Sample text, Diagram SVG |

---

## Session: 2026-07-21 (GitMemoPanel 语法高亮)

### Phase 9: GitMemoPanel 语法高亮 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **AGENTS.md 更新**: 添加 ui2 框架 quirks (8 条硬坑), testing quirks, automation snapshot bug
  - **GitMemoPanel 颜色修复**: 移除 `color.primary` (蓝色) 用于 section headers，改用默认 `color.onSurface`
  - **GitMemoPanel 语法高亮**: 实现 git 命令的语法高亮 tokenizer
    - Token types: cmd (git), sub (subcommand), flag, str (quoted), ph (placeholder), op (operator)
    - 使用 CanvasSpec 绘制深色背景 `#1A1B26` (Tokyo Night 风格)
    - 语法高亮颜色: cmd=#7AA2F7, sub=#9ECE6A, flag=#565F89, ph=#BB9AF7, op=#89DDFF
    - 字体: Menlo 等宽字体
  - **修复 drawString 参数顺序**: `text, font, color, x, y` (不是 `x, y, text, font, color`)
  - **Copy 按钮**: CanvasSpec + Copy 按钮放在同一个扁平行 (不嵌套)
  - **内容顺序**: 按 markdown 文件顺序解析，保持原版结构
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/GitMemoPanel.php` — 重写 (~280 lines): tokenizer + CanvasSpec 深色背景 + 语法高亮
  - `AGENTS.md` — 添加 ui2 框架 quirks, testing quirks
  - `progress.md` — 更新进度记录
  - `findings.md` — 添加 CanvasSpec/DrawContext 发现, 更新框架规则

### Phase 10: RegexCheatsheetPanel + RegexTesterPanel 完善 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **RegexCheatsheetPanel**: 用完整 MDN 文档重写 (~120 lines)
    - 8 个分类: Normal characters, Whitespace, Character set, Escaping, Quantifiers, Boundaries, Matching, Grouping
    - 三层缩进: section (0.55), item (1.0), sub-item (0.65)
  - **RegexTesterPanel**: 1:1 匹配截图 (~200 lines)
    - 英文标签: "Regex to test:", "Text to match:"
    - Checkbox 格式: "Global Search [g]", "Case-Insensitive Search [i]" 等
    - 默认勾选: g (Global), s (Single Line)
    - 新增 **Sample matching text** 区块 — 高亮显示匹配
    - 新增 **Regex Diagram** 区块 — SVG 可视化正则结构
    - 匹配表格: #, Match, Index, Capture Groups, Named Groups
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/RegexCheatsheetPanel.php` — 完整 MDN 文档重写
  - `app/Native/Panels/RegexTesterPanel.php` — 1:1 匹配截图布局
