<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function peakrack_turnstile_config()
{
    // Keeping fields here ensures WHMCS creates the rows in proper format for `tbladdonmodules`
    // and allows fallback if someone uses the modal config. 
    return [
        'name' => 'PeakRack Turnstile 管理器',
        'description' => '使用 Cloudflare Turnstile 替换 WHMCS 默认验证码，并可在下方管理启用页面与选择器。',
        'author' => 'PeakRack',
        'language' => 'english',
        'version' => '1.3.0',
        'fields' => [
            'site_key' => ['FriendlyName' => '站点密钥', 'Type' => 'text', 'Size' => '50', 'Description' => 'Cloudflare Turnstile 的 Site Key，用于在前台页面渲染验证码。'],
            'secret_key' => ['FriendlyName' => '私钥', 'Type' => 'password', 'Size' => '50', 'Description' => 'Cloudflare Turnstile 的 Secret Key，仅用于服务端向 Cloudflare 校验用户提交的 token。'],
            'theme' => ['FriendlyName' => '主题', 'Type' => 'dropdown', 'Options' => 'auto,light,dark', 'Default' => 'auto', 'Description' => '控制前台 Turnstile 组件外观；自动会跟随访客浏览器或系统偏好。'],
            'enable_login' => ['FriendlyName' => '登录页启用', 'Type' => 'yesno', 'Description' => '在客户登录页启用 Turnstile；结账页若已启用购物车验证，则不会额外显示登录验证码。'],
            'enable_register' => ['FriendlyName' => '注册页启用', 'Type' => 'yesno', 'Description' => '在客户注册页提交前要求完成 Turnstile 验证。'],
            'enable_pwreset' => ['FriendlyName' => '密码重置启用', 'Type' => 'yesno', 'Description' => '在找回密码/重置密码的邮箱提交步骤启用 Turnstile。'],
            'enable_contact' => ['FriendlyName' => '联系我们启用', 'Type' => 'yesno', 'Description' => '在联系我们表单提交前启用 Turnstile，减少垃圾消息。'],
            'enable_ticket' => ['FriendlyName' => '提交工单启用', 'Type' => 'yesno', 'Description' => '在未登录或客户提交新工单时启用 Turnstile。'],
            'enable_cart' => ['FriendlyName' => '购物车/结账启用', 'Type' => 'yesno', 'Description' => '在购物车完成订单前启用 Turnstile；结账页只显示一个验证码。'],
            'custom_login_sel' => ['FriendlyName' => '登录页选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。自定义登录页中插入 Turnstile 的目标元素选择器，留空使用自动识别。'],
            'custom_register_sel' => ['FriendlyName' => '注册页选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。自定义注册页中插入 Turnstile 的目标元素选择器，通常填写提交按钮选择器。'],
            'custom_pwreset_sel' => ['FriendlyName' => '密码重置选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。自定义密码重置页面中插入 Turnstile 的目标元素选择器。'],
            'custom_contact_sel' => ['FriendlyName' => '联系我们选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。自定义联系我们页面中插入 Turnstile 的目标元素选择器。'],
            'custom_ticket_sel' => ['FriendlyName' => '工单选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。自定义提交工单页面中插入 Turnstile 的目标元素选择器。'],
            'custom_cart_sel' => ['FriendlyName' => '购物车/结账选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。自定义结账页中插入 Turnstile 的目标元素选择器，建议指向完成订单按钮附近。'],
        ]
    ];
}

function peakrack_turnstile_activate()
{
    peakrack_turnstile_migrate_legacy_settings();
    return ['status' => 'success', 'description' => 'PeakRack Turnstile 管理器已成功启用。'];
}

function peakrack_turnstile_deactivate()
{
    return ['status' => 'success', 'description' => 'PeakRack Turnstile 管理器已停用。'];
}

function peakrack_turnstile_migrate_legacy_settings()
{
    $currentModule = 'peakrack_turnstile';
    $legacyModule = 'megabre_turnstile';

    $legacyRows = Capsule::table('tbladdonmodules')->where('module', $legacyModule)->get();
    foreach ($legacyRows as $row) {
        if (!isset($row->setting)) {
            continue;
        }

        $exists = Capsule::table('tbladdonmodules')
            ->where('module', $currentModule)
            ->where('setting', $row->setting)
            ->exists();

        if (!$exists) {
            Capsule::table('tbladdonmodules')->insert([
                'module' => $currentModule,
                'setting' => $row->setting,
                'value' => isset($row->value) ? $row->value : '',
            ]);
        }
    }
}

