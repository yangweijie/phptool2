# phptools2 — Images 分类 1:1 还原总结

对照原版 `FlyEnv-4.15.4`（`src/render/components/Tools/{QRCodeGenerator,WifiQRCodeGenerator,ImageCompress,Capturer}`）完成 Images 分类 4 个工具的界面/交互 1:1 还原。

## 改动清单

### QR Code Generator（`__p.qr`）
- 新增 **Foreground / Background 颜色选择器**（原生 `<input type=color>`）
- Error Resistance 改为原版字符串值 `low / medium / quartile / high`（原数字 0–3）
- 输入即**实时生成**（30ms 防抖）+ **Download QR Code** 按钮（从渲染的 SVG 导出）
- 后端 `qr` 绑定扩展 `fgColor` / `bgColor`（`chillerlan\QRCode` QROptions）

### WiFi QR Code（`__p.wifi`）
- 新增 **Hidden SSID** 开关、**FG/BG 颜色**、**WPA2-EAP 分支**（EAP method 17 项 + Identity + Anonymous + Phase 2）
- WIFI 字符串严格对齐原版 `useWifiQRCode`（含特殊字符转义、`H:true`、EAP 的 `A:anon`/`I:`/`PH2:`/`E:` 顺序）
- 实时生成 + Download

### ImageCompress（`__p.img`）
- 重建原版 **6 标签页**结构：Batch / Basic / Compress / Effects / Watermark / Texture（默认 Batch）
- 新增后端 `image_b64` 绑定（base64 输入输出，GD `imagescale`），让 **Batch 批量压缩真正可用**（选文件 → 逐张压缩 → 显示体积 + 下载）
- Basic 页保留路径 + 质量 + 最大宽（经 `image_c`）
- Effects / Watermark / Texture 为配置 UI（后端暂不支持，布局 1:1）

### Capturer（`__p.capture`）
- 重建原版布局：**快捷键录制卡**（hover 进入录制 + keydown 捕获组合键）+ **保存目录**（`<input webkitdirectory>`）+ **命名规则**（`{index}/{timestamp}/{datetime}/{uuid}` 插入）+ Save + Capture + Capture & Hide
- 后端 `capture` 扩展 `dir` / `name` 模板（占位符替换）+ 默认交互式区域截图

## 验证
- `node --check` 全过；`php -l` 通过
- Vitest **93/93** ✓（含 qr/img 桥接测试修复）
- Pest **26/642** ✓
- 烟测：4 面板关键 DOM + 初始化钩子齐全，无 `</script>` 字面量陷阱

## 状态
phptools2 全分类（Code / Development / Crypto / Converter / Web / Images）1:1 还原已全部完成。
