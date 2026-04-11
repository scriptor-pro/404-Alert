<?php
/**
 * WordPress Runtime Stubs for PHPStan Static Analysis
 * This file provides type hints for WordPress functions used in the plugin
 */

defined( 'ABSPATH' ) || exit;

class WP_User {
	/** @var int */
	public $ID = 0;
	/** @var string */
	public $user_login = '';
	/** @var string */
	public $user_email = '';
}

class PHPMailer {
	/** @var string */
	public $Host = '';
	/** @var int */
	public $Port = 0;
	/** @var bool */
	public $SMTPAuth = false;
	/** @var string */
	public $Username = '';
	/** @var string */
	public $Password = '';
	/** @var string */
	public $SMTPSecure = '';
	/** @var int */
	public $Timeout = 0;
	/** @var string */
	public $Subject = '';
	/** @var string */
	public $Body = '';

	public function __construct( bool $exceptions = false ) {}
	public function isSMTP(): void {}
	public function setFrom( string $address, string $name = '' ): bool { return true; }
	public function addAddress( string $address, string $name = '' ): bool { return true; }
	public function isHTML( bool $is_html = true ): void {}
	public function addCustomHeader( string $name, ?string $value = null ): bool { return true; }
	public function send(): bool { return true; }
	public function smtpConnect( $options = null ): bool { return true; }
	public function smtpClose(): void {}
}

function plugin_dir_path( string $file ): string { return ''; }
function add_filter( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): bool { return true; }
function add_action( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): bool { return true; }
function apply_filters( string $hook_name, $value, ...$args ) { return $value; }
function do_action( string $hook_name, ...$args ): void {}
function is_404(): bool { return false; }
function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '' ): string { return ''; }
function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '' ): string { return ''; }
function current_user_can( string $capability ): bool { return true; }
function wp_die( string $message = '' ): void {}
function sanitize_text_field( $str ): string { return is_scalar( $str ) ? (string) $str : ''; }
function sanitize_email( string $email ): string { return $email; }
function sanitize_url( string $url ): string { return $url; }
function esc_html( $text ): string { return (string) $text; }
function esc_attr( $text ): string { return (string) $text; }
function esc_url( string $url ): string { return $url; }
function esc_url_raw( string $url ): string { return $url; }
function esc_js( string $text ): string { return $text; }
function wp_unslash( $value ) { return $value; }
function check_admin_referer( string $action = '-1', string $query_arg = '_wpnonce' ): bool { return true; }
function check_ajax_referer( string $action = '-1', string $query_arg = false, bool $die = true ): int { return 1; }
function wp_verify_nonce( $nonce, string $action = '-1' ): bool { return true; }
function wp_create_nonce( string $action = '-1' ): string { return 'nonce'; }
function wp_nonce_url( string $actionurl, string $action = '-1', string $name = '_wpnonce' ): string { return $actionurl; }
function admin_url( string $path = '', string $scheme = 'admin' ): string { return $path; }
function home_url( string $path = '', ?string $scheme = null ): string { return $path; }
function get_bloginfo( string $show = '', string $filter = 'raw' ): string { return ''; }
function get_option( string $option, $default = false ) { return $default; }
function update_option( string $option, $value, $autoload = null ): bool { return true; }
function delete_option( string $option ): bool { return true; }
function get_transient( string $transient ) { return false; }
function set_transient( string $transient, $value, int $expiration = 0 ): bool { return true; }
function delete_transient( string $transient ): bool { return true; }
function wp_hash( string $data, string $scheme = 'auth' ): string { return $data; }
function current_time( string $type, int|bool $gmt = 0 ) { return '1970-01-01 00:00:00'; }
function wp_mail( $to, string $subject, string $message, $headers = '', $attachments = array() ): bool { return true; }
function wp_json_encode( $value, int $flags = 0, int $depth = 512 ): string { return json_encode( $value, $flags, $depth ) ?: ''; }
function wp_get_current_user(): WP_User { return new WP_User(); }
function is_user_logged_in(): bool { return false; }
function register_setting( string $option_group, string $option_name, array $args = array() ): bool { return true; }
function add_settings_section( string $id, string $title, $callback, string $page ): void {}
function add_settings_field( string $id, string $title, $callback, string $page, string $section = 'default', array $args = array() ): void {}
function settings_fields( string $option_group ): void {}
function do_settings_sections( string $page ): void {}
function submit_button( string $text = null ): void {}
function checked( $checked, $current = true, bool $echo = true ): string { return ''; }
function selected( $selected, $current = true, bool $echo = true ): string { return ''; }
function wp_send_json_error( $data = null, ?int $status_code = null ): void {}
function wp_send_json( $response, ?int $status_code = null, int $flags = 0 ): void {}
