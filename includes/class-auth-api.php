<?php

/**
 * API REST du checkout pour la connexion : login, création de compte et déconnexion
 * (plus, en version Pro, la détection d'un compte existant). Accessible aux visiteurs anonymes (nécessaire pour un
 * checkout invité) : le permission_callback exige le nonce REST standard, pas d'être
 * déjà connecté.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Auth_Api {
    const NAMESPACE_ = 'chic-checkout/v1';

    public function __construct() {
        add_action( 'rest_api_init', array($this, 'register_routes') );
    }

    public function register_routes() : void {
        register_rest_route( self::NAMESPACE_, '/login', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'login'),
            'permission_callback' => array(self::class, 'verify_nonce'),
            'args'                => array(
                'email'    => array(
                    'required' => true,
                ),
                'password' => array(
                    'required' => true,
                ),
            ),
        ) );
        register_rest_route( self::NAMESPACE_, '/register', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'register'),
            'permission_callback' => array(self::class, 'verify_nonce'),
            'args'                => array(
                'email'    => array(
                    'required' => true,
                ),
                'password' => array(
                    'required' => true,
                ),
            ),
        ) );
        register_rest_route( self::NAMESPACE_, '/logout', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'logout'),
            'permission_callback' => array(self::class, 'verify_nonce'),
        ) );
    }

    /**
     * permission_callback des routes du checkout : nonce REST obligatoire. Les routes
     * restent ouvertes aux visiteurs anonymes (connexion, création de compte, commande
     * invité) : le nonce prouve que la requête vient de nos pages, pas d'un autre site.
     */
    public static function verify_nonce( \WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( !$nonce || !wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new \WP_Error('chic_invalid_nonce', __( 'Nonce invalide.', 'chic-checkout' ), array(
                'status' => 403,
            ));
        }
        return true;
    }

    /**
     * Création de compte autorisée par le marchand (WooCommerce → Réglages → Comptes et
     * confidentialité → « Autoriser les clients à créer un compte pendant la validation de
     * commande »). Vérifiée côté serveur : masquer le bouton ne suffit pas.
     */
    public static function registration_enabled() : bool {
        return 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
    }

    public function login( \WP_REST_Request $request ) {
        $limited = Rate_Limiter::hit( 'login' );
        if ( is_wp_error( $limited ) ) {
            return $limited;
        }
        $email = sanitize_email( (string) $request->get_param( 'email' ) );
        $password = (string) $request->get_param( 'password' );
        $user = wp_signon( array(
            'user_login'    => $email,
            'user_password' => $password,
            'remember'      => true,
        ), is_ssl() );
        if ( is_wp_error( $user ) ) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __( 'Email ou mot de passe incorrect.', 'chic-checkout' ),
            ), 401);
        }
        // Le frontend recharge la page après un succès (voir LoginStep.tsx) : le nonce
        // initial de cette requête n'a pas besoin d'être renouvelé ici — un nonce généré
        // dans cette même requête PHP ne serait de toute façon pas valide pour la suivante
        // (le token de session ne reflète le nouveau cookie qu'après un vrai aller-retour HTTP).
        return new \WP_REST_Response(array(
            'success' => true,
        ), 200);
    }

    public function register( \WP_REST_Request $request ) {
        if ( !self::registration_enabled() ) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __( 'La création de compte n’est pas disponible sur cette boutique.', 'chic-checkout' ),
            ), 403);
        }
        $limited = Rate_Limiter::hit( 'register' );
        if ( is_wp_error( $limited ) ) {
            return $limited;
        }
        $email = sanitize_email( (string) $request->get_param( 'email' ) );
        $password = (string) $request->get_param( 'password' );
        if ( !is_email( $email ) ) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __( 'Adresse email invalide.', 'chic-checkout' ),
            ), 400);
        }
        if ( email_exists( $email ) ) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __( 'Impossible de créer un compte avec cet email. Si vous avez déjà un compte, connectez-vous.', 'chic-checkout' ),
            ), 409);
        }
        // Username vide : WooCommerce le génère automatiquement à partir de l'email
        // (cohérent avec le réglage 'woocommerce_registration_generate_username' du site).
        $user_id = wc_create_new_customer( $email, '', $password );
        if ( is_wp_error( $user_id ) ) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $user_id->get_error_message(),
            ), 400);
        }
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true, is_ssl() );
        // 'active' aujourd'hui : WordPress/WooCommerce ne demandent pas de vérification
        // email par défaut. Le champ existe pour accueillir ce cas si un jour un plugin
        // l'exige, sans avoir à changer le contrat de l'API à ce moment-là.
        return new \WP_REST_Response(array(
            'success' => true,
            'status'  => 'active',
        ), 200);
    }

    public function logout( \WP_REST_Request $request ) {
        wp_logout();
        return new \WP_REST_Response(array(
            'success' => true,
        ), 200);
    }

}
