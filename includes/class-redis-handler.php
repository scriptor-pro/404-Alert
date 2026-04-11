<?php
/**
 * Gestionnaire Redis pour le plugin 404 Alert
 * Fournit une interface atomique pour les opérations de rate limiting
 */

defined( 'ABSPATH' ) || exit;

class Alert404_Redis_Handler {
	/**
	 * Instance unique de la connexion Redis
	 *
	 * @var \Redis|null
	 */
	private static $redis = null;

	/**
	 * Indique si Redis est disponible et connecté
	 *
	 * @var bool|null
	 */
	private static $available = null;

	/**
	 * Initialise la connexion Redis
	 * Essaie de se connecter, gère les erreurs gracieusement
	 *
	 * @return bool true si Redis est disponible, false sinon
	 */
	public static function init(): bool {
		if ( self::$available !== null ) {
			return self::$available;
		}

		self::$available = false;

		// Vérifier que l'extension Redis est installée
		if ( ! extension_loaded( 'redis' ) ) {
			Alert404_Logger::log_redis_unavailable( 'Extension Redis non installée' );
			return false;
		}

		try {
			$redis = new \Redis();

			// Configuration depuis les constantes WordPress
			$host     = defined( 'ALERT404_REDIS_HOST' ) ? ALERT404_REDIS_HOST : 'localhost';
			$port     = defined( 'ALERT404_REDIS_PORT' ) ? ALERT404_REDIS_PORT : 6379;
			$password = defined( 'ALERT404_REDIS_PASSWORD' ) ? ALERT404_REDIS_PASSWORD : null;
			$db       = defined( 'ALERT404_REDIS_DB' ) ? ALERT404_REDIS_DB : 0;
			$timeout  = defined( 'ALERT404_REDIS_TIMEOUT' ) ? ALERT404_REDIS_TIMEOUT : 2;

			// Tentative de connexion
			$connected = @$redis->connect( $host, $port, $timeout );

			if ( ! $connected ) {
				Alert404_Logger::log_redis_unavailable( "Impossible de se connecter à $host:$port" );
				return false;
			}

			// Authentification si password
			if ( ! empty( $password ) ) {
				$authenticated = @$redis->auth( $password );
				if ( ! $authenticated ) {
					Alert404_Logger::log_redis_unavailable( 'Authentification Redis échouée' );
					return false;
				}
			}

			// Sélectionner la base de données
			@$redis->select( $db );

			// Tester la connexion avec PING
			$ping = @$redis->ping();
			if ( $ping !== true && $ping !== '+PONG' ) {
				Alert404_Logger::log_redis_unavailable( 'Redis PING échoué' );
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
	 * Retourne l'instance Redis ou null si indisponible
	 *
	 * @return \Redis|null
	 */
	public static function get_instance(): ?\Redis {
		if ( self::$available === null ) {
			self::init();
		}

		return self::$redis;
	}

	/**
	 * Vérifie si Redis est disponible
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		if ( self::$available === null ) {
			self::init();
		}

		return self::$available === true;
	}

	/**
	 * Acquiert un verrou de manière atomique (SET ... NX)
	 * Retour immédiat, pas de spin-wait
	 *
	 * @param string $key Clé du verrou
	 * @param int    $timeout Durée du verrou en secondes
	 * @return bool true si le verrou a été acquis, false sinon
	 */
	public static function acquire_lock( string $key, int $timeout = 5 ): bool {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return false;
		}

		try {
			// SET key value NX EX timeout
			// Atomique: SET si n'existe pas, avec expiration
			$result = @$redis->set(
				$key,
				wp_hash( uniqid( '', true ) ),
				// Valeur unique
				array(
					'EX' => $timeout,
					// Expiration en secondes
														'NX' => true,
				// Only if Not eXists
				)
			);

			return $result === true;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'acquire_lock failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Libère un verrou
	 *
	 * @param string $key Clé du verrou
	 * @return bool
	 */
	public static function release_lock( string $key ): bool {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return false;
		}

		try {
			@$redis->del( $key );
			return true;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'release_lock failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Incrémente un compteur de manière atomique
	 * Retourne la nouvelle valeur
	 *
	 * @param string $key Clé du compteur
	 * @param int    $ttl Durée de vie en secondes (0 = sans expiration)
	 * @return int|false La nouvelle valeur, ou false si erreur
	 */
	public static function increment( string $key, int $ttl = 0 ) {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return false;
		}

		try {
			// INCR est atomique dans Redis
			$value = @$redis->incr( $key );

			// Définir l'expiration si demandée
			if ( $ttl > 0 ) {
				@$redis->expire( $key, $ttl );
			}

			return $value;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'increment failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Obtient une valeur
	 *
	 * @param string $key Clé
	 * @return string|false La valeur, ou false si n'existe pas
	 */
	public static function get( string $key ) {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return false;
		}

		try {
			return @$redis->get( $key );
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'get failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Définit une valeur
	 *
	 * @param string $key Clé
	 * @param mixed  $value Valeur
	 * @param int    $ttl Durée de vie en secondes (0 = pas d'expiration)
	 * @return bool
	 */
	public static function set( string $key, $value, int $ttl = 0 ): bool {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return false;
		}

		try {
			if ( $ttl > 0 ) {
				$result = @$redis->setex( $key, $ttl, $value );
			} else {
				$result = @$redis->set( $key, $value );
			}

			return $result === true;
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'set failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Supprime une ou plusieurs clés
	 *
	 * @param string|array $keys Clé(s) à supprimer
	 * @return int Nombre de clés supprimées
	 */
	public static function delete( $keys ): int {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return 0;
		}

		try {
			if ( is_array( $keys ) ) {
				return @$redis->del( ...$keys );
			}

			return @$redis->del( $keys );
		} catch ( \Throwable $e ) {
			Alert404_Logger::log_redis_error( 'delete failed: ' . $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Force une reconnexion à Redis (utile après une perte de connexion)
	 *
	 * @return bool true si reconnexion réussie, false sinon
	 */
	public static function reconnect(): bool {
		self::close();
		$success = self::init();

		if ( $success ) {
			Alert404_Logger::log_redis_reconnected( 'Connexion rétablie après une perte' );
		}

		return $success;
	}

	/**
	 * Ferme la connexion Redis
	 *
	 * @return void
	 */
	public static function close(): void {
		if ( self::$redis !== null ) {
			try {
				@self::$redis->close();
			} catch ( \Throwable $e ) {
				// Silently ignore close errors (Redis may already be closed).
				unset( $e );
			}

			self::$redis     = null;
			self::$available = null;
		}
	}

	/**
	 * Obtient les statistiques Redis (info)
	 *
	 * @return array|false
	 */
	public static function get_info() {
		$redis = self::get_instance();

		if ( $redis === null ) {
			return false;
		}

		try {
			return @$redis->info();
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}
