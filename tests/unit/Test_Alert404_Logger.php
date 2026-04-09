<?php
/**
 * Tests unitaires pour Alert404_Logger
 */

class Test_Alert404_Logger extends Alert404_UnitTestCase {

	/**
	 * Fichier de log temporaire pour les tests
	 */
	private $temp_log_file;
	private $original_error_log;

	public function setUp(): void {
		parent::setUp();
		$this->setup_plugin_options( array( 'force_logging' => 1 ) );
		// Créer un fichier de log temporaire
		$this->temp_log_file = tempnam( sys_get_temp_dir(), 'alert404_test_' );
		$this->original_error_log = ini_get( 'error_log' );
		ini_set( 'error_log', $this->temp_log_file );
	}

	public function tearDown(): void {
		ini_set( 'error_log', (string) $this->original_error_log );
		parent::tearDown();
		// Nettoyer le fichier temporaire
		if ( file_exists( $this->temp_log_file ) ) {
			unlink( $this->temp_log_file );
		}
	}

	/**
	 * Récupère le contenu du fichier de log
	 */
	private function get_log_content() {
		if ( file_exists( $this->temp_log_file ) ) {
			return file_get_contents( $this->temp_log_file );
		}
		return '';
	}

	/**
	 * Teste que log_invalid_ip() enregistre un événement
	 */
	public function test_log_invalid_ip() {
		// Vider le fichier
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_invalid_ip( 'invalid-ip-here' );

		$content = $this->get_log_content();

		$this->assertStringContainsString( 'invalid_ip', $content );
		$this->assertStringContainsString( 'invalid-ip-here', $content );
	}

	/**
	 * Teste que log_rate_limit_ip() enregistre un événement
	 */
	public function test_log_rate_limit_ip() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_rate_limit_ip( '192.168.1.1', 300 );

		$content = $this->get_log_content();

		$this->assertStringContainsString( 'rate_limit_ip', $content );
		$this->assertStringContainsString( '192.168.1.1', $content );
		$this->assertStringContainsString( '300', $content );
	}

	/**
	 * Teste que log_rate_limit_daily() enregistre un événement
	 */
	public function test_log_rate_limit_daily() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_rate_limit_daily( 500 );

		$content = $this->get_log_content();

		$this->assertStringContainsString( 'rate_limit_daily', $content );
		$this->assertStringContainsString( '500', $content );
	}

	/**
	 * Teste que log_email_sent() enregistre un événement
	 */
	public function test_log_email_sent() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_email_sent( 'admin@example.com', 'https://example.com/404' );

		$content = $this->get_log_content();

		$this->assertStringContainsString( 'email_sent', $content );
		$this->assertStringContainsString( 'admin@example.com', $content );
		$this->assertStringContainsString( 'https://example.com/404', $content );
	}

	/**
	 * Teste que log_email_failed() enregistre un événement
	 */
	public function test_log_email_failed() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_email_failed( 'admin@example.com', 'SMTP connection refused' );

		$content = $this->get_log_content();

		$this->assertStringContainsString( 'email_failed', $content );
		$this->assertStringContainsString( 'admin@example.com', $content );
		$this->assertStringContainsString( 'SMTP connection refused', $content );
	}

	/**
	 * Teste que log_404_detected() enregistre un événement
	 */
	public function test_log_404_detected() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_404_detected( '192.168.1.1', 'https://example.com/404', [ 'extra' => 'data' ] );

		$content = $this->get_log_content();

		$this->assertStringContainsString( '404_detected', $content );
		$this->assertStringContainsString( '192.168.1.1', $content );
		$this->assertStringContainsString( 'https://example.com/404', $content );
		$this->assertStringContainsString( 'extra', $content );
	}

	/**
	 * Teste que le timestamp est inclus dans le log
	 */
	public function test_log_includes_timestamp() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_invalid_ip( 'test' );

		$content = $this->get_log_content();

		// Doit contenir une date/heure au format MySQL
		$this->assertMatchesRegularExpression( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content );
	}

	/**
	 * Teste que le format est JSON pour les données
	 */
	public function test_log_format_is_json_for_context() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_rate_limit_ip( '10.0.0.1', 600 );

		$content = $this->get_log_content();

		// Doit contenir du JSON
		$this->assertStringContainsString( '{', $content );
		$this->assertStringContainsString( '}', $content );
		$this->assertStringContainsString( '"', $content );
	}

	/**
	 * Teste que les données sensibles sont incluses correctement
	 */
	public function test_sensitive_data_is_logged() {
		file_put_contents( $this->temp_log_file, '' );

		$ip = '203.0.113.50';
		Alert404_Logger::log_rate_limit_ip( $ip, 300 );

		$content = $this->get_log_content();

		// L'IP doit être loggée (même si sensible, c'est intentionnel)
		$this->assertStringContainsString( $ip, $content );
	}

	/**
	 * Teste que plusieurs appels sont tous loggés
	 */
	public function test_multiple_logs_are_recorded() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_invalid_ip( 'bad1' );
		Alert404_Logger::log_invalid_ip( 'bad2' );
		Alert404_Logger::log_rate_limit_ip( '192.168.1.1', 300 );

		$content = $this->get_log_content();

		// Tous les événements doivent être présents
		$this->assertStringContainsString( 'bad1', $content );
		$this->assertStringContainsString( 'bad2', $content );
		$this->assertStringContainsString( '192.168.1.1', $content );
	}

	/**
	 * Teste que le préfixe [404-Alert] est présent
	 */
	public function test_log_includes_plugin_prefix() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_invalid_ip( 'test' );

		$content = $this->get_log_content();

		$this->assertStringContainsString( '[404-Alert]', $content );
	}

	/**
	 * Teste que les données nulles sont gérées
	 */
	public function test_null_values_are_handled() {
		file_put_contents( $this->temp_log_file, '' );

		Alert404_Logger::log_email_failed( 'admin@example.com' );

		$content = $this->get_log_content();

		$this->assertStringContainsString( 'email_failed', $content );
		// Doit avoir loggé quelque chose même si reason est vide
		$this->assertNotEmpty( $content );
	}
}
