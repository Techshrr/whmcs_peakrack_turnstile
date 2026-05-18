<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function peakrack_turnstile_config()
{
    return [
        'name' => 'PeakRack Turnstile 管理器',
        'description' => '使用 Cloudflare Turnstile 替换 WHMCS 默认验证码。优先适配 Nexus、Six、Twenty-One，再兼容 Lagom/Lagom2 等商业主题。',
        'author' => 'PeakRack',
        'language' => 'english',
        'version' => '1.4.4',
        'fields' => [
            'site_key' => [
                'FriendlyName' => 'Site Key / 站点密钥',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Cloudflare Turnstile Site Key，用于前台显示验证组件。',
            ],
            'secret_key' => [
                'FriendlyName' => 'Secret Key / 私钥',
                'Type' => 'password',
                'Size' => '50',
                'Description' => 'Cloudflare Turnstile Secret Key，仅用于服务端校验用户提交的 token。',
            ],
            'theme' => [
                'FriendlyName' => 'Theme / 主题',
                'Type' => 'dropdown',
                'Options' => 'auto,light,dark',
                'Default' => 'auto',
                'Description' => 'Turnstile 外观主题。建议使用 auto。',
            ],
            'alignment' => [
                'FriendlyName' => 'Alignment / 对齐方式',
                'Type' => 'dropdown',
                'Options' => 'center,left',
                'Default' => 'center',
                'Description' => 'Turnstile 小组件显示位置：居中或左对齐。默认居中。',
            ],
            'enable_login' => [
                'FriendlyName' => '登录页启用',
                'Type' => 'yesno',
                'Description' => '客户登录页。支持 Nexus、Six、Twenty-One、Lagom/Lagom2。',
            ],
            'enable_register' => [
                'FriendlyName' => '注册页启用',
                'Type' => 'yesno',
                'Description' => '客户注册页。启用 WHMCS 服务条款接受时会自动放在条款附近，关闭时放在提交按钮前。',
            ],
            'enable_pwreset' => [
                'FriendlyName' => '密码重置启用',
                'Type' => 'yesno',
                'Description' => '找回密码/密码重置邮箱提交步骤。',
            ],
            'enable_contact' => [
                'FriendlyName' => '联系我们启用',
                'Type' => 'yesno',
                'Description' => '联系我们表单提交前验证。',
            ],
            'enable_ticket' => [
                'FriendlyName' => '提交工单启用',
                'Type' => 'yesno',
                'Description' => '提交新工单表单提交前验证。',
            ],
            'enable_cart' => [
                'FriendlyName' => '购物车/结账启用',
                'Type' => 'yesno',
                'Description' => '购物车/结账完成订单前验证。结账页只显示一个 Turnstile。',
            ],
            'custom_login_sel' => ['FriendlyName' => '登录页自定义选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。只在自定义主题需要时填写；会作为附加 selector，不会覆盖默认适配。'],
            'custom_register_sel' => ['FriendlyName' => '注册页自定义选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。建议指向提交按钮或提交按钮所在容器。'],
            'custom_pwreset_sel' => ['FriendlyName' => '密码重置自定义选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。建议指向重置密码提交按钮。'],
            'custom_contact_sel' => ['FriendlyName' => '联系我们自定义选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。建议指向联系表单提交按钮。'],
            'custom_ticket_sel' => ['FriendlyName' => '提交工单自定义选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。建议指向 #openTicketSubmit。'],
            'custom_cart_sel' => ['FriendlyName' => '购物车/结账自定义选择器', 'Type' => 'text', 'Size' => '50', 'Description' => '可选。建议指向完成订单按钮附近。'],
        ],
    ];
}

function peakrack_turnstile_activate()
{
    peakrack_turnstile_migrate_legacy_settings();
    return ['status' => 'success', 'description' => 'PeakRack Turnstile 管理器已启用。'];
}

function peakrack_turnstile_deactivate()
{
    return ['status' => 'success', 'description' => 'PeakRack Turnstile 管理器已停用。'];
}

