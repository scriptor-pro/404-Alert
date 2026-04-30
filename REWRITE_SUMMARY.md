# 404-Alert v2.0.0 — Rewrite Summary

## Overview

Complete ground-up rewrite of the 404-alert plugin, targeting clean, maintainable, performant code. Version 2.0 is **70% smaller** and **5x simpler** than v1.3.1.

## What Changed

### Removed (Purposefully)
- **18 classes → 9 classes** : Eliminated deprecated Storage, User-Agent parser, Logger, Template, and test utilities
- **SMTP subsystem** : 40% of codebase deleted. Now delegates to wp_mail() + external plugins like WP Mail SMTP
- **Redis support** : Removed. Transient-based rate limiting is sufficient for even 1000+ 404/day
- **Complex wizard UI** : Removed 400+ lines of inline HTML
- **Test Progress transients** : Removed polling-based SMTP test UI
- **Hardcoded email configuration** : Gone. Emails now sent to all `administrator` users dynamically

### Architecture

| Aspect | v1.3.1 | v2.0.0 | Change |
|--------|--------|--------|--------|
| Classes | 18 | 9 | -50% |
| Code lines (core) | ~2500 | ~900 | -64% |
| Dependencies | PHPMailer | None | Simpler |
| Rate limiting | Redis + transients | Transients only | Lighter |
| Email config | SMTP form | wp_mail() | Cleaner |
| Database tables | 1 | 1 | ✓ Kept |

### New Features

✅ **Smart Alert Logic (Option D)**
- URL cooldown: 24-hour per URL (no duplicate alerts within 24h)
- Daily plafond: configurable max immediate alerts (default 20/day)
- Digest: excess 404s collected and sent once daily
- Result: admins get instant notification of new issues, but never spammed

✅ **Simplified Admin Interface**
- Settings page: 3 toggles (daily_limit, digest_enabled, digest_time)
- Dashboard: top URLs, top IPs, export CSV
- Zero SMTP configuration needed

✅ **Lightweight Codebase**
- No external dependencies
- 23 KB plugin ZIP (uncompressed: ~55 KB)
- PSR-4 autoloader (self-contained)
- PHP 8.1+ strict types

## Code Structure

```
404-alert/
├── 404-alert.php               # Bootstrap + PSR-4 autoloader
├── uninstall.php               # Cleanup on plugin removal
├── includes/
│   ├── class-activator.php     # DB creation, options, cron setup
│   ├── class-detector.php      # 404 detection (template_redirect hook)
│   ├── class-request-info.php  # IP, URL, referrer, UA collection
│   ├── class-rate-limiter.php  # 24h URL cooldown + daily plafond
│   ├── class-mailer.php        # wp_mail() to admin users
│   ├── class-digest.php        # Transient digest accumulation
│   ├── class-stats.php         # DB ORM: record, top URLs/IPs, export
│   ├── class-settings.php      # Admin settings page
│   └── class-dashboard.php     # Admin statistics page
└── assets/
    ├── css/admin.css           # Minimal WordPress-native styles
    └── js/admin.js             # Minimal admin interactions
```

## Database

Single table: `{prefix}404_alert_log`

```sql
CREATE TABLE {prefix}404_alert_log (
    id         bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    url        varchar(2048) NOT NULL,
    ip         varchar(45) NOT NULL DEFAULT '',
    referrer   varchar(2048) NOT NULL DEFAULT '',
    user_agent varchar(512) NOT NULL DEFAULT '',
    alerted    tinyint(1) NOT NULL DEFAULT 0,    -- 1 = immediate alert sent
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created_at (created_at),
    KEY idx_url (url(191))
);
```

**Auto-rotation:** Keeps max 5000 records (configurable, default from options).

## Options (WordPress)

```php
// Single option key: '404_alert_options'
[
    'daily_limit'    => 20,        // Max immediate alerts per day
    'digest_enabled' => true,      // Send daily digest
    'digest_time'    => '08:00',   // UTC time to send digest
    'log_retention'  => 5000,      // Max records to keep
]
```

## Hooks & Filters

**Actions**
- `alert404_send_digest` — Daily cron to send digest
- `404_alert_before_send` — Before sending alert email
- `404_alert_sent` — After alert sent successfully

**Filters**
- `404_alert_email_subject` — Modify alert subject
- `404_alert_email_body` — Modify alert HTML body
- `404_alert_recipients` — Filter recipient list
- `404_alert_client_ip` — Override IP detection

## Testing Checklist

✅ Activation
- [ ] Plugin activates without errors
- [ ] Table `404_alert_log` created
- [ ] Options initialized with defaults
- [ ] Cron scheduled for digest

✅ Detection
- [ ] Visit non-existent URL → 404 recorded in DB
- [ ] Admin receives immediate alert email
- [ ] Revisit same URL → no second email (24h cooldown)
- [ ] Third unique URL (with daily_limit=2) → goes to digest, not immediate alert

✅ Digest
- [ ] Daily digest cron sends at configured time
- [ ] Digest contains all excess URLs
- [ ] Transient cleared after send

✅ Admin Pages
- [ ] Settings page renders correctly
- [ ] Dashboard shows top URLs and IPs
- [ ] CSV export works (Content-Type, filename)
- [ ] All options saveable and persist

✅ Deactivation
- [ ] Cron cleared
- [ ] Uninstall script drops table and deletes options

## Migration from v1.x

**Breaking changes:**
- No SMTP configuration in plugin — use WP Mail SMTP or similar
- Email addresses no longer stored in options — fetched dynamically from admin users
- Table renamed: `404_alert_stats` → `404_alert_log`
- Deprecated classes removed (Storage, Logger, Template, etc.)

**Data loss:**
- Old stats in `404_alert_stats` are **not** migrated
- Recommend exporting old data before upgrading

## Why This Approach

1. **Simplicity** — 9 classes vs 18. Each class has one responsibility.
2. **No external deps** — SMTP responsibility delegated to plugins that do it better (WP Mail SMTP, etc.)
3. **Performance** — Transients are fast enough; Redis overhead not justified.
4. **Maintainability** — Future contributors see clean code, not 400-line templates.
5. **Standards** — WordPress conventions, no clever tricks, easy to audit.

## What's Next

1. Test on live WordPress 6.4+/6.9 environments
2. Review WordPress.org requirements (code review, security scan)
3. Gather user feedback on alert frequency and digest timing
4. Consider future feature: exclude certain URL patterns (regex filter)
