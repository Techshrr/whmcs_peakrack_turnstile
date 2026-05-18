<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function peakrack_turnstile_get_settings()
{
    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    $settings = [];
    $rows = Capsule::table('tbladdonmodules')
        ->whereIn('module', ['megabre_turnstile', 'peakrack_turnstile'])
        ->orderBy('module', 'asc')
        ->get();

    foreach ($rows as $row) {
        if (isset($row->setting)) {
            $settings[$row->setting] = isset($row->value) ? $row->value : '';
        }
    }

    return $settings;
}

function peakrack_turnstile_get_setting($name)
{
    $settings = peakrack_turnstile_get_settings();
    return array_key_exists($name, $settings) ? $settings[$name] : '';
}

function peakrack_turnstile_is_enabled($pageSetting)
{
    return peakrack_turnstile_get_setting($pageSetting) === 'on';
}

function peakrack_turnstile_get_site_key()
{
    return trim((string) peakrack_turnstile_get_setting('site_key'));
}

function peakrack_turnstile_get_theme()
{
    $theme = peakrack_turnstile_get_setting('theme');
    return in_array($theme, ['auto', 'light', 'dark'], true) ? $theme : 'auto';
}

function peakrack_turnstile_get_alignment()
{
    $alignment = peakrack_turnstile_get_setting('alignment');
    return in_array($alignment, ['center', 'left'], true) ? $alignment : 'center';
}

function peakrack_turnstile_lang_root()
{
    if (defined('ROOTDIR') && is_string(ROOTDIR) && ROOTDIR !== '') {
        return ROOTDIR;
    }

    $resolved = realpath(__DIR__ . '/../../..');
    return $resolved ?: __DIR__;
}

function peakrack_turnstile_lang_file_candidates()
{
    $langDir = peakrack_turnstile_lang_root() . DIRECTORY_SEPARATOR . 'lang';
    if (!is_dir($langDir)) {
        return [];
    }

    $codes = [];
    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
        if (!empty($_SESSION['Language'])) {
            $codes[] = (string) $_SESSION['Language'];
        }
        if (!empty($_SESSION['locale'])) {
            $codes[] = (string) $_SESSION['locale'];
        }
    }

    $files = [];
    foreach ($codes as $raw) {
        $code = strtolower(preg_replace('/\.php$/', '', trim(basename($raw))));
        $code = preg_replace('/[^a-z0-9_\-]/', '', $code);
        if ($code === '') {
            continue;
        }

        $file = $langDir . DIRECTORY_SEPARATOR . $code . '.php';
        if (is_file($file) && !in_array($file, $files, true)) {
            $files[] = $file;
        }
    }

    $english = $langDir . DIRECTORY_SEPARATOR . 'english.php';
    if (is_file($english) && !in_array($english, $files, true)) {
        $files[] = $english;
    }

    return $files;
}

function peakrack_turnstile_load_lang_array_from_path($path)
{
    if (!is_file($path)) {
        return [];
    }

    $_LANG = [];
    include $path;

    return isset($_LANG) && is_array($_LANG) ? $_LANG : [];
}

function peakrack_turnstile_client_lang_array()
{
    if (!empty($GLOBALS['_LANG']) && is_array($GLOBALS['_LANG'])) {
        return $GLOBALS['_LANG'];
    }

    foreach (peakrack_turnstile_lang_file_candidates() as $file) {
        $lang = peakrack_turnstile_load_lang_array_from_path($file);
        if (!empty($lang)) {
            return $lang;
        }
    }

    return [];
}

function peakrack_turnstile_text($key)
{
    static $resolved = null;

    if ($resolved === null) {
        $lang = peakrack_turnstile_client_lang_array();
        $captchaFailed = '';

        if (!empty($lang['captcha']['verification']['failed'])) {
            $captchaFailed = $lang['captcha']['verification']['failed'];
        } elseif (!empty($lang['captchaIncorrect'])) {
            $captchaFailed = $lang['captchaIncorrect'];
        }

        $resolved = [
            'prompt' => !empty($lang['captchaIncorrect']) ? $lang['captchaIncorrect'] : 'Complete the captcha and try again.',
            'error' => $captchaFailed !== '' ? $captchaFailed : 'Captcha verification failed. Please try again.',
        ];
    }

    return array_key_exists($key, $resolved) ? $resolved[$key] : '';
}

function peakrack_turnstile_log($message)
{
    if (function_exists('logActivity')) {
        logActivity('Cloudflare Turnstile: ' . $message);
        return;
    }

    error_log('Cloudflare Turnstile: ' . $message);
}

function peakrack_turnstile_verify($response)
{
    static $verifiedTokens = [];

    $token = trim((string) $response);
    if ($token === '' || strlen($token) > 2048) {
        return false;
    }

    $tokenHash = hash('sha256', $token);
    if (array_key_exists($tokenHash, $verifiedTokens)) {
        return $verifiedTokens[$tokenHash];
    }

    $secretKey = trim((string) peakrack_turnstile_get_setting('secret_key'));
    if ($secretKey === '') {
        $verifiedTokens[$tokenHash] = false;
        return false;
    }

    $postFields = [
        'secret' => $secretKey,
        'response' => $token,
    ];

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $postFields['remoteip'] = trim((string) $_SERVER['REMOTE_ADDR']);
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $result = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false || $httpCode < 200 || $httpCode >= 300) {
        peakrack_turnstile_log('siteverify request failed' . ($curlError ? ': ' . $curlError : '') . ($httpCode ? ' (HTTP ' . $httpCode . ')' : ''));
        $verifiedTokens[$tokenHash] = false;
        return false;
    }

    $json = json_decode($result, true);
    if (!is_array($json)) {
        peakrack_turnstile_log('siteverify returned invalid JSON');
        $verifiedTokens[$tokenHash] = false;
        return false;
    }

    $success = !empty($json['success']);
    if (!$success && !empty($json['error-codes']) && is_array($json['error-codes'])) {
        peakrack_turnstile_log('siteverify rejected token: ' . implode(', ', $json['error-codes']));
    }

    $verifiedTokens[$tokenHash] = $success;
    return $success;
}