function peakrack_turnstile_valid_settings()
{
    return [
        'site_key',
        'secret_key',
        'theme',
        'alignment',
        'enable_login',
        'enable_register',
        'enable_pwreset',
        'enable_contact',
        'enable_ticket',
        'enable_cart',
        'custom_login_sel',
        'custom_register_sel',
        'custom_pwreset_sel',
        'custom_contact_sel',
        'custom_ticket_sel',
        'custom_cart_sel',
    ];
}

function peakrack_turnstile_default_settings()
{
    return [
        'site_key' => '',
        'secret_key' => '',
        'theme' => 'auto',
        'alignment' => 'center',
        'enable_login' => '',
        'enable_register' => '',
        'enable_pwreset' => '',
        'enable_contact' => '',
        'enable_ticket' => '',
        'enable_cart' => '',
        'custom_login_sel' => '',
        'custom_register_sel' => '',
        'custom_pwreset_sel' => '',
        'custom_contact_sel' => '',
        'custom_ticket_sel' => '',
        'custom_cart_sel' => '',
    ];
}

function peakrack_turnstile_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function peakrack_turnstile_normalize_admin_language($language): string
{
    return in_array((string) $language, ['zh', 'en'], true) ? (string) $language : '';
}

function peakrack_turnstile_admin_language(): string
{
    $requested = peakrack_turnstile_normalize_admin_language($_GET['prt_admin_lang'] ?? '');
    if ($requested !== '') {
        $_SESSION['peakrack_turnstile_admin_lang'] = $requested;
        if (!headers_sent()) {
            setcookie('peakrack_turnstile_admin_lang', $requested, time() + 31536000, '', '', false, true);
        }

        return $requested;
    }

    $sessionLanguage = peakrack_turnstile_normalize_admin_language($_SESSION['peakrack_turnstile_admin_lang'] ?? '');
    if ($sessionLanguage !== '') {
        return $sessionLanguage;
    }

    $cookieLanguage = peakrack_turnstile_normalize_admin_language($_COOKIE['peakrack_turnstile_admin_lang'] ?? '');
    return $cookieLanguage !== '' ? $cookieLanguage : 'zh';
}

function peakrack_turnstile_admin_url(string $language): string
{
    $params = ['module' => 'peakrack_turnstile', 'prt_admin_lang' => peakrack_turnstile_normalize_admin_language($language) ?: 'zh'];
    return 'addonmodules.php?' . http_build_query($params);
}

