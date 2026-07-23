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
| RequestTimePanel flat layout | ✅ |
| Backend requestTime structured | ✅ |
| SslMakePanel 1:1 layout | ✅ |
| FileInfoPanel drop zone | ✅ |
| DrawContext StrokeParams fix | ✅ |
| PortKillPanel 1:1 layout | ✅ |
| CanvasSpec centered text | ✅ |
| ProcessKillPanel 1:1 layout | ✅ |
| PhpObfuscatorPanel 1:1 layout | ✅ |
| WebView config editor | ✅ |
| BomCleanPanel 1:1 layout | ✅ |
| Backend bomCleanDir | ✅ |
| ChmodPanel 1:1 layout | ✅ |
| Checkbox state update fix | ✅ |
| SiteSuckerPanel settings fix | ✅ |
| TokenGeneratorPanel 1:1 layout | ✅ |
| CheckboxSpec with label fix | ✅ |
| HashPanel 1:1 layout | ✅ |
| DropdownMenuControl digest encoding | ✅ |
| EncryptPanel 1:1 layout | ✅ |
| EVP_BytesToKey CryptoJS compat | ✅ |
| TextAreaControl getValue fix | ✅ |
| JwtPanel WebView 1:1 layout | ✅ |
| Web Crypto API JWT encode/decode | ✅ |
| TimestampPanel WebView 1:1 layout | ✅ |
| Base64Panel WebView 1:1 layout | ✅ |
| Base64FilePanel WebView new | ✅ |
| Image preview functionality | ✅ |
| TimestampPanel WebView 1:1 layout | ✅ |
| datetime-local input for date picker | ✅ |
| SiteSuckerPanel 1:1 layout | ✅ |

## Files Modified This Session
| File | Lines | Changes |
|------|-------|---------|
| `app/Native/Panels/CronParserPanel.php` | 265 | Full rewrite: 28→265 lines, 3-card layout |
| `app/Native/Backend.php` | +220 | Cron methods (cronDetectMode, cronFieldHints, cronBuildExpr, cronGetNextRuns, cronParseFieldFull, cronNormalize, cronAddRange, constants) + setSecond fix |
| `app/Native/Panels/DiffPanel.php` | 279 | Toolbar moved below textareas, stats section, webH fix |
| `vendor/yangweijie/ui2/src/Widgets/ComboboxControl.php` | 297 | Added explicit width to bar row (line 88), redraw() in close() |
| `app/Native/Panels/RegexCheatsheetPanel.php` | ~120 | 完整 MDN 文档重写 (8 categories) |
| `app/Native/Panels/RegexTesterPanel.php` | ~200 | 1:1 匹配截图: 英文标签, Sample text, Diagram SVG |
| `app/Native/Panels/RequestTimePanel.php` | ~70 | 重写: 扁平结构, 11 行指标表格 |
| `app/Native/Backend.php` | +30 | requestTime() 返回结构化数组 |
| `app/Native/Panels/SslMakePanel.php` | ~80 | 重写: 匹配截图布局 (域名列表 + 文件路径) |
| `app/Native/Panels/FileInfoPanel.php` | ~90 | 重写: CanvasSpec 拖拽区 + 文件对话框 |
| `app/Native/Panels/PortKillPanel.php` | ~180 | 重写: 匹配截图布局 (搜索+表格+空状态) |
| `app/Native/Panels/ProcessKillPanel.php` | ~180 | 重写: PortKillPanel 风格 |
| `app/Native/Panels/PhpObfuscatorPanel.php` | ~150 | 重写: 匹配截图 + WebView 配置编辑器 |
| `app/Native/Panels/BomCleanPanel.php` | ~180 | 重写: 匹配原版布局 (文件夹选择器+文件类型) |
| `app/Native/Backend.php` | +40 | 新增 bomCleanDir() 递归清理函数 |
| `app/Native/Panels/ChmodPanel.php` | ~110 | 重写: 匹配截图布局 (表格+复选框+结果显示) |
| `app/Native/Panels/SiteSuckerPanel.php` | ~160 | 重写: 匹配原版布局 (URL/设置/下载) |

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