function peakrack_turnstile_output($vars)
{
    peakrack_turnstile_migrate_legacy_settings();

    $moduleName = 'peakrack_turnstile';
    $validSettings = [
        'site_key', 'secret_key', 'theme', 
        'enable_login', 'enable_register', 'enable_pwreset', 'enable_contact', 'enable_ticket', 'enable_cart',
        'custom_login_sel', 'custom_register_sel', 'custom_pwreset_sel', 'custom_contact_sel', 'custom_ticket_sel', 'custom_cart_sel'
    ];

    // Handle Save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
        foreach ($validSettings as $setting) {
            $value = isset($_POST[$setting]) ? trim($_POST[$setting]) : '';
            
            // Checkbox logic for WHMCS 'yesno' fields
            if (strpos($setting, 'enable_') === 0) {
                 $value = ($value === 'on') ? 'on' : '';
            }

            Capsule::table('tbladdonmodules')->updateOrInsert(
                ['module' => $moduleName, 'setting' => $setting],
                ['value' => $value]
            );
        }
        echo '<div class="alert alert-success">设置已保存。</div>';
    }

    // Retrieve settings
    $settings = [];
    foreach ($validSettings as $key) {
        $settings[$key] = Capsule::table('tbladdonmodules')->where('module', $moduleName)->where('setting', $key)->value('value');
    }

    // Render Form
    echo '<style>
        .peakrack-card { background: #fff; padding: 25px; border-radius: 6px; border: 1px solid #e0e0e0; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .peakrack-card h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; color: #333; font-size: 18px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #555; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        input[type="text"]:focus, input[type="password"]:focus, select:focus { border-color: #2196F3; outline: none; }
        .help-block { color: #888; font-size: 0.85em; margin-top: 5px; }
        .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
        .col-half { flex: 0 0 50%; padding: 0 15px; box-sizing: border-box; }
        
        /* Switch UI */
        .toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f5f5f5; }
        .toggle-row:last-child { border-bottom: none; }
        .switch { position: relative; display: inline-block; width: 46px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #4CAF50; }
        input:checked + .slider:before { transform: translateX(20px); }
        
        .btn-save { background: #007bff; color: #fff; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s; }
        .btn-save:hover { background: #0056b3; }
        .actions-row { margin-top: 20px; text-align: right; }
    </style>';

    echo '<form method="post" action="">
        <input type="hidden" name="action" value="save">
        
        <div class="peakrack-card">
            <h3>API 配置</h3>
            <div class="row">
                <div class="col-half">
                    <div class="form-group">
                        <label>站点密钥</label>
                        <input type="text" name="site_key" value="' . htmlspecialchars($settings['site_key']) . '" placeholder="0x4AAAAAA..." autocomplete="off">
                    </div>
                </div>
                <div class="col-half">
                    <div class="form-group">
                        <label>私钥</label>
                        <input type="password" name="secret_key" value="' . htmlspecialchars($settings['secret_key']) . '" placeholder="0x4AAAAAA..." autocomplete="off">
                    </div>
                </div>
            </div>
             <div class="form-group" style="max-width: 200px;">
                <label>主题</label>
                <select name="theme">
                    <option value="auto" ' . ($settings['theme'] == 'auto' ? 'selected' : '') . '>自动</option>
                    <option value="light" ' . ($settings['theme'] == 'light' ? 'selected' : '') . '>浅色</option>
                    <option value="dark" ' . ($settings['theme'] == 'dark' ? 'selected' : '') . '>深色</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-half">
                <div class="peakrack-card">
                    <h3>页面启用设置</h3>
                    <div class="toggle-row">
                        <span>登录页</span>
                        <label class="switch">
                            <input type="checkbox" name="enable_login" ' . ($settings['enable_login'] == 'on' ? 'checked' : '') . '>
                            <span class="slider"></span>
                        </label>
                    </div>
                     <div class="toggle-row">
                        <span>注册页</span>
                        <label class="switch">
                            <input type="checkbox" name="enable_register" ' . ($settings['enable_register'] == 'on' ? 'checked' : '') . '>
                            <span class="slider"></span>
                        </label>
                    </div>
                     <div class="toggle-row">
                        <span>密码重置</span>
                        <label class="switch">
                            <input type="checkbox" name="enable_pwreset" ' . ($settings['enable_pwreset'] == 'on' ? 'checked' : '') . '>
                            <span class="slider"></span>
                        </label>
                    </div>
                     <div class="toggle-row">
                        <span>联系我们</span>
                        <label class="switch">
                            <input type="checkbox" name="enable_contact" ' . ($settings['enable_contact'] == 'on' ? 'checked' : '') . '>
                            <span class="slider"></span>
                        </label>
                    </div>
                     <div class="toggle-row">
                        <span>提交工单</span>
                        <label class="switch">
                            <input type="checkbox" name="enable_ticket" ' . ($settings['enable_ticket'] == 'on' ? 'checked' : '') . '>
                            <span class="slider"></span>
                        </label>
                    </div>
                     <div class="toggle-row">
                        <span>购物车/结账</span>
                        <label class="switch">
                            <input type="checkbox" name="enable_cart" ' . ($settings['enable_cart'] == 'on' ? 'checked' : '') . '>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="col-half">
                <div class="peakrack-card">
                    <h3>高级设置：自定义选择器</h3>
                    <p class="help-block" style="margin-bottom: 15px;">填写 jQuery 选择器（例如 <code>.btn-submit</code>），模块会在对应元素前自动插入 Turnstile。留空则使用自动识别。</p>
                    
                    <div class="form-group">
                        <label>登录页选择器</label>
                        <input type="text" name="custom_login_sel" value="' . htmlspecialchars($settings['custom_login_sel']) . '">
                    </div>
                    <div class="form-group">
                        <label>注册页选择器</label>
                        <input type="text" name="custom_register_sel" value="' . htmlspecialchars($settings['custom_register_sel']) . '">
                    </div>
                    <div class="form-group">
                        <label>密码重置选择器</label>
                        <input type="text" name="custom_pwreset_sel" value="' . htmlspecialchars($settings['custom_pwreset_sel']) . '">
                    </div>
                    <div class="form-group">
                        <label>联系我们选择器</label>
                        <input type="text" name="custom_contact_sel" value="' . htmlspecialchars($settings['custom_contact_sel']) . '">
                    </div>
                    <div class="form-group">
                        <label>工单选择器</label>
                        <input type="text" name="custom_ticket_sel" value="' . htmlspecialchars($settings['custom_ticket_sel']) . '">
                    </div>
                    <div class="form-group">
                        <label>购物车/结账选择器</label>
                        <input type="text" name="custom_cart_sel" value="' . htmlspecialchars($settings['custom_cart_sel']) . '">
                    </div>
                </div>
            </div>
        </div>

        <div class="actions-row">
            <button type="submit" class="btn-save">保存配置</button>
        </div>
    </form>';
}
