<?php
/**
 * SMTP Wizard for 404 Alert plugin.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wizard for SMTP configuration.
 */
class Alert404_SMTP_Wizard {
	const TRANSIENT_KEY = '404_alert_smtp_wizard_state';
	const TRANSIENT_TTL = 24 * HOUR_IN_SECONDS;

	/**
	 * Initialize wizard hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu_page' ) );
		add_action( 'wp_ajax_404_alert_smtp_wizard_get_state', array( self::class, 'ajax_get_state' ) );
		add_action( 'wp_ajax_404_alert_smtp_wizard_save_step', array( self::class, 'ajax_save_step' ) );
		add_action( 'wp_ajax_404_alert_smtp_wizard_test_connection', array( self::class, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_404_alert_smtp_wizard_finalize', array( self::class, 'ajax_finalize' ) );
	}

	/**
	 * Get current wizard state from transient.
	 */
	public static function get_wizard_state(): array {
		$state = get_transient( self::TRANSIENT_KEY );
		return is_array( $state ) ? $state : array();
	}

	/**
	 * Save wizard step state.
	 */
	public static function save_step( array $data ): void {
		$state = self::get_wizard_state();
		$state = array_merge( $state, $data );
		set_transient( self::TRANSIENT_KEY, $state, self::TRANSIENT_TTL );
	}

	/**
	 * Clear wizard state.
	 */
	public static function clear_wizard_state(): void {
		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * AJAX: Get current wizard state.
	 */
	public static function ajax_get_state(): void {
		check_ajax_referer( '404_alert_smtp_wizard_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
		}

		$state = self::get_wizard_state();
		wp_send_json_success( $state );
	}

	/**
	 * AJAX: Save step state.
	 */
	public static function ajax_save_step(): void {
		check_ajax_referer( '404_alert_smtp_wizard_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
		}

		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();

		self::save_step( $data );
		wp_send_json_success( array( 'message' => 'Step saved' ) );
	}

	/**
	 * AJAX: Test SMTP connection.
	 */
	public static function ajax_test_connection(): void {
		check_ajax_referer( '404_alert_smtp_wizard_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
		}

		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();

		$result = Alert404_SMTP_Handler::test_connection(
			array(
				'host'       => sanitize_text_field( $data['host'] ?? '' ),
				'port'       => absint( $data['port'] ?? 587 ),
				'encryption' => sanitize_text_field( $data['encryption'] ?? 'tls' ),
				'email'      => sanitize_email( $data['email'] ?? '' ),
				'password'   => $data['password'] ?? '',
			)
		);

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => 'Connexion réussie !' ) );
		} else {
			wp_send_json_error( array( 'message' => $result['error'] ?? 'Erreur inconnue' ) );
		}
	}

	/**
	 * AJAX: Finalize and save configuration.
	 */
	public static function ajax_finalize(): void {
		check_ajax_referer( '404_alert_smtp_wizard_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
		}

		$state = self::get_wizard_state();

		if ( empty( $state['host'] ) || empty( $state['email'] ) || empty( $state['password'] ) ) {
			wp_send_json_error( array( 'message' => 'Configuration incomplète' ) );
		}

		$options = get_option( '404_alert_options', array() );
		$options['smtp_host']       = sanitize_text_field( $state['host'] );
		$options['smtp_port']       = absint( $state['port'] );
		$options['smtp_encryption'] = sanitize_text_field( $state['encryption'] );
		$options['smtp_email']      = sanitize_email( $state['email'] );
		$options['smtp_password']   = $state['password']; // Will be encrypted by sanitize_options()

		update_option( '404_alert_options', $options );
		self::clear_wizard_state();

		wp_send_json_success( array( 'message' => 'Configuration sauvegardée !' ) );
	}

	/**
	 * Add wizard menu page.
	 */
	public static function add_menu_page(): void {
		add_menu_page(
			'Configuration SMTP',
			'Configuration SMTP',
			'manage_options',
			'404_alert_smtp_wizard',
			array( self::class, 'render_page' ),
			'dashicons-email',
			25
		);
	}

	/**
	 * Render wizard page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé' );
		}

		wp_enqueue_script( 'alert404-smtp-wizard' );
		wp_enqueue_style( 'alert404-smtp-wizard' );

		include ALERT404_DIR . 'templates/smtp-wizard-modal.php';
	}
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', array( 'Alert404_SMTP_Wizard', 'init' ) );
