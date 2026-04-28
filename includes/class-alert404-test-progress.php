<?php
/**
 * Test Progress Management for 404 Alert plugin.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages SMTP test progress with step tracking and transient storage.
 */
class Alert404_Test_Progress {
	/**
	 * Transient key prefix for test progress
	 */
	private const TRANSIENT_KEY = 'alert404_test_progress_';

	/**
	 * Test timeout in seconds
	 */
	private const TEST_TIMEOUT = 60;

	/**
	 * Initialize a new SMTP test with empty steps
	 *
	 * @return void
	 */
	public static function init_test(): void {
		$user_id = get_current_user_id();
		$key     = self::get_transient_key( $user_id );

		$steps = array(
			array(
				'step'      => 'Vérification de la configuration',
				'status'    => 'pending',
				'message'   => '',
				'timestamp' => microtime( true ),
			),
			array(
				'step'      => 'Connexion au serveur',
				'status'    => 'pending',
				'message'   => '',
				'timestamp' => microtime( true ),
			),
			array(
				'step'      => 'Authentification',
				'status'    => 'pending',
				'message'   => '',
				'timestamp' => microtime( true ),
			),
			array(
				'step'      => 'Configuration de l\'email',
				'status'    => 'pending',
				'message'   => '',
				'timestamp' => microtime( true ),
			),
			array(
				'step'      => 'Envoi de l\'email de test',
				'status'    => 'pending',
				'message'   => '',
				'timestamp' => microtime( true ),
			),
			array(
				'step'      => 'Fermeture de la connexion',
				'status'    => 'pending',
				'message'   => '',
				'timestamp' => microtime( true ),
			),
		);

		set_transient( $key, $steps, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Update a specific step in the progress
	 *
	 * @param string $step Step name to update.
	 * @param string $status Status: 'pending', 'running', 'success', 'error'.
	 * @param string $message Details message for this step.
	 * @return bool True if updated successfully.
	 */
	public static function update_step( string $step, string $status, string $message = '' ): bool {
		$user_id = get_current_user_id();
		$key     = self::get_transient_key( $user_id );
		$steps   = get_transient( $key );

		if ( ! is_array( $steps ) ) {
			return false;
		}

		foreach ( $steps as &$s ) {
			if ( $s['step'] === $step ) {
				$s['status']    = $status;
				$s['message']   = $message;
				$s['timestamp'] = microtime( true );
				break;
			}
		}

		set_transient( $key, $steps, 5 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Get current test progress for polling
	 *
	 * @return array Progress data with steps and completion status.
	 */
	public static function get_progress(): array {
		$user_id = get_current_user_id();
		$key     = self::get_transient_key( $user_id );
		$steps   = get_transient( $key );

		if ( ! is_array( $steps ) || empty( $steps ) ) {
			return array(
				'steps'      => array(),
				'progress'   => 0,
				'is_running' => false,
				'is_expired' => true,
			);
		}

		$completed = 0;
		$total     = count( $steps );
		$is_running = false;

		foreach ( $steps as $s ) {
			if ( 'success' === $s['status'] ) {
				++$completed;
			} elseif ( 'running' === $s['status'] ) {
				$is_running = true;
			}
		}

		$progress_percent = ( $completed / $total ) * 100;

		return array(
			'steps'      => $steps,
			'progress'   => (int) $progress_percent,
			'is_running' => $is_running,
			'is_expired' => false,
		);
	}

	/**
	 * Clear test progress after completion
	 *
	 * @return void
	 */
	public static function clear_test(): void {
		$user_id = get_current_user_id();
		$key     = self::get_transient_key( $user_id );
		delete_transient( $key );
	}

	/**
	 * Get the transient key for a user
	 *
	 * @param int $user_id User ID.
	 * @return string Transient key.
	 */
	private static function get_transient_key( int $user_id ): string {
		return self::TRANSIENT_KEY . $user_id;
	}
}
