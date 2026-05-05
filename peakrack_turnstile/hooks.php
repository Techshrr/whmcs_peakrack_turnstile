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
    $siteKey = peakrack_turnstile_get_site_key();
    if ($siteKey === '') {
        return '';
    }

    return '<div class="peakrack-turnstile" data-sitekey="' . htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8') . '" data-theme="' . htmlspecialchars(peakrack_turnstile_get_theme(), ENT_QUOTES, 'UTF-8') . '"></div>';
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
    $placements[] = [
        'targets' => $custom !== '' ? [$custom] : $targets,
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
            '.peakrack-login-wrap form button[type="submit"]',
            'form.login-form button[type="submit"], form.login-form input[type="submit"]',
            'form[action*="dologin"] button[type="submit"], form[action*="dologin"] input[type="submit"]',
            'form[action*="login/validate"] button[type="submit"], form[action*="login/validate"] input[type="submit"]',
            'form[action*="login%2fvalidate"] button[type="submit"], form[action*="login%2fvalidate"] input[type="submit"]',
        ], [
            'form.login-form',
            '.peakrack-login-wrap form',
            'form[action*="dologin"]',
            'form[action*="login/validate"]',
            'form[action*="login%2fvalidate"]',
        ]);
    }

    if ($templatefile === 'clientregister') {
        peakrack_turnstile_add_placement($placements, 'enable_register', 'custom_register_sel', [
            '.peakrack-register-wrap form button[type="submit"]',
            '#btnRegister',
            'form:has(input[name="register"][value="true"]) button[type="submit"], form:has(input[name="register"][value="true"]) input[type="submit"]',
            'form#frmCheckout button[type="submit"], form#frmCheckout input[type="submit"]',
        ], [
            '.peakrack-register-wrap form',
            'form[action*="register"]',
            'form:has(input[name="register"][value="true"])',
            'form#frmCheckout',
        ]);
    }

    if (peakrack_turnstile_is_enabled('enable_pwreset')) {
        peakrack_turnstile_add_placement($placements, 'enable_pwreset', 'custom_pwreset_sel', [
            'input[type="hidden"][name="action"][value="reset"]',
            'form[action*="pwreset"] input[name="email"]',
            'form[action*="password-reset"] input[name="email"]',
            'form[action*="password%2freset"] input[name="email"]',
        ], [
            'input[type="hidden"][name="action"][value="reset"]',
            'form[action*="password-reset-validate-email"]',
            'form[action*="password%2freset%2fvalidate-email"]',
            'form[action*="pwreset"]',
        ]);
    }

    if ($templatefile === 'supportticketsubmit-stepone' || $templatefile === 'supportticketsubmit-steptwo') {
        peakrack_turnstile_add_placement($placements, 'enable_ticket', 'custom_ticket_sel', [
            '#openTicketSubmit',
        ], [
            'form[action*="submitticket"]',
            'form',
        ]);
    }

    if ($templatefile === 'contact') {
        peakrack_turnstile_add_placement($placements, 'enable_contact', 'custom_contact_sel', [
            '.peakrack-contact-form-wrap form button[type="submit"]',
            'form[action*="contact"] button[type="submit"], form[action*="contact"] input[type="submit"]',
        ], [
            'form[action*="contact.php"]',
            '.peakrack-contact-form-wrap form',
        ]);
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
        ], [
            '#frmCheckout',
            'form[action*="cart"]',
        ], 'checkout-order');
    }

    $config = [
        'siteKey' => $siteKey,
        'theme' => peakrack_turnstile_get_theme(),
        'placements' => $placements,
        'checkoutLogin' => $checkoutLogin,
        'messages' => [
            'prompt' => peakrack_turnstile_text('prompt'),
            'error' => peakrack_turnstile_text('error'),
        ],
    ];

    $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    $css = '<style>
        .g-recaptcha,
        #google-recaptcha-domainchecker,
        .recaptcha-container,
        #default-captcha-domainchecker,
        .default-captcha,
        #captchaContainer,
        #inputCaptcha,
        #inputCaptchaImage {
            display: none !important;
        }
        .peakrack-turnstile {
            display: block !important;
            margin: 15px 0;
            min-height: 65px;
        }
    </style>';

    $script = <<<'HTML'
<script>
(function (window, $) {
    'use strict';

    var config = __MEGABRE_TURNSTILE_CONFIG__;
    var renderAttempts = 0;

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
            'data-theme': config.theme
        };

        if (purpose) {
            attributes['data-peakrack-purpose'] = purpose;
        }

        return $('<div/>', attributes);
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

    function insertWidgets() {
        $.each(config.placements || [], function (_, placement) {
            $.each(placement.targets || [], function (_, selector) {
                bySelector(selector).each(function () {
                    var $target = $(this);
                    var $form = targetForm($target);
                    var $scope = $form.length ? $form : $target.closest('#containerExistingUserSignin');

                    if (!$scope.length || $scope.find('.peakrack-turnstile').length) {
                        return;
                    }

                    $target.before(createWidget(placement.purpose || ''));
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

        $('.peakrack-turnstile').each(function () {
            var element = this;

            if (element.getAttribute('data-peakrack-rendered') === '1') {
                return;
            }

            try {
                var purpose = element.getAttribute('data-peakrack-purpose') || '';
                var options = {
                    sitekey: config.siteKey,
                    theme: config.theme,
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

        return hasScopedWidget ? token : tokenFrom($form);
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
        renderWidgets();
        showCaptchaError();
        installCheckoutAjaxBridge();

        $(document).off('submit.peakrackTurnstile').on('submit.peakrackTurnstile', 'form[data-peakrack-turnstile-form="1"]', function (event) {
            if (!tokenFromSubmitScope($(this))) {
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
