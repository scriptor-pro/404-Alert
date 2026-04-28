# 404 Alert — WordPress Plugin

A lightweight, self-contained plugin that sends an email to the administrator each time a visitor accesses a non-existent page (404 error).

## Features

- 📧 **Instant Email**: HTML notifications for each detected 404
- 🛡️ **Rate Limiting**: protection against abuse (by IP + daily limit)
- ⚙️ **Simple Configuration**: native WordPress settings page
- 🚀 **Lightweight**: zero external dependencies, WordPress core only
- 🔒 **Secure**: sanitization, XSS protection, strict validation

## Quick Installation

**See [INSTALL.md](./INSTALL.md) for detailed instructions.**

Tl;dr :
1. Download the ZIP or clone the repo
2. Place in `wp-content/plugins/`
3. Activate in **Plugins**
4. Configure in **Settings > 404 Alert**

## Requirements

- **WordPress**: 5.9+
- **PHP**: 8.1+ (8.2+ recommended)
- **External Dependencies**: None

## Documentation

- **[INSTALL.md](./INSTALL.md)** — Installation and basic configuration
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** — Technical architecture and execution flow
- **[CONTRIBUTING.md](./CONTRIBUTING.md)** — Contributing to development

### Advanced Configuration

- **[REDIS.md](./REDIS.md)** — Optimize rate limiting with Redis (optional)
- **[SMTP.md](./SMTP.md)** — SMTP configuration for better deliverability (optional)

### Deployment

- **[CONFIGURATION-PRODUCTION.md](./CONFIGURATION-PRODUCTION.md)** — Production checklist
- **[WORDPRESS-ORG.md](./WORDPRESS-ORG.md)** — WordPress.org compliance and publication

## Usage

Once activated, the plugin automatically detects 404 errors and sends an email for each occurrence (within configured limits).

### Example Email

```
Subject: 404 on Example Site — /non-existent-page

HTML Body:
  404 Detected on Example Site
  
  URL: https://example.com/non-existent-page
  Referer: https://google.com
  IP: 192.168.1.100
  User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
  
  [Complete data in JSON for debugging]
```

### Basic Configuration

Settings available in **Settings > 404 Alert** :

| Parameter | Default | Description |
|-----------|---------|-------------|
| Email | Admin email | Notification recipient |
| Daily Limit | 500 | Max emails/day |
| Delay per IP | 300s | Cooldown between 2 emails from same IP |

## Architecture

Minimal structure:

```
404-alert/
├── 404-alert.php           # Bootstrap & hooks
├── includes/               # Main classes
│   ├── class-detector.php       # 404 detection
│   ├── class-rate-limiter.php   # Rate limiting
│   ├── class-mailer.php         # Email sending
│   ├── class-settings.php       # Settings page
│   ├── class-logger.php         # Logging
│   └── ... (other classes)
└── templates/              # Templates (optional)
```

**See [ARCHITECTURE.md](./ARCHITECTURE.md) for details.**

## Security

✅ **SQL Injection**: Prepared with `$wpdb->prepare()`  
✅ **XSS**: Escaped with `esc_html()`, `esc_attr()`  
✅ **CSRF**: WordPress nonces  
✅ **Authorization**: `current_user_can('manage_options')`  

See [WORDPRESS-ORG.md](./WORDPRESS-ORG.md) for full compliance.

## Troubleshooting

**Emails not being received?**
1. Check the destination email in **Settings > 404 Alert**
2. Verify that the daily limit has not been reached
3. Check your site's SMTP configuration (WP Mail SMTP, etc.)

**Rate limiting not working?**
- Without Redis: based on WordPress transients (may lose data on restart)
- Solution: Install Redis (see [REDIS.md](./REDIS.md))

**More questions?**
Check [INSTALL.md](./INSTALL.md) troubleshooting section or open an issue.

## Development

See [CONTRIBUTING.md](./CONTRIBUTING.md) to contribute code.

**Tests:**
```bash
composer install  # Install dev dependencies
vendor/bin/phpcs includes/ 404-alert.php  # Lint
vendor/bin/phpstan analyse  # Static analysis
vendor/bin/phpunit  # Unit tests
```

## License

**GPL v2 or later**

See [LICENSE](./LICENSE) or https://www.gnu.org/licenses/gpl-2.0.html

## Roadmap

- ✅ 404 detection + Email
- ✅ Rate limiting (IP + global)
- ✅ Redis support (optional)
- ✅ SMTP support (optional)
- ✅ Unit tests
- ⏳ Statistics dashboard (future)
- ⏳ Webhook support (future)

---

**Made with ❤️ for WordPress**