function peakrack_turnstile_admin_text(string $language, string $key): string
{
    $texts = [
        'zh' => [
            'title' => 'PeakRack Turnstile 管理器',
            'subtitle' => '优先适配 WHMCS 自带 Nexus、Six、Twenty-One 的登录、注册、密码重置、联系我们、提交工单、购物车/结账页面；再兼容 Lagom/Lagom2 等商业主题。Turnstile 统一显示为 Cloudflare 默认 320px 宽，可选择居中或左对齐，并位于提交动作区域上方。',
            'version' => '版本 1.4.4',
            'saved' => '设置已保存。',
            'keys' => 'Cloudflare 密钥',
            'site_key' => 'Site Key / 站点密钥',
            'site_key_desc' => '填写 Cloudflare Turnstile 小组件的 Site Key。',
            'secret_key' => 'Secret Key / 私钥',
            'secret_key_desc' => '用于服务端校验，请不要填写 Site Key。',
            'theme' => '小组件主题',
            'theme_desc' => '建议使用 Auto，让 Cloudflare 根据访问者环境自动选择。',
            'alignment' => '显示对齐方式',
            'alignment_desc' => '默认居中。某些模板按钮区域左对齐更自然时，可切换为左对齐。',
            'pages' => '页面启用',
            'login' => '登录页',
            'login_desc' => '客户登录页；购物车已启用验证时，不重复显示购物车登录验证码。',
            'register' => '注册页',
            'register_desc' => '客户注册页；服务条款开关只影响显示位置，不影响验证逻辑。',
            'pwreset' => '密码重置',
            'pwreset_desc' => '找回密码邮箱提交步骤。',
            'contact' => '联系我们',
            'contact_desc' => '联系我们表单提交前验证。',
            'ticket' => '提交工单',
            'ticket_desc' => '提交新工单前验证。',
            'cart' => '购物车/结账',
            'cart_desc' => '完成订单前验证；结账页只显示一个 Turnstile。',
            'tos_title' => '服务条款接受开关影响检查',
            'tos_notice' => 'WHMCS 的“启用服务条款接受”只会影响注册页和购物车/结账页是否出现服务条款区域。插件会自动处理：有服务条款时放在服务条款之后、提交按钮之前；没有服务条款时放在提交按钮之前。其它页面统一放在提交按钮之前。它不会影响登录页、密码重置、联系我们、提交工单的服务端验证。',
            'th_page' => '页面',
            'th_core' => 'Nexus / Six / Twenty-One',
            'th_lagom' => 'Lagom / Lagom2',
            'th_tos' => '服务条款开关影响',
            'no_effect' => '无影响',
            'tos_position' => '开启时条款后、按钮前；关闭时按钮前',
            'advanced' => '高级设置：自定义选择器',
            'advanced_note' => '一般留空。只有自定义主题自动识别不到位置时才填写。填写后会优先尝试你的 selector，但系统模板和 Lagom 默认适配仍然保留。',
            'custom_login' => '登录页选择器',
            'custom_register' => '注册页选择器',
            'custom_pwreset' => '密码重置选择器',
            'custom_contact' => '联系我们选择器',
            'custom_ticket' => '提交工单选择器',
            'custom_cart' => '购物车/结账选择器',
            'custom_login_desc' => '例：#login 或 form.login-form button[type="submit"]。',
            'custom_register_desc' => '例：#btnRegister 或注册表单提交按钮。',
            'custom_pwreset_desc' => '例：#resetPasswordButton。',
            'custom_contact_desc' => '例：form[action*="contact.php"] button[type="submit"]。',
            'custom_ticket_desc' => '例：#openTicketSubmit。',
            'custom_cart_desc' => '例：#btnCompleteOrder 或 #checkout。',
            'save' => '保存设置',
        ],
        'en' => [
            'title' => 'PeakRack Turnstile Manager',
            'subtitle' => 'Prioritizes WHMCS built-in Nexus, Six, and Twenty-One pages for login, registration, password reset, contact, ticket submission, and cart/checkout, then supports commercial themes such as Lagom/Lagom2. The widget keeps the standard Cloudflare 320px visual width, can be centered or left aligned, and is placed near the submit action.',
            'version' => 'Version 1.4.4',
            'saved' => 'Settings saved.',
            'keys' => 'Cloudflare Keys',
            'site_key' => 'Site Key',
            'site_key_desc' => 'Enter the Cloudflare Turnstile Site Key used to render the widget.',
            'secret_key' => 'Secret Key',
            'secret_key_desc' => 'Used only for server-side token verification. Do not enter the Site Key here.',
            'theme' => 'Widget Theme',
            'theme_desc' => 'Auto is recommended so Cloudflare can match the visitor environment.',
            'alignment' => 'Widget Alignment',
            'alignment_desc' => 'Centered by default. Use left alignment when a theme looks more natural with left-aligned action areas.',
            'pages' => 'Enabled Pages',
            'login' => 'Login Page',
            'login_desc' => 'Client login page. Cart login verification is not duplicated when cart verification is enabled.',
            'register' => 'Registration Page',
            'register_desc' => 'Client registration page. The terms-of-service switch only changes placement, not verification logic.',
            'pwreset' => 'Password Reset',
            'pwreset_desc' => 'Password reset email submission step.',
            'contact' => 'Contact Us',
            'contact_desc' => 'Verifies the contact form before submission.',
            'ticket' => 'Submit Ticket',
            'ticket_desc' => 'Verifies new ticket submissions.',
            'cart' => 'Cart / Checkout',
            'cart_desc' => 'Verifies before order completion. Only one Turnstile widget is shown on checkout.',
            'tos_title' => 'Terms Acceptance Placement Check',
            'tos_notice' => 'WHMCS “Enable TOS Acceptance” only affects whether registration and cart/checkout pages include a terms area. The module automatically places Turnstile after the terms area and before the submit button when terms are enabled, or before the submit button when terms are disabled. Other pages are placed before submit buttons. Server-side verification for login, password reset, contact, and ticket pages is unaffected.',
            'th_page' => 'Page',
            'th_core' => 'Nexus / Six / Twenty-One',
            'th_lagom' => 'Lagom / Lagom2',
            'th_tos' => 'TOS Switch Impact',
            'no_effect' => 'No impact',
            'tos_position' => 'Enabled: after terms, before button. Disabled: before button.',
            'advanced' => 'Advanced: Custom Selectors',
            'advanced_note' => 'Usually leave these empty. Use them only when a custom theme cannot be detected automatically. Custom selectors are tried first while built-in system theme and Lagom handling remains available.',
            'custom_login' => 'Login Selector',
            'custom_register' => 'Registration Selector',
            'custom_pwreset' => 'Password Reset Selector',
            'custom_contact' => 'Contact Selector',
            'custom_ticket' => 'Ticket Selector',
            'custom_cart' => 'Cart / Checkout Selector',
            'custom_login_desc' => 'Example: #login or form.login-form button[type="submit"].',
            'custom_register_desc' => 'Example: #btnRegister or the registration form submit button.',
            'custom_pwreset_desc' => 'Example: #resetPasswordButton.',
            'custom_contact_desc' => 'Example: form[action*="contact.php"] button[type="submit"].',
            'custom_ticket_desc' => 'Example: #openTicketSubmit.',
            'custom_cart_desc' => 'Example: #btnCompleteOrder or #checkout.',
            'save' => 'Save Settings',
        ],
    ];

    $language = peakrack_turnstile_normalize_admin_language($language) ?: 'zh';
    return $texts[$language][$key] ?? $texts['zh'][$key] ?? $key;
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

function peakrack_turnstile_load_settings()
{
    $settings = peakrack_turnstile_default_settings();
    $rows = Capsule::table('tbladdonmodules')
        ->where('module', 'peakrack_turnstile')
        ->whereIn('setting', peakrack_turnstile_valid_settings())
        ->get();

    foreach ($rows as $row) {
        if (isset($row->setting) && array_key_exists($row->setting, $settings)) {
            $settings[$row->setting] = isset($row->value) ? (string) $row->value : '';
        }
    }

    if (!in_array($settings['theme'], ['auto', 'light', 'dark'], true)) {
        $settings['theme'] = 'auto';
    }

    if (!in_array($settings['alignment'], ['center', 'left'], true)) {
        $settings['alignment'] = 'center';
    }

    return $settings;
}

function peakrack_turnstile_save_settings()
{
    foreach (peakrack_turnstile_valid_settings() as $setting) {
        $value = isset($_POST[$setting]) ? trim((string) $_POST[$setting]) : '';

        if (strpos($setting, 'enable_') === 0) {
            $value = $value === 'on' ? 'on' : '';
        }

        if ($setting === 'theme' && !in_array($value, ['auto', 'light', 'dark'], true)) {
            $value = 'auto';
        }

        if ($setting === 'alignment' && !in_array($value, ['center', 'left'], true)) {
            $value = 'center';
        }

        Capsule::table('tbladdonmodules')->updateOrInsert(
            ['module' => 'peakrack_turnstile', 'setting' => $setting],
            ['value' => $value]
        );
    }
}

function peakrack_turnstile_checked($settings, $key)
{
    return !empty($settings[$key]) && $settings[$key] === 'on' ? ' checked' : '';
}

function peakrack_turnstile_select($settings, $key, $value)
{
    return isset($settings[$key]) && $settings[$key] === $value ? ' selected' : '';
}

function peakrack_turnstile_toggle($settings, $key, $title, $desc)
{
    return '<div class="prt-toggle">
        <div>
            <strong>' . peakrack_turnstile_e($title) . '</strong>
            <span>' . peakrack_turnstile_e($desc) . '</span>
        </div>
        <label class="prt-switch">
            <input type="checkbox" name="' . peakrack_turnstile_e($key) . '"' . peakrack_turnstile_checked($settings, $key) . '>
            <span></span>
        </label>
    </div>';
}

function peakrack_turnstile_text_input($settings, $key, $title, $desc, $placeholder = '')
{
    return '<div class="prt-field">
        <label for="' . peakrack_turnstile_e($key) . '">' . peakrack_turnstile_e($title) . '</label>
        <input id="' . peakrack_turnstile_e($key) . '" type="text" name="' . peakrack_turnstile_e($key) . '" value="' . peakrack_turnstile_e($settings[$key] ?? '') . '" placeholder="' . peakrack_turnstile_e($placeholder) . '" autocomplete="off">
        <p>' . peakrack_turnstile_e($desc) . '</p>
    </div>';
}

function peakrack_turnstile_output($vars)
{
    peakrack_turnstile_migrate_legacy_settings();

    $saved = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
        peakrack_turnstile_save_settings();
        $saved = true;
    }

    $settings = peakrack_turnstile_load_settings();
    $language = peakrack_turnstile_admin_language();
    $t = static fn(string $key): string => peakrack_turnstile_admin_text($language, $key);
    $zhUrl = peakrack_turnstile_e(peakrack_turnstile_admin_url('zh'));
    $enUrl = peakrack_turnstile_e(peakrack_turnstile_admin_url('en'));

    echo '<style>
        .peakrack-turnstile-admin { max-width: 1180px; margin: 0; color: #263238; }
        .peakrack-turnstile-admin * { box-sizing: border-box; }
        .prt-hero { background: #0f172a; color: #fff; border-radius: 6px; padding: 22px 24px; margin: 0 0 18px; }
        .prt-hero-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
        .prt-hero h2 { margin: 0 0 8px; color: #fff; font-size: 22px; }
        .prt-hero p { margin: 0; color: #cbd5e1; line-height: 1.6; }
        .prt-head-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; }
        .prt-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 3px 9px; background: rgba(37,99,235,.18); color: #bfdbfe; border: 1px solid rgba(191,219,254,.35); font-size: 12px; font-weight: 700; white-space: nowrap; }
        .prt-lang { display: inline-flex; border: 1px solid rgba(203,213,225,.45); border-radius: 6px; overflow: hidden; background: rgba(255,255,255,.06); }
        .prt-lang a { display: inline-flex; align-items: center; padding: 6px 9px; color: #cbd5e1; text-decoration: none; font-size: 12px; font-weight: 700; }
        .prt-lang a.active { background: #2563eb; color: #fff; }
        .prt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .prt-card { background: #fff; border: 1px solid #dfe4ea; border-radius: 6px; padding: 18px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
        .prt-card h3 { margin: 0 0 14px; color: #111827; font-size: 16px; border-bottom: 1px solid #eef2f7; padding-bottom: 10px; }
        .prt-field { margin-bottom: 14px; }
        .prt-field label { display: block; font-weight: 600; margin-bottom: 6px; color: #374151; }
        .prt-field input, .prt-field select { width: 100%; max-width: 100%; height: 38px; border: 1px solid #cfd8e3; border-radius: 4px; padding: 7px 10px; color: #111827; background: #fff; }
        .prt-field p, .prt-note, .prt-card li { color: #64748b; font-size: 12px; line-height: 1.6; margin: 5px 0 0; }
        .prt-toggle { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
        .prt-toggle:last-child { border-bottom: 0; }
        .prt-toggle strong { display: block; color: #111827; margin-bottom: 3px; }
        .prt-toggle span { display: block; color: #64748b; font-size: 12px; line-height: 1.5; }
        .prt-switch { position: relative; display: inline-block; width: 48px; height: 26px; flex: 0 0 48px; margin: 0; }
        .prt-switch input { opacity: 0; width: 0; height: 0; }
        .prt-switch span { position: absolute; inset: 0; cursor: pointer; background: #cbd5e1; border-radius: 999px; transition: .2s; }
        .prt-switch span:before { content: ""; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 2px rgba(0,0,0,.15); }
        .prt-switch input:checked + span { background: #16a34a; }
        .prt-switch input:checked + span:before { transform: translateX(22px); }
        .prt-alert { border-radius: 4px; padding: 12px 14px; margin-bottom: 16px; }
        .prt-alert-success { background: #ecfdf5; border: 1px solid #bbf7d0; color: #166534; }
        .prt-alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .prt-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .prt-table th, .prt-table td { border: 1px solid #e5e7eb; padding: 9px 10px; text-align: left; vertical-align: top; }
        .prt-table th { background: #f8fafc; color: #334155; }
        .prt-actions { display: flex; justify-content: flex-end; margin: 18px 0 0; }
        .prt-save { background: #2563eb; border: 0; border-radius: 4px; color: #fff; font-weight: 600; padding: 10px 22px; cursor: pointer; }
        .prt-save:hover { background: #1d4ed8; }
        @media (max-width: 900px) { .prt-grid { grid-template-columns: 1fr; } .prt-hero-head { display: block; } .prt-head-actions { justify-content: flex-start; margin-top: 12px; } }
    </style>';

    echo '<div class="peakrack-turnstile-admin">
        <div class="prt-hero">
            <div class="prt-hero-head">
                <div>
                    <h2>' . peakrack_turnstile_e($t('title')) . '</h2>
                    <p>' . peakrack_turnstile_e($t('subtitle')) . '</p>
                </div>
                <div class="prt-head-actions">
                    <span class="prt-badge">' . peakrack_turnstile_e($t('version')) . '</span>
                    <div class="prt-lang" aria-label="Admin language">
                        <a class="' . ($language === 'zh' ? 'active' : '') . '" href="' . $zhUrl . '">中文</a>
                        <a class="' . ($language === 'en' ? 'active' : '') . '" href="' . $enUrl . '">English</a>
                    </div>
                </div>
            </div>
        </div>';

    if ($saved) {
        echo '<div class="prt-alert prt-alert-success">' . peakrack_turnstile_e($t('saved')) . '</div>';
    }

    echo '<form method="post" action="">
        <input type="hidden" name="action" value="save">

        <div class="prt-grid">
            <div class="prt-card">
                <h3>' . peakrack_turnstile_e($t('keys')) . '</h3>
                <div class="prt-field">
                    <label for="site_key">' . peakrack_turnstile_e($t('site_key')) . '</label>
                    <input id="site_key" type="text" name="site_key" value="' . peakrack_turnstile_e($settings['site_key']) . '" placeholder="0x4AAAAAA..." autocomplete="off">
                    <p>' . peakrack_turnstile_e($t('site_key_desc')) . '</p>
                </div>
                <div class="prt-field">
                    <label for="secret_key">' . peakrack_turnstile_e($t('secret_key')) . '</label>
                    <input id="secret_key" type="password" name="secret_key" value="' . peakrack_turnstile_e($settings['secret_key']) . '" placeholder="0x4AAAAAA..." autocomplete="off">
                    <p>' . peakrack_turnstile_e($t('secret_key_desc')) . '</p>
                </div>
                <div class="prt-field" style="max-width:220px">
                    <label for="theme">' . peakrack_turnstile_e($t('theme')) . '</label>
                    <select id="theme" name="theme">
                        <option value="auto"' . peakrack_turnstile_select($settings, 'theme', 'auto') . '>Auto / 自动</option>
                        <option value="light"' . peakrack_turnstile_select($settings, 'theme', 'light') . '>Light / 浅色</option>
                        <option value="dark"' . peakrack_turnstile_select($settings, 'theme', 'dark') . '>Dark / 深色</option>
                    </select>
                    <p>' . peakrack_turnstile_e($t('theme_desc')) . '</p>
                </div>
                <div class="prt-field" style="max-width:220px">
                    <label for="alignment">' . peakrack_turnstile_e($t('alignment')) . '</label>
                    <select id="alignment" name="alignment">
                        <option value="center"' . peakrack_turnstile_select($settings, 'alignment', 'center') . '>Center / 居中</option>
                        <option value="left"' . peakrack_turnstile_select($settings, 'alignment', 'left') . '>Left / 左对齐</option>
                    </select>
                    <p>' . peakrack_turnstile_e($t('alignment_desc')) . '</p>
                </div>
            </div>

            <div class="prt-card">
                <h3>' . peakrack_turnstile_e($t('pages')) . '</h3>' .
                peakrack_turnstile_toggle($settings, 'enable_login', $t('login'), $t('login_desc')) .
                peakrack_turnstile_toggle($settings, 'enable_register', $t('register'), $t('register_desc')) .
                peakrack_turnstile_toggle($settings, 'enable_pwreset', $t('pwreset'), $t('pwreset_desc')) .
                peakrack_turnstile_toggle($settings, 'enable_contact', $t('contact'), $t('contact_desc')) .
                peakrack_turnstile_toggle($settings, 'enable_ticket', $t('ticket'), $t('ticket_desc')) .
                peakrack_turnstile_toggle($settings, 'enable_cart', $t('cart'), $t('cart_desc')) .
            '</div>
        </div>

        <div class="prt-card">
            <h3>' . peakrack_turnstile_e($t('tos_title')) . '</h3>
            <div class="prt-alert prt-alert-info">' . peakrack_turnstile_e($t('tos_notice')) . '</div>
            <table class="prt-table">
                <thead>
                    <tr><th>' . peakrack_turnstile_e($t('th_page')) . '</th><th>' . peakrack_turnstile_e($t('th_core')) . '</th><th>' . peakrack_turnstile_e($t('th_lagom')) . '</th><th>' . peakrack_turnstile_e($t('th_tos')) . '</th></tr>
                </thead>
                <tbody>
                    <tr><td>' . peakrack_turnstile_e($t('login')) . '</td><td>form.login-form, #login</td><td>form.login-form, .login-captcha</td><td>' . peakrack_turnstile_e($t('no_effect')) . '</td></tr>
                    <tr><td>' . peakrack_turnstile_e($t('register')) . '</td><td>#frmCheckout, accepttos, submit button</td><td>#frmCheckout, Lagom registration form, accepttos</td><td>' . peakrack_turnstile_e($t('tos_position')) . '</td></tr>
                    <tr><td>' . peakrack_turnstile_e($t('pwreset')) . '</td><td>#resetPasswordButton, password reset form</td><td>loginForm, .login-captcha, password reset form</td><td>' . peakrack_turnstile_e($t('no_effect')) . '</td></tr>
                    <tr><td>' . peakrack_turnstile_e($t('contact')) . '</td><td>contact.php form, action=send</td><td>contact.php form, Lagom captcha container</td><td>' . peakrack_turnstile_e($t('no_effect')) . '</td></tr>
                    <tr><td>' . peakrack_turnstile_e($t('ticket')) . '</td><td>#openTicketSubmit</td><td>#openTicketSubmit, .login-captcha</td><td>' . peakrack_turnstile_e($t('no_effect')) . '</td></tr>
                    <tr><td>' . peakrack_turnstile_e($t('cart')) . '</td><td>#frmCheckout, #btnCompleteOrder, accepttos</td><td>#frmCheckout, #checkout, #submit-checkout, order-checkbox</td><td>' . peakrack_turnstile_e($t('tos_position')) . '</td></tr>
                </tbody>
            </table>
        </div>

        <div class="prt-card">
            <h3>' . peakrack_turnstile_e($t('advanced')) . '</h3>
            <p class="prt-note">' . peakrack_turnstile_e($t('advanced_note')) . '</p>
            <div class="prt-grid">' .
                peakrack_turnstile_text_input($settings, 'custom_login_sel', $t('custom_login'), $t('custom_login_desc'), '#login') .
                peakrack_turnstile_text_input($settings, 'custom_register_sel', $t('custom_register'), $t('custom_register_desc'), '#btnRegister') .
                peakrack_turnstile_text_input($settings, 'custom_pwreset_sel', $t('custom_pwreset'), $t('custom_pwreset_desc'), '#resetPasswordButton') .
                peakrack_turnstile_text_input($settings, 'custom_contact_sel', $t('custom_contact'), $t('custom_contact_desc'), 'form[action*="contact.php"] button[type="submit"]') .
                peakrack_turnstile_text_input($settings, 'custom_ticket_sel', $t('custom_ticket'), $t('custom_ticket_desc'), '#openTicketSubmit') .
                peakrack_turnstile_text_input($settings, 'custom_cart_sel', $t('custom_cart'), $t('custom_cart_desc'), '#btnCompleteOrder') .
            '</div>
        </div>

        <div class="prt-actions">
            <button type="submit" class="prt-save">' . peakrack_turnstile_e($t('save')) . '</button>
        </div>
    </form>
    </div>';
}