### Phase 11: RequestTimePanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **Backend::requestTime()**: 返回结构化数组（不是纯文本），包含 11 个指标
  - **RequestTimePanel**: 重写为扁平结构匹配截图
    - 标题: "URL请求计时分析"
    - 表格布局: 指标 | 数值 (11 行)
    - 使用 `LayoutNode::row()` 直接传 width（`Ui::row()` 不传 width）
    - 用 `$surface->rootLayout()` 而不是 `$surface->root()`（后者返回 Area）
  - **修复 3 个布局问题:**
    1. `Ui::row()` 不传 width → 改用 `LayoutNode::row(width: $w)`
    2. 嵌套 column 导致行挤压 → 改用扁平结构
    3. `$surface->root()` 返回 Area → 改用 `$surface->rootLayout()`
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/RequestTimePanel.php` — 重写 (~70 lines): 扁平结构 + 结构化数据
  - `app/Native/Backend.php` — `requestTime()` 返回数组 + 计算下载速度/HTTP版本

### Phase 12: SslMakePanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **SslMakePanel**: 重写匹配截图布局
    - 标题行: "SSL证书" + "生成" 按钮在右侧
    - 域名列表: 大文本框, placeholder: "Domains (Example: *.mydomain.tld), separated by line."
    - CA证书路径: 输入框 + 📁 按钮
    - 证书保存路径: 输入框 + 📁 按钮
    - 生成逻辑: 从域名列表提取第一个域名作为 CN
  - **扁平结构**: ScrollViewControl 直接接受所有子节点
  - **`LayoutNode::row()` 直接传 width**: 不用 `Ui::row()`
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/SslMakePanel.php` — 重写 (~80 lines): 匹配截图布局

### Phase 13: FileInfoPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **FileInfoPanel**: 重写匹配截图布局
    - 标题: "文件信息"
    - 拖拽区: CanvasSpec 绘制虚线边框 + 云朵图标 + "点击选择文件"
    - "选择文件" 按钮: 打开原生文件对话框
    - 文件路径显示: 选中后显示路径
    - 文件信息结果: TextAreaControl 显示
  - **修复 3 个 DrawContext 错误:**
    1. `strokeRect()` 第6个参数需要 `StrokeParams::solid()`，不是裸 float
    2. `Path::__construct()` 需要 `DrawFillMode` 枚举，不是 int
    3. 简化箭头绘制，用 `fillRect` 代替 `Path`
  - **libui 限制**: 不支持文件拖拽，改为点击选择
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/FileInfoPanel.php` — 重写 (~90 lines): CanvasSpec 拖拽区 + 文件对话框

### Phase 14: PortKillPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **PortKillPanel**: 重写匹配截图布局
    - 标题: "端口查杀" + ⭐ 图标
    - 搜索框: placeholder "Please Input Port" + 🔍 按钮
    - 按钮: "清除选择" (outline) + "消除全部" (danger)
    - 表格: checkbox, PID, User, COMMAND 列
    - 空状态: "暂无数据" 居中显示 (CanvasSpec)
  - **扁平结构**: ScrollViewControl 直接接受子节点
  - **CanvasSpec 居中文本**: 用 `($cw - $textW) / 2, ($ch - 13) / 2` 计算居中位置
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/PortKillPanel.php` — 重写 (~180 lines): 匹配截图布局

### Phase 15: ProcessKillPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **ProcessKillPanel**: 用 PortKillPanel 风格重写
    - 标题: "进程查杀" + ⚡ 图标
    - 搜索框: placeholder "Please Input Process Name" + 🔍 按钮
    - 按钮: "清除选择" (outline) + "消除全部" (danger)
    - 表格: checkbox, PID, User, COMMAND 列
    - 空状态: "暂无数据" 居中显示 (CanvasSpec)
  - **扁平结构**: 与 PortKillPanel 一致
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/ProcessKillPanel.php` — 重写 (~180 lines): PortKillPanel 风格

### Phase 16: PhpObfuscatorPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **PhpObfuscatorPanel**: 重写匹配截图布局
    - 标题行: "PHP混淆" + ☆ + "生成" 按钮
    - PHP版本: 输入框 placeholder "混淆用PHP版本"
    - 源文件: 输入框 + 📁 按钮
    - 输出路径: 输入框 + 📁 按钮
    - 高级设置: WebView 配置编辑器 (Catppuccin 暗色主题)
    - 配置编辑器: 默认 yakpro-po.cnf 配置示例
  - **WebView 配置编辑器**: 始终显示 300px 高度
    - 暗色主题 (Catppuccin Mocha)
    - 工具栏: 保存配置、重置默认、清空
    - 支持 Tab 缩进
  - **文件选择**: 使用 FilePickerDialog::pick() 打开原生文件对话框
  - **修复问题**: rebuild ScrollView 后 layout 不更新 → 改为始终显示编辑器
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/PhpObfuscatorPanel.php` — 重写 (~150 lines): 匹配截图 + WebView 编辑器

