# Findings & Decisions
<!-- 
  WHAT: 你任务的知识库。存储你发现和下定决心的一切。
  WHY: 上下文窗口是有限的。这个文件是你的"外部记忆" - 持久且无限。
  WHEN: 在任何发现后更新，特别是在 2 个查看/浏览器/搜索操作后（2-Action Rule）。
-->

## Requirements
<!-- 
  WHAT: 用户要求的，分解为具体需求。
  WHY: 保持需求可见，这样你就不会忘记你在构建什么。
  WHEN: 在阶段 1（需求与发现）期间填写这个。
  EXAMPLE:
    - 命令行界面
    - 添加任务
    - 列出所有任务
    - 删除任务
    - Python 实现
-->
<!-- Captured from user request -->
- 建立完整的 FlyEnv 工具箱测试体系
- 集成 Pest PHP 测试框架
- 集成 Vitest 前端测试框架
- 修复表单控件高度对齐的 UI 问题
- 确保所有测试通过且易于运行

## Research Findings
<!-- 
  WHAT: 从网络搜索、文档阅读或探索中发现的关键信息。
  WHY: 多模态内容（图像、浏览器结果）不会持久化。立即写下来。
  WHEN: 在每次 2 个查看/浏览器/搜索操作后更新这个部分（2-Action Rule）。
  EXAMPLE:
    - Python 的 argparse 模块支持子命令，用于简洁的 CLI 设计
    - JSON 模块轻松处理文件持久化
    - 标准模式：python script.py <command> [args]
-->
<!-- Key discoveries during exploration -->
- Pest PHP 使用描述性函数名（test('...', fn())）使测试可读
- Vitest 与 Vite 生态更匹配，速度快，适合前端测试
- jsdom 提供模拟的 DOM 环境，适合单元测试
- CSS appearance: none 可以移除 select 的系统默认样式
- 自定义 SVG 背景可以模拟下拉箭头

## Technical Decisions
<!-- 
  WHAT: 你已做出的架构和实现选择，以及推理。
  WHY: 你会忘记为什么选择了某个技术或方法。这个表格保留了知识。
  WHEN: 每当你做出重要技术选择时更新。
  EXAMPLE:
    | 使用 JSON 存储 | 简单、人类可读、内置 Python 支持 |
    | 带有子命令的 argparse | 简洁的 CLI：python todo.py add "task" |
-->
<!-- Decisions made with rationale -->
| Decision | Rationale |
|----------|-----------|
| 使用 Pest PHP 而非 PHPUnit | Pest 语法更简洁，适合快速编写测试 |
| 使用 Vitest 而非 Jest | Vitest 与 Vite 生态更匹配，速度快 |
| 使用 jsdom 模拟 DOM | 不需要真实浏览器，适合单元测试 |
| 统一表单控件高度为 34px | 确保视觉对齐，提升 UI 一致性 |
| 移除 select 系统样式 | 允许 CSS 完全控制高度和外观 |
| 使用 globalThis 而非局部变量 | 确保 Vitest 测试中全局变量同步 |
| 标题栏固定在顶部 | 将 titleBar/tabBar 放到 ScrollViewControl 外层 column，内容区域单独滚动 |
| ui2 列中列必须设高度 | FlexLayout 对 column-in-column 无高度时 basis=0 导致折叠为0 |

## Issues Encountered
<!-- 
  WHAT: 你遇到的问题以及你如何解决它们。
  WHY: 与 task_plan.md 中的错误类似，但专注于更广泛的问题（不仅仅是代码错误）。
  WHEN: 当你遇到阻碍或意外挑战时记录。
  EXAMPLE:
    | 空文件导致 JSONDecodeError | 在 json.load() 之前添加了显式空文件检查 |
-->
<!-- Errors and how they were resolved -->
| Issue | Resolution |
|-------|------------|
| Pest 测试断言失败（数组键检查） | 改用 PHPUnit::hasKey() 和 expect()->toBeIn() |
| Vitest 全局变量不同步 | 使用 globalThis 而非局部变量 |
| select 高度仍然不对齐 | 添加 appearance: none 移除系统样式 |
| CSS 修改后需要重启应用 | CSS 内联到 HTML，运行时不会重新读取文件 |
| 测试文件有重复测试块 | 删除重复的 describe() 块 |
| CodeLibraryPanel 标题栏+标签不可见 | 将 titleBar/tabBar 移出 ScrollViewControl，放到外层 column |
| CodeLibraryPanel 初始加载空状态 | 添加 auto-select first group 逻辑（lines 59-65） |
| ui2 FlexLayout 列中列无高度→折叠为0 | 必须显式设置 column->style->height |
| ScrollViewControl padding 在视口内部 | padding 添加在 viewport row 内部，不是外部 |

## Resources
<!-- 
  WHAT: 你发现有用的 URL、文件路径、API 参考、文档链接。
  WHY: 以后容易参考。不要丢失重要链接在上下文中。
  WHEN: 当你发现有用资源时添加。
  EXAMPLE:
    - Python argparse 文档：https://docs.python.org/3/library/argparse.html
    - 项目结构：src/main.py、src/utils.py
-->
<!-- URLs, file paths, API references -->
- Pest PHP 文档：https://pestphp.com/docs/installation
- Vitest 文档：https://vitest.dev/guide/
- jsdom 文档：https://github.com/jsdom/jsdom
- CSS appearance 属性：https://developer.mozilla.org/en-US/docs/Web/CSS/appearance
- FlyEnv 工具箱项目：/Volumes/data/git/php/phptools2/
- 测试文件：tests/Unit/FlyEnvWebAppTest.php、tests/JS/toolbox.test.js
- CodeLibraryPanel: app/Native/Panels/CodeLibraryPanel.php (~881 lines)
- ScrollViewControl: vendor/yangweijie/ui2/src/Widgets/ScrollViewControl.php (282 lines)

## Visual/Browser Findings
<!-- 
  WHAT: 你从查看图像、PDF 或浏览器结果中学到的信息。
  WHY: 关键 - 视觉/多模态内容不会在上下文中持久化。必须立即捕获为文本。
  WHEN: 在每次查看图像或浏览器结果后立即更新。不要等待！
  EXAMPLE:
    - 截图显示登录表单有电子邮件和密码字段
    - 浏览器显示 API 返回带有 "status" 和 "data" 键的 JSON
-->
<!-- CRITICAL: Update after every 2 view/browser operations -->
<!-- Multimodal content must be captured as text immediately -->
- 用户截图显示 select 下拉高度比旁边按钮小
- 问题根因：select 使用系统默认 appearance，忽略 CSS padding
- 解决方案：添加 appearance: none 和自定义 SVG 箭头背景

---
<!-- 
  REMINDER: The 2-Action Rule
  在每次 2 个查看/浏览器/搜索操作后，你必须更新这个文件。
  这可以防止视觉信息在上下文重置时丢失。
-->
*Update this file after every 2 view/browser/search operations*
*This prevents visual information from being lost*
