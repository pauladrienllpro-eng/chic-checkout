<?php
/**
 * Désactive le template de panier WooCommerce classique et sert notre app React à la place.
 * Même traitement "distraction-free" que le checkout (voir class-checkout-override.php) —
 * pas le footer réel du thème, contrairement à la page de confirmation.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cart_Override {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'maybe_override_cart_template' ), 99 );
		add_action( 'after_setup_theme', array( $this, 'ensure_title_tag_support' ) );
		add_action( 'wp', array( $this, 'maybe_hide_admin_bar' ) );
		add_filter( 'body_class', array( $this, 'strip_woocommerce_cart_body_class' ), 999 );
		add_action( 'template_redirect', array( $this, 'prevent_caching' ), 1 );
	}

	/**
	 * Le panier affiche le contenu propre à CHAQUE visiteur — même raisonnement que
	 * Checkout_Override::prevent_caching().
	 */
	public function prevent_caching(): void {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
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
	 * Même raisonnement que Checkout_Override::strip_woocommerce_checkout_body_class() :
	 * WooCommerce ajoute "woocommerce-cart" au <body> même quand c'est notre template
	 * qui s'affiche — des règles de thème ciblant cette classe ne doivent pas s'appliquer.
	 *
	 * @param string[] $classes
	 * @return string[]
	 */
	public function strip_woocommerce_cart_body_class( array $classes ): array {
		if ( 'cart' !== Compat::current_app() || ! Compat::should_render( 'cart' ) ) {
			return $classes;
		}

		return array_values( array_diff( $classes, array( 'woocommerce-cart' ) ) );
	}

	public function ensure_title_tag_support(): void {
		add_theme_support( 'title-tag' );
	}

	public function maybe_hide_admin_bar(): void {
		if ( ! is_cart() ) {
			return;
		}

		add_filter( 'show_admin_bar', '__return_false' );
	}

	public function maybe_override_cart_template( string $template ): string {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return $template;
		}

		if ( ! Compat::should_render( 'cart' ) ) {
			return $template;
		}

		$custom_template = CHIC_CHECKOUT_DIR . 'templates/cart-app.php';

		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}
}
