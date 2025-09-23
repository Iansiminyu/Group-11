# Copilot Instructions for Smart Restaurant Reservation & Ordering System

## Project Overview
This is a PHP-based web application for restaurant reservation and ordering. The codebase is organized as a monolithic app with core logic in top-level PHP files and supporting libraries in `vendor/` (managed by Composer). The UI is styled with custom CSS in `assets/`.

## Key Components
- **Authentication & 2FA:**
  - `login.php`, `register.php`, `logout.php`, `verify_2fa.php`, `enable_2fa.php` handle user authentication and two-factor verification.
  - 2FA codes are validated via `verify2FACode()` (see `helpers.php`).
  - Rate limiting and session management are enforced in `verify_2fa.php`.
- **Dashboard & Main Logic:**
  - `dashboard.php` is the main user landing page after login.
- **Configuration:**
  - `config.php` contains database and app configuration.
- **Helpers:**
  - `helpers.php` provides utility functions (e.g., 2FA code verification).
- **Assets:**
  - `assets/` contains CSS, JS, and static files.
- **Dependencies:**
  - Managed via Composer (`composer.json`, `composer.lock`, `vendor/`).
  - Key packages: PHPMailer, robthree/twofactorauth, guzzlehttp/guzzle, etc.

## Developer Workflows
- **Install dependencies:**
  - Run `composer install` in the project root to install PHP packages.
- **Debugging:**
  - Use `error_log()` for server-side logging (see `verify_2fa.php`).
  - Check `email_success.log` for email-related events.
- **Testing:**
  - No formal test suite detected. Manual testing via browser is typical.
- **Session Management:**
  - PHP sessions are used for authentication and rate limiting.

## Project-Specific Patterns
- **2FA Rate Limiting:**
  - Attempts are tracked in `$_SESSION['2fa_attempts']` and reset after 15 minutes.
  - Lockout after 5 failed attempts (see `verify_2fa.php`).
- **Security Notices:**
  - Codes expire in 10 minutes; never share codes (see UI in `verify_2fa.php`).
- **Error Handling:**
  - Errors are displayed inline in forms and logged via `error_log()`.

## Integration Points
- **Email:**
  - PHPMailer is used for sending emails (see `vendor/phpmailer/` and `PHPMailer-6.8.1/`).
- **2FA:**
  - robthree/twofactorauth is used for generating and verifying codes.

## Conventions
- All PHP files use procedural style with some helper functions.
- UI feedback is provided via inline alerts and warnings.
- Composer is the only package manager; do not manually edit `vendor/`.

## Example: 2FA Verification Flow
1. User logs in, receives a 2FA code.
2. Enters code in `verify_2fa.php`.
3. Code is validated via `verify2FACode()`.
4. On success, session is updated and user is redirected to `dashboard.php`.
5. On failure, attempts are tracked and lockout enforced after 5 tries.

---
**For AI agents:**
- Always use Composer for dependency management.
- Reference `helpers.php` for utility functions.
- Follow session-based rate limiting for authentication flows.
- When adding new features, match the procedural PHP style and UI feedback patterns.
