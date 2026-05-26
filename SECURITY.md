# Security Policy

## Reporting a vulnerability

Please do not open public GitHub issues for security vulnerabilities.

Report Turnstile bypasses, token-validation problems, or unsafe handling of Cloudflare credentials to:

security@peakrack.com

Please include:

- Affected module version, WHMCS version, and PHP version
- The enabled Turnstile placement, such as login, registration, ticket, contact, or cart
- Description of the issue and reproduction steps
- Potential impact on client-area form submission
- Suggested mitigation, if available

## Supported versions

| Version | Supported |
|---|---|
| 1.x | Yes |
| < 1.0 | No |

## Sensitive data

Do not include production Turnstile secret keys, challenge tokens, WHMCS session values, admin credentials, client IP addresses tied to real accounts, or server logs containing customer identifiers.

## Public issues

Installation problems, selector compatibility bugs, and documentation fixes may be submitted through GitHub Issues.

Security vulnerabilities must be reported privately by email.
