# Temple admin password recovery — deployment checklist

Status: **code staged, disabled by default**. Do not enable on a public deployment until the checks below pass.

## Current flow
1. User expands Forgot password and enters their email.
2. Only an **enabled user with an already verified email** gets a pending request. The response is deliberately identical for all email addresses.
3. The Super Admin can approve (send email) or reject from the dashboard.
4. A CLI scheduler may send a link after 30 minutes if the request is still pending.
5. The link is random, hashed at rest, valid for 30 minutes, and single use. Passwords must be at least 12 characters.

## Required before activation
- Set up a real email transport. The current PHP implementation calls `mail()`, which may **not work** on Windows PHP, shared hosting, or cloud hosting without configuration. Verify delivery and SPF/DKIM/DMARC. Use an authenticated mail service in production rather than committing credentials.
- Configure environment variables outside the repository:
  - `HM_RECOVERY_ENABLED=1` (leave unset until verified)
  - `HM_RECOVERY_FROM=hindu@mamanram.no`
  - `HM_RECOVERY_ADMIN_EMAIL=hindu@mamanram.no`
  - `HM_RECOVERY_BASE_URL=https://YOUR-ACTUAL-TEMPLE-DOMAIN` (must be HTTPS and point to this deployment)
- Existing users currently have no registered verified email. Securely enroll and verify each user's email **out of band** before adding `email` and `email_verified: true` to the private `hm-private/users.json` record. Never infer a verified email from a form submission. Never commit this private file.
- Schedule `php bin/recovery-cron.php` every 1–5 minutes **from the server CLI**. Without this job, 30-minute automatic fallback does not run.
- Test on a staging HTTPS environment with a dedicated test account and mailbox: request, approval, rejection, fallback, expiry, reuse, unknown email, and failed mail delivery.
- Add a secure email-enrollment UI and session revocation on password change before relying on this as a production-grade recovery feature. Existing sessions are not currently revoked.
- Verify the private directory is outside the public document root. Keep backups before modifying accounts.

## Local checkout
```powershell
git fetch origin
git restore --source=origin/design/admin-login-responsive --worktree -- admin/index.php css/admin-login.css includes/recovery.php admin/reset-password.php bin/recovery-cron.php
php -l .\admin\index.php
php -l .\includes\recovery.php
php -l .\admin\reset-password.php
php -l .\bin\recovery-cron.php
```

**Important:** Until a verified email, email delivery, HTTPS URL, and scheduler are configured, requests do not generate reset emails. The UI is a staged feature, not a working production email service.