### Phase 17: BomCleanPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **BomCleanPanel**: 重写匹配原版布局
    - 标题: "文件Bom清理" + ☆ + "清理" 按钮
    - 目录输入: 输入框 + 📁 按钮 (使用 `openFolder()` 文件夹选择器)
    - 排除目录: TextAreaControl, 默认: .idea, .git, .svn, .vscode, node_modules
    - File Type: 显示文件类型摘要 (前 10 种 + 溢出提示)
    - 结果: TextAreaControl 显示清理结果
  - **Backend 新增**: `bomCleanDir($path, $exclude)` 递归清理目录 BOM
  - **状态持久化**: 静态变量 `$lastPath`, `$fileTypeText` 保持窗口大小变化时的状态
  - **高度调整**: File Type 区域从 80px → 200px 避免内容截断
  - **文件夹选择器**: 用 `$win->dialogs()->openFolder()` 替代 `FilePickerDialog::pick()`
  - **TextareaControl 初始化**: 在 `bind()` 后设置 `TextAreaSpec` 避免默认值丢失
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/BomCleanPanel.php` — 重写 (~180 lines): 匹配原版布局
  - `app/Native/Backend.php` — 新增 `bomCleanDir()` 递归清理函数

### Phase 18: ChmodPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **ChmodPanel**: 重写匹配截图布局
    - 标题: "Chmod计算器" + ☆
    - 权限表格: Owner (u), Group (g), Public (o) 列
    - 行: Read (4), Write (2), Execute (1)
    - 每个单元格有复选框，点击实时更新
    - 结果显示: CanvasSpec 绘制大号绿色八进制和符号表示
    - 命令显示: "chmod 755 path" + 📋 复制按钮
  - **修复 2 个 bug:**
    1. `foreach ($groups as $gi)` → `foreach ($groups as $gi => $gName)` 获取索引
    2. Checkbox 状态更新: 找到节点并更新 `CheckboxSpec(checked: ...)`
  - **扁平结构**: ScrollViewControl 直接接受所有子节点
  - **CanvasSpec 居中文本**: 用 `($cw - $textW) / 2` 计算居中位置
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/ChmodPanel.php` — 重写 (~110 lines): 匹配截图布局

### Phase 19: SiteSuckerPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **SiteSuckerPanel**: 重写匹配原版布局
    - 标题: "网站抓取" + ☆ + ⚙️ 设置按钮
    - URL 输入框
    - url(0/0) + 搜索框
    - host + 搜索框
    - 暂无数据状态
    - 设置区域 (inline): 保存路径、窗口数量、网络代理、超时时间、图片/视频限制、页面限制、域名排除
  - **设置按钮**: 在标题行添加 ⚙️ 按钮
  - **设置切换**: 点击 ⚙️ 切换显示/隐藏设置区域
  - **扁平结构**: ScrollViewControl 直接接受所有子节点
  - **下载功能**: SiteSucker 下载网站
- **已知问题**: ScrollViewControl 不支持 split/side-by-side 布局; height=0 toggle 在 bind() 后不触发重排
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/SiteSuckerPanel.php` — 重写 (~160 lines): 匹配原版布局

### Phase 20: SiteSuckerPanel 设置修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **修复设置按钮不显示**: `$settingsBtn` 创建了但没加到布局中 → 添加到标题行
  - **修复设置面板布局错乱**: ScrollViewControl 不支持 split 布局 → 改用内联显示
  - **修复首次点击无效**: FlexLayout 在 bind() 后不重新计算 (框架规则 #6) → 设置区域始终显示
  - **最终方案**: 设置区域始终可见，滚动查看
- **根本原因**: FlexLayout 运行一次后不再重新计算，改变 height 属性不会触发布局更新
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/SiteSuckerPanel.php` — 修复设置按钮和布局

