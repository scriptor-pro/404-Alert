<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-test-lib';

define( 'DB_NAME', getenv( 'WORDPRESS_DB_NAME' ) ?: 'wordpress_test' );
define( 'DB_USER', getenv( 'WORDPRESS_DB_USER' ) ?: 'wordpress' );
define( 'DB_PASSWORD', getenv( 'WORDPRESS_DB_PASSWORD' ) ?: 'wordpress123' );
define( 'DB_HOST', getenv( 'WORDPRESS_DB_HOST' ) ?: '127.0.0.1:3307' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = getenv( 'WORDPRESS_TABLE_PREFIX' ) ?: 'wp_';

define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

$wp_core_dir = getenv( 'WP_CORE_DIR' ) ?: '/tmp/wordpress-develop-trunk/src';
define( 'ABSPATH', rtrim( $wp_core_dir, '/\\' ) . '/' );

define( 'WP_PHP_BINARY', 'php' );
