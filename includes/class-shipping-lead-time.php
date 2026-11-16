<?php
/**
 * Injecte le délai de livraison (texte libre, configuré par méthode d'expédition dans
 * l'onglet "Livraison") dans la réponse Store API — aucun calcul de date ici, seulement
 * un texte contribué par le marchand (ex : "6-8 jours").
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shipping_Lead_Time {

	public function __construct() {
		add_filter( 'woocommerce_shipping_rate_delivery_time', array( $this, 'filter_delivery_time' ), 10, 2 );
	}

	/**
	 * @param string           $delivery_time Valeur d'origine (vide par défaut, WooCommerce ne la remplit jamais nativement).
	 * @param \WC_Shipping_Rate $rate Le tarif de livraison concerné.
	 */
	public function filter_delivery_time( $delivery_time, $rate ) {
		$lead_times = Settings::get()['shipping_lead_times'];
		$instance_id = (string) $rate->get_instance_id();

		return $lead_times[ $instance_id ] ?? $delivery_time;
	}

	/**
	 * Liste tous les couples zone/méthode configurés dans WooCommerce (Réglages > Livraison),
	 * pour que l'onglet "Livraison" du plugin propose un champ "Délai" à côté de chacun
	 * sans dupliquer la configuration des zones/méthodes elle-même.
	 *
	 * @return array<int, array{instance_id:int, zone_name:string, method_title:string}>
	 */
	public static function get_configured_methods(): array {
		$rows = array();

		$zones   = \WC_Shipping_Zones::get_zones();
		$zones[] = array(
			'zone_id'   => 0,
			'zone_name' => __( 'Localités non couvertes par vos autres zones', 'chic-checkout' ),
		);

		foreach ( $zones as $zone_data ) {
			$zone = new \WC_Shipping_Zone( $zone_data['zone_id'] );

			foreach ( $zone->get_shipping_methods() as $method ) {
				if ( ! $method->is_enabled() ) {
					continue;
				}

				$rows[] = array(
					'instance_id'  => $method->instance_id,
					'zone_name'    => $zone_data['zone_name'],
					'method_title' => $method->get_title(),
				);
			}
		}

		return $rows;
	}
}
