# PeakRack Turnstile Manager for WHMCS

> 官方仓库：https://github.com/Techshrr/whmcs_peakrack_turnstile
> 许可证：MIT License

PeakRack Turnstile Manager 是一个 WHMCS 插件，用于给指定客户区表单加入 Cloudflare Turnstile 校验。

## 项目说明

插件会将 Turnstile 小组件注入到指定 WHMCS 客户区表单，并通过 WHMCS hooks 在服务端校验提交的 Turnstile token。

模块包含针对 WHMCS 内置模板和常见订购表单的放置逻辑。高度自定义主题可以通过自定义选择器补充定位。

## 功能特性

- 支持登录、注册、密码重置、联系我们、提交工单和购物车结账页面。
- 包含 WHMCS Nexus、Six、Twenty-One、Lagom/Lagom2、Standard Cart、Nexus Cart 和常见订购表单的放置处理。
- 提供 Cloudflare Site Key、Secret Key、小组件主题、对齐方式、页面开关和自定义选择器设置。
- 为结账页现有客户登录请求 `/login/cart` 添加 Turnstile 响应字段。
- 对 hook 插入的小组件进行显式 Turnstile 渲染。
- 在服务端校验提交 token，并在单次请求中缓存校验结果。
- 后台界面支持中文和英文切换。

## 环境要求

- WHMCS 9.0.x
- PHP 8.2 或更高版本
- Cloudflare Turnstile Site Key 和 Secret Key

## 安装方法

1. 从官方仓库下载最新版本。
2. 将插件目录上传到：

   `modules/addons/peakrack_turnstile/`

3. 登录 WHMCS 后台。
4. 进入 **System Settings > Addon Modules** 并启用 **PeakRack Turnstile Manager**。
5. 为对应管理员角色组授予访问权限。
6. 打开 **Addons > PeakRack Turnstile Manager**，配置密钥和页面开关。

## 配置说明

| 配置项 | 说明 | 默认值 |
|---|---|---|
| Site Key | 前端小组件使用的 Cloudflare Turnstile Site Key | 空 |
| Secret Key | 服务端校验使用的 Cloudflare Turnstile Secret Key | 空 |
| Theme | 小组件主题 | auto |
| Alignment | 小组件对齐方式 | center |
| Enable login | 登录页是否启用校验 | 关闭 |
| Enable register | 注册页是否启用校验 | 关闭 |
| Enable password reset | 密码重置是否启用校验 | 关闭 |
| Enable contact | 联系我们表单是否启用校验 | 关闭 |
| Enable ticket | 提交工单是否启用校验 | 关闭 |
| Enable cart | 购物车结账是否启用校验 | 关闭 |
| Custom selectors | 自定义主题使用的附加选择器 | 空 |

## 使用说明

管理员配置 Cloudflare 密钥，选择小组件主题和对齐方式，并启用需要校验的页面。客户在启用的表单上会看到 Turnstile 小组件。提交时如果缺少 token 或服务端校验失败，请求会被拒绝。

如果不希望同一表单出现多个验证码，请关闭 WHMCS 内置 captcha。

## 升级说明

请查看 [UPGRADE.zh-CN.md](UPGRADE.zh-CN.md)。

## 英文文档

请查看 [README.md](README.md)。

## 安全说明

请勿提交生产环境凭据、API Key、数据库密码、支付密钥、WHMCS 授权信息、客户数据、身份证件或私有签名密钥。

安全问题报告方式请查看 [SECURITY.md](SECURITY.md)。

## 许可证

本项目基于 MIT License 发布。完整许可证请查看 [LICENSE](LICENSE)。
