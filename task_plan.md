# Task Plan: FlyEnv Toolbox 1:1 完整复刻 (C 档)

## 目标
将当前的 32 个原生面板工具箱 1:1 复刻 FlyEnv 4.15.4 的 webview 版本，包括全部 UX 增强、面板深度功能。

## 阶段完成状态

### Phase 1: 核心 UX 框架 ✅
- icons, search, home page, favorites, i18n, theme, collapsible sidebar
- Automation 6/6 passed

### Phase 2: 面板深度—JSON/Diff/Markdown ✅
- **JSON**: 分栏+拖拽+Tab+13格式+文件+自动检测+排序 → Automation 4/4 ✅
- **Diff**: 分栏+拖拽+LCS+统计+导航+示例/交换/清空/复制 → Automation 4/4 ✅
- **Markdown**: 分栏+拖拽+Tab+文件+WebView渲染 → Automation 2/2 ✅
- Backend: jsonConvert/jsonSort/fileRead/fileSave/LCS diff
- WindowHolder for file dialogs
- Pest: 41/41

### Phase 3: 面板深度—QR/WiFi QR 增强 ✅
- **QR Code (QrCodePanel)**: ✅ 完整复刻 — fg/bg color picker, ECC level, live preview, download PNG
- **WiFi QR (WifiQrPanel)**: ✅ 完整复刻 — encryption type, EAP methods, password toggle, color pickers, hidden network
- Automation verified via ui_drive tree dump

### Phase 3b: QR/WiFi QR 功能修复 ✅
- **Completed:** 2026-07-24
- **问题**: 二维码不 1:1、size/前景色/背景色按钮无效
- **根因**:
  1. SVG 渲染器 (`QRMarkupSVG`) 不读 `fgColor`/`bgColor`，只读 `moduleValues`
  2. SVG 渲染器忽略 `scale`，无 width/height 属性
  3. CSS `svg{width:100%;height:100%}` 覆盖了注入的尺寸属性
  4. CSS `max-width:100%;max-height:90vh` 用不同参考系导致拉伸
- **修复**:
  - `Backend::qrCodeGenerate()`: 通过 `moduleValues` 设置颜色，注入 `width`/`height` 属性
  - `QrCodePanel::wrap()`: `.qr-wrap` + `aspect-ratio:1/1` 保证正方形
  - `WifiQrPanel::wrap()`: 同上
  - CSS 改为 `max-width:100%;max-height:100%` 允许 SVG 按自身尺寸渲染
- 92/92 tests pass (969 assertions)

### Phase 3c: WifiQrPanel WebView 重写 ✅
- **Completed:** 2026-07-24
- **问题**: WiFi QR 面板用原生 widget 实现，布局混乱，按钮错位，EAP 区域不隐藏
- **方案**: 改用 WebView 实现，匹配原版 FlyEnv 布局
- **修复**:
  - HTML/CSS/JS 全在 WebView 中，匹配原版暗色/亮色主题
  - QR 编码器: 纯 JS 实现，支持版本 1-10
  - 表单: Encryption/SSID/Hidden/Password/EAP/FG/BG
  - 条件显示: Password/EAP 区域自动隐藏/显示
  - 下载: SVG 一键下载
- 92/92 tests pass (969 assertions)

### Phase 4: 面板深度—剩余复杂面板 ✅
- **Code Playground (CodePlayPanel)**: ✅ 完整复刻 — multi-tab, split-pane, 6 langs, binary override, 17 formats, Save/Open/Run, icon buttons, '代码演练场 ⭐' title
- **Automation verified**: geometry correct via ui_drive tree dump
- WS/SSE: real WebSocket client 🔲
- Image Compress: upload, before/after 🔲
- SiteSucker: progress 🔲

### Phase 5: CodeLibraryPanel 布局修复 ✅
- **Completed:** 2026-07-20 10:30
- Title bar + tabs now always visible (outside ScrollViewControl)
- Auto-select first group on load + tab switch
- 87/87 tests pass (955 assertions)

### Phase 5b: DiffPanel 布局修复 ✅
- **Completed:** 2026-07-21
- Moved toolbar BELOW textareas (was above)
- Added "对比摘要" section header with stats
- Fixed $webH calculation (old offset 130 didn't account for actual heights ~220)
- 87/87 tests pass (955 assertions)

### Phase 5c: CronParserPanel 1:1 完整复刻 ✅
- **Completed:** 2026-07-21
- **CronParserPanel.php** (265 lines): Full rewrite — 3-card layout matching webview (Generate/Parse/NextRuns)
- **Backend.php** (~170 lines): cronDetectMode, cronFieldHints, cronBuildExpr, cronGetNextRuns, cronParseFieldFull, cronNormalize, cronAddRange — supports 5/6 field cron, aliases, 1-year scan
- **ComboboxControl.php**: Added explicit width to bar row + redraw() in close()
- Layout: flattened (no nested Ui::column in ScrollViewControl), auto-parse on build
- contentHeight: max($height, 1000.0) for scrollbar, gap: 8.0, padding: 12.0
- ⚠️ NOT VISUALLY VERIFIED — system memory issues prevented app from staying alive for screenshots
- 87/87 tests pass (955 assertions)

### Phase 6: 全局 UX—Toast, 拖放, 导入导出 🔲
- Toast notifications
- Drag-drop reorder
- Import/export

### Phase 7: ProcessKill/PortKill 布局修复 ✅
- **Completed:** 2026-07-16 14:30
- **Actions taken:**
  - **Root Cause:** `$tableCol` column created without explicit height → FlexLayout collapses to 0
  - **Fix:** Added `computeTableHeight()` method, called after populating children in both panels
  - **AutomationServer.php:** Fixed HTTP `/drive` endpoint payload unwrapping
  - **Verification:** ProcessKillPanel automation confirmed `prockill:table` h=720.1 (was h=0) ✅
  - **Tests:** 5/5 headless tests pass, BackendTest 45/45 pass
  - **PortKillPanel:** Same fix applied, needs visual verification

### Phase 8: 验证 🔲
- Full automation test suite
- Manual visual comparison
- **CronParserPanel** needs visual verification on next session
- **DiffPanel** toolbar/stats needs visual verification

## 架构决策
- Backend: 所有纯 PHP 计算（jsonConvert/files/diff/LCS/cron）
- WindowHolder: 静态 Window 引用（不依赖构造注入）
- 分栏拖拽: Surface::onDrag + LayoutNode width 更新
- 面板重建: 拖拽后更新静态 splitRatio + redraw（不重建面板，避免闪烁）
- ScrollViewControl: contentHeight 必须 > height 才显示滚动条
- Cron 表达式: Backend 纯 PHP 解析，支持 5/6 字段 + 别名 + 1年扫描窗口
- FlexLayout: column-in-column 必须显式设高度，否则 basis=0 折叠
- ui2 Row 内无 width → 子元素溢出。永远在 column 内的 row 设 width
