# Temple Email (admin/email.php)

The admin dashboard now links to a Temple Email composer for enabled accounts with the `email_manager` or `super_admin` role. It includes editable Poojai, Festival, Membership, and General templates.

## Safety and deployment

Email sending is **disabled by default**. Do not put SMTP passwords in GitHub, JavaScript, or publicly served PHP files. On the eventual PHP host, configure these environment variables outside the public web root:

- `HM_TEMPLE_EMAIL_ENABLED=1`
- `HM_SMTP_HOST=<verified Loopia SMTP hostname>`
- `HM_SMTP_PORT=587`
- `HM_SMTP_USER=<mailbox login>`
- `HM_SMTP_PASSWORD=<mailbox password>`
- `HM_SMTP_FROM=hindu@mamanram.no`

The SMTP implementation requires authenticated STARTTLS on port 587 and validates the remote TLS certificate using PHP stream defaults. Verify the Loopia SMTP settings and hosting outbound port access before enabling it. If Loopia requires a different configuration, update and test the transport before enabling it.

Before going live, test role restrictions, invalid CSRF, invalid addresses, SMTP failure, correct recipient privacy, and sending to one committee-approved test address. The composer currently supports manually entered recipients (up to 20 per submission). It does not yet import Google Contacts, automatically populate BCC from contacts, or sync sent messages to Webmail. Avoid bulk sends until consent and mail-provider limits are confirmed.

No changes to the domain redirect or the existing mailbox are needed during development.
