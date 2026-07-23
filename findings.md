# Findings & Decisions
<!-- 
  WHAT: 你任务的知识库。存储你发现和下定决心的一切。
  WHY: 上下文窗口是有限的。这个文件是你的"外部记忆" - 持久且无限。
  WHEN: 在任何发现后更新，特别是在 2 个查看/浏览器/搜索操作后（2-Action Rule）。
-->

## Requirements
<!-- Captured from user request -->
- 建立完整的 FlyEnv 工具箱测试体系
- 集成 Pest PHP 测试框架
- 集成 Vitest 前端测试框架
- 修复表单控件高度对齐的 UI 问题
- 确保所有测试通过且易于运行
- 1:1 复刻 webview 版本的 Cron 解析工具（3-card layout）

## Research Findings

### Cron Engine (Backend.php)
- Linux cron uses 5 fields: 分 时 日 月 周 (minute hour day month weekday)
- Seconds cron uses 6 fields: 秒 分 时 日 月 周
- Field syntax: `*` (all), `N` (specific), `N-M` (range), `N/S` (step), `N,M,O` (list)
- Aliases: jan-dec (months), sun-sat (days) — case-insensitive
- `* * * * *` = every minute, `*/5 * * * *` = every 5 minutes, `0 9 * * 1-5` = weekdays at 9am
- Scan window: 1 year (525600 minutes) — covers all practical use cases
- PHP `DateTimeImmutable` has NO `setSecond()` method — must use `setTime()`

### CronParserPanel Webview Layout (3-card)
- **Card 1 (生成):** Mode buttons (Auto/Linux 5/Seconds 6) → Schedule dropdown → conditional inputs (interval, hour/minute, dow, dom) → expression preview → "使用此表达式" button
- **Card 2 (解析):** Expression field with live parsing → mode tag + hint tag → 5 preset buttons → count control
- **Card 3 (下次执行):** Error display → results table with # and Run time columns

### DiffPanel Webview Layout
- Toolbar: 6 outline buttons (Sample/Swap/Clear/Prev/Next/Copy) — NO Compare button
- Buttons are BELOW textareas, not above
- Stats: colored badges (green/red/orange/gray) with "对比摘要" header
- Diff runs automatically on input via `oninput="diffRun()"`

### ComboboxControl
- Bar row (field + caret) was missing explicit `width` — caused FlexLayout to give it `$base = 0`
- Adding `width: $this->width` to bar row fixes text overlap/ghost text issue
- `close()` must call `surface->redraw()` after `refreshFocusables()` to clear overlay visually
- `truncateForField()`: max chars = floor((width-34-16)/7.5), for width=200 = 20 chars

### WebView for Complex Tools
- **JWT, Regex, Timestamp**: Tools with rich input/output benefit from HTML/CSS/JS instead of fighting ui2 limitations
- **Web Crypto API**: Browser-native HMAC-SHA256/384/512 — no PHP backend needed for JWT
- **No native date/time picker in ui2**: Must use WebView `<input type="datetime-local">`
- **onChange callback pattern**: `$ta->onChange(function (string $v) { self::$values[$key] = $v; })` — reliable way to capture user input without holding control references

## Technical Decisions
| Decision | Rationale |
|----------|-----------|
| 使用 Pest PHP 而非 PHPUnit | Pest 语法更简洁，适合快速编写测试 |
| 使用 Vitest 而非 Jest | Vitest 与 Vite 生态更匹配，速度快 |
| 使用 jsdom 模拟 DOM | 不需要真实浏览器，适合单元测试 |
| 统一表单控件高度为 34px | 确保视觉对齐，提升 UI 一致性 |
| 移除 select 系统样式 | 允许 CSS 完全控制高度和外观 |
| 标题栏固定在顶部 | 将 titleBar/tabBar 放到 ScrollViewControl 外层 column，内容区域单独滚动 |
| ui2 列中列必须设高度 | FlexLayout 对 column-in-column 无高度时 basis=0 导致折叠为0 |
| Cron 解析纯 PHP 后端 | 不依赖 JS 引擎，支持 5/6 字段 + 别名 + 1年扫描窗口 |
| CronParserPanel 扁平布局 | ScrollViewControl 内部禁止嵌套 Ui::column()，FlexLayout 会给子 column 赋 h=0 |
| ScrollViewControl contentHeight > height | 否则 ScrollViewRenderer 不渲染滚动条 (line 90: contentHeight <= viewportHeight → null) |
| DiffPanel toolbar 在 textareas 下方 | 匹配 webview 原版布局 |
| Cron 全自动解析初始加载 | 避免用户看到空的 "下次执行" 区域 |
| GitMemoPanel 用 CanvasSpec 做代码块 | CanvasSpec 支持深色背景 + 自定义绘制，适合语法高亮代码块 |
| GitMemoPanel tokenizer 分词 | 支持 git/subcommand/flag/string/placeholder/operator 6 种 token 类型 |
| CanvasSpec + 按钮用扁平行 | CanvasSpec 是叶子节点不能嵌套，用 `Ui::row([$canvas, $btn])` 组合 |

