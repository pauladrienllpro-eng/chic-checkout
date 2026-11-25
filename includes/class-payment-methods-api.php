<?php
/**
 * Expose les moyens de paiement activés (Réglages WooCommerce > Paiements), dans l'ordre
 * configuré par le marchand — titre et icône viennent directement de chaque passerelle,
 * pas besoin de les dupliquer/hardcoder côté plugin.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payment_Methods_Api {

	const NAMESPACE_    = 'chic-checkout/v1';
	const MYCRED_GATEWAY_ID = 'mycred';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_,
			'/payment-methods',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payment_methods' ),
				// Nonce REST obligatoire (envoyé par payment-methods-api.ts).
				'permission_callback' => array( Auth_Api::class, 'verify_nonce' ),
			)
		);
	}

	public function get_payment_methods(): \WP_REST_Response {
		// Une route REST custom (contrairement à /wc/store/*, que WooCommerce fait
		// suivre de wc_load_cart() lui-même) ne garantit pas que WC()->cart soit déjà
		// instancié à ce stade. myCred (addons/gateway/carts/mycred-woocommerce.php,
		// mycred_woo_available_gateways()) retire silencieusement sa passerelle dès que
		// `empty( $woocommerce->cart )` — indépendamment du solde du client — d'où des
		// clients avec largement assez de points qui ne la voyaient jamais : constaté en
		// capturant is_available() = FALSE avec un solde confirmé suffisant au même
		// instant. wc_load_cart() est sans effet si déjà chargé (guards internes sur
		// chaque sous-composant), donc aucun risque à l'appeler ici à chaque requête.
		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$methods                  = array();
		$shipping_matches_billing = $this->shipping_matches_billing();
		$available_gateways       = WC()->payment_gateways->get_available_payment_gateways();

		// get_available_payment_gateways() respecte à la fois l'ordre configuré (drag & drop
		// dans Réglages > Paiements) et les restrictions de disponibilité (ex : COD limité à
		// certaines méthodes de livraison), déjà résolues pour le panier/client courant.
		// myCred traité séparément ci-dessous (voir Mycred_Helper) : get_available_
		// payment_gateways() n'est pas fiable pour lui (le solde insuffisant n'est pas
		// pris en compte au moment de CET appel, voir la note plus bas).
		foreach ( $available_gateways as $gateway ) {
			if ( self::MYCRED_GATEWAY_ID === $gateway->id ) {
				continue;
			}

			// Passerelle qui attend ses propres champs JS que ce checkout n'affiche pas
			// (Stripe officiel, WooPayments...) : masquée plutôt que proposée puis refusée
			// au paiement. Voir Payment_Support.
			if ( ! Payment_Support::is_supported( $gateway ) ) {
				continue;
			}

			// Le paiement à la livraison suppose que le livreur encaisse à l'adresse de
			// livraison — non pertinent si la facturation part ailleurs, donc masqué dans
			// ce cas plutôt que de le proposer pour un usage qui ne correspond pas à son objet.
			if ( 'cod' === $gateway->id && ! $shipping_matches_billing ) {
				continue;
			}

			$methods[] = array(
				'id'        => $gateway->id,
				'title'     => Payment_Support::checkout_title( $gateway ),
				// HTML <img> : logo téléversé par le marchand, sinon celui de la passerelle
				// (WC_Payment_Gateway::get_icon(), filtré comme sur le panier) — voir
				// Payment_Support::icon_html(). Vide si aucun.
				'icon'      => Payment_Support::icon_html( $gateway ),
				'config'    => $this->gateway_config( $gateway ),
				'available' => true,
			);
		}

		// myCred (mycred_woo_available_gateways()) retire le gateway de
		// get_available_payment_gateways() pour un utilisateur déconnecté/exclu, MAIS
		// PAS pour un solde insuffisant — dans ce cas elle fait seulement
		// `$gateways['mycred']->enabled = 'no'`, un effet de bord qui ne s'applique qu'à
		// un ÉVENTUEL appel ultérieur de cette fonction DANS LA MÊME REQUÊTE. On
		// n'appelle cette fonction qu'une fois ici, donc un solde insuffisant n'était
		// jamais reflété dans $available_gateways — constaté en prod : commande dont le
		// coût dépassait le solde, passerelle quand même affichée disponible. On
		// revérifie donc nous-mêmes le solde (Mycred_Helper), et on affiche quand même
		// la passerelle (marquée `available: false`) plutôt que la faire disparaître
		// silencieusement quand elle est enregistrée + activée dans les réglages.
		$mycred_gateway = Integrations::is_enabled( 'mycred' ) ? Mycred_Helper::get_gateway() : null;
		if ( $mycred_gateway ) {
			$mycred_listed = isset( $available_gateways[ self::MYCRED_GATEWAY_ID ] );
			$cost_balance  = $mycred_listed ? Mycred_Helper::get_cost_and_balance( $mycred_gateway ) : null;

			$methods[] = array(
				'id'        => self::MYCRED_GATEWAY_ID,
				'title'     => $mycred_gateway->get_title(),
				'icon'      => Payment_Support::icon_html( $mycred_gateway ),
				'config'    => array(),
				'available' => $mycred_listed && $cost_balance && $cost_balance['balance'] >= $cost_balance['cost'],
			);
		}

		$response = new \WP_REST_Response( $methods, 200 );
		// Route ouverte aux visiteurs anonymes (voir register_routes) + disponibilité qui dépend
		// du client courant (myCred, COD selon adresse...) : un cache de page/CDN qui ne
		// varie pas par cookie sert autrement la même réponse à tout le monde — constaté
		// en prod, un moyen de paiement pourtant disponible pour un client précis
		// (confirmé côté WooCommerce) n'apparaissait jamais côté checkout à cause d'une
		// réponse mise en cache pour un autre visiteur (souvent anonyme).
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );

		return $response;
	}

	/**
	 * Données de configuration nécessaires côté front pour les moyens de paiement dont
	 * l'intégration ne passe pas par le flux générique Store API (bouton natif à
	 * monter nous-mêmes) — vide pour tous les autres.
	 */
	private function gateway_config( \WC_Payment_Gateway $gateway ): array {
		// Ids exacts plutôt qu'un préfixe 'ppcp' : ppcp_card, ppcp_googlepay, ppcp_applepay
		// partagent ce préfixe mais ne sont pas le bouton PayPal (voir Payment_Support).
		if ( ! Payment_Support::is_paypal( $gateway->id ) ) {
			return array();
		}

		// "Passerelle PayPal par Payment Plugins" (pymntpl-paypal-woocommerce) : la clé
		// publique attendue par le SDK JS PayPal, résolue selon l'environnement
		// (sandbox/production) déjà configuré dans les réglages du gateway.
		if ( ! function_exists( 'wc_ppcp_get_container' ) || ! class_exists( '\PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings' ) ) {
			return array();
		}

		try {
			$api_settings = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings::class );
			return array( 'clientId' => $api_settings->get_client_id() );
		} catch ( \Exception $e ) {
			return array();
		}
	}

	private function shipping_matches_billing(): bool {
		$customer = WC()->customer;

		if ( ! $customer ) {
			return true;
		}

		$fields = array( 'address_1', 'address_2', 'city', 'postcode', 'country' );

		foreach ( $fields as $field ) {
			$shipping_getter = "get_shipping_$field";
			$billing_getter  = "get_billing_$field";

			$shipping_value = strtolower( trim( (string) $customer->$shipping_getter() ) );
			$billing_value  = strtolower( trim( (string) $customer->$billing_getter() ) );

			if ( $shipping_value !== $billing_value ) {
				return false;
			}
		}

		return true;
	}
}
