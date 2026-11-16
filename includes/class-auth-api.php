<?php
/**
 * API REST du checkout pour la connexion : vérification d'email, login, création
 * de compte et déconnexion. Accessible aux visiteurs anonymes (nécessaire pour un
 * checkout invité), protégée par le nonce REST standard plutôt que par un
 * permission_callback qui exigerait d'être déjà connecté.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Auth_Api {

	const NAMESPACE_ = 'chic-checkout/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_,
			'/check-email',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'check_email' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'email' => array( 'required' => true ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/login',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'login' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'email'    => array( 'required' => true ),
					'password' => array( 'required' => true ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'register' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'email'    => array( 'required' => true ),
					'password' => array( 'required' => true ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/logout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'logout' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Vérifie le nonce REST manuellement (les routes doivent rester accessibles aux
	 * visiteurs anonymes, donc pas de permission_callback classique).
	 */
	private function verify_nonce( \WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error( 'chic_invalid_nonce', __( 'Nonce invalide.', 'chic-checkout' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Création de compte autorisée par le marchand (WooCommerce → Réglages → Comptes et
	 * confidentialité → « Autoriser les clients à créer un compte pendant la validation de
	 * commande »). Vérifiée côté serveur : masquer le bouton ne suffit pas.
	 */
	public static function registration_enabled(): bool {
		return 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
	}

	public function check_email( \WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$limited = Rate_Limiter::hit( 'check_email' );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		if ( ! is_email( $email ) ) {
			return new \WP_REST_Response( array( 'exists' => false ), 200 );
		}

		return new \WP_REST_Response( array( 'exists' => (bool) email_exists( $email ) ), 200 );
	}

	public function login( \WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$limited = Rate_Limiter::hit( 'login' );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$password = (string) $request->get_param( 'password' );

		$user = wp_signon(
			array(
				'user_login'    => $email,
				'user_password' => $password,
				'remember'      => true,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Email ou mot de passe incorrect.', 'chic-checkout' ),
				),
				401
			);
		}

		// Le frontend recharge la page après un succès (voir LoginStep.tsx) : le nonce
		// initial de cette requête n'a pas besoin d'être renouvelé ici — un nonce généré
		// dans cette même requête PHP ne serait de toute façon pas valide pour la suivante
		// (le token de session ne reflète le nouveau cookie qu'après un vrai aller-retour HTTP).
		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function register( \WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		if ( ! self::registration_enabled() ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'La création de compte n’est pas disponible sur cette boutique.', 'chic-checkout' ),
				),
				403
			);
		}

		$limited = Rate_Limiter::hit( 'register' );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$password = (string) $request->get_param( 'password' );

		if ( ! is_email( $email ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Adresse email invalide.', 'chic-checkout' ),
				),
				400
			);
		}

		if ( email_exists( $email ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Un compte existe déjà avec cet email.', 'chic-checkout' ),
				),
				409
			);
		}

		// Username vide : WooCommerce le génère automatiquement à partir de l'email
		// (cohérent avec le réglage 'woocommerce_registration_generate_username' du site).
		$user_id = wc_create_new_customer( $email, '', $password );

		if ( is_wp_error( $user_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $user_id->get_error_message(),
				),
				400
			);
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );

		// 'active' aujourd'hui : WordPress/WooCommerce ne demandent pas de vérification
		// email par défaut. Le champ existe pour accueillir ce cas si un jour un plugin
		// l'exige, sans avoir à changer le contrat de l'API à ce moment-là.
		return new \WP_REST_Response(
			array(
				'success' => true,
				'status'  => 'active',
			),
			200
		);
	}

	public function logout( \WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		wp_logout();

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}
}
