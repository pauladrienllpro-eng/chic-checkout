<?php
/**
 * Compteurs quotidiens agrégés, stockés sur le site du marchand.
 *
 * Deux tables dédiées, sans aucune donnée personnelle : (jour, métrique, appareil,
 * dimension) => valeur, et (jour, histogramme, appareil, tranche) => nombre. Les
 * incréments d'une requête sont gardés en mémoire puis écrits en une seule requête SQL
 * par table à la fin de la requête PHP (shutdown) : aucune écriture pendant le rendu.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stats_Store {

	const DB_VERSION        = '2';
	const DB_VERSION_OPTION = 'chic_checkout_stats_db_version';

	/** @var array<string, int> clé "jour|métrique|appareil|clé|valeur" => incrément */
	private static array $counters = array();

	/** @var array<string, int> clé "jour|histogramme|appareil|tranche" => incrément */
	private static array $histograms = array();

	private static bool $flush_hooked = false;

	public static function counters_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'chic_stats_counters';
	}

	public static function histograms_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'chic_stats_histograms';
	}

	/** Création ou mise à jour des tables (dbDelta), une fois par version de schéma. */
	public static function maybe_install(): void {
		if ( self::DB_VERSION === get_option( self::DB_VERSION_OPTION ) ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			'CREATE TABLE ' . self::counters_table() . " (
				day date NOT NULL,
				metric varchar(48) NOT NULL,
				device varchar(8) NOT NULL,
				dim_key varchar(32) NOT NULL DEFAULT '',
				dim_value varchar(32) NOT NULL DEFAULT '',
				value bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (day,metric,device,dim_key,dim_value)
			) $charset;"
		);
		dbDelta(
			'CREATE TABLE ' . self::histograms_table() . " (
				day date NOT NULL,
				metric varchar(48) NOT NULL,
				device varchar(8) NOT NULL,
				bucket tinyint(3) unsigned NOT NULL,
				count bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (day,metric,device,bucket)
			) $charset;"
		);

		self::repair_truncated_flows();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Avant la 1.8.3, dim_value faisait 16 caractères : MySQL (mode non strict) tronquait
	 * express_apple_pay et express_google_pay, et ces lignes, devenues hors contrat, n'étaient
	 * jamais envoyées (commandes express comptées comme absentes). Les valeurs sont rétablies
	 * et les journées concernées encore dans la fenêtre d'envoi seront renvoyées (un envoi
	 * plus récent remplace la journée dans Chic Insights).
	 */
	private static function repair_truncated_flows(): void {
		global $wpdb;
		$table   = self::counters_table();
		$fixed   = array(
			'express_apple_p' => 'express_apple_pay',
			'express_google_' => 'express_google_pay',
		);
		$days    = array();
		foreach ( $fixed as $truncated => $full ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nom de table interne.
			$found = (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT day FROM {$table} WHERE dim_key = 'flow' AND dim_value = %s", $truncated ) );
			if ( ! $found ) {
				continue;
			}
			$days = array_merge( $days, $found );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nom de table interne.
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET dim_value = %s WHERE dim_key = 'flow' AND dim_value = %s", $full, $truncated ) );
		}
		if ( $days ) {
			Stats_Sender::forget_sent_days( array_unique( $days ) );
		}
	}

	/** Journée courante dans le fuseau de la boutique. */
	public static function today(): string {
		return wp_date( 'Y-m-d' );
	}

	/**
	 * Ajoute $value au compteur du jour. Ignoré si la ligne sort du contrat (jamais
	 * stockée, donc jamais envoyée).
	 */
	public static function add( string $metric, string $device, int $value = 1, string $dim_key = '', string $dim_value = '' ): void {
		if ( $value <= 0 || ! Stats_Contract::is_valid_counter( $metric, $device, $dim_key, $dim_value, $value ) ) {
			return;
		}
		$key                    = implode( '|', array( self::today(), $metric, $device, $dim_key, $dim_value ) );
		self::$counters[ $key ] = ( self::$counters[ $key ] ?? 0 ) + $value;
		self::hook_flush();
	}

	public static function add_histogram( string $metric, string $device, float $observed, int $count = 1 ): void {
		if ( ! isset( Stats_Contract::histograms()[ $metric ] ) || ! in_array( $device, Stats_Contract::DEVICES, true ) || $count <= 0 ) {
			return;
		}
		self::add_histogram_bucket( $metric, $device, Stats_Contract::bucket_index( $metric, $observed ), $count );
	}

	public static function add_histogram_bucket( string $metric, string $device, int $bucket, int $count ): void {
		$edges = Stats_Contract::histograms()[ $metric ] ?? null;
		if ( null === $edges || $bucket < 0 || $bucket >= count( $edges ) || $count <= 0 || ! in_array( $device, Stats_Contract::DEVICES, true ) ) {
			return;
		}
		$key                      = implode( '|', array( self::today(), $metric, $device, (string) $bucket ) );
		self::$histograms[ $key ] = ( self::$histograms[ $key ] ?? 0 ) + $count;
		self::hook_flush();
	}

	private static function hook_flush(): void {
		if ( ! self::$flush_hooked ) {
			self::$flush_hooked = true;
			add_action( 'shutdown', array( self::class, 'flush' ), 1 );
		}
	}

	/** Écrit les incréments en attente : une requête INSERT … ON DUPLICATE KEY UPDATE par table. */
	public static function flush(): void {
		global $wpdb;

		if ( self::$counters ) {
			$rows   = array();
			$values = array();
			foreach ( self::$counters as $key => $value ) {
				$rows[] = '(%s, %s, %s, %s, %s, %d)';
				// PHP 8.0 : pas d'argument positionnel après un déballage (...).
				$values = array_merge( $values, explode( '|', $key ), array( $value ) );
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- nom de table en %i, groupes de valeurs générés ici (espaces réservés seulement), valeurs préparées.
			$wpdb->query( $wpdb->prepare( 'INSERT INTO %i (day, metric, device, dim_key, dim_value, value) VALUES ' . implode( ',', $rows ) . ' ON DUPLICATE KEY UPDATE value = value + VALUES(value)', array_merge( array( self::counters_table() ), $values ) ) );
			self::$counters = array();
		}

		if ( self::$histograms ) {
			$rows   = array();
			$values = array();
			foreach ( self::$histograms as $key => $count ) {
				$rows[] = '(%s, %s, %s, %d, %d)';
				list( $day, $metric, $device, $bucket ) = explode( '|', $key );
				array_push( $values, $day, $metric, $device, (int) $bucket, $count );
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- nom de table en %i, groupes de valeurs générés ici (espaces réservés seulement), valeurs préparées.
			$wpdb->query( $wpdb->prepare( 'INSERT INTO %i (day, metric, device, bucket, count) VALUES ' . implode( ',', $rows ) . ' ON DUPLICATE KEY UPDATE count = count + VALUES(count)', array_merge( array( self::histograms_table() ), $values ) ) );
			self::$histograms = array();
		}
	}

	/** Annule les incréments de la requête en cours (statistiques désactivées entre-temps). */
	public static function discard_pending(): void {
		self::$counters   = array();
		self::$histograms = array();
	}

	/** @return array<int, array{metric:string,device:string,dim_key:string,dim_value:string,value:int}> */
	public static function day_counters( string $day ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT metric, device, dim_key, dim_value, value FROM %i WHERE day = %s', self::counters_table(), $day ), ARRAY_A );
		return array_map(
			static fn( $row ) => array(
				'metric'    => $row['metric'],
				'device'    => $row['device'],
				'dim_key'   => $row['dim_key'],
				'dim_value' => $row['dim_value'],
				'value'     => (int) $row['value'],
			),
			$rows ?: array()
		);
	}

	/** @return array<int, array{metric:string,device:string,bucket:int,count:int}> */
	public static function day_histograms( string $day ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT metric, device, bucket, count FROM %i WHERE day = %s', self::histograms_table(), $day ), ARRAY_A );
		return array_map(
			static fn( $row ) => array(
				'metric' => $row['metric'],
				'device' => $row['device'],
				'bucket' => (int) $row['bucket'],
				'count'  => (int) $row['count'],
			),
			$rows ?: array()
		);
	}

	/** Journées présentes en base, de la plus ancienne à la plus récente. */
	public static function days(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$days = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT day FROM %i UNION SELECT DISTINCT day FROM %i ORDER BY day', self::counters_table(), self::histograms_table() ) );
		return array_map( 'strval', $days ?: array() );
	}

	/**
	 * Somme des compteurs sur une période (écran Performances).
	 *
	 * @return array<int, array{metric:string,dim_key:string,dim_value:string,value:int}>
	 */
	public static function totals( string $from, string $to ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT metric, dim_key, dim_value, SUM(value) AS value FROM %i WHERE day BETWEEN %s AND %s GROUP BY metric, dim_key, dim_value', self::counters_table(), $from, $to ), ARRAY_A );
		return array_map(
			static fn( $row ) => array(
				'metric'    => $row['metric'],
				'dim_key'   => $row['dim_key'],
				'dim_value' => $row['dim_value'],
				'value'     => (int) $row['value'],
			),
			$rows ?: array()
		);
	}

	/**
	 * Compteurs jour par jour sur une période, pour les métriques demandées (courbes de
	 * l'écran Performances). La clé primaire commence par le jour : lecture par plage.
	 *
	 * @param string[] $metrics
	 * @return array<int, array{day:string,metric:string,dim_key:string,dim_value:string,value:int}>
	 */
	public static function daily_totals( string $from, string $to, array $metrics ): array {
		global $wpdb;
		$metrics = array_values( array_filter( $metrics, static fn( $metric ) => isset( Stats_Contract::counters()[ $metric ] ) ) );
		if ( ! $metrics ) {
			return array();
		}
		$in = implode( ',', array_fill( 0, count( $metrics ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT day, metric, dim_key, dim_value, SUM(value) AS value FROM %i WHERE day BETWEEN %s AND %s AND metric IN ($in) GROUP BY day, metric, dim_key, dim_value", array_merge( array( self::counters_table(), $from, $to ), $metrics ) ), ARRAY_A );
		return array_map(
			static fn( $row ) => array(
				'day'       => (string) $row['day'],
				'metric'    => $row['metric'],
				'dim_key'   => $row['dim_key'],
				'dim_value' => $row['dim_value'],
				'value'     => (int) $row['value'],
			),
			$rows ?: array()
		);
	}

	/** Plus ancienne journée en base (null si aucune) : borne des périodes comparables. */
	public static function first_day(): ?string {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$day = $wpdb->get_var( $wpdb->prepare( 'SELECT MIN(day) FROM %i', self::counters_table() ) );
		return $day ? (string) $day : null;
	}

	public static function delete_before( string $day ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE day < %s', self::counters_table(), $day ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE day < %s', self::histograms_table(), $day ) );
	}

	public static function delete_all(): void {
		global $wpdb;
		self::discard_pending();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', self::counters_table() ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', self::histograms_table() ) );
	}

	/** Désinstallation : tables supprimées. */
	public static function drop(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i, %i', self::counters_table(), self::histograms_table() ) );
		delete_option( self::DB_VERSION_OPTION );
	}
}
