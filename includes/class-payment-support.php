<?php
/**
 * Quels moyens de paiement le checkout React sait réellement encaisser.
 *
 * Une passerelle qui attend ses propres champs JS (Stripe officiel, WooPayments...)
 * s'affichait sans formulaire de carte, puis la Store API refusait le paiement
 * ("Your payment details were not submitted") en laissant une commande "Échouée" —
 * vérifié en local. Seules sont donc proposées :
 *  - les passerelles intégrées côté front (Payment Plugins for Stripe : carte, Apple Pay, Google Pay,
 *    Klarna, Wero, et Alma via la passerelle universelle ; bouton PayPal ; myCred) ;
 *  - les passerelles sans champs (`has_fields` faux) : virement, chèque, paiement à la
 *    livraison, passerelles à redirection vers la banque — la Store API renvoie l'URL à
 *    suivre, aucun JS propre à la passerelle n'est nécessaire.
 *
 * Filtrable : add_filter( 'chic_checkout_gateway_supported', fn( $ok, $gateway ) => ..., 10, 2 ).
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payment_Support {

	/**
	 * Passerelles avec champs que le front gère explicitement (voir PaymentStep.tsx).
	 * 'ppcp' : bouton PayPal de Payment Plugins for PayPal WooCommerce.
	 */
	const SUPPORTED_IDS = array( 'stripe_cc', 'stripe_applepay', 'stripe_googlepay', 'stripe_klarna', 'stripe_wero', 'ppcp', 'mycred' );

	const PAYPAL_IDS = array( 'ppcp' );

	/**
	 * Passerelle « Universal Payment Method » de Payment Plugins for Stripe : elle regroupe
	 * dans un seul formulaire Stripe tous les moyens cochés dans ses réglages. Alma n'existe
	 * que là (pas de passerelle dédiée comme Klarna ou Wero) : le checkout ne propose donc
	 * cette passerelle que pour Alma, affichée sous ce nom, et seulement si Alma y est coché.
	 */
	const UPM_ID = 'stripe_upm';

	/**
	 * Toujours masquées, même sans champs : 'ppcp-gateway' est l'extension officielle
	 * WooCommerce PayPal Payments. Le bouton PayPal du checkout appelle les routes et la
	 * configuration de Payment Plugins (lib/paypal.ts) : avec l'extension officielle, il
	 * s'affichait puis échouait (« PayPal n'est pas configuré correctement »).
	 */
	const UNSUPPORTED_IDS = array( 'ppcp-gateway' );

	/** Slugs WordPress.org des plugins recommandés (voir Plugin_Links). */
	const STRIPE_PLUGIN = 'woo-stripe-payment';
	const PAYPAL_PLUGIN = 'pymntpl-paypal-woocommerce';

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_notice_unsupported' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'name_alma_order' ) );
	}

	/**
	 * Une commande Alma passée par la passerelle universelle portait son titre générique
	 * (« Popular payment methods » par défaut) dans l'admin, les e-mails et la page de
	 * confirmation. Appelé après l'enregistrement du moyen de paiement, avant l'encaissement.
	 */
	public function name_alma_order( $order ): void {
		if ( $order instanceof \WC_Order && self::UPM_ID === $order->get_payment_method() ) {
			$order->set_payment_method_title( 'Alma' );
			$order->save();
		}
	}

	public static function is_supported( \WC_Payment_Gateway $gateway ): bool {
		if ( self::UPM_ID === $gateway->id ) {
			$supported = self::upm_offers_alma( $gateway );
		} else {
			$supported = ! in_array( $gateway->id, self::UNSUPPORTED_IDS, true )
				&& ( in_array( $gateway->id, self::SUPPORTED_IDS, true ) || ! $gateway->has_fields );
		}

		return (bool) apply_filters( 'chic_checkout_gateway_supported', $supported, $gateway );
	}

	public static function upm_offers_alma( \WC_Payment_Gateway $gateway ): bool {
		return method_exists( $gateway, 'is_enabled_payment_method' ) && $gateway->is_enabled_payment_method( 'alma' );
	}

	/** Titre affiché dans le checkout : la passerelle universelle n'y sert qu'à Alma. */
	public static function checkout_title( \WC_Payment_Gateway $gateway ): string {
		return self::UPM_ID === $gateway->id ? 'Alma' : $gateway->get_title();
	}

	/**
	 * Logo HTML d'un moyen de paiement : celui téléversé dans Checkout → Paiement s'il y
	 * en a un, sinon celui de la passerelle (vide si aucun). Alma (passerelle universelle)
	 * n'en fournit pas : le logo officiel d'Alma (orange, assets/img/alma.svg, viewBox
	 * recadré sur le logo, tracés inchangés) est fourni par défaut. `alt` = nom du moyen
	 * de paiement, pour les logos affichés seuls (panier, réassurance) ; le checkout les
	 * masque aux lecteurs d'écran, le nom y étant déjà.
	 */
	public static function icon_html( \WC_Payment_Gateway $gateway ): string {
		$logos         = Settings::get()['payment_logos'] ?? array();
		$attachment_id = (int) ( $logos[ $gateway->id ] ?? 0 );
		$url           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		if ( ! $url && self::UPM_ID === $gateway->id && self::upm_offers_alma( $gateway ) ) {
			$url = CHIC_CHECKOUT_URL . 'assets/img/alma.svg';
		}

		if ( $url ) {
			return sprintf( '<img src="%s" alt="%s" />', esc_url( $url ), esc_attr( self::checkout_title( $gateway ) ) );
		}

		return wp_kses_post( $gateway->get_icon() );
	}

	public static function is_paypal( string $gateway_id ): bool {
		return in_array( $gateway_id, self::PAYPAL_IDS, true );
	}

	/**
	 * Passerelles activées dans WooCommerce mais masquées dans le checkout.
	 *
	 * @return \WC_Payment_Gateway[]
	 */
	public static function unsupported_enabled_gateways(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		return array_values(
			array_filter(
				WC()->payment_gateways()->payment_gateways(),
				static function ( $gateway ) {
					return $gateway instanceof \WC_Payment_Gateway && 'yes' === $gateway->enabled && ! self::is_supported( $gateway );
				}
			)
		);
	}

	/**
	 * Slug WordPress.org du plugin à installer pour remplacer une passerelle non
	 * supportée, ou '' si aucune alternative connue.
	 */
	public static function recommended_plugin( \WC_Payment_Gateway $gateway ): string {
		$id = $gateway->id;

		// Stripe officiel (WooCommerce Stripe Gateway : 'stripe', 'stripe_sepa_debit'...)
		// et WooPayments ('woocommerce_payments', 'woocommerce_payments_*').
		if ( 'stripe' === $id || str_starts_with( $id, 'woocommerce_payments' ) || ( str_starts_with( $id, 'stripe_' ) && ! self::is_payment_plugins_stripe( $gateway ) ) ) {
			return self::STRIPE_PLUGIN;
		}

		// PayPal officiel (WooCommerce PayPal Payments).
		if ( 'ppcp-gateway' === $id ) {
			return self::PAYPAL_PLUGIN;
		}

		return '';
	}

	private static function is_payment_plugins_stripe( \WC_Payment_Gateway $gateway ): bool {
		return str_starts_with( get_class( $gateway ), 'PaymentPlugins\\' );
	}

	/**
	 * Nom lisible d'une passerelle : certaines (ex. stripe_link) n'ont pas de
	 * method_title, seulement un titre affiché au client.
	 */
	public static function label( \WC_Payment_Gateway $gateway ): string {
		foreach ( array( $gateway->get_method_title(), $gateway->get_title() ) as $label ) {
			if ( is_string( $label ) && '' !== trim( $label ) ) {
				return wp_strip_all_tags( $label );
			}
		}

		return $gateway->id;
	}

	public function maybe_notice_unsupported(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'woocommerce_page_wc-settings', 'toplevel_page_' . Settings::PAGE_SLUG ), true ) ) {
			return;
		}

		$gateways = self::unsupported_enabled_gateways();

		if ( ! $gateways ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Chic-checkout : moyens de paiement masqués dans le checkout', 'chic-checkout' ) . '</strong></p><ul style="list-style:disc;padding-left:20px;">';

		foreach ( $gateways as $gateway ) {
			$title  = self::label( $gateway );
			$plugin = self::recommended_plugin( $gateway );

			echo '<li>';
			if ( self::UPM_ID === $gateway->id ) {
				printf(
					/* translators: %s: payment gateway name */
					esc_html__( '%s : seul Alma est proposé dans le checkout Chic-checkout. Cochez Alma dans les réglages de cette passerelle pour l\'afficher.', 'chic-checkout' ),
					'<strong>' . esc_html( $title ) . '</strong>'
				);
			} elseif ( $plugin ) {
				printf(
					/* translators: 1: payment gateway name, 2: plugin name */
					esc_html__( '%1$s n\'est pas compatible avec le checkout Chic-checkout. Pour accepter ce moyen de paiement, installez %2$s.', 'chic-checkout' ),
					'<strong>' . esc_html( $title ) . '</strong>',
					'<strong>' . esc_html( Plugin_Links::name( $plugin ) ) . '</strong>'
				);
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans Plugin_Links::render().
				echo ' ' . Plugin_Links::render( $plugin );
			} else {
				printf(
					/* translators: %s: payment gateway name */
					esc_html__( '%s n\'est pas compatible avec le checkout Chic-checkout et n\'y est pas proposé.', 'chic-checkout' ),
					'<strong>' . esc_html( $title ) . '</strong>'
				);
			}
			echo '</li>';
		}

		echo '</ul></div>';
	}
}
