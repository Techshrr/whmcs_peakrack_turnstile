# Upgrade Notes

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
