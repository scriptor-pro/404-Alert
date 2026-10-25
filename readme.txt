=== 404 Alert ===
Contributors: baudouin
Tags: 404, error, email, notification, alert
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 2.3.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intelligent 404 error alerts with smart rate limiting and daily digest for WordPress administrators.

== Description ==

404 Alert monitors 404 page not found errors on your WordPress site and sends intelligent email notifications to a configurable recipient, with administrator fallback.

= Features =

* **Smart Alert Logic** - First 404 occurrence triggers immediate alert, excess errors collected in daily digest
* **24-Hour URL Cooldown** - Never spam admins with duplicate errors for the same URL
* **Configurable Daily Limit** - Set maximum number of immediate alerts per day (default: 20)
* **Daily Digest** - Excess 404s sent once per day at configured time (8 AM default)
* **Top URLs & IPs** - Dashboard displays most-hit URLs and attacking IPs
* **CSV Export** - Download all 404 logs for analysis
* **Recipient Override** - Send alerts to a specific email address or fall back to all administrators
* **Lightweight** - Zero external dependencies, clean code, 27 KB plugin

= Perfect For =

* Monitoring broken links and missing pages
* Detecting SEO issues and site migrations
* Identifying attack patterns (brute force attempts)
* Large sites with high 404 volume (avoids email overload)

= How It Works =

1. **Activation** - Plugin creates database table, initializes settings, schedules daily cron
2. **Detection** - Monitors 404 errors on template_redirect hook
3. **Smart Limiting** - First alert is immediate, same URL within 24h goes to digest
4. **Daily Digest** - Excess 404s collected and sent once daily at configured time
5. **Dashboard** - View top URLs, IPs, and export logs as CSV

= Performance =

* Transient-based rate limiting (no external services needed)
* Database auto-rotation keeps only 5000 recent records
* Minimal overhead on template_redirect hook
* Works out-of-the-box with WordPress wp_mail()

= Settings =

Configure in WordPress admin under **404 Alert** menu:

* **Daily Alert Limit** - Maximum immediate alerts per day (1-1000)
* **Enable Digest** - Toggle daily digest email on/off
* **Digest Time** - UTC time to send daily digest
* **SMTP Configuration** - Built-in SMTP delivery for servers without `sendmail`

= Email & Integration =

* Sends to a custom recipient email if set, otherwise to all users with **administrator** role
* Uses WordPress standard wp_mail() function or the built-in SMTP settings
* Built-in SMTP support for direct delivery when the server has no sendmail
* Compatible with WP Mail SMTP, Postman SMTP, FluentSMTP, etc.

== Installation ==

1. Upload `404-alert` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Go to **404 Alert** menu to configure settings
4. View statistics in **404 Alert > Statistics** submenu

== Screenshots ==

1. Admin settings page with rate limiting configuration
2. Statistics dashboard showing top 404 URLs and IPs
3. Email alert example sent to administrators

== Frequently Asked Questions ==

= Will this spam my email inbox? =

No. The plugin uses intelligent rate limiting:
* Each unique 404 URL triggers one immediate alert
* The same URL within 24 hours goes to the daily digest instead
* Set the daily limit to control max immediate alerts (default: 20)
* Excess 404s are collected and sent once daily

= Which email addresses receive alerts? =

If you set an alert recipient email in the plugin settings, alerts go there. Otherwise, the plugin fetches all administrator emails dynamically, with `admin_email` as a fallback.

= Can I change the email subject or body? =

Yes, use the following filters in your theme's functions.php:

```php
add_filter( '404_alert_email_subject', function( $subject ) {
    return '[MyBrand] ' . $subject;
} );

add_filter( '404_alert_email_body', function( $body ) {
    // Modify HTML body
    return $body;
} );

add_filter( '404_alert_recipients', function( $emails ) {
    // Add or remove email addresses
    $emails[] = 'myemail@example.com';
    return $emails;
} );
```

= Does this require external services? =

No. Everything runs on your server. Uses standard WordPress:
* wp_mail() for emails (configure via wp-config.php or external plugin)
* Transients for rate limiting (no Redis needed)
* Database for logging (auto-rotates at 5000 records)

= What's the performance impact? =

Minimal:
* 404 detection runs on template_redirect hook
* Database record: ~1ms
* Rate limit check: <1ms via transients
* No external API calls

= Can I export the 404 logs? =

Yes. Go to **404 Alert > Statistics** and click **Export to CSV**.

= How far back are logs kept? =

By default, 5000 most recent 404 records. Configure `log_retention` in options.

= What if I need custom SMTP configuration? =

Use the built-in SMTP settings in the 404 Alert admin page, or install a dedicated SMTP plugin like **WP Mail SMTP** or **FluentSMTP**.

== Upgrade Notice ==

= 2.3.0 =
Added a configurable alert recipient email while keeping administrator fallback.

= 2.2.0 =
Refined the mail stack into a lighter internal architecture with settings, secrets, transport, service, and health checking layers.

= 2.1.0 =
Added built-in SMTP delivery settings while keeping the smart alert logic and daily digest. The plugin now supports either WordPress mail or direct SMTP.

== Changelog ==

= 2.3.0 =
* New: Configurable alert recipient email with administrator fallback

= 2.2.0 =
* New: Lightweight internal mail architecture with health diagnostics
* New: SMTP transport, recipient resolver, message factory, and mail logging layers
* Improvement: SMTP test button now reports clearer configuration issues

= 2.1.0 =
* Complete ground-up rewrite
* New: Smart alert logic (24h URL cooldown + daily plafond)
* New: Daily digest for excess 404s
* New: CSV export in dashboard
* New: Built-in SMTP configuration and direct delivery
* Removed: Redis support (transients sufficient)
* Removed: Deprecated classes and complexity
* Simplified: 9 lean classes instead of 18
* Database: Single clean table with auto-rotation
* Admin interface: Settings + Statistics pages

== Support ==

For issues, feature requests, or questions:
* Check WordPress.org plugin support forum
* Or visit https://github.com/baudouin-dev/404-alert/issues

Enjoy! 🚀