## Issues Encountered
| Issue | Resolution |
|-------|------------|
| Pest 测试断言失败（数组键检查） | 改用 PHPUnit::hasKey() 和 expect()->toBeIn() |
| Vitest 全局变量不同步 | 使用 globalThis 而非局部变量 |
| select 高度仍然不对齐 | 添加 appearance: none 移除系统样式 |
| CodeLibraryPanel 标题栏+标签不可见 | 将 titleBar/tabBar 移出 ScrollViewControl，放到外层 column |
| CodeLibraryPanel 初始加载空状态 | 添加 auto-select first group 逻辑（lines 59-65） |
| ui2 FlexLayout 列中列无高度→折叠为0 | 必须显式设置 column->style->height |
| ScrollViewControl padding 在视口内部 | padding 添加在 viewport row 内部，不是外部 |
| CronParserPanel 界面重叠 | 嵌套 Ui::column() 导致 h=0，必须扁平化 |
| ComboboxControl 下拉字体重叠 | bar row 缺少 width，FlexLayout 给 $base=0 |
| ComboboxControl 关闭后残留文字 | close() 未调用 redraw()，需在 refreshFocusables 后 redraw |
| DateTimeImmutable 无 setSecond() | 改用 setTime() 重建时间 |
| CronParserPanel 内容被截断 | contentHeight 必须 > height 才显示滚动条 |
| Automation snapshot 返回空数组 | toNode() 不处理 Surface/Window，仅处理 SemanticsNode |
| libui Area 无 scroll 回调 | AreaDelegate 无 scroll 方法，滚动仅支持键盘箭头/拖拽/滚动条拖动 |
| GitMemoPanel 嵌套 row 导致 token 垂直堆叠 | 改用扁平行：所有 token + Copy 按钮放在同一个 Ui::row |
| GitMemoPanel DialogCardSpec 背景是浅色 | 改用 CanvasSpec 自定义绘制深色背景 |
| DrawContext::drawString 参数顺序错误 | 正确顺序: text, font, color, x, y |
| GitMemoPanel 语法高亮颜色不明显 | 使用 Tokyo Night 配色: cmd=蓝, sub=绿, flag=灰, ph=紫, op=青 |
| RequestTimePanel 行挤压/重叠 | `Ui::row()` 不传 width → 改用 `LayoutNode::row(width: $w)` |
| RequestTimePanel 嵌套 column 导致行压缩 | 改用扁平结构，ScrollViewControl 直接接受所有子节点 |
| LayoutNode::find() 参数类型错误 | `$surface->root()` 返回 Area → 改用 `$surface->rootLayout()` |
| strokeRect() 参数类型错误 | 第6个参数需要 `StrokeParams::solid()`，不是裸 float |
| Path::__construct() 参数类型错误 | 需要 `DrawFillMode` 枚举，不是 int |
| libui 不支持文件拖拽 | 改为点击选择文件 + 原生文件对话框 |

## Resources
- Pest PHP 文档：https://pestphp.com/docs/installation
- Vitest 文档：https://vitest.dev/guide/
- jsdom 文档：https://github.com/jsdom/jsdom
- FlyEnv 工具箱项目：/Volumes/data/git/php/phptools2/
- 测试文件：tests/Unit/FlyEnvWebAppTest.php、tests/JS/toolbox.test.js
- CodeLibraryPanel: app/Native/Panels/CodeLibraryPanel.php (~881 lines)
- CronParserPanel: app/Native/Panels/CronParserPanel.php (265 lines)
- DiffPanel: app/Native/Panels/DiffPanel.php (279 lines)
- GitMemoPanel: app/Native/Panels/GitMemoPanel.php (~280 lines)
- Backend: app/Native/Backend.php
- ScrollViewControl: vendor/yangweijie/ui2/src/Widgets/ScrollViewControl.php (282 lines)
- ComboboxControl: vendor/yangweijie/ui2/src/Widgets/ComboboxControl.php (297 lines)
- FlexLayout: vendor/yangweijie/ui2/src/Layout/FlexLayout.php (220 lines)
- ScrollViewRenderer: vendor/yangweijie/ui2/src/Rendering/WidgetRenderer/ScrollViewRenderer.php (175 lines)
- TextFieldRenderer: vendor/yangweijie/ui2/src/Rendering/WidgetRenderer/TextFieldRenderer.php (101 lines)
- CanvasSpec: vendor/yangweijie/ui2/src/Rendering/WidgetRenderer/CanvasSpec.php (45 lines) — 自定义绘制回调
- CanvasRenderer: vendor/yangweijie/ui2/src/Rendering/WidgetRenderer/CanvasRenderer.php (57 lines) — 支持 background hex
- DrawContext: vendor/helgesverre/libui/src/Draw/DrawContext.php (303 lines) — drawString 参数: text, font, color, x, y
- AreaDelegate: vendor/helgesverre/libui/src/AreaDelegate.php (67 lines) — NO scroll callback
- AreaMouseEvent: vendor/helgesverre/libui/src/Draw/Params/AreaMouseEvent.php (82 lines) — NO scroll data

