# PeakRack Turnstile WHMCS 插件

PeakRack Turnstile 是用于 WHMCS 的 Cloudflare Turnstile 验证码插件，用于替换旧式验证码显示。

English documentation: [README.md](README.md)

## 功能

- 支持 WHMCS 9.x 客户区登录、注册、密码重置、联系我们、提交工单、购物车/结账页面。
- 优先适配 WHMCS 自带 Nexus、Six、Twenty-One 模板，再兼容 Lagom/Lagom2。
- 后台可配置 Site Key、Secret Key、启用页面、小组件主题、小组件对齐方式和自定义选择器。
- Turnstile 统一使用 Cloudflare 默认视觉宽度，可选择居中或左对齐。
- 自动处理 WHMCS “启用服务条款接受”开关开启或关闭时的结账页位置。
- 使用 Cloudflare 显式渲染 API，确保 Hook 插入的小组件稳定渲染。
- 服务端校验 token，并在同一请求内缓存校验结果。

## 安装

把 `peakrack_turnstile` 目录上传到：

```text
modules/addons/peakrack_turnstile/
```

然后在 WHMCS 后台：

1. 进入 **系统设置 > 插件模块**。
2. 启用 **PeakRack Turnstile 管理器**。
3. 给对应管理员角色组授权访问。
4. 进入 **插件 > PeakRack Turnstile 管理器**。
5. 填写 Cloudflare Turnstile Site Key 和 Secret Key。
6. 勾选需要启用验证的页面。

## 推荐 WHMCS 设置

建议关闭 WHMCS 自带验证码，避免重复显示：

```text
系统设置 > 常规设置 > 安全 > 验证码表单保护
```

将验证码类型设置为 **Always Off**。

## 更新记录

### 1.4.1

- 新增全局前台对齐方式：居中或左对齐。
- 保持 Cloudflare 默认视觉宽度，减少不同模板里的横向拉伸。
- 优化 Nexus、Six、Twenty-One、Lagom/Lagom2 多页面显示位置一致性。

## 开源协议

MIT License。详见 [LICENSE](LICENSE)。