function peakrack_turnstile_widget_html()
{
    return '<div class="peakrack-turnstile-native-slot" data-peakrack-native-slot="1"></div>';
}

function peakrack_turnstile_post_token()
{
    return isset($_POST['cf-turnstile-response']) ? trim((string) $_POST['cf-turnstile-response']) : '';
}

function peakrack_turnstile_post_is_valid()
{
    $token = peakrack_turnstile_post_token();
    return $token !== '' && peakrack_turnstile_verify($token);
}

function peakrack_turnstile_is_cart_login_request()
{
    $requestUri = strtolower(rawurldecode((string) ($_SERVER['REQUEST_URI'] ?? '')));
    return strpos($requestUri, 'login/cart') !== false;
}

if (
    php_sapi_name() !== 'cli'
    && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'contact.php'
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && $_POST['action'] === 'send'
    && peakrack_turnstile_is_enabled('enable_contact')
    && !peakrack_turnstile_post_is_valid()
) {
    unset($_POST['action']);
    $_REQUEST['action'] = '';
}

if (
    php_sapi_name() !== 'cli'
    && (!defined('ADMINAREA') || !ADMINAREA)
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && peakrack_turnstile_is_enabled('enable_login')
) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $path = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if (
        strpos($path, '/admin/') === false
        && strpos($path, '\\admin\\') === false
        && ($script === 'index.php' || $script === 'dologin.php')
        && isset($_POST['username'], $_POST['password'])
        && is_string($_POST['username'])
        && is_string($_POST['password'])
        && $_POST['username'] !== ''
        && $_POST['password'] !== ''
        && !(peakrack_turnstile_is_cart_login_request() && peakrack_turnstile_is_enabled('enable_cart'))
        && !peakrack_turnstile_post_is_valid()
    ) {
        header('Location: login.php?error=captcha');
        exit;
    }
}

if (
    php_sapi_name() !== 'cli'
    && (!defined('ADMINAREA') || !ADMINAREA)
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && peakrack_turnstile_is_enabled('enable_pwreset')
    && isset($_POST['action']) && $_POST['action'] === 'reset'
    && isset($_POST['email']) && is_string($_POST['email']) && trim($_POST['email']) !== ''
) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $path = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if (
        strpos($path, '/admin/') === false
        && strpos($path, '\\admin\\') === false
        && ($script === 'index.php' || $script === 'pwreset.php')
        && !peakrack_turnstile_post_is_valid()
    ) {
        header('Location: pwreset.php?error=captcha');
        exit;
    }
}

add_hook('ClientAreaPageHooks', 1, function ($vars) {
    return [
        'display_turnstile' => function ($params, $smarty) {
            return peakrack_turnstile_widget_html();
        }
    ];
});

add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    if (peakrack_turnstile_get_site_key() === '') {
        return '';
    }

    return '<link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin><script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" defer></script>';
});

