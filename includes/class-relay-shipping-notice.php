<?php
/**
 * Notice admin : un mode de livraison en point relais Boxtal / La Poste est actif
 * dans une zone WooCommerce alors que son intégration est désactivée dans l'onglet
 * Intégrations. Sans la carte (non chargée dans ce cas), le client ne peut pas choisir
 * de point relais et la commande ne peut pas aboutir avec ce mode.
 *
 * Un mode est "point relais" quand au moins une de ses tranches tarifaires actives
 * porte un réseau de points relais (Controller::get_pricing_items() du plugin, colonne
 * parcel_point_network). Si cette API n'est pas disponible (version du plugin
 * différente), le mode est considéré comme point relais : mieux vaut une alerte de trop
 * qu'une commande impossible passée sous silence.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Relay_Shipping_Notice {

	/**
	 * Intégration => [ id du mode de livraison, classe Controller du plugin ].
	 */
	const METHODS = array(
		'boxtal'  => array( 'boxtal_connect', '\Boxtal\BoxtalConnectWoocommerce\Shipping_Method\Controller' ),
		'laposte' => array( 'la_poste_pro_expeditions_woocommerce', '\LaPoste\LaPosteProExpeditionsWoocommerce\Shipping_Method\Controller' ),
	);

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
	}

	/**
	 * Modes point relais actifs dont l'intégration est désactivée (plugin détecté,
	 * interrupteur éteint).
	 *
	 * Lecture directe des tables de zones plutôt que WC_Shipping_Zone::
	 * get_shipping_methods() : celle-ci instancie chaque mode et émet un warning
	 * PHP pour un mode dont le plugin a été retiré, ce qui s'afficherait sur le tableau
	 * de bord avec WP_DEBUG.
	 *
	 * @return array<int, array{method: string, zone: string, integration: string}>
	 */
	public static function orphan_relay_methods(): array {
		global $wpdb;

		$orphans     = array();
		$definitions = null;

		foreach ( self::METHODS as $key => list( $method_id, $controller ) ) {
			if ( ! Integrations::is_detected( $key ) || Integrations::is_switched_on( $key ) ) {
				continue;
			}

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT m.instance_id, m.zone_id, z.zone_name
					FROM {$wpdb->prefix}woocommerce_shipping_zone_methods m
					LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zones z ON z.zone_id = m.zone_id
					WHERE m.method_id = %s AND m.is_enabled = 1",
					$method_id
				)
			);

			foreach ( (array) $rows as $row ) {
				if ( ! self::is_relay( $method_id, (int) $row->instance_id, $controller ) ) {
					continue;
				}

				$settings    = get_option( 'woocommerce_' . $method_id . '_' . $row->instance_id . '_settings', array() );
				$definitions = $definitions ?? Integrations::definitions();
				$orphans[]   = array(
					'method'      => wp_strip_all_tags( ! empty( $settings['title'] ) ? $settings['title'] : $method_id ),
					// Zone 0 = "Lieux non couverts par vos autres zones", absente de la table.
					'zone'        => $row->zone_name ? $row->zone_name : __( 'Lieux non couverts par vos autres zones', 'chic-checkout' ),
					'integration' => $definitions[ $key ]['label'],
				);
			}
		}

		return $orphans;
	}

	private static function is_relay( string $method_id, int $instance_id, string $controller ): bool {
		if ( ! class_exists( $controller ) || ! method_exists( $controller, 'get_pricing_items' ) ) {
			return true;
		}

		// Même identifiant que Shipping_Method_Util::get_unique_identifier() des deux plugins.
		$items = $controller::get_pricing_items( $method_id . ':' . $instance_id );

		foreach ( (array) $items as $item ) {
			// 'deactivated' en base, 'deactivate' dans le code du plugin : tranche désactivée.
			if ( ! in_array( $item['pricing'] ?? '', array( 'deactivate', 'deactivated' ), true ) && ! empty( $item['parcel_point_network'] ) ) {
				return true;
			}
		}

		return false;
	}

	public function maybe_notice(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'woocommerce_page_wc-settings', 'toplevel_page_' . Settings::PAGE_SLUG ), true ) ) {
			return;
		}

		$orphans = self::orphan_relay_methods();

		if ( ! $orphans ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Chic-checkout : point relais impossible à choisir', 'chic-checkout' ) . '</strong></p><ul style="list-style:disc;padding-left:20px;">';

		foreach ( $orphans as $orphan ) {
			echo '<li>';
			printf(
				/* translators: 1: shipping method name, 2: shipping zone name, 3: integration name */
				esc_html__( 'Le mode de livraison %1$s (zone %2$s) est actif mais l\'intégration %3$s est désactivée : vos clients ne pourront pas choisir de point relais.', 'chic-checkout' ),
				'<strong>' . esc_html( $orphan['method'] ) . '</strong>',
				esc_html( $orphan['zone'] ),
				'<strong>' . esc_html( $orphan['integration'] ) . '</strong>'
			);
			echo '</li>';
		}

		echo '</ul><p><a href="' . esc_url( admin_url( 'admin.php?page=' . Settings::PAGE_SLUG ) ) . '">' . esc_html__( 'Activer l\'intégration (onglet Intégrations)', 'chic-checkout' ) . '</a> · <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ) . '">' . esc_html__( 'Gérer les zones de livraison', 'chic-checkout' ) . '</a></p></div>';
	}
}
