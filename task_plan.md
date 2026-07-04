# Task Plan: FlyEnv 工具箱测试体系建设与 UI 优化

<!-- 
  WHAT: 这是整个任务的路线图。把它想象成"磁盘上的工作记忆"。
  WHY: 在 50+ 工具调用后，原始目标可能会被遗忘。这个文件让它们保持新鲜。
  WHEN: 在开始任何工作之前创建这个。在每个阶段完成后更新。
-->

## Goal
<!-- 
  WHAT: 一个清晰的句子，描述你试图实现什么。
  WHY: 这是你的北极星。重新阅读这个让你专注于最终状态。
  EXAMPLE: "创建一个具有添加、列出和删除功能的 Python CLI 待办事项应用。"
-->
建立完整的 FlyEnv 工具箱测试体系（Pest PHP + Vitest 前端），并修复表单控件高度对齐的 UI 问题。

## Current Phase
<!-- 
  WHAT: 你当前正在工作的阶段（例如，"Phase 1", "Phase 3"）。
  WHY: 快速参考你在任务中的位置。随着进展更新这个。
-->
Phase 3

## Phases
<!-- 
  WHAT: 将任务分解为 3-7 个逻辑阶段。每个阶段应该是可完成的。
  WHY: 将工作分解为阶段可以防止不知所措，并使进展可见。
  WHEN: 每个阶段完成后更新状态：pending → in_progress → complete
-->

### Phase 1: 项目迁移与环境搭建
<!-- 
  WHAT: 将核心文件从 php-tools 迁移到 phptools2，并验证基本功能。
  WHY: 为新项目建立一个干净的基础。
-->
- [x] 将 flyenv-web.php、app/、assets/ 复制到 phptools2
- [x] 复制 composer.json、composer.lock、vendor/ 依赖
- [x] 复制 box.json、phpunit.xml.dist 配置
- [x] 验证应用可以正常启动
- **Status:** complete
<!-- 
  STATUS VALUES:
  - pending: 还没有开始
  - in_progress: 目前正在处理这个
  - complete: 完成这个阶段
-->

### Phase 2: Pest PHP 测试框架集成
<!-- 
  WHAT: 安装和配置 Pest PHP 测试框架，为 FlyEnvWebApp 编写单元测试。
  WHY: 确保 PHP 后端逻辑的正确性和可维护性。
-->
- [x] 通过 Composer 安装 pestphp/pest (^4.7)
- [x] 创建 tests/ 目录结构（Unit/、Feature/）
- [x] 创建 tests/Pest.php 配置文件
- [x] 编写 FlyEnvWebAppTest.php 单元测试
- [x] 测试工具数据完整性、分类校验、renderTree/getHtml 输出结构
- [x] 修复测试断言方式（数组键检查、类型检查）
- [x] 验证 12 个测试全部通过（617 断言）
- **Status:** complete

### Phase 3: Vitest 前端测试框架集成
<!-- 
  WHAT: 安装和配置 Vitest，为 JavaScript 模块编写单元测试。
  WHY: 确保前端逻辑的正确性和可维护性。
-->
- [x] 初始化 npm，安装 vitest 和 jsdom
- [x] 创建 vitest.config.js 配置文件
- [x] 创建 tests/JS/setup.js 加载 JS 源文件
- [x] 创建 tests/JS/toolbox.test.js 测试文件
- [x] 测试 _t()、esc()、isFav()、b64u/b64ud() 等纯函数
- [x] 测试 goHome()、openTool()、toggleSidebar() 等 DOM 操作
- [x] 测试 __p 面板模板、HC/MM 静态数据
- [x] 修复全局变量同步问题（globalThis 与局部变量）
- [x] 验证 39 个测试全部通过
- **Status:** complete

### Phase 4: CSS 表单控件高度对齐修复
<!-- 
  WHAT: 修复 select 与 input/button 高度不对齐的 UI 问题。
  WHY: 提升用户界面的一致性和视觉质量。