function peakrack_turnstile_add_placement(&$placements, $enabledSetting, $customSetting, $targets, $forms = [], $purpose = '')
{
    if (!peakrack_turnstile_is_enabled($enabledSetting)) {
        return;
    }

    $custom = trim((string) peakrack_turnstile_get_setting($customSetting));
    $targetList = $targets;
    if ($custom !== '') {
        array_unshift($targetList, $custom);
    }

    $placements[] = [
        'targets' => $targetList,
        'forms' => $forms,
        'purpose' => $purpose,
    ];
}

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $siteKey = peakrack_turnstile_get_site_key();
    if ($siteKey === '') {
        return '';
    }

    $templatefile = isset($vars['templatefile']) ? (string) $vars['templatefile'] : '';
    $filename = isset($vars['filename']) ? (string) $vars['filename'] : '';
    $placements = [];
    $checkoutLogin = false;

    if ($templatefile === 'login') {
        peakrack_turnstile_add_placement($placements, 'enable_login', 'custom_login_sel', [
            'form.login-form button[type="submit"], form.login-form input[type="submit"]',
            '#login',
            'form[action*="dologin"] button[type="submit"], form[action*="dologin"] input[type="submit"]',
            'form[action*="login/validate"] button[type="submit"], form[action*="login/validate"] input[type="submit"]',
            'form[action*="login%2fvalidate"] button[type="submit"], form[action*="login%2fvalidate"] input[type="submit"]',
            '.peakrack-login-wrap form button[type="submit"]',
        ], [
            'form.login-form',
            'form[action*="dologin"]',
            'form[action*="login/validate"]',
            'form[action*="login%2fvalidate"]',
            '.peakrack-login-wrap form',
        ], 'login');
    }

    if ($templatefile === 'clientregister') {
        peakrack_turnstile_add_placement($placements, 'enable_register', 'custom_register_sel', [
            'form#frmCheckout button[type="submit"], form#frmCheckout input[type="submit"]',
            'form:has(input[name="register"][value="true"]) button[type="submit"], form:has(input[name="register"][value="true"]) input[type="submit"]',
            '#btnRegister',
            '.peakrack-register-wrap form button[type="submit"]',
        ], [
            'form#frmCheckout',
            'form:has(input[name="register"][value="true"])',
            'form[action*="register"]',
            '.peakrack-register-wrap form',
        ], 'register');
    }

    if (peakrack_turnstile_is_enabled('enable_pwreset')) {
        peakrack_turnstile_add_placement($placements, 'enable_pwreset', 'custom_pwreset_sel', [
            'form:has(input[type="hidden"][name="action"][value="reset"]) button[type="submit"]',
            'form:has(input[type="hidden"][name="action"][value="reset"]) input[type="submit"]',
            '#resetPasswordButton',
            'form[action*="pwreset"] button[type="submit"], form[action*="pwreset"] input[type="submit"]',
            'form[action*="password-reset"] button[type="submit"], form[action*="password-reset"] input[type="submit"]',
            'form[action*="password/reset"] button[type="submit"], form[action*="password/reset"] input[type="submit"]',
            'form[action*="validate-email"] button[type="submit"], form[action*="validate-email"] input[type="submit"]',
            'form[action*="password%2freset"] button[type="submit"], form[action*="password%2freset"] input[type="submit"]',
        ], [
            'input[type="hidden"][name="action"][value="reset"]',
            'form[action*="password-reset-validate-email"]',
            'form[action*="password/reset"]',
            'form[action*="validate-email"]',
            'form[action*="password%2freset%2fvalidate-email"]',
            'form[action*="pwreset"]',
        ], 'pwreset');
    }

    if ($templatefile === 'supportticketsubmit-stepone' || $templatefile === 'supportticketsubmit-steptwo') {
        peakrack_turnstile_add_placement($placements, 'enable_ticket', 'custom_ticket_sel', [
            '#openTicketSubmit',
            'form[action*="submitticket"] button[type="submit"], form[action*="submitticket"] input[type="submit"]',
            'form:has(#openTicketSubmit) button[type="submit"], form:has(#openTicketSubmit) input[type="submit"]',
        ], [
            'form:has(#openTicketSubmit)',
            'form[action*="submitticket"]',
            'form',
        ], 'ticket');
    }

    if ($templatefile === 'contact') {
        peakrack_turnstile_add_placement($placements, 'enable_contact', 'custom_contact_sel', [
            'form:has(input[name="action"][value="send"]) button[type="submit"], form:has(input[name="action"][value="send"]) input[type="submit"]',
            'form[action*="contact.php"] button[type="submit"], form[action*="contact.php"] input[type="submit"]',
            'form[action*="contact"] button[type="submit"], form[action*="contact"] input[type="submit"]',
            '.peakrack-contact-form-wrap form button[type="submit"]',
        ], [
            'form:has(input[name="action"][value="send"])',
            'form[action*="contact.php"]',
            'form[action*="contact"]',
            '.peakrack-contact-form-wrap form',
        ], 'contact');
    }

    if (strpos($templatefile, 'checkout') !== false || $filename === 'cart') {
        $checkoutCart = peakrack_turnstile_is_enabled('enable_cart');
        $checkoutLogin = peakrack_turnstile_is_enabled('enable_login') && !$checkoutCart;
        if ($checkoutLogin) {
            $placements[] = [
                'targets' => ['#btnExistingLogin'],
                'forms' => ['#containerExistingUserSignin form'],
                'purpose' => 'checkout-login',
            ];
        }

        peakrack_turnstile_add_placement($placements, 'enable_cart', 'custom_cart_sel', [
            '#btnCompleteOrder',
            '#checkout',
            '#submit-checkout',
            '#frmCheckout button[type="submit"], #frmCheckout input[type="submit"]',
        ], [
            '#frmCheckout',
            'form[action*="cart"]',
        ], 'checkout-order');
    }

    $config = [
        'siteKey' => $siteKey,
        'theme' => peakrack_turnstile_get_theme(),
        'alignment' => peakrack_turnstile_get_alignment(),
        'placements' => $placements,
        'checkoutLogin' => $checkoutLogin,
        'messages' => [
            'prompt' => peakrack_turnstile_text('prompt'),
            'error' => peakrack_turnstile_text('error'),
        ],
    ];

    $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    $alignment = peakrack_turnstile_get_alignment();
    $alignmentCss = $alignment === 'left' ? '
        .peakrack-turnstile {
            margin-left: 0 !important;
            margin-right: auto !important;
        }
        .peakrack-turnstile-row,
        .peakrack-turnstile-row[data-peakrack-row-purpose="login"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="register"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="pwreset"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="contact"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="ticket"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-login"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"],
        .summary-actions .peakrack-turnstile-row,
        .main-sidebar .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"],
        .order-summary-mob .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"] {
            margin-left: 0 !important;
            margin-right: auto !important;
            text-align: left !important;
            justify-self: start;
        }
        .peakrack-turnstile-preserved-actions {
            margin-left: 0 !important;
            margin-right: auto !important;
            text-align: left !important;
        }
    ' : '
        .peakrack-turnstile {
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .peakrack-turnstile-row,
        .peakrack-turnstile-row[data-peakrack-row-purpose="login"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="register"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="pwreset"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="contact"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="ticket"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-login"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"],
        .summary-actions .peakrack-turnstile-row,
        .main-sidebar .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"],
        .order-summary-mob .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"] {
            margin-left: auto !important;
            margin-right: auto !important;
            text-align: center !important;
            justify-self: center;
        }
        .peakrack-turnstile-preserved-actions {
            margin-left: auto !important;
            margin-right: auto !important;
            text-align: center !important;
        }
    ';

    $css = '<style>
        .g-recaptcha,
        #google-recaptcha-domainchecker,
        .recaptcha-container,
        .cf-turnstile:not(.peakrack-turnstile),
        .h-captcha,
        #default-captcha-domainchecker,
        .default-captcha,
        #captchaContainer,
        #inputCaptcha,
        #inputCaptchaImage {
            display: none !important;
        }
        .peakrack-turnstile {
            box-sizing: border-box;
            display: block !important;
            margin: 0 auto !important;
            width: 100%;
            max-width: 320px;
            min-height: 65px;
            line-height: normal;
        }
        .peakrack-turnstile-row {
            box-sizing: border-box;
            clear: both;
            display: block !important;
            float: none !important;
            width: 100%;
            max-width: 320px;
            min-height: 65px;
            margin: 14px auto !important;
            text-align: center;
            flex: 0 1 320px;
            grid-column: 1 / -1;
            justify-self: center;
            align-self: center;
        }
        .peakrack-turnstile-row[data-peakrack-row-purpose="login"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="register"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="pwreset"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="contact"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="ticket"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-login"],
        .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"] {
            max-width: 320px;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .summary-actions .peakrack-turnstile-row,
        .main-sidebar .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"] {
            margin-top: 14px !important;
            margin-bottom: 14px !important;
        }
        .order-summary-mob .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-order"] {
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .inline-form + .peakrack-turnstile-row[data-peakrack-row-purpose="checkout-login"] {
            margin-top: 12px !important;
        }
        .peakrack-turnstile iframe {
            max-width: 100%;
        }
        .peakrack-turnstile-native-slot {
            display: none !important;
        }
        .peakrack-turnstile-preserved-actions {
            display: block !important;
            width: 100%;
            margin: 0 auto 14px !important;
            text-align: center !important;
        }
        .peakrack-turnstile-preserved-actions .btn,
        .peakrack-turnstile-preserved-actions button,
        .peakrack-turnstile-preserved-actions input[type="submit"] {
            float: none !important;
        }
    ' . $alignmentCss . '
    </style>';

    $script = <<<'HTML'
