# 升级说明

## 1.4.5

- 将 WHMCS 插件标题固定为 `PeakRack Turnstile Manager`。
- 固定右上角版本号和语言切换区域布局，避免随说明文字行数变化。
- 已安装站点升级到此版本不需要修改数据库。
- 手动更新时，把 `peakrack_turnstile/` 覆盖上传到 `modules/addons/peakrack_turnstile/`。
- 插件版本号升级到 `1.4.5`。

## 1.4.4

- 在 Turnstile 管理器页面右上角增加 `中文 / English` 后台语言切换按钮。
- 管理器主要配置文字、提示、表格标题和保存提示支持中英文切换。
- 插件版本号升级到 `1.4.4`。

## 1.4.3

- 仅调整仓库展示结构：可部署插件目录现在位于仓库根目录 `peakrack_turnstile/`。
- 已安装站点升级到此版本不需要修改数据库。
- 手动更新时，把 `peakrack_turnstile/` 覆盖上传到 `modules/addons/peakrack_turnstile/`。
- 插件版本号升级到 `1.4.3`。

## 1.4.2

- 仅调整仓库发布目录结构：可部署文件现在位于 `whmcs_peakrack_turnstile/modules`。
- 已安装站点升级到此版本不需要修改数据库。
- 手动更新时，把新的 `whmcs_peakrack_turnstile/modules` 目录内容覆盖上传到 WHMCS 根目录即可。
- 插件版本号升级到 `1.4.2`。

## 1.4.1

- 新增全局前台对齐方式：居中或左对齐。
- 保持 Cloudflare 默认视觉宽度，减少不同模板里的横向拉伸。
- 优化 Nexus、Six、Twenty-One、Lagom/Lagom2 多页面显示位置一致性。
