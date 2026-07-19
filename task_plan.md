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

## 架构决策
- Backend: 所有纯 PHP 计算（jsonConvert/files/diff/LCS）
- WindowHolder: 静态 Window 引用（不依赖构造注入）
- 分栏拖拽: Surface::onDrag + LayoutNode width 更新
- 面板重建: 拖拽后更新静态 splitRatio + redraw（不重建面板，避免闪烁）
