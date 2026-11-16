<?php
/**
 * Logique commune à Payment_Methods_Api et Mycred_Balance_Api : résoudre la passerelle
 * myCred et calculer le coût de la commande en cours / le solde du client, sans jamais
 * dépendre de get_available_payment_gateways() pour la vérification du solde.
 *
 * myCred (mycred_woo_available_gateways(), addons/gateway/carts/mycred-woocommerce.php)
 * ne retire PAS la passerelle du tableau qu'elle retourne quand le solde est
 * insuffisant — elle fait `$gateways['mycred']->enabled = 'no';`, un effet de bord qui
 * ne modifie le résultat que d'un ÉVENTUEL appel ultérieur à get_available_payment_
 * gateways() DANS LA MÊME REQUÊTE PHP. Sur un endpoint qui n'appelle cette fonction
 * qu'une fois (notre cas), le solde insuffisant n'empêche donc jamais la passerelle
 * d'apparaître comme disponible à cet appel précis — constaté en prod : commande dont
 * le coût dépassait le solde, passerelle quand même affichée active. On recalcule donc
 * nous-mêmes coût et solde pour ne pas dépendre de ce détail d'implémentation.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mycred_Helper {

	const GATEWAY_ID = 'mycred';

	/**
	 * `null` si myCred est inactif, la passerelle absente/désactivée dans les réglages,
	 * ou le client non connecté (myCred n'a pas de solde à montrer pour un invité).
	 */
	public static function get_gateway(): ?\WC_Payment_Gateway {
		if ( ! is_user_logged_in() || ! function_exists( 'mycred_get_users_balance' ) || ! WC()->payment_gateways ) {
			return null;
		}

		$gateways = WC()->payment_gateways->payment_gateways();

		if ( ! isset( $gateways[ self::GATEWAY_ID ] ) || 'yes' !== $gateways[ self::GATEWAY_ID ]->get_option( 'enabled', 'no' ) ) {
			return null;
		}

		return $gateways[ self::GATEWAY_ID ];
	}

	public static function point_type_exists( string $point_type ): bool {
		return function_exists( 'mycred_point_type_exists' ) && mycred_point_type_exists( $point_type );
	}

	/**
	 * `null` si le panier n'est pas exploitable (WC()->cart absent). Sinon coût de la
	 * commande en cours en points myCred (même calcul que WC_Gateway_myCRED::
	 * process_payment()/mycred_woo_available_gateways()) et solde actuel du client
	 * (AVANT cette commande) pour le type de points réellement utilisé par la
	 * passerelle.
	 *
	 * @return array{cost:int,balance:int,point_type:string}|null
	 */
	public static function get_cost_and_balance( \WC_Payment_Gateway $gateway ): ?array {
		if ( ! WC()->cart || ! function_exists( 'mycred_get_users_balance' ) ) {
			return null;
		}

		$point_type = $gateway->get_option( 'point_type' );
		if ( ! $point_type || ! self::point_type_exists( $point_type ) ) {
			$point_type = defined( 'MYCRED_DEFAULT_TYPE_KEY' ) ? MYCRED_DEFAULT_TYPE_KEY : 'mycred_default';
		}

		// Même règle que WC_Gateway_myCRED::use_exchange() : pas de taux de change à
		// appliquer si la devise de la boutique est elle-même un type de points myCred.
		$currency = get_woocommerce_currency();
		if ( self::point_type_exists( $currency ) || 'MYC' === $currency ) {
			$cost = (int) round( (float) WC()->cart->total );
		} else {
			$exchange_rate = (float) $gateway->get_option( 'exchange_rate', 1 );
			if ( ! is_numeric( $exchange_rate ) || 0.0 === $exchange_rate ) {
				$exchange_rate = 1.0;
			}

			$cost = (int) round( ( (float) WC()->cart->total ) / $exchange_rate );
		}

		return array(
			'cost'       => $cost,
			'balance'    => (int) mycred_get_users_balance( get_current_user_id(), $point_type ),
			'point_type' => $point_type,
		);
	}
}
