# PeakRack Turnstile WHMCS 模块

Cloudflare Turnstile integration for WHMCS — a free, open-source, privacy-friendly alternative to Google reCAPTCHA.

## Features

- **seamless Integration**: Works with standard WHMCS themes (Six, Twenty-One) and custom themes.
- **Admin Dashboard**: Fully custom configuration interface directly within WHMCS Addons.
- **Page Control**: Enable/Disable Turnstile specifically for:
  - Login Page
  - Registration Page
  - Password Reset
  - Contact Us
  - Support Ticket Submission
  - Shopping Cart / Checkout
- **Theme Support**: Choose between `Auto`, `Light`, or `Dark` widgets.
- **Explicit Rendering**: Uses Cloudflare's explicit rendering API so widgets inserted by WHMCS hooks are rendered reliably.
- **Request Safety**: Adds Siteverify timeouts and per-request token result caching to avoid duplicate validation failures.
- **Advanced Selectors**: Define custom jQuery selectors to inject the widget into ANY form on any theme without editing template files.
- **Smarty Tag Support**: Use `{display_turnstile}` in your `.tpl` files for manual placement.

## Installation

1. Download the repository.
2. Upload the folder `peakrack_turnstile` to your WHMCS installation at:
   `/modules/addons/peakrack_turnstile/`
3. Log in to your WHMCS Admin Area.
4. Go to **System Settings > Addon Modules**.
5. Find **PeakRack Turnstile 管理器** and click **Activate**.
6. Click **Configure** to grant access permissions to your admin role group.

## Configuration

1. Go to **Addons > PeakRack Turnstile 管理器**.
2. Enter your Cloudflare **Site Key** and **Secret Key**.
3. Toggle the pages where you want the captcha to appear.
4. (Optional) If using a custom theme that isn't auto-detected, enter the jQuery selector for the submit button (e.g., `.btn-login` or `#submit-btn`) in the **"高级设置：自定义选择器"** section.
5. Click **保存配置**.

## Important Note

To avoid conflicts, please disable the default WHMCS Captcha:
1. Go to **System Settings > General Settings > Security**.
2. Set **Captcha Form Protection** > **Captcha Type** to **"Always Off"**.

*Note: This module automatically attempts to hide legacy captcha elements via CSS, but disabling them in settings is recommended.*

## Manual Usage (Developers)

If you prefer to place the widget manually in your template files, you can use the Smarty tag:

```html
<form method="post" action="login.php">
    ...
    <!-- Manual Placement -->
    {display_turnstile}
    
    <button type="submit">Login</button>
</form>
```

## License

This project is open-source and available under the MIT License.
