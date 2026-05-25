# PeakRack Turnstile Manager for WHMCS

> Official repository: https://github.com/Techshrr/whmcs_peakrack_turnstile
> License: MIT License

PeakRack Turnstile Manager is a WHMCS addon that adds Cloudflare Turnstile checks to selected client-area forms.

## Overview

The addon injects Turnstile widgets into selected WHMCS client-area forms and verifies submitted Turnstile tokens server-side through WHMCS hooks.

It includes placement logic for WHMCS built-in templates and several common order form layouts. Custom selectors can be configured for heavily customized themes.

## Features

- Supports login, registration, password reset, contact, support ticket submission, and cart checkout pages.
- Includes placement handling for WHMCS Nexus, Six, Twenty-One, Lagom/Lagom2, Standard Cart, Nexus Cart, and common order form templates.
- Provides settings for Cloudflare Site Key, Secret Key, widget theme, widget alignment, page toggles, and custom selectors.
- Adds Turnstile responses to checkout-page existing-customer login requests sent to `/login/cart`.
- Uses explicit Turnstile rendering for widgets inserted by hooks.
- Verifies submitted tokens server-side and caches per-request verification results.
- Provides a Chinese and English admin language switch.

## Requirements

- WHMCS 9.0.x
- PHP 8.2 or later
- Cloudflare Turnstile Site Key and Secret Key

## Installation

1. Download the latest release from the official repository.
2. Upload the addon directory to:

   `modules/addons/peakrack_turnstile/`

3. Log in to the WHMCS admin area.
4. Go to **System Settings > Addon Modules** and activate **PeakRack Turnstile Manager**.
5. Grant access permissions to the relevant admin role group.
6. Open **Addons > PeakRack Turnstile Manager** and configure the keys and page toggles.

## Configuration

| Option | Description | Default |
|---|---|---|
| Site Key | Cloudflare Turnstile site key used by frontend widgets | Empty |
| Secret Key | Cloudflare Turnstile secret key used for server verification | Empty |
| Theme | Widget theme | auto |
| Alignment | Widget alignment | center |
| Enable login | Adds verification to the client login page | Disabled |
| Enable register | Adds verification to the registration page | Disabled |
| Enable password reset | Adds verification to password reset requests | Disabled |
| Enable contact | Adds verification to the contact form | Disabled |
| Enable ticket | Adds verification to new ticket submission | Disabled |
| Enable cart | Adds verification to cart checkout | Disabled |
| Custom selectors | Additional selectors for customized themes | Empty |

## Usage

The administrator configures the Cloudflare keys, chooses widget theme and alignment, and enables the pages that require verification. Clients see a Turnstile widget on enabled forms. Submissions are rejected when the Turnstile token is missing or fails server-side verification.

Disable WHMCS built-in captcha if you do not want duplicate captcha widgets on the same forms.

## Upgrade

See [UPGRADE.md](UPGRADE.md).

## Chinese Documentation

See [README.zh-CN.md](README.zh-CN.md).

## Security

Do not commit production credentials, API keys, database passwords, payment secrets, WHMCS license data, customer data, identity documents, or private signing keys.

To report a security issue, see [SECURITY.md](SECURITY.md).

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
