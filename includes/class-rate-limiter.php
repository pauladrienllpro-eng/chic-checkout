<?php
/**
 * Limite de fréquence des routes d'identification (vérification d'e-mail, connexion,
 * création de compte, connexion Google / Apple) : ces routes sont publiques et leur
 * nonce, identique pour tous les visiteurs anonymes, n'empêche pas l'automatisation.
 *
 * Compteur par adresse IP et par route, dans un transient. L'adresse IP n'est jamais
 * stockée telle quelle : seulement son empreinte HMAC (clé secrète du site).
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rate_Limiter {

	/** Route => [ requêtes autorisées, fenêtre en secondes ]. */
	const LIMITS = array(
		'check_email'  => array( 30, 10 * MINUTE_IN_SECONDS ),
		'login'        => array( 10, 10 * MINUTE_IN_SECONDS ),
		'register'     => array( 5, HOUR_IN_SECONDS ),
		'social_login' => array( 10, 10 * MINUTE_IN_SECONDS ),
		// Statistiques : un visiteur n'envoie que quelques balises par page (sortie,
		// changement d'onglet). Au-delà, envois en boucle qui fausseraient les chiffres.
		'stats'        => array( 30, 10 * MINUTE_IN_SECONDS ),
		// Lien d'accès support temporaire (version Pro) : ouvert une ou deux fois par l'éditeur.
		'support_access' => array( 10, 10 * MINUTE_IN_SECONDS ),
		// Avis client (version Pro) : un envoi par commande, quelques nouvelles tentatives au plus.
		'review'       => array( 10, 10 * MINUTE_IN_SECONDS ),
	);

	/**
	 * Compte une requête et renvoie une erreur 429 si la limite est dépassée.
	 *
	 * @return true|\WP_Error
	 */
	public static function hit( string $route ) {
		list( $limit, $window ) = self::LIMITS[ $route ] ?? array( 30, 10 * MINUTE_IN_SECONDS );
		$limit = (int) apply_filters( 'chic_checkout_rate_limit', $limit, $route );
		if ( $limit <= 0 ) {
			return true;
		}

		$key   = 'chic_rl_' . $route . '_' . substr( hash_hmac( 'sha256', self::client_ip(), wp_salt( 'nonce' ) ), 0, 32 );
		$state = get_transient( $key );
		$now   = time();
		if ( ! is_array( $state ) || (int) ( $state['until'] ?? 0 ) <= $now ) {
			$state = array( 'count' => 0, 'until' => $now + $window );
		}
		++$state['count'];
		set_transient( $key, $state, max( 1, (int) $state['until'] - $now ) );

		if ( $state['count'] > $limit ) {
			return new \WP_Error(
				'chic_rate_limited',
				__( 'Trop de tentatives. Merci de réessayer dans quelques minutes.', 'chic-checkout' ),
				array( 'status' => 429 )
			);
		}
		return true;
	}

	/**
	 * Adresse IP du client telle que vue par le serveur (REMOTE_ADDR). Les en-têtes
	 * X-Forwarded-For ne sont pas lus : falsifiables par le client, ils permettraient
	 * de contourner la limite. Derrière un proxy (CDN), l'hébergeur ou un filtre fournit
	 * l'adresse réelle.
	 */
	private static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return (string) apply_filters( 'chic_checkout_rate_limit_ip', $ip );
	}
}
