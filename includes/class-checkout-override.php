<?php
/**
 * Désactive le template de checkout WooCommerce classique et sert notre app React à la place.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Checkout_Override {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'maybe_override_checkout_template' ), 99 );
		// Notre template appelle wp_head()/wp_footer() directement (indépendant du thème actif),
		// donc on garantit nous-mêmes le support de la balise <title> plutôt que de dépendre du thème.
		add_action( 'after_setup_theme', array( $this, 'ensure_title_tag_support' ) );
		add_action( 'wp', array( $this, 'maybe_hide_admin_bar' ) );
		// Priorité tardive pour s'exécuter après wc_body_class() (WooCommerce core).
		add_filter( 'body_class', array( $this, 'strip_woocommerce_checkout_body_class' ), 999 );
		// Priorité très tôt, avant que le moindre octet de sortie ne parte : les plugins
		// de cache full-page (SG Optimizer, WP Rocket, LiteSpeed...) décident souvent de
		// mettre en cache une page à ce stade, pour les visiteurs non connectés.
		add_action( 'template_redirect', array( $this, 'prevent_caching' ), 1 );
	}

	/**
	 * Le checkout affiche un panier/une adresse propres à CHAQUE visiteur (connecté ou
	 * invité) : le mettre en cache "page complète" ferait voir à un invité le panier
	 * (ou pire, les identifiants de nonce) d'un visiteur précédent. WooCommerce évite
	 * normalement ça via ses propres hooks, mais certains plugins de cache se basent sur
	 * des heuristiques (classe body, cookies) qui ne nous concernent plus une fois qu'on
	 * sert notre propre template — on le signale donc nous-mêmes de façon explicite,
	 * via la convention DONOTCACHEPAGE que la plupart des plugins de cache respectent.
	 */
	public function prevent_caching(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}

	/**
	 * WooCommerce ajoute "woocommerce-checkout" au <body> dès que is_checkout() est
	 * vrai — même quand c'est notre template qui s'affiche, pas le sien. Certains
	 * thèmes ciblent ".woocommerce-checkout select/input" avec leurs propres règles
	 * !important : en retirant cette classe fantôme, ces règles ne matchent plus du
	 * tout, plutôt que d'essayer de neutraliser chaque propriété une par une.
	 *
	 * @param string[] $classes
	 * @return string[]
	 */
	public function strip_woocommerce_checkout_body_class( array $classes ): array {
		if ( 'checkout' !== Compat::current_app() || ! Compat::should_render( 'checkout' ) ) {
			return $classes;
		}

		return array_values( array_diff( $classes, array( 'woocommerce-checkout' ) ) );
	}

	public function ensure_title_tag_support(): void {
		add_theme_support( 'title-tag' );
	}

	/**
	 * La barre admin WordPress n'a pas sa place sur un checkout "distraction-free" —
	 * on la masque uniquement ici, pas sur le reste du site.
	 */
	public function maybe_hide_admin_bar(): void {
		if ( ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}

		add_filter( 'show_admin_bar', '__return_false' );
	}

	/**
	 * Ne touche qu'à la page de checkout principale, pas aux endpoints
	 * (order-pay, order-received, add-payment-method...) qui restent gérés par WooCommerce.
	 */
	public function maybe_override_checkout_template( string $template ): string {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
			return $template;
		}

		// Build absent ou ?chic-classic=1 : on laisse WooCommerce afficher son checkout
		// standard plutôt qu'un template vide (voir Compat::should_render()).
		if ( ! Compat::should_render( 'checkout' ) ) {
			return $template;
		}

		$custom_template = CHIC_CHECKOUT_DIR . 'templates/checkout-app.php';

		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}
}
