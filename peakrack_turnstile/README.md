# WHMCS PeakRack Turnstile

PeakRack Turnstile is a WHMCS addon module that replaces legacy captcha output with Cloudflare Turnstile.

Chinese documentation: [README.zh-CN.md](README.zh-CN.md)

## Features

- Supports WHMCS 9.x client-area pages: login, registration, password reset, contact, support ticket submission, and shopping cart checkout.
- Prioritizes built-in WHMCS templates Nexus, Six, and Twenty-One, with compatibility handling for Lagom/Lagom2.
- Covers Standard Cart, Nexus Cart, Lagom Cart/Lagom Checkout, and common WHMCS order form templates.
- Admin page for Site Key, Secret Key, page toggles, widget theme, widget alignment, and custom selectors.
- Cloudflare default visual widget width with selectable center or left alignment.
- Handles checkout terms-of-service placement whether the WHMCS terms checkbox is enabled or disabled.
- Adds Turnstile tokens to checkout-page existing customer login requests sent to `/login/cart`.
- Handles dynamically rendered cart and checkout forms with DOM mutation monitoring.
- Explicit Turnstile rendering for widgets inserted by WHMCS hooks.
- Server-side token verification with per-request result caching.
- Turnstile manager page includes a Chinese / English admin language switch.

## Installation

Upload this directory to:

```text
modules/addons/peakrack_turnstile/
```

Then activate and configure **PeakRack Turnstile Manager** in WHMCS Admin.

## Recommended WHMCS Setting

Disable the built-in WHMCS captcha to avoid duplicate captcha widgets:

```text
System Settings > General Settings > Security > Captcha Form Protection
```

Set the captcha type to **Always Off**.

## Release Notes

### 1.4.7

- Improved Turnstile placement across Lagom, Nexus, Six, Twenty-One, Standard Cart, Nexus Cart, and Lagom checkout layouts.
- Added DOM mutation handling for dynamically rendered cart and checkout forms.
- Improved token synchronization and removed reliance on deprecated jQuery trim helpers.

### 1.4.6

- Fixed checkout-page existing customer login AJAX requests to include `cf-turnstile-response`.
- Returns JSON for `/login/cart` captcha failures instead of redirecting to `login.php?error=captcha`.
- Prevents hidden checkout login containers from placing a widget in the complete-order area.

### 1.4.4

- Added a Chinese / English admin language switch to the Turnstile manager.
- Localized the main manager UI text.

### 1.4.3

- Repository root now shows this addon directory directly for easier GitHub browsing.

### 1.4.1

- Added a global frontend alignment option: center or left.
- Kept the Cloudflare default visual widget width across supported templates.
- Improved placement consistency across Nexus, Six, Twenty-One, and Lagom/Lagom2 pages.

## License

MIT License. See the repository root [LICENSE](../LICENSE).
