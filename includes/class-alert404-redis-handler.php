<?php
/**
 * Redis handler for 404 Alert plugin.
 *
 * @package Alert404
 * Provides atomic interface for rate limiting operations.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redis handler for atomic operations.
 */
class Alert404_Redis_Handler {
	/**
	 * Unique Redis connection instance
	 *
	 * @var \Redis|null
	 */
	private static $redis = null;

	/**
	 * Indicates if Redis is available and connected
	 *
	 * @var bool|null
	 */
	private static $available = null;

	/**
	 * Initialize the Redis connection
	 * Attempts to connect, handles errors gracefully
	 *
	 * @return bool true if Redis is available, false otherwise.
	 */
	public static function init(): bool {
		if ( null !== self::$available ) {
			return self::$available;
		}

		self::$available = false;

		// Check that the Redis extension is installed.
		if ( ! extension_loaded( 'redis' ) ) {
			Alert404_Logger::log_redis_unavailable( 'Redis extension not installed' );
			return false;
		}

		try {
			$redis = new \Redis();

			// Configuration from WordPress constants.
			$host     = defined( 'ALERT404_REDIS_HOST' ) ? ALERT404_REDIS_HOST : 'localhost';
			$port     = defined( 'ALERT404_REDIS_PORT' ) ? ALERT404_REDIS_PORT : 6379;
			$password = defined( 'ALERT404_REDIS_PASSWORD' ) ? ALERT404_REDIS_PASSWORD : null;
			$db       = defined( 'ALERT404_REDIS_DB' ) ? ALERT404_REDIS_DB : 0;
			$timeout  = defined( 'ALERT404_REDIS_TIMEOUT' ) ? ALERT404_REDIS_TIMEOUT : 2;

			// Connection attempt.
			$connected = $redis->connect( $host, $port, $timeout );

			if ( ! $connected ) {
				Alert404_Logger::log_redis_unavailable( "Cannot connect to $host:$port" );
				return false;
			}

			// Authentication if password.
			if ( ! empty( $password ) ) {
				$authenticated = $redis->auth( $password );
				if ( ! $authenticated ) {
					Alert404_Logger::log_redis_unavailable( 'Redis authentication failed' );
					return false;
				}
			}

			// Select the database.
			$redis->select( $db );

			// Test connection with PING.
			$ping = $redis->ping();
			if ( true !== $ping && '+PONG' !== $ping ) {
				Alert404_Logger::log_redis_unavailable( 'Redis PING failed' );
				return false;
			}

			self::$redis     = $redis;
			self::$available = true;

			return true;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_unavailable( 'Exception: ' . $e->getMessage() );
			return false;
		}//end try
	}

	/**
	 * Returns the Redis instance or null if unavailable
	 *
	 * @return \Redis|null Redis instance or null.
	 */
	public static function get_instance(): ?\Redis {
		if ( null === self::$available ) {
			self::init();
		}

		return self::$redis;
	}

	/**
	 * Check if Redis is available
	 *
	 * @return bool true if Redis is available.
	 */
	public static function is_available(): bool {
		if ( null === self::$available ) {
			self::init();
		}

		return true === self::$available;
	}

	/**
	 * Acquire a lock atomically (SET ... NX)
	 * Immediate return, no spin-wait
	 *
	 * @param string $key Lock key.
	 * @param int    $timeout Lock duration in seconds.
	 * @return bool true if lock acquired, false otherwise.
	 */
	public static function acquire_lock( string $key, int $timeout = 5 ): bool {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return false;
		}

		try {
			// SET key value NX EX timeout.
			// Atomic: SET if not exists, with expiration.
			$result = $redis->set(
				$key,
				wp_hash( uniqid( '', true ) ),
				// Unique value.
				array(
					'EX' => $timeout,
					// Expiration in seconds.
					'NX' => true,
				// Only if Not eXists.
				)
			);

			return true === $result;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'acquire_lock failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Release a lock
	 *
	 * @param string $key Lock key.
	 * @return bool true if lock released.
	 */
	public static function release_lock( string $key ): bool {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return false;
		}

		try {
			$redis->del( $key );
			return true;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'release_lock failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Increment a counter atomically
	 * Returns the new value
	 *
	 * @param string $key Counter key.
	 * @param int    $ttl Time to live in seconds (0 = no expiration).
	 * @return int|false The new value, or false if error.
	 */
	public static function increment( string $key, int $ttl = 0 ) {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return false;
		}

		try {
			// INCR is atomic in Redis.
			$value = $redis->incr( $key );

			// Set expiration if requested.
			if ( $ttl > 0 ) {
				$redis->expire( $key, $ttl );
			}

			return $value;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'increment failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get a value
	 *
	 * @param string $key Key.
	 * @return string|false The value, or false if doesn't exist.
	 */
	public static function get( string $key ) {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return false;
		}

		try {
			return $redis->get( $key );
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'get failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Set a value
	 *
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 * @param int    $ttl Time to live in seconds (0 = no expiration).
	 * @return bool true if successful, false otherwise.
	 */
	public static function set( string $key, $value, int $ttl = 0 ): bool {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return false;
		}

		try {
			if ( $ttl > 0 ) {
				$result = $redis->setex( $key, $ttl, $value );
			} else {
				$result = $redis->set( $key, $value );
			}

			return true === $result;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'set failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Delete one or more keys
	 *
	 * @param string|array $keys Key(s) to delete.
	 * @return int Number of keys deleted.
	 */
	public static function delete( $keys ): int {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return 0;
		}

		try {
			if ( is_array( $keys ) ) {
				return $redis->del( ...$keys );
			}

			return $redis->del( $keys );
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'delete failed: ' . $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Force a reconnection to Redis (useful after connection loss)
	 *
	 * @return bool true if reconnection successful, false otherwise.
	 */
	public static function reconnect(): bool {
		self::close();
		$success = self::init();

		if ( $success ) {
			Alert404_Logger::log_redis_reconnected( 'Connection restored after loss' );
		}

		return $success;
	}

	/**
	 * Close the Redis connection
	 *
	 * @return void
	 */
	public static function close(): void {
		if ( null !== self::$redis ) {
			try {
				self::$redis->close();
			} catch ( \Throwable $e ) {
				// Silently ignore close errors (Redis may already be closed).
				unset( $e );
			}

			self::$redis     = null;
			self::$available = null;
		}
	}

	/**
	 * Get Redis statistics (info)
	 *
	 * @return array|false Redis information or false if error.
	 */
	public static function get_info() {
		$redis = self::get_instance();

		if ( null === $redis ) {
			return false;
		}

		try {
			return $redis->info();
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}
