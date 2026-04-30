<?php
/**
 * 404 detection and orchestration
 *
 * @package Alert404
 */

namespace Alert404;

use function is_404;
use function add_action;
use function get_option;

defined( 'ABSPATH' ) || exit;

class Detector {
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'on_template_redirect' ], 5 );
		add_action( 'alert404_send_digest', [ $this, 'send_daily_digest' ] );
	}

	/**
	 * Main 404 detection hook
	 */
	public function on_template_redirect(): void {
		if ( ! is_404() ) {
			return;
		}

		$url      = RequestInfo::get_url();
		$ip       = RequestInfo::get_client_ip();
		$referrer = RequestInfo::get_referrer();
		$user_agent = RequestInfo::get_user_agent();

		if ( empty( $url ) ) {
			return;
		}

		// Check rate limiting
		$should_alert = RateLimiter::should_alert_immediately( $url );

		if ( $should_alert ) {
			// Send immediate alert
			Mailer::send_alert( $url, $ip, $referrer );
			Stats::record( $url, $ip, $referrer, $user_agent, true );
		} else {
			// Add to digest (if digest is enabled and daily limit exceeded)
			$options = get_option( '404_alert_options', [] );
			if ( $options['digest_enabled'] ?? true ) {
				Digest::add_to_digest( $url );
			}
			Stats::record( $url, $ip, $referrer, $user_agent, false );
		}
	}

	/**
	 * Daily digest cron handler
	 */
	public function send_daily_digest(): void {
		Digest::send_and_clear();
	}
}
