<?php
/**
 * Route REST interne du site : reçoit les compteurs calculés en mémoire dans le
 * navigateur (niveau 2) et les remontées d'erreurs du front (niveau 1), les additionne
 * aux compteurs du jour puis oublie la requête. Aucun identifiant de visiteur, rien
 * n'est journalisé ; rien ne part du navigateur vers un service tiers.
 *
 * Envoi par navigator.sendBeacon (pagehide / visibilitychange) avec le nonce REST de la
 * page en paramètre (_wpnonce), seul moyen de le transmettre sans en-tête.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stats_Rest {

	const NAMESPACE = 'chic-checkout/v1';
	const ROUTE     = '/stats';

	/** Mesures de niveau 1 que le front peut remonter (le reste du niveau 1 est mesuré côté serveur). */
	const FRONT_SERVER_METRICS = array( 'error_boundary', 'payment_failed', 'payment_failed_by', 'express_failed' );

	const MAX_ROWS      = 60;
	const MAX_PER_ROW   = 50;
	const MAX_BODY_SIZE = 8192;

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'receive' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);
	}

	public function check_nonce( \WP_REST_Request $request ): bool {
		return (bool) wp_verify_nonce( (string) $request->get_param( '_wpnonce' ), 'wp_rest' );
	}

	public function receive( \WP_REST_Request $request ): \WP_REST_Response {
		// 204 dans tous les cas : le navigateur n'attend rien (sendBeacon) et n'apprend rien.
		$done = new \WP_REST_Response( null, 204 );

		if ( ! Stats_Settings::is_enabled() || Stats_Server::is_bot() || strlen( (string) $request->get_body() ) > self::MAX_BODY_SIZE ) {
			return $done;
		}

		// Le nonce est le même pour tous les visiteurs anonymes : il n'empêche pas l'envoi
		// en boucle. Limite par IP, réponse identique pour ne rien apprendre au client.
		if ( is_wp_error( Rate_Limiter::hit( 'stats' ) ) ) {
			return $done;
		}

		$payload = json_decode( (string) $request->get_body(), true );
		if ( ! is_array( $payload ) ) {
			return $done;
		}

		// Niveau 2 accepté seulement si le site l'autorise (WP Consent API ou option du marchand) ;
		// le consentement du visiteur, lui, est vérifié dans le navigateur avant toute mesure.
		$browser = ! empty( $payload['browser'] ) && Stats_Settings::browser_possible();
		$device  = Stats_Server::device();

		$counters = is_array( $payload['counters'] ?? null ) ? array_slice( $payload['counters'], 0, self::MAX_ROWS ) : array();
		foreach ( $counters as $row ) {
			if ( ! is_array( $row ) || 4 !== count( $row ) ) {
				continue;
			}
			list( $metric, $dim_key, $dim_value, $value ) = array_map( 'strval', $row );
			$value = min( (int) $value, self::MAX_PER_ROW );
			$level2 = Stats_Contract::is_browser_counter( $metric );
			if ( ( $level2 && ! $browser ) || ( ! $level2 && ! in_array( $metric, self::FRONT_SERVER_METRICS, true ) ) ) {
				continue;
			}
			// Stats_Store::add() ignore toute ligne hors contrat.
			Stats_Store::add( $metric, $device, $value, $dim_key, $dim_value );
		}

		if ( $browser ) {
			$histograms = is_array( $payload['histograms'] ?? null ) ? array_slice( $payload['histograms'], 0, self::MAX_ROWS ) : array();
			foreach ( $histograms as $row ) {
				if ( ! is_array( $row ) || 3 !== count( $row ) || ! in_array( (string) $row[0], Stats_Contract::BROWSER_HISTOGRAMS, true ) ) {
					continue;
				}
				Stats_Store::add_histogram_bucket( (string) $row[0], $device, (int) $row[1], min( (int) $row[2], self::MAX_PER_ROW ) );
			}
		}

		return $done;
	}
}