-->
- [x] 诊断问题根因：select 使用系统默认 appearance，忽略 CSS padding
- [x] 添加 select { appearance: none } 移除系统样式
- [x] 添加自定义 SVG 下拉箭头背景
- [x] 统一 input/select/textarea 的 min-height: 34px
- [x] 统一 .btn 的 min-height: 34px
- [x] 添加 padding-right: 28px 为箭头留空间
- [x] 验证 CSS 修改后所有测试仍通过
- **Status:** complete

### Phase 5: 测试自动化与文档
<!-- 
  WHAT: 配置一键运行所有测试的命令，并创建测试概览文档。
  WHY: 提升开发体验，确保测试易于运行和维护。
-->
- [x] 添加 composer test 命令（运行 Pest）
- [x] 添加 npm test 和 npm run test:all 命令
- [x] 创建 overview-testing.md 测试概览文档
- [x] 验证所有测试通过（PHP 12 + JS 39 = 51 总测试）
- **Status:** complete

### Phase 6: 持续集成与后续优化
<!-- 
  WHAT: （可选）设置 CI 流水线，持续优化测试覆盖率。
  WHY: 确保代码质量和长期可维护性。
-->
- [ ] 配置 GitHub Actions / 其他 CI 工具
- [ ] 增加更多边界情况测试
- [ ] 添加 E2E 测试（如果需要）
- **Status:** pending

## Key Questions
<!-- 
  WHAT: 你在任务期间需要回答的重要问题。
  WHY: 这些问题指导你的研究和决策。随着进展回答它们。
  EXAMPLE: 
    1. 任务是否应该在会话之间持久化？（是 - 需要文件存储）
    2. 存储任务的格式是什么？（JSON 文件）
-->
1. 如何在不重启应用的情况下热重载 CSS？（开发模式）
2. 是否需要为每次 CSS 修改都重启 flyenv-web.php？（是的，因为 CSS 内联到 HTML）

## Decisions Made
<!-- 
  WHAT: 你已做出的技术和设计决策，以及背后的理由。
  WHY: 你会忘记为什么做了某些选择。这个表格帮助你记住并证明决策的合理性。
  WHEN: 每当你做出重要选择（技术、方法、结构）时更新。
  EXAMPLE:
    | 使用 JSON 存储 | 简单、人类可读、内置 Python 支持 |
-->
| Decision | Rationale |
|----------|-----------|
| 使用 Pest PHP 而非 PHPUnit | Pest 语法更简洁，适合快速编写测试 |
| 使用 Vitest 而非 Jest | Vitest 与 Vite 生态更匹配，速度快 |
| 使用 jsdom 模拟 DOM | 不需要真实浏览器，适合单元测试 |
| 统一表单控件高度为 34px | 确保视觉对齐，提升 UI 一致性 |
| 移除 select 系统样式 | 允许 CSS 完全控制高度和外观 |

## Errors Encountered
<!-- 
  WHAT: 你遇到的每个错误，它是第几次尝试，以及你如何解决它。
  WHY: 记录错误可以防止重复同样的错误。这对学习至关重要。
  WHEN: 一旦发生错误就立即添加，即使你很快修复了它。
  EXAMPLE:
    | FileNotFoundError | 1 | 检查文件是否存在，如果不存在则创建空列表 |
    | JSONDecodeError | 2 | 显式处理空文件情况 |
-->
| Error | Attempt | Resolution |
|-------|---------|------------|
| Pest 测试断言失败（数组键检查） | 1 | 改用 PHPUnit::hasKey() 和 expect()->toBeIn() |
| Vitest 全局变量不同步 | 1 | 使用 globalThis 而非局部变量 |
| select 高度仍然不对齐 | 2 | 添加 appearance: none 移除系统样式 |
| 测试文件有重复测试块 | 1 | 删除重复的 describe() 块 |

## Notes
<!-- 
  REMINDERS:
  - 随着进展更新阶段状态：pending → in_progress → complete
  - 在重大决策之前重新阅读这个计划（注意力操纵）
  - 记录所有错误 - 它们有助于避免重复
  - 永远不要重复失败的操作 - 改变你的方法
-->
- CSS 修改后需要重启应用才能生效（因为 CSS 内联到 HTML）
- 考虑添加开发模式热重载功能（监听文件改动后自动刷新 WebView）
- 所有测试通过：Pest 12 个测试（617 断言）+ Vitest 39 个测试 = 51 总测试
