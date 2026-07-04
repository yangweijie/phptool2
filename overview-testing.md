# FlyEnv Toolbox — 测试体系

## 运行方式

```bash
# 全部 PHP 测试
composer test

# 全部前端测试
npm test

# 全部测试
npm run test:all
```

## 测试统计

| 测试框架 | 测试文件 | 测试数 | 断言数 | 状态 |
|---------|---------|--------|--------|------|
| Pest (PHP) | `tests/Unit/FlyEnvWebAppTest.php` | 12 | 617 | ✅ 通过 |
| Vitest (JS) | `tests/JS/toolbox.test.js` | 39 | — | ✅ 通过 |
| **合计** | | **51** | | ✅ |

## PHP 测试覆盖

| 测试类别 | 测试内容 |
|---------|---------|
| 数据完整性 | 38 个工具的 id/cat/name/icon 非空检查 |
| 唯一性 | 所有工具 ID 无重复 |
| 分类校验 | 工具分类属于 `[Code, Development, Crypto, Converter, Web, Images]` |
| 分类覆盖 | 每个分类至少有一个工具 |
| PanelMap | 所有工具都有 panelMap 映射 |
| SVG 图标 | 所有图标都是有效的 `<svg>` 片段 |
| renderTree | 输出包含所有分类名、工具名、data-id |
| getHtml | 输出包含完整 HTML 结构、内联 CSS/JS、数据注入 |

## 前端测试覆盖

| 测试类别 | 函数 | 测试数 |
|---------|------|--------|
| i18n 系统 | `_t()`, `toggleLang()`, `I18N` | 6 |
| HTML 转义 | `esc()` | 6 |
| 收藏功能 | `isFav()`, `toggleFav()` | 4 |
| Base64 | `b64u()`, `b64ud()` | 5 |
| 键排序 | `sK()` | 5 |
| 界面导航 | `goHome()`, `openTool()`, `toggleSidebar()` | 4 |
| 面板系统 | `getPanelContent()`, `__p` | 5 |
| 搜索 | `doSearch()` | 2 |
| 数据常量 | `HC`, `MM` | 2 |
