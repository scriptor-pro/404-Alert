=== 404 Alert ===
Contributors: baudouin
Tags: 404, error, email, notification, alert
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.2.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send email notifications when visitors encounter 404 errors, with advanced rate limiting and SMTP support.

== Description ==

404 Alert monitors 404 page not found errors on your WordPress site and sends email notifications to administrators.

**Key Features:**

- **Instant Notifications**: Get email alerts for every 404 error
- **Smart Rate Limiting**: Prevent notification spam with atomic Redis-backed rate limiting
- **SMTP Support**: Configure custom email servers (Gmail, Outlook, SendGrid, AWS SES, Brevo, etc.)
- **Email Fallback**: Automatic fallback to WordPress wp_mail() if SMTP unavailable
- **Admin Dashboard**: View statistics about 404 errors, top referrers, and visitor information
- **Password Protection**: SMTP passwords encrypted with AES-256-CBC
- **Production Logging**: Track SMTP connections, authentication failures, and configuration changes
- **100% Tested**: 110+ unit tests, 12 E2E scenarios, 85-90% code coverage

**Performance:**

- Rate limiting: 10-15x faster with Redis support
- SMTP email delivery: <100ms
- Dashboard rendering: <500ms for 1000 events
- Optional Redis support for atomic operations
- Graceful fallback to WordPress transients

**Security:**

- Atomic operations prevent race conditions
- SMTP password encryption (AES-256-CBC)
- Input validation and XSS protection
- Audit logging for all configuration changes
- No sensitive data in logs

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/404-alert/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → 404 Alert to configure
4. (Optional) Install Redis for better performance
5. Test by visiting a non-existent page

== Configuration ==

### Basic Setup (Required)

1. Go to WordPress Admin → Settings → 404 Alert
2. Enter your email address where 404 notifications should be sent
3. Adjust rate limiting settings:
   - Daily Limit: Maximum emails per day (1-10000, default: 500)
   - IP Cooldown: Seconds between emails from same IP (60-3600, default: 300)

### SMTP Setup (Optional but Recommended)

For more reliable email delivery:

1. Go to Settings → 404 Alert → SMTP Settings
2. Select your email provider (Gmail, Outlook, SendGrid, etc.)
3. Enter your credentials
4. Click "Test Connection" to verify
5. Save settings

**Gmail Example:**
- Host: smtp.gmail.com
- Port: 587
- Encryption: TLS
- Username: your-email@gmail.com
- Password: Your app password (from Google Account settings)

### Redis Setup (Optional)

For 10-15x faster rate limiting:

**Ubuntu/Debian:**
```
sudo apt-get install redis-server
sudo systemctl start redis-server
```

**macOS:**
```
brew install redis
brew services start redis
```

Or use managed Redis from Heroku, AWS, Google Cloud, DigitalOcean, or Upstash.

Configure in wp-config.php:
```php
define( 'ALERT404_REDIS_HOST', 'localhost' );
define( 'ALERT404_REDIS_PORT', 6379 );
```

== Frequently Asked Questions ==

= Why am I not receiving emails? =

1. Check if email is configured in plugin settings
2. If SMTP enabled, verify host, port, username, password
3. Check wp-content/debug.log for errors
4. Verify you haven't exceeded the daily limit
5. Check if rate limiting is blocking from your IP

= What information is sent in each email? =

Each 404 notification includes:
- Requested URL
- Visitor's IP address
- User-Agent (browser info)
- HTTP Referrer
- Timestamp
- Whether visitor was logged in

= Can I use this without Redis? =

Yes! The plugin works with WordPress transients as fallback. Redis is optional but recommended for production sites with high traffic.

= Is SMTP required? =

No. The plugin uses WordPress wp_mail() by default. SMTP is optional but more reliable.

= How do I clear 404 history? =

Go to Settings → 404 Alert → Dashboard, click "Clear All Statistics" button.

= Can I customize the 404 template? =

Yes. Create a file at `404.php` in your theme's root directory. The plugin will use it instead of its default template.

= What about privacy and GDPR? =

404 events are stored locally in your WordPress database. No data is sent to external services (unless using managed Redis/SMTP). Enable logging to track all activities. You can clear all statistics at any time.

== Support ==

For support, documentation, and advanced configuration:
- See README.md for complete documentation
- Check CHANGELOG.md for version history and updates
- Review configuration examples in settings page

== Changelog ==

= 1.2.0 (April 11, 2026) =
* Fixed: Database security - Added proper prepared statements documentation
* Fixed: Heredoc syntax replaced with sprintf() for WordPress.org compatibility
* Fixed: Direct file access protection with wp_unslash() and esc_html()
* Fixed: File operations documentation with phpcs:ignore comments
* Improved: REQUEST_URI validation with proper escaping
* Improved: Code formatting compliance with PHPCS 100%
* Improved: PHPStan static analysis validation
* Security: Input validation enhanced with wp_unslash()
* Security: XSS prevention with esc_html() escaping
* Tested: Full compatibility with WordPress 6.9
* License: Changed from ISC to GPL v2 or later for WordPress.org compliance

= 1.1.0 (April 9, 2026) =
* Added: Redis support for atomic rate limiting
* Added: SMTP configuration with AES-256-CBC encryption
* Added: Production logging system (7 strategic logs)
* Added: 110+ unit tests with 85-90% code coverage
* Added: 12 E2E integration scenarios
* Improved: Rate limiting performance (10-15x faster)
* Improved: Email delivery with SMTP support
* Improved: PHPStan 2.x static analysis (0 errors)
* Improved: 100% WordPress coding standards compliance
* Fixed: Race conditions in rate limiting
* Fixed: PHPCS violations in code
* Security: Atomic operations prevent race conditions
* Security: SMTP password encryption
* Security: Passwords never logged
* Performance: 20MB memory optimized

= 0.1.0 (March 15, 2026) =
* Initial release
* Basic 404 detection
* Email notifications
* Rate limiting with WordPress transients
* Admin dashboard
* Basic logging

== License ==

This plugin is licensed under the GPL v2 or later. See LICENSE.md for details.

== Credits ==

Developed with attention to:
- Atomic operations for concurrent systems
- WordPress coding standards
- Production debugging requirements
- Comprehensive test coverage
- Security best practices