### Phase 21: TokenGeneratorPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **TokenGeneratorPanel**: 重写匹配原版布局
    - 标题: "Token 生成器" + 🔑 图标
    - 字符类型: CheckboxSpec 复选框 (大写/小写/数字/符号)
    - Token 长度: −/+/− 按钮 (1-512)
    - 生成按钮: "生成 Token" (filled)
    - 结果显示: Label 显示生成的 token
    - 复制按钮: "📋 复制"
  - **修复 3 个问题:**
    1. ButtonSpec 模拟复选框 → 改用 CheckboxSpec(checked, label)
    2. TextAreaSpec 更新不触发重绘 → 改用 Label + 静态变量
    3. 点击生成按钮无反应 → 用 updateOutput() 方法更新显示
  - **静态变量存储**: `$token`, `$upper`, `$lower`, `$numbers`, `$symbols`, `$length`
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/TokenGeneratorPanel.php` — 重写 (~170 lines): 匹配原版布局

### Phase 22: HashPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **HashPanel**: 重写匹配原版布局
    - 标题: "Hash 文本" + ☆
    - 输入区: "Your text to hash:" + TextAreaControl
    - 编码选择: DropdownMenuControl (Hex/Bin/Base64/Base64url)
    - 计算按钮: "计算" (filled)
    - 哈希结果表格: 8 种算法单独显示
      - MD5, SHA1, SHA256, SHA224, SHA512, SHA384, SHA3, RIPEMD160
      - 每行: 算法名 + 哈希值 + 📋 复制按钮
    - 实时计算: 输入文本后自动更新
  - **修复 2 个问题:**
    1. Digest Encoding 无法切换 → 改用 DropdownMenuControl
    2. 二进制编码哈希值溢出 → 截断显示 (60 字符)，完整值存储在静态变量
  - **静态变量存储**: `$hashes` 保存完整哈希值用于复制
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/HashPanel.php` — 重写 (~130 lines): 匹配原版布局

### Phase 23: EncryptPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **EncryptPanel**: 重写匹配原版布局
    - 标题: "加密解密" + 🔐 图标
    - 两列并排: 加密 | 解密
    - 加密列: 明文输入 + 密钥 + 算法选择 + 加密结果 + 加密按钮
    - 解密列: 密文输入 + 密钥 + 算法选择 + 解密结果 + 解密按钮
    - 算法选择: AES, TripleDES, Rabbit, RC4 (按钮切换)
  - **修复 4 个问题:**
    1. TextAreaSpec 更新不触发重绘 → 改用 Label
    2. Backend::decrypt 只支持 AES-256-CBC → 实现 CryptoJS 兼容 (EVP_BytesToKey)
    3. TextAreaControl 值获取错误 → 使用 `getValue()` 而不是 `spec->value`
    4. EncryptPanel 传多余参数 → 移除 `$algo` 参数
  - **EVP_BytesToKey 实现**: 修正 key derivation 使用前一个 block 的最后 16 字节
  - **静态变量存储**: `$controls` 保存 TextAreaControl 引用
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/EncryptPanel.php` — 重写 (~180 lines): 匹配原版布局
  - `app/Native/Backend.php` — 修正 EVP_BytesToKey 实现 + CryptoJS 兼容

### Phase 24: JwtPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **JwtPanel**: 用 WebView 重写匹配原版布局
    - 两列网格: Encode | Decode
    - Encode: Algorithm + Secret + Header JSON + Payload JSON + JWT 输出 + Encode 按钮
    - Decode: Algorithm + Secret + JWT 输入 + 签名验证状态 + Header + Payload + Decode 按钮
    - 使用 Web Crypto API (HMAC-SHA256/384/512)
    - 暗色主题 (Catppuccin Mocha)
    - 响应式布局: 两列网格，小屏自动单列
  - **修复 3 个问题:**
    1. TextAreaControl spec 更新不触发重绘 → 改用 WebView
    2. 嵌套 column 布局错乱 → 改用扁平结构
    3. encode/decode 按钮无效 → 改用 Web Crypto API 直接处理
  - **WebView 实现**: 使用 HTML/CSS/JS + Web Crypto API
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/JwtPanel.php` — 重写 (~150 lines): WebView 实现

