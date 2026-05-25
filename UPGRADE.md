# Upgrade Notes

## 1.4.7

- Improved Turnstile placement for login, registration, password reset, contact, ticket submission, cart, and checkout pages across Lagom, Nexus, Six, and Twenty-One themes.
- Refined placement around terms-of-service and submit actions for Standard Cart, Nexus Cart, Lagom Cart, and Lagom Checkout layouts.
- Added DOM mutation handling for cart and checkout forms that are rendered or updated by frontend scripts.
- Removed reliance on deprecated jQuery trim helpers in token synchronization code.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy `peakrack_turnstile/` to `modules/addons/peakrack_turnstile/`.
- Addon version bumped to `1.4.7`.

## 1.4.6

- Fixed checkout-page existing customer login when `/login/cart` is submitted through AJAX without a Turnstile token.
- Returns a JSON error for checkout login captcha failures instead of redirecting to `login.php?error=captcha`, preventing WHMCS frontend `parsererror` responses.
- Prevents hidden checkout login containers from placing their Turnstile widget in the complete-order area.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy `peakrack_turnstile/` to `modules/addons/peakrack_turnstile/`.
- Addon version bumped to `1.4.6`.

## 1.4.5

- Fixed the WHMCS addon title to `PeakRack Turnstile Manager`.
- Stabilized the top-right version badge and language switch layout.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy `peakrack_turnstile/` to `modules/addons/peakrack_turnstile/`.
- Addon version bumped to `1.4.5`.

## 1.4.4

- Added a top-right Chinese / English admin language switch to the Turnstile manager page.
- Localized the main Turnstile manager UI labels, notices, table headings, and save message.
- Addon version bumped to `1.4.4`.

## 1.4.3

- Repository layout only: the deployable addon now lives at repository root as `peakrack_turnstile/`.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy `peakrack_turnstile/` to `modules/addons/peakrack_turnstile/`.
- Addon version bumped to `1.4.3`.

## 1.4.2

- Repository layout only: deployable files now live under `whmcs_peakrack_turnstile/modules`.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy the new `whmcs_peakrack_turnstile/modules` directory contents over your WHMCS root.
- Addon version bumped to `1.4.2`.

## 1.4.1

- Added a global frontend alignment option: center or left.
- Kept the Cloudflare default visual widget width across supported templates.
- Improved placement consistency across Nexus, Six, Twenty-One, and Lagom/Lagom2 pages.
