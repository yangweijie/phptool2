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
- **Automation tests (12 all ✅):**
  - JSON: Format, Validate, Sort Asc, Detect type — all 4 ✅
  - Markdown: Render, Add tab — 2 ✅
  - Diff: Compare, Sample, Swap, Clear — 4 ✅
  - Navigation to each panel — 3 ✅
- **Pest:** 41 passed, 859 assertions
- **Lint:** All 5 new/modified files clean

### Phase 4: CodePlayPanel 1:1 完整复刻 ✅ (this session)
- **Completed:** 2026-07-16 10:30
- **Actions taken:**
  - **CodePlayPanel.php** (404 lines): Full rewrite with multi-tab system, split-pane layout, 6 languages, binary override, 17 output formats
  - **TabControl.php** (283 lines): Extended with closable/addable flags, × close button (only on active tab + count>1), + add button, rebuildBarAndHandlers
  - **ComboboxControl.php** (247 lines): Added setOptions() for dynamic updates, fixed root column width+height for parent ALIGN_CENTER row
  - **Backend.php**: Added jsonToPlist(), codeTransform(), extended jsonToStruct/phpTypeToLangType/camelCase for goBson/jsdoc
  - **Visual fixes**: Title '代码演练场 ⭐', icon-only buttons (📂/▶/💾 26×26), + button in toolbar far right
  - **Layout fixes**: Accurate paneH calculation, shrink=1 for textareas/divider, removed ALIGN_CENTER from split row
  - **Automation verification**: Launched app with UI2_AUTOMATION=true, navigated to CodePlay, dumped full tree via ui_drive
  - **Geometry verified**: All comboboxes (lang 110×30, bin 80×30, fmt 130×30), buttons (26×26), textareas match split row height
- **Automation tests:**
  - Tab close button hidden with 1 tab ✅
  - Add button in toolbar position ✅
  - Comboboxes have proper geometry ✅
  - Split row matches textarea heights ✅
- **Files modified:**
  - `app/Native/Panels/CodePlayPanel.php` — full rewrite
  - `vendor/yangweijie/ui2/src/Widgets/TabControl.php` — extended with closable/addable
  - `vendor/yangweijie/ui2/src/Widgets/ComboboxControl.php` — fixed width+height, added setOptions()
  - `app/Native/Backend.php` — added jsonToPlist, codeTransform, goBson/jsdoc support

### Phase 3-5: Remaining
- QR color/ECC/download
- WiFi QR full options
- WS/SSE real client
- Image Compress upload+compare
- Toast notification system
- Drag-drop reorder
- Import/export

## Test Results
| Test | Result |
|------|--------|
| Pest PHP tests (41) | ✅ |
| JSON Format | ✅ |
| JSON Validate | ✅ |
| JSON Sort Asc | ✅ |
| JSON Detect type | ✅ |
| MD Render | ✅ |
| MD Add tab | ✅ |
| Diff Compare | ✅ |
| Diff Sample | ✅ |
| Diff Swap | ✅ |
| Diff Clear | ✅ |
| All navigation | ✅ |
| CodePlay Tab close | ✅ |
| CodePlay Add button | ✅ |
| CodePlay Comboboxes | ✅ |
| CodePlay Split row | ✅ |