### Phase 25: TimestampPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **TimestampPanel**: 用 WebView 重写匹配原版布局
    - 当前 Unix 时间戳 (自动更新, 点击复制)
    - Unix → Date 转换 (秒/毫秒单位选择)
    - Date → Unix 转换 (datetime-local 选择器 + 单位选择)
    - 暗色主题 (Catppuccin Mocha)
  - **修复 2 个问题:**
    1. 原版无日期时间选择器 → 改用 WebView `<input type="datetime-local">`
    2. 原版静态布局 → 改用响应式两列网格
  - **WebView 实现**: 使用 HTML/CSS/JS + 原生 datetime-local input
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/TimestampPanel.php` — 重写 (~100 lines): WebView 实现

### Phase 26: Base64Panel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **Base64Panel**: 用 WebView 重写匹配原版布局
    - 两列网格: 字符串 → Base64 | Base64 → 字符串
    - URL-safe 开关 (每列独立)
    - 输入/输出文本框
    - 📋 复制按钮
    - 实时编码/解码 (oninput)
    - 无效 Base64 错误提示
    - 暗色主题 (Catppuccin Mocha)
  - **WebView 实现**: 使用 HTML/CSS/JS + btoa/atob
- **Pest:** 92 passed, 965 assertions
- **Files modified:**
  - `app/Native/Panels/Base64Panel.php` — 重写 (~120 lines): WebView 实现

### Phase 27: Base64FilePanel 新建 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **Base64FilePanel**: 新建 WebView 实现文件 Base64 转换器
    - 两列网格: 文件 → Base64 | Base64 → 文件
    - 文件 → Base64: 拖拽/选择文件，转换为 Base64，文件信息，复制/下载
    - Base64 → 文件: 粘贴 Base64，文件名输入，文件信息，下载
    - 图片预览: 点击预览按钮显示 Base64 图片
    - 文件类型检测: PNG, JPEG, GIF, PDF, ZIP, GZIP, ELF
    - 暗色主题 (Catppuccin Mocha)
  - **注册到 Catalog**: 添加 'base64-file-converter' 工具
  - **注册到 NativeApp**: 添加 Base64FilePanel 面板
  - **图片预览功能**: 自动检测图片类型，显示预览
- **Pest:** 92 passed, 969 assertions
- **Files modified:**
  - `app/Native/Panels/Base64FilePanel.php` — 新建 (~280 lines): WebView 实现
  - `app/Native/Catalog.php` — 添加工具条目和面板映射
  - `app/Native/NativeApp.php` — 注册 Base64FilePanel

### Phase 28: 崩溃分析 — WebView 子窗口竞态条件 ⚠️
- **Completed:** 2026-07-21
- **问题描述**: PHP 进程在关闭窗口时崩溃 (EXC_BAD_ACCESS, SIGSEGV at 0x0000000000000000)
- **崩溃位置**: `uiControlDestroy` 通过 FFI 调用时函数指针为 null
- **根本原因**: libui + PebView bridge 的底层问题 — 12 个 WebView 子窗口在 Window 关闭时的 destroy 顺序与 bridge 库的 uninit 存在竞态条件
- **框架规则 #10**: WebView creates a borderless child window — NOT a Composite. Destroying native browser windows is crash-prone (segfaults in the FFI bridge under rapid create/destroy cycles)
- **已知限制**: 这是 ui2 框架的底层问题，非 PHP 代码 bug
- **可能的缓解方案**:
  1. 在关闭窗口前手动销毁所有 WebView overlays
  2. 减少同时存在的 WebView 面板数量
  3. 需要在 ui2 框架层面修复 WebView 生命周期管理
- **影响范围**: 所有使用 WebViewSpec 的面板 (RegexTester, JWT, Timestamp, Base64, Base64File, PhpObfuscator, Markdown 等)
- **状态**: 已知问题，需要框架层面修复

### Phase 29: UrlPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **UrlPanel**: 用 WebView 重写匹配原版
    - 两列网格: URL 编码 | URL 解码
    - URL 编码: encodeURIComponent 实时转换
    - URL 解码: decodeURIComponent 实时转换
    - 每列: 输入文本框 + 输出文本框 + 📋 复制按钮
    - 暗色主题 (Catppuccin Mocha)
- **Pest:** 92 passed, 969 assertions
- **Files modified:**
  - `app/Native/Panels/UrlPanel.php` — 重写 (~120 lines): WebView 两列布局

### Phase 30: EscapeHtmlPanel 1:1 修复 ✅
- **Completed:** 2026-07-21
- **Actions taken:**
  - **EscapeHtmlPanel**: 用 WebView 重写匹配原版
    - 两列网格: HTML 转义 | HTML 反转义
    - 转义: textContent → innerHTML 实时转换
    - 反转义: innerHTML → textContent 实时转换
    - 每列: 输入文本框 + 输出文本框 + 📋 复制按钮
    - 暗色主题 (Catppuccin Mocha)
- **Pest:** 92 passed, 969 assertions
- **Files modified:**
  - `app/Native/Panels/EscapeHtmlPanel.php` — 重写 (~90 lines): WebView 两列布局