<script>
(function (window, $) {
    'use strict';

    var config = __MEGABRE_TURNSTILE_CONFIG__;
    var renderAttempts = 0;
    var checkoutRelocationAttempts = 0;

    if (!$) {
        window.console && console.warn && console.warn('Cloudflare Turnstile: jQuery is required for WHMCS form injection.');
        return;
    }

    function bySelector(selector) {
        try {
            return $(selector);
        } catch (error) {
            if (window.console && console.warn) {
                console.warn('Cloudflare Turnstile: invalid selector skipped:', selector, error);
            }
            return $();
        }
    }

    function createWidget(purpose) {
        var attributes = {
            'class': 'peakrack-turnstile',
            'data-sitekey': config.siteKey,
            'data-theme': config.theme,
            'data-size': 'normal',
            'data-peakrack-managed': '1'
        };

        if (purpose) {
            attributes['data-peakrack-purpose'] = purpose;
        }

        return $('<div/>', attributes);
    }

    function nativeCaptchaBlock($form) {
        var $scope = $form && $form.length ? $form : $(document);
        var $slot = $scope.find(
            '.peakrack-turnstile-native-slot,' +
            '#captchaContainer,' +
            '.recaptcha-container,' +
            '#google-recaptcha-domainchecker,' +
            '#default-captcha-domainchecker'
        ).first();
        var $block;

        if (!$slot.length) {
            return $();
        }

        $block = $slot.closest(
            '.peakrack-turnstile-native-slot,' +
            '#captchaContainer,' +
            '.captcha,' +
            '.login-captcha,' +
            '.recaptcha-container,' +
            '.domainchecker-homepage-captcha,' +
            '.domain-search-captcha,' +
            '.row.justify-content-center,' +
            '.text-center.row,' +
            '.form-group'
        ).first();

        if ($block.length && !$block.is('form')) {
            return $block;
        }

        return $slot;
    }

    function replaceNativeCaptchaBlock($form, $row) {
        var $block = nativeCaptchaBlock($form);
        var $submitControls;
        var $preservedActions;

        if (!$block.length) {
            return false;
        }

        $submitControls = $block.find('button[type="submit"], input[type="submit"]').detach();
        $block.replaceWith($row);

        if ($submitControls.length) {
            $preservedActions = $('<div/>', {
                'class': 'form-actions text-center peakrack-turnstile-preserved-actions'
            }).append($submitControls);
            $row.after($preservedActions);
        }

        return true;
    }

    function widgetRow($widget, purpose) {
        var rowPurpose = purpose || $widget.attr('data-peakrack-purpose') || '';
        var $row = $widget.closest('.peakrack-turnstile-row');

        if ($row.length) {
            return $row.detach();
        }

        return $('<div/>', {
            'class': 'peakrack-turnstile-row',
            'data-peakrack-row-purpose': rowPurpose
        }).append($widget.detach());
    }

    function targetForm($target) {
        if ($target.is('form')) {
            return $target;
        }

        if ($target.closest('form').length) {
            return $target.closest('form');
        }

        if ($target.is('#containerExistingUserSignin')) {
            return $target.find('form').first();
        }

        return $();
    }

    function findLoginForm($target) {
        var $form = targetForm($target);

        if ($form.length && ($form.hasClass('login-form') || $form.find('input[name="username"], input[name="password"]').length)) {
            return $form;
        }

        return firstVisible($(
            'form.login-form,' +
            '.peakrack-login-wrap form,' +
            'form[action*="dologin"],' +
            'form[action*="login/validate"],' +
            'form[action*="login%2fvalidate"]'
        ));
    }

    function findPasswordResetForm($target) {
        var $form = targetForm($target);

        if ($form.length && $form.find('input[type="hidden"][name="action"][value="reset"], input[name="email"]').length) {
            return $form;
        }

        return firstVisible($(
            'form:has(input[type="hidden"][name="action"][value="reset"]),' +
            'form[action*="password-reset-validate-email"],' +
            'form[action*="password/reset"],' +
            'form[action*="validate-email"],' +
            'form[action*="password%2freset%2fvalidate-email"],' +
            'form[action*="pwreset"]'
        ));
    }

    function hasPlacementPurpose(purpose) {
        var found = false;

        $.each(config.placements || [], function (_, placement) {
            if ((placement.purpose || '') === purpose) {
                found = true;
                return false;
            }
        });

        return found;
    }

    function visibleItems($items) {
        return $items.filter(function () {
            var $item = $(this);
            return $item.is(':visible') && !$item.hasClass('hidden') && !$item.hasClass('d-none') && $item.css('display') !== 'none';
        });
    }

    function firstVisible($items) {
        return visibleItems($items).first();
    }

    function lastVisible($items) {
        return visibleItems($items).last();
    }

    function formTermsBlock($form) {
        var $scope = $form && $form.length ? $form : $(document);
        var $virtual = firstVisible($scope.find('.order-checkbox[data-form-input="#accepttos"], .order-checkbox:has([data-tos-checkbox])'));
        var $input = firstVisible($scope.find('#accepttos, input[name="accepttos"], input.accepttos')).first();
        var $label;
        var $block;

        if ($virtual.length) {
            return $virtual;
        }

        if (!$input.length) {
            return $();
        }

        $label = $input.closest('label');
        $block = $label.closest('p, .form-check, .custom-control, .checkbox, .form-group, .mb-3').first();

        if ($block.length) {
            return $block;
        }

        if ($label.length) {
            var $parent = $label.parent('div');
            return $parent.length && !$parent.is('form, #frmCheckout') ? $parent : $label;
        }

        $block = $input.closest('p, .form-check, .custom-control, .checkbox, .form-group, .mb-3').first();
        return $block.length ? $block : $input;
    }

    function checkoutTermsBlock() {
        var $virtual = firstVisible($('.main-sidebar .order-checkbox[data-form-input="#accepttos"], #orderSummary .order-checkbox[data-form-input="#accepttos"], .summary-actions .order-checkbox[data-form-input="#accepttos"], .order-checkbox[data-form-input="#accepttos"]'));

        if ($virtual.length) {
            return $virtual;
        }

        return formTermsBlock($('#frmCheckout'));
    }

    function actionBlock($target) {
        var $row = $target.closest('.row').first();
        var $block = $target.closest('p, .text-center, .form-actions, .actions, .summary-actions, .form-group, .btn-group-wrap, .button-row').first();

        if ($row.length && $row.find('input[type="text"], input[type="email"], input[type="password"], select, textarea').length) {
            return $row;
        }

        if ($block.length && !$block.is('form, #frmCheckout')) {
            return $block;
        }

        return $target;
    }

    function submitBlock($form) {
        var $submit;
        var $block;

        if (!$form || !$form.length) {
            return $();
        }

        $submit = lastVisible($form.find(
            '#btnCompleteOrder:not(.hidden),' +
            '#resetPasswordButton:not(.hidden),' +
            '#login:not(.hidden),' +
            '#btnSubmit:not(.hidden),' +
            '#btnRegister:not(.hidden),' +
            'button[type="submit"]:not(.hidden),' +
            'input[type="submit"]:not(.hidden)'
        ));

        if (!$submit.length) {
            return $();
        }

        $block = $submit.closest('p, .float-left, .text-center, .form-actions, .actions, .summary-actions, .form-group, .btn-group-wrap, .button-row').first();
        return $block.length && !$block.is('form, #frmCheckout') ? $block : $submit;
    }

    function checkoutOrderAnchor($fallback) {
        var selectors = [
            '.main-sidebar #orderSummary .summary-actions button.btn-checkout',
            '.sidebar-sticky-summary button.btn-checkout',
            '#orderSummary .summary-actions button.btn-checkout',
            '#checkout',
            '#orderSummary button[type="submit"]',
            '#orderSummary .btn-primary',
            '#orderSummary .btn-success',
            '#btnCompleteOrder:not(.hidden)',
            '#frmCheckout button[type="submit"]:not(.hidden)',
            '#frmCheckout input[type="submit"]:not(.hidden)'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var $candidate = firstVisible(bySelector(selectors[i]));
            if ($candidate.length) {
                return $candidate;
            }
        }

        return $fallback && $fallback.length ? $fallback : $();
    }

    function placeCheckoutOrderWidget($widget, $fallback) {
        var $existingRow = $widget.closest('.peakrack-turnstile-row');
        var $form = $('#frmCheckout');
        var $terms;
        var $row;
        var $anchor;
        var $submit;

        if (
            $widget.attr('data-peakrack-rendered') === '1'
            && $existingRow.closest('body').length
            && $existingRow.closest('#frmCheckout, #orderSummary, .summary-actions, .main-sidebar').length
        ) {
            return true;
        }

        $terms = checkoutTermsBlock();
        $row = widgetRow($widget, 'checkout-order');

        if ($terms.length) {
            $terms.after($row);
            return true;
        }

        $submit = submitBlock($form);
        if ($submit.length) {
            $submit.before($row);
            return true;
        }

        $anchor = checkoutOrderAnchor($fallback);
        if ($anchor.length) {
            actionBlock($anchor).before($row);
            return true;
        }

        if ($form.length) {
            $form.append($row);
            return true;
        }

        return false;
    }

    function placeCheckoutLoginWidget($target, $widget) {
        var $row = widgetRow($widget, 'checkout-login');
        var $form = targetForm($target);
        var $inlineForm = $target.closest('.inline-form').first();
        var $block = actionBlock($target);
        var $loginActions;

        if (replaceNativeCaptchaBlock($form, $row)) {
            return true;
        }

        if ($inlineForm.length) {
            $inlineForm.after($row);
            return true;
        }

        $loginActions = $form.find('#login, button[type="submit"], input[type="submit"]').last().closest('.float-left, .form-actions, p, .form-group').first();
        if ($loginActions.length) {
            $loginActions.before($row);
            return true;
        }

        if ($block.length && $block.find('input[type="text"], input[type="email"], input[type="password"]').length) {
            $block.after($row);
            return true;
        }

        if ($block.length) {
            $block.before($row);
            return true;
        }

        return false;
    }

    function placeRegisterWidget($target, $widget) {
        var $form = targetForm($target);
        var $terms = formTermsBlock($form);
        var $row = widgetRow($widget, 'register');
        var $block;
        var $submitBlock;

        if (replaceNativeCaptchaBlock($form, $row)) {
            return true;
        }

        if ($terms.length) {
            $terms.after($row);
            return true;
        }

        $submitBlock = $form.find('#btnRegister, button[type="submit"], input[type="submit"]').last().closest('p, .form-actions, .form-group, .text-center').first();
        if ($submitBlock.length) {
            $submitBlock.before($row);
            return true;
        }

        $block = actionBlock($target);
        if ($block.length) {
            $block.before($row);
            return true;
        }

        return false;
    }

    function placeLoginWidget($target, $widget) {
        var $form = findLoginForm($target);
        var $row = widgetRow($widget, 'login');
        var $submitBlock;
        var $passwordBlock;
        var $block;

        if (!$form.length) {
            return false;
        }

        if (replaceNativeCaptchaBlock($form, $row)) {
            return true;
        }

        $submitBlock = submitBlock($form);
        if ($submitBlock.length) {
            $submitBlock.before($row);
            return true;
        }

        $passwordBlock = $form.find('input[type="password"]').last().closest('.form-group, .mb-4, .row').first();
        if ($passwordBlock.length) {
            $passwordBlock.after($row);
            return true;
        }

        $block = actionBlock($target);
        if ($block.length && $block.closest($form).length) {
            $block.before($row);
            return true;
        }

        return false;
    }

    function placePasswordResetWidget($target, $widget) {
        var $form = findPasswordResetForm($target);
        var $row = widgetRow($widget, 'pwreset');
        var $submitBlock;
        var $emailBlock;
        var $block;

        if (!$form.length) {
            return false;
        }

        if (replaceNativeCaptchaBlock($form, $row)) {
            return true;
        }

        $submitBlock = submitBlock($form);
        if ($submitBlock.length) {
            $submitBlock.before($row);
            return true;
        }

        $emailBlock = $form.find('input[type="email"], input[name="email"]').last().closest('.form-group, .row').first();
        if ($emailBlock.length) {
            $emailBlock.after($row);
            return true;
        }

        $block = actionBlock($target);
        if ($block.length && $block.closest($form).length) {
            $block.before($row);
            return true;
        }

        return false;
    }

    function placeFormSubmitWidget($target, $widget, purpose) {
        var $form = targetForm($target);
        var $row = widgetRow($widget, purpose);
        var $submitBlock;
        var $block;

        if (!$form.length) {
            return false;
        }

        if (replaceNativeCaptchaBlock($form, $row)) {
            return true;
        }

        $submitBlock = submitBlock($form);
        if ($submitBlock.length) {
            $submitBlock.before($row);
            return true;
        }

        $block = actionBlock($target);
        if ($block.length && $block.closest($form).length) {
            $block.before($row);
            return true;
        }

        return false;
    }

    function insertWidgetNearTarget($target, $widget, purpose) {
        if (purpose === 'checkout-order' && placeCheckoutOrderWidget($widget, $target)) {
            return;
        }

        if (purpose === 'checkout-login' && placeCheckoutLoginWidget($target, $widget)) {
            return;
        }

        if (purpose === 'register' && placeRegisterWidget($target, $widget)) {
            return;
        }

        if (purpose === 'login' && placeLoginWidget($target, $widget)) {
            return;
        }

        if (purpose === 'pwreset' && placePasswordResetWidget($target, $widget)) {
            return;
        }

        if ((purpose === 'contact' || purpose === 'ticket') && placeFormSubmitWidget($target, $widget, purpose)) {
            return;
        }

        actionBlock($target).before(widgetRow($widget, purpose));
    }

    function ensureCheckoutOrderWidget() {
        if (!hasPlacementPurpose('checkout-order')) {
            return;
        }

        if ($('.peakrack-turnstile[data-peakrack-purpose="checkout-order"][data-peakrack-rendered="1"]').length) {
            return;
        }

        var $form = $('#frmCheckout');
        if (!$form.length) {
            return;
        }

        var $widget = $('.peakrack-turnstile[data-peakrack-purpose="checkout-order"]').first();

        if (!$widget.length) {
            $widget = createWidget('checkout-order');
        }

        placeCheckoutOrderWidget($widget, lastVisible($('#btnCompleteOrder')).length ? lastVisible($('#btnCompleteOrder')) : $form);
        $form.attr('data-peakrack-turnstile-form', '1');
    }

    function relocateCheckoutOrderWidget() {
        var $widget = $('.peakrack-turnstile[data-peakrack-purpose="checkout-order"]').first();

        if (!$widget.length) {
            ensureCheckoutOrderWidget();
            return;
        }

        placeCheckoutOrderWidget($widget, lastVisible($('#btnCompleteOrder')));
    }

    function scheduleCheckoutOrderRelocation() {
        if (!hasPlacementPurpose('checkout-order')) {
            return;
        }

        relocateCheckoutOrderWidget();

        if (checkoutRelocationAttempts < 30) {
            checkoutRelocationAttempts++;
            window.setTimeout(scheduleCheckoutOrderRelocation, 200);
        }
    }

    function insertWidgets() {
        $.each(config.placements || [], function (_, placement) {
            $.each(placement.targets || [], function (_, selector) {
                bySelector(selector).each(function () {
                    var $target = $(this);
                    var $form = targetForm($target);
                    var $scope = $form.length ? $form : $target.closest('#containerExistingUserSignin');
                    var purpose = placement.purpose || '';
                    var hasExistingWidget = purpose
                        ? $('.peakrack-turnstile[data-peakrack-purpose="' + purpose + '"]').length
                        : $scope.find('.peakrack-turnstile').length;

                    if (!$scope.length || hasExistingWidget) {
                        return;
                    }

                    insertWidgetNearTarget($target, createWidget(purpose), purpose);
                });
            });

            $.each(placement.forms || [], function (_, selector) {
                bySelector(selector).each(function () {
                    var $candidate = $(this);
                    var $form = $candidate.is('form') ? $candidate : $candidate.closest('form');
                    if ($form.length && $form.find('.peakrack-turnstile').not('[data-peakrack-purpose="checkout-login"]').length) {
                        $form.attr('data-peakrack-turnstile-form', '1');
                    }
                });
            });
        });

        ensureCheckoutOrderWidget();
        $('form').has('.peakrack-turnstile:not([data-peakrack-purpose="checkout-login"])').attr('data-peakrack-turnstile-form', '1');
    }

    function renderWidgets() {
        if (!window.turnstile || typeof window.turnstile.render !== 'function') {
            if (renderAttempts < 50) {
                renderAttempts++;
                window.setTimeout(renderWidgets, 100);
            } else if (window.console && console.warn) {
                console.warn('Cloudflare Turnstile: api.js did not become ready.');
            }
            return;
        }

        $('.peakrack-turnstile[data-peakrack-managed="1"]').each(function () {
            var element = this;

            if (element.getAttribute('data-peakrack-rendered') === '1') {
                return;
            }

            try {
                var purpose = element.getAttribute('data-peakrack-purpose') || '';
                var options = {
                    sitekey: config.siteKey,
                    theme: config.theme,
                    size: 'normal',
                    action: purpose || 'form',
                    callback: function (token) {
                        element.setAttribute('data-peakrack-token', token || '');
                    },
                    'expired-callback': function () {
                        element.setAttribute('data-peakrack-token', '');
                        window.turnstile.reset(widgetId);
                    },
                    'error-callback': function (code) {
                        if (window.console && console.warn) {
                            console.warn('Cloudflare Turnstile error:', code);
                        }
                    }
                };

                if (purpose === 'checkout-login') {
                    options['response-field-name'] = 'peakrack-turnstile-checkout-login-response';
                }

                var widgetId = window.turnstile.render(element, options);

                element.setAttribute('data-peakrack-rendered', '1');
                element.setAttribute('data-peakrack-widget-id', widgetId);
            } catch (error) {
                if (window.console && console.warn) {
                    console.warn('Cloudflare Turnstile render failed:', error);
                }
            }
        });
    }

    function showCaptchaError() {
        if (window.location.search.indexOf('error=captcha') === -1 || $('.peakrack-turnstile-error').length) {
            return;
        }

        var $form = $('form[data-peakrack-turnstile-form="1"]').first();
        var $container = $form.closest('section, .container, .card, main, .login_form, .login-page, .peakrack-form-wrap').first();

        ($container.length ? $container : $form).prepend(
            $('<div/>', {
                'class': 'alert alert-danger peakrack-turnstile-error',
                text: config.messages.error
            }).css('margin-bottom', '20px')
        );
    }

    function tokenFrom($scope, fieldName) {
        var name = fieldName || 'cf-turnstile-response';
        var value = $.trim($scope.find('[name="' + name + '"]').val() || '');
        return value;
    }

    function tokenFromSubmitScope($form) {
        var token = '';
        var hasScopedWidget = false;

        $form.find('.peakrack-turnstile').not('[data-peakrack-purpose="checkout-login"]').each(function () {
            hasScopedWidget = true;
            token = $.trim(this.getAttribute('data-peakrack-token') || $(this).find('[name="cf-turnstile-response"]').val() || '');
            if (token) {
                return false;
            }
        });

        if (!token && $form.is('#frmCheckout')) {
            $('.peakrack-turnstile[data-peakrack-purpose="checkout-order"]').each(function () {
                hasScopedWidget = true;
                token = $.trim(this.getAttribute('data-peakrack-token') || $(this).find('[name="cf-turnstile-response"]').val() || '');
                if (token) {
                    return false;
                }
            });
        }

        return hasScopedWidget ? token : tokenFrom($form);
    }

    function syncSubmitToken($form) {
        var token = tokenFromSubmitScope($form);
        if (!token) {
            return '';
        }

        var $field = $form.find('input[name="cf-turnstile-response"][data-peakrack-proxy="1"]').first();
        if (!$field.length) {
            $field = $('<input/>', {
                type: 'hidden',
                name: 'cf-turnstile-response',
                'data-peakrack-proxy': '1'
            }).appendTo($form);
        }
        $field.val(token);

        return token;
    }

    function checkoutLoginToken() {
        var $scope = $('#containerExistingUserSignin');
        if (!$scope.length) {
            $scope = $('#btnExistingLogin').parent();
        }
        var $widget = $scope.find('.peakrack-turnstile[data-peakrack-purpose="checkout-login"]').first();
        return $.trim(($widget.length ? $widget.attr('data-peakrack-token') : '') || tokenFrom($scope, 'peakrack-turnstile-checkout-login-response'));
    }

    function installCheckoutAjaxBridge() {
        if (!config.checkoutLogin) {
            return;
        }

        $.ajaxPrefilter(function (options) {
            var url = String(options.url || '');
            if (url.indexOf('login/cart') === -1) {
                return;
            }

            var token = checkoutLoginToken();
            if ($.isPlainObject(options.data)) {
                options.data['cf-turnstile-response'] = token;
            } else if (typeof options.data === 'string') {
                options.data += (options.data.length ? '&' : '') + 'cf-turnstile-response=' + encodeURIComponent(token);
            } else {
                options.data = 'cf-turnstile-response=' + encodeURIComponent(token);
            }
        });

        document.addEventListener('click', function (event) {
            if (!event.target || !event.target.closest || !event.target.closest('#btnExistingLogin')) {
                return;
            }

            if (!checkoutLoginToken()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                window.alert(config.messages.prompt);
            }
        }, true);

        $(document).ajaxError(function (event, jqXHR, ajaxSettings) {
            var url = String(ajaxSettings.url || '');
            if (url.indexOf('login/cart') === -1 || !window.turnstile || typeof window.turnstile.reset !== 'function') {
                return;
            }

            $('#containerExistingUserSignin .peakrack-turnstile').each(function () {
                var widgetId = this.getAttribute('data-peakrack-widget-id');
                if (widgetId) {
                    window.turnstile.reset(widgetId);
                }
            });
        });
    }

    $(function () {
        insertWidgets();
        scheduleCheckoutOrderRelocation();
        renderWidgets();
        showCaptchaError();
        installCheckoutAjaxBridge();

        $(document).off('submit.peakrackTurnstile').on('submit.peakrackTurnstile', 'form[data-peakrack-turnstile-form="1"]', function (event) {
            if (!syncSubmitToken($(this))) {
                event.preventDefault();
                window.alert(config.messages.prompt);
                return false;
            }
        });
    });
})(window, window.jQuery);
</script>
HTML;

    return $css . str_replace('__MEGABRE_TURNSTILE_CONFIG__', $configJson, $script);
});

