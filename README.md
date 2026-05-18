# WHMCS PeakRack Turnstile

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
- Turnstile manager page includes a Chinese / English admin language switch.

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
modules/addons/peakrack_turnstile/
```

Then in WHMCS Admin:

1. Go to **System Settings > Addon Modules**.
2. Activate **PeakRack Turnstile Manager**.
3. Grant access permissions to the relevant admin role group.
4. Open **Addons > PeakRack Turnstile Manager**.
5. Enter the Cloudflare Turnstile Site Key and Secret Key.
6. Enable the pages that should require verification.

## Recommended WHMCS Setting

Disable the built-in WHMCS captcha to avoid duplicate captcha widgets:

```text
System Settings > General Settings > Security > Captcha Form Protection
```

Set the captcha type to **Always Off**.

## Release Notes

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
