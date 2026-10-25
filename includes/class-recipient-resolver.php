<?php
/**
 * Resolve mail recipients.
 *
 * @package Alert404
 */

namespace Alert404;

use function get_option;
use function get_the_author_meta;
use function get_users;
use function sanitize_email;
use function apply_filters;

defined( 'ABSPATH' ) || exit;

class RecipientResolver {
	/**
	 * Resolve the final recipient list.
	 *
	 * @return array<int, string>
	 */
	public static function resolve(): array {
		$options  = get_option( '404_alert_options', [] );
		$options  = is_array( $options ) ? $options : [];
		$explicit = sanitize_email( (string) ( $options['recipient_email'] ?? '' ) );
		if ( '' !== $explicit ) {
			$emails = [ $explicit ];
			$emails = apply_filters( '404_alert_recipients', array_values( array_unique( $emails ) ) );

			if ( ! is_array( $emails ) ) {
				return [];
			}

			$clean = [];
			foreach ( $emails as $email ) {
				$email = sanitize_email( (string) $email );
				if ( '' !== $email ) {
					$clean[] = $email;
				}
			}

			return array_values( array_unique( $clean ) );
		}

		$admins = get_users(
			[
				'role'   => 'administrator',
				'fields' => 'ID',
			]
		);

		$emails = [];

		foreach ( $admins as $admin_id ) {
			$email = sanitize_email( (string) get_the_author_meta( 'user_email', $admin_id ) );
			if ( '' !== $email ) {
				$emails[] = $email;
			}
		}

		if ( empty( $emails ) ) {
			$fallback = sanitize_email( (string) get_option( 'admin_email', '' ) );
			if ( '' !== $fallback ) {
				$emails[] = $fallback;
			}
		}

		$emails = apply_filters( '404_alert_recipients', array_values( array_unique( $emails ) ) );

		if ( ! is_array( $emails ) ) {
			return [];
		}

		$clean = [];
		foreach ( $emails as $email ) {
			$email = sanitize_email( (string) $email );
			if ( '' !== $email ) {
				$clean[] = $email;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}