add_hook('UserLoginVerification', 1, function ($vars) {
    if (peakrack_turnstile_is_cart_login_request() && peakrack_turnstile_is_enabled('enable_cart')) {
        return;
    }

    if (peakrack_turnstile_is_enabled('enable_login') && !peakrack_turnstile_post_is_valid()) {
        return peakrack_turnstile_text('error');
    }
});

add_hook('ClientDetailsValidation', 1, function ($vars) {
    if (!isset($_SESSION['uid']) && peakrack_turnstile_is_enabled('enable_register') && !peakrack_turnstile_post_is_valid()) {
        return [peakrack_turnstile_text('error')];
    }
});

add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    if (peakrack_turnstile_is_enabled('enable_cart') && !peakrack_turnstile_post_is_valid()) {
        return peakrack_turnstile_text('error');
    }
});

add_hook('TicketOpenValidation', 1, function ($vars) {
    if (peakrack_turnstile_is_enabled('enable_ticket') && !peakrack_turnstile_post_is_valid()) {
        return peakrack_turnstile_text('error');
    }
});

add_hook('ClientAreaPageContact', 1, function ($vars) {
    if (peakrack_turnstile_is_enabled('enable_contact') && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!isset($_POST['action']) || $_POST['action'] !== 'send') {
            return;
        }

        if (!peakrack_turnstile_post_is_valid()) {
            header('Location: contact.php?error=captcha');
            exit;
        }
    }
});
