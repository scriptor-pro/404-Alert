<?php
/**
 * Plugin Name: 404 Alert
 * Description: Envoie un email à l'administrateur à chaque erreur 404.
 * Version: 1.1.1
 * Requires PHP: 8.1
 * Author: Baudouin
 * License: ISC
 */

defined( 'ABSPATH' ) || exit;

define( 'ALERT404_DIR', plugin_dir_path( __FILE__ ) );

require_once ALERT404_DIR . 'includes/class-logger.php';
require_once ALERT404_DIR . 'includes/class-redis-handler.php';
require_once ALERT404_DIR . 'includes/class-user-agent-parser.php';
require_once ALERT404_DIR . 'includes/class-request-info.php';
require_once ALERT404_DIR . 'includes/class-smtp-handler.php';
require_once ALERT404_DIR . 'includes/class-settings.php';
require_once ALERT404_DIR . 'includes/class-rate-limiter.php';
require_once ALERT404_DIR . 'includes/class-storage.php';
require_once ALERT404_DIR . 'includes/class-mailer.php';
require_once ALERT404_DIR . 'includes/class-detector.php';
require_once ALERT404_DIR . 'includes/class-404-template.php';
require_once ALERT404_DIR . 'includes/class-dashboard.php';

// Initialiser Redis (optionnel, fallback à transients)
Alert404_Redis_Handler::init();

Alert404_Settings::init();
Alert404_Storage::init();
Alert404_Template::init();
Alert404_Detector::init();
Alert404_Dashboard::init();