## Visual/Browser Findings
- CronParserPanel 3-card layout: mode buttons, schedule combo, interval/time fields, presets, count — all visible
- "📅 下次执行" header visible but results below clipped at default window size
- Automation click works (POST /drive returns ok:true) but snapshot always empty (toNode() broken)
- System memory issues: php process reaching 4.66GB, macOS Force Quit dialog appearing

## Key Framework Rules (Permanent Reference)
1. **NEVER nest `Ui::column()` inside ScrollViewControl's content column** — FlexLayout gives column children h=0
2. **`LayoutNode::row()` WITHOUT explicit width → children overflow** — ALWAYS set `width: $w` on rows inside columns
3. **ScrollViewControl `contentHeight` MUST be > `height`** for scrollbar to appear
4. **FlexLayout basis**: `$fixed = $isRow ? $cs->width : $cs->height; $base = $fixed ?? $cs->basis ?? 0.0`
5. **ui2 has no `display:none`** — use `height=0` to hide
6. **FlexLayout runs ONCE during `bind()`** — updating style->width after bind does NOT update $w
7. **libui Area has NO scroll callback** — AreaDelegate only has draw/mouse/mouseCrossed/dragBroken/key
8. **DrawContext::drawString() 参数顺序**: `text, font, color, x, y` — 不是 `x, y, text, font, color`
9. **CanvasSpec 适合代码块背景**: 用 `background` 参数指定深色背景色 (hex float)，在回调中用 `fillRect` + `drawString` 绘制
10. **CanvasSpec 是叶子节点**: 不能有子节点，需要和按钮组合时用 `Ui::row([$canvas, $button])` 扁平结构
11. **`Ui::row()` 不传 width**: 需要显式 width 时用 `LayoutNode::row(width: $w)` 直接创建
12. **`$surface->root()` 返回 Area**: 需要 LayoutNode 时用 `$surface->rootLayout()`
13. **`DrawContext::strokeRect()` 参数**: 第6个参数是 `?StrokeParams`，用 `StrokeParams::solid(width)` 创建
14. **`Path` 构造函数**: 需要 `DrawFillMode` 枚举（`DrawFillMode::Winding`），不是 int
15. **libui 无文件拖拽**: AreaDelegate 没有 drop 事件，用 `Dialogs::openFile()` 代替
16. **ScrollView rebuild 后 layout 不更新**: FlexLayout 在 bind() 后不重新计算，改用始终显示或静态变量控制
17. **WebView 适合配置编辑器**: 用 WebViewSpec 嵌入 HTML 编辑器，支持语法高亮和工具栏
18. **文件夹选择器**: 用 `$win->dialogs()->openFolder()` 替代 `FilePickerDialog::pick()`
19. **静态变量保持状态**: 窗口大小变化时面板重建，用静态变量 `$lastPath`, `$fileTypeText` 保持状态
20. **LabelSpec 高度**: 多行文本需要足够高度，10 行约需 200px
21. **foreach 索引获取**: `foreach ($arr as $val)` 只能获取值，`foreach ($arr as $idx => $val)` 才能获取索引
22. **CheckboxSpec 状态更新**: 点击后需要找到节点并更新 `$node->spec = new CheckboxSpec(checked: ...)`
23. **TextAreaControl 值获取**: 使用 `getValue()` 方法，不是 `spec->value`（spec 只保存初始值）
24. **EVP_BytesToKey**: CryptoJS 兼容的 key derivation 使用前一个 block 的最后 16 字节，不是整个累积的 key
25. **TextAreaSpec 更新不触发重绘**: 改用 LabelSpec 显示输出结果
26. **WebView 适合复杂交互工具**: JWT、正则测试等复杂工具用 WebView 实现更可靠
27. **Web Crypto API**: 浏览器原生支持 HMAC-SHA256/384/512，比 OpenSSL 更简单
23. **ScrollViewControl 不支持分栏**: 子节点始终垂直堆叠，不能并排显示主内容和设置面板
24. **height=0 切换在 bind() 后不重排**: `style->height` 修改后 FlexLayout 不会重新计算，需要其他方式

*Update this file after every 2 view/browser/search operations*
*This prevents visual information from being lost*
