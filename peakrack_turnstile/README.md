# PeakRack Turnstile for WHMCS

PeakRack Turnstile is a WHMCS addon module that replaces legacy captcha output with Cloudflare Turnstile.

Chinese documentation: [README.zh-CN.md](README.zh-CN.md)

## Features

- Supports WHMCS 9.x client-area pages: login, registration, password reset, contact, support ticket submission, and shopping cart checkout.
- Prioritizes built-in WHMCS templates Nexus, Six, and Twenty-One, with compatibility handling for Lagom/Lagom2.
- Admin page for Site Key, Secret Key, page toggles, widget theme, widget alignment, and custom selectors.
- Cloudflare default visual widget width with selectable center or left alignment.
- Handles checkout terms-of-service placement whether the WHMCS terms checkbox is enabled or disabled.
- Explicit Turnstile rendering for widgets inserted by WHMCS hooks.
- Server-side token verification with per-request result caching.

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

### 1.4.1

- Added a global frontend alignment option: center or left.
- Kept the Cloudflare default visual widget width across supported templates.
- Improved placement consistency across Nexus, Six, Twenty-One, and Lagom/Lagom2 pages.

## License

MIT License. See [../LICENSE](../LICENSE).
