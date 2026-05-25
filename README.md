# WHMCS PeakRack Turnstile

PeakRack Turnstile is a WHMCS addon module that replaces legacy captcha output with Cloudflare Turnstile.

Chinese documentation: [README.zh-CN.md](README.zh-CN.md)

## Features

- Supports WHMCS 9.x client-area pages: login, registration, password reset, contact, support ticket submission, and shopping cart checkout.
- Prioritizes built-in WHMCS templates Nexus, Six, and Twenty-One, with compatibility handling for Lagom/Lagom2.
- Covers Standard Cart, Nexus Cart, Lagom Cart/Lagom Checkout, and common WHMCS order form templates.
- Admin page for Site Key, Secret Key, page toggles, widget theme, widget alignment, and custom selectors.
- Cloudflare default visual widget width with selectable center or left alignment.
- Handles checkout terms-of-service placement and keeps the widget near the intended submit action.
- Handles dynamically rendered cart and checkout forms with DOM mutation monitoring.
- Adds Turnstile tokens to checkout-page existing customer login requests sent to `/login/cart`.
- Explicit Turnstile rendering for widgets inserted by WHMCS hooks.
- Server-side token verification with per-request result caching.
- Turnstile manager page includes a Chinese / English admin language switch.

## Requirements

- WHMCS 9.x
- PHP 8.2 or PHP 8.3
- Cloudflare Turnstile Site Key and Secret Key
- File deployment access to the WHMCS `modules/addons/` directory

## Installation

The repository root is intentionally shallow for GitHub browsing. The deployable addon is the `peakrack_turnstile` directory:

```text
peakrack_turnstile/
```

Upload or copy it to this WHMCS path:

```text
modules/addons/peakrack_turnstile/
```

The final addon path should be:

```text
modules/addons/peakrack_turnstile/hooks.php
modules/addons/peakrack_turnstile/peakrack_turnstile.php
```

Then in WHMCS Admin:

1. Go to **System Settings > Addon Modules**.
2. Activate **PeakRack Turnstile Manager**.
3. Grant access permissions to the relevant admin role group.
4. Open **Addons > PeakRack Turnstile Manager**.
5. Enter the Cloudflare Turnstile Site Key and Secret Key.
6. Enable the pages that should require verification.
7. Choose the frontend widget alignment. Center alignment is recommended for most themes; left alignment is available for templates with left-aligned action areas.

## Recommended WHMCS Setting

Disable the built-in WHMCS captcha to avoid duplicate captcha widgets:

```text
System Settings > General Settings > Security > Captcha Form Protection
```

Set the captcha type to **Always Off**.

## Theme And Cart Compatibility

The default selectors cover:

- WHMCS built-in themes: Nexus, Six, Twenty-One
- Lagom / Lagom2 client-area themes
- Standard Cart
- Nexus Cart
- Lagom Cart / Lagom Checkout
- Common legacy WHMCS order forms, including Legacy Boxes, Legacy Modern, Premium Comparison, Pure Comparison, Supreme Comparison, and Universal Slider

For heavily customized themes, use **Advanced Settings: Custom Selectors** in the addon manager to target the desired form or submit action. Custom selectors should only be needed when the default placement logic cannot identify the local template structure.

## Release Notes

### 1.4.7

- Improved Turnstile placement across Lagom, Nexus, Six, Twenty-One, Standard Cart, Nexus Cart, and Lagom checkout layouts.
- Added DOM mutation handling for dynamically rendered cart and checkout forms.
- Improved token synchronization and removed reliance on deprecated jQuery trim helpers.

### 1.4.6

- Fixed checkout-page existing customer login AJAX requests to include `cf-turnstile-response`.
- Returns JSON for `/login/cart` captcha failures instead of redirecting to `login.php?error=captcha`.
- Prevents hidden checkout login containers from placing a widget in the complete-order area.

### 1.4.5

- Fixed the WHMCS addon title to `PeakRack Turnstile Manager`.
- Stabilized the top-right version badge and language switch so it no longer shifts with translated text length.

### 1.4.4

- Added a Chinese / English admin language switch to the Turnstile manager.
- Localized the main manager UI text.

### 1.4.1

- Added a global frontend alignment option: center or left.
- Kept the Cloudflare default visual widget width across supported templates.
- Improved placement consistency across Nexus, Six, Twenty-One, and Lagom/Lagom2 pages.

### 1.4.2

- Renamed the local/repository package shape to `whmcs_peakrack_turnstile`.
- Normalized the deployable files under `whmcs_peakrack_turnstile/modules` for consistent WHMCS plugin releases.

### 1.4.3

- Flattened the GitHub repository layout so `peakrack_turnstile/` is visible at the root.
- Updated installation and upgrade documentation for the direct addon-folder layout.

Detailed upgrade notes: [UPGRADE.md](UPGRADE.md).

## License

MIT License. See [LICENSE](LICENSE).
