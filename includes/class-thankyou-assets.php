<?php
/**
 * CSS de la page de confirmation — réutilise le CSS déjà compilé du checkout (mêmes
 * classes .chic-header/.chic-shipping-address-card/.chic-order-summary...) plus une
 * petite feuille statique dédiée aux éléments propres à cette page (bandeau de succès,
 * carte point relais/horaires, badges de moyen de paiement). Volontairement séparé de
 * Assets::class : pas de bundle React à charger ici, cette page n'a aucune interactivité.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Thankyou_Assets {

	const CSS_ENTRY = 'src/thankyou.css';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() || ! Thankyou_Override::should_render() ) {
			return;
		}

		// Toujours le CSS du build (jamais le dev server Vite) : cette page n'importe
		// aucun module JS qui déclencherait l'injection de style de Vite en dev, le
		// fichier compilé est donc la seule source fiable dans les deux cas.
		// Entrée CSS dédiée (src/thankyou.css) : le checkout et le panier sont buildés en
		// IIFE et injectent leur CSS via le JS, leur entrée de manifest n'a pas de 'css'.
		$manifest_path = CHIC_CHECKOUT_DIR . Assets::build_dir() . '.vite/manifest.json';
		$manifest      = file_exists( $manifest_path ) ? json_decode( (string) file_get_contents( $manifest_path ), true ) : null;
		$css_file      = is_array( $manifest ) ? ( $manifest[ self::CSS_ENTRY ]['file'] ?? null ) : null;
		$deps          = array();

		if ( is_string( $css_file ) && file_exists( CHIC_CHECKOUT_DIR . Assets::build_dir() . $css_file ) ) {
			wp_enqueue_style(
				'chic-checkout-thankyou-app',
				CHIC_CHECKOUT_URL . Assets::build_dir() . $css_file,
				array(),
				Assets::asset_version( Assets::build_dir() . $css_file )
			);
			$deps[] = 'chic-checkout-thankyou-app';
		}

		Assets::enqueue_shell_styles( false );

		wp_enqueue_style(
			'chic-checkout-thankyou',
			CHIC_CHECKOUT_URL . 'assets/css/thankyou.css',
			$deps,
			Assets::asset_version( 'assets/css/thankyou.css' )
		);
	}
}
