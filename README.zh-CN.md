# WHMCS PeakRack Turnstile 插件

PeakRack Turnstile 是面向 WHMCS 客户区的 Cloudflare Turnstile 验证码插件，用于替换传统验证码并提升登录、注册、工单和结账流程的验证体验。

English documentation: [README.md](README.md)

## 功能

- 支持 WHMCS 9.x 客户区登录、注册、密码重置、联系我们、提交工单、购物车和结账页面。
- 兼容 WHMCS 自带 Nexus、Six、Twenty-One 主题，并针对 Lagom/Lagom2 做了布局适配。
- 覆盖 Standard Cart、Nexus Cart、Lagom Cart/Lagom Checkout 以及常见 WHMCS 购物车模板的关键提交区域。
- 后台可配置 Cloudflare Turnstile Site Key、Secret Key、启用页面、小组件主题、前台对齐方式和自定义选择器。
- 前台 Turnstile 默认保持 Cloudflare 320px 标准视觉宽度，可按模板选择居中或左对齐。
- 自动识别服务条款区域，将购物车/结账验证码放置在条款确认之后、提交订单按钮之前。
- 支持动态渲染的购物车页面，通过 DOM 监听自动补充 Turnstile 小组件。
- 结账页“现有客户登录”AJAX 请求会自动附加 `cf-turnstile-response`，避免 `/login/cart` 返回重定向导致前端 `parsererror`。
- 服务端校验 Turnstile token，并在同一请求内缓存校验结果，减少重复验证请求。
- 管理器页面支持中文 / English 后台语言切换。

## 环境要求

- WHMCS 9.x
- PHP 8.2 或 PHP 8.3
- Cloudflare Turnstile Site Key 和 Secret Key
- 可访问 WHMCS `modules/addons/` 目录的文件上传或部署权限

## 安装

本仓库包含可直接部署到 WHMCS 的插件目录：

```text
peakrack_turnstile/
```

将该目录完整上传或复制到 WHMCS：

```text
modules/addons/peakrack_turnstile/
```

最终目录结构应为：

```text
modules/addons/peakrack_turnstile/hooks.php
modules/addons/peakrack_turnstile/peakrack_turnstile.php
```

然后在 WHMCS 后台完成启用：

1. 进入 **系统设置 > 插件模块**。
2. 启用 **PeakRack Turnstile Manager**。
3. 为需要管理该插件的管理员角色组授予访问权限。
4. 进入 **插件 > PeakRack Turnstile Manager**。
5. 填写 Cloudflare Turnstile **Site Key** 和 **Secret Key**。
6. 按需启用登录页、注册页、密码重置、联系我们、提交工单、购物车/结账页面。
7. 根据当前主题选择小组件对齐方式。一般建议保持默认居中；如果模板按钮区域更适合左侧排版，可切换为左对齐。

## 推荐 WHMCS 设置

建议关闭 WHMCS 自带验证码，避免同一页面重复显示多个验证码：

```text
系统设置 > 常规设置 > 安全 > 验证码表单保护
```

将验证码类型设置为 **Always Off**，再由本插件统一负责 Turnstile 输出和验证。

## 主题和购物车兼容性

插件默认覆盖以下常见前台结构：

- WHMCS 自带主题：Nexus、Six、Twenty-One
- Lagom / Lagom2 客户区主题
- Standard Cart
- Nexus Cart
- Lagom Cart / Lagom Checkout
- WHMCS 常见旧版购物车模板，如 Legacy Boxes、Legacy Modern、Premium Comparison、Pure Comparison、Supreme Comparison、Universal Slider

如果站点使用深度定制模板，可在后台 **高级设置：自定义选择器** 中指定提交按钮或表单选择器。自定义选择器只建议在默认识别不到目标位置时使用。

## 升级

升级时覆盖上传新的 `peakrack_turnstile/` 目录即可：

```text
peakrack_turnstile/ -> modules/addons/peakrack_turnstile/
```

覆盖前建议备份旧目录。当前版本升级不需要数据库迁移。

详细升级说明见 [UPGRADE.zh-CN.md](UPGRADE.zh-CN.md)。

## 更新记录

### 1.4.7

- 增强 Lagom、Nexus、Six、Twenty-One 主题下登录、注册、密码重置、联系我们、提交工单、购物车/结账页面的 Turnstile 插入位置。
- 优化 Standard Cart、Nexus Cart、Lagom 购物车和 Lagom 结账页的服务条款及提交按钮附近布局。
- 增加动态 DOM 监听，兼容由前端脚本延迟生成的购物车/结账表单。
- 优化 token 同步逻辑，避免未来 jQuery 版本变化导致 token 读取失败。

### 1.4.6

- 修复结账页“现有客户登录”使用 AJAX 请求 `/login/cart` 时未携带 Turnstile token 的问题。
- 当结账登录验证失败时返回 JSON 响应，避免 WHMCS 前端收到 `login.php?error=captcha` 重定向后触发 `parsererror`。
- 隐藏的结账登录区域不会再把 Turnstile 错误插入到完成订单区域。

### 1.4.5

- 将 WHMCS 插件标题固定为 `PeakRack Turnstile Manager`。
- 固定右上角版本号和语言切换区域宽度，避免随中英文说明文字行数变化而错位。

### 1.4.4

- 在 Turnstile 管理器增加中文 / English 后台语言切换按钮。
- 管理器主要配置文字支持中英文切换。

### 1.4.3

- 压平 GitHub 仓库结构，根目录直接显示 `peakrack_turnstile/`。
- 更新安装和升级文档，改为直接上传插件目录到 `modules/addons/`。

### 1.4.2

- 将本地/仓库发布包命名统一为 `whmcs_peakrack_turnstile`。
- 将可部署文件统一放到 `whmcs_peakrack_turnstile/modules`，方便和其他 WHMCS 插件仓库保持一致。

### 1.4.1

- 新增全局前台对齐方式：居中或左对齐。
- 保持 Cloudflare 默认视觉宽度，减少不同模板里的横向拉伸。
- 优化 Nexus、Six、Twenty-One、Lagom/Lagom2 多页面显示位置一致性。

## 开源协议

MIT License。详见 [LICENSE](LICENSE)。
