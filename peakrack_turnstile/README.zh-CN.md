# WHMCS PeakRack Turnstile 插件

PeakRack Turnstile 是用于 WHMCS 的 Cloudflare Turnstile 验证码插件。

English documentation: [README.md](README.md)

## 安装

把本目录完整上传到 WHMCS：

```text
modules/addons/peakrack_turnstile/
```

上传后目录中应包含：

```text
hooks.php
peakrack_turnstile.php
```

然后在 WHMCS 后台启用并配置 **PeakRack Turnstile Manager**：

1. 进入 **系统设置 > 插件模块**。
2. 启用 **PeakRack Turnstile Manager**。
3. 授权对应管理员角色组访问。
4. 打开 **插件 > PeakRack Turnstile Manager**。
5. 填写 Cloudflare Turnstile Site Key 和 Secret Key。
6. 勾选需要启用验证的页面。

## 功能摘要

- 支持登录、注册、密码重置、联系我们、提交工单、购物车/结账页面。
- 兼容 Nexus、Six、Twenty-One、Lagom/Lagom2 主题。
- 兼容 Standard Cart、Nexus Cart、Lagom Cart/Lagom Checkout 以及常见 WHMCS 购物车模板。
- 支持 Turnstile 主题、前台对齐方式和自定义选择器配置。
- 购物车/结账页会优先放置在服务条款确认之后、提交按钮之前。
- 结账页“现有客户登录”的 AJAX 请求会自动附加 `cf-turnstile-response`。
- 动态渲染的购物车页面会在 DOM 更新后自动补充 Turnstile 小组件。
- 管理器页面支持中文 / English 后台语言切换。

## 推荐设置

建议在 WHMCS 后台关闭系统自带验证码，避免重复显示：

```text
系统设置 > 常规设置 > 安全 > 验证码表单保护 > Always Off
```

## 开源协议

MIT License。详见仓库根目录 [LICENSE](../LICENSE)。
