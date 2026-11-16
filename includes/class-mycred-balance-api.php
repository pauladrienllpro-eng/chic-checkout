<?php
/**
 * Coût de la commande en cours en points myCred et solde du client — exposé en lecture
 * seule via la Store API, calculé nous-mêmes plutôt que relayé depuis myCred.
 *
 * myCred expose déjà ces valeurs (myCred_Woo_Extend_Store_Endpoint::extend_checkout_
 * block_data(), addons/gateway/carts/block-compatibility/mycred-woo-block-store-api.php)
 * mais renvoie un tableau VIDE dès que get_available_payment_gateways() ne liste pas
 * 'mycred' — donc rien du tout dès que le solde est insuffisant, alors que c'est
 * justement le cas qu'on veut afficher sur le panier ("dans tous les cas"). Voir
 * Mycred_Helper pour le calcul du coût/solde, partagé avec Payment_Methods_Api.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mycred_Balance_Api {

	const EXTEND_NAMESPACE = 'chic-mycred-balance';

	public function __construct() {
		// Le service container de la Store API n'est prêt qu'à ce hook.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_extend_schema' ) );
	}

	public function register_extend_schema(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) || ! Integrations::is_enabled( 'mycred' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => self::EXTEND_NAMESPACE,
				'data_callback'   => array( $this, 'get_data' ),
				'schema_callback' => array( $this, 'get_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Réinjecté dans chaque réponse GET/POST panier sous
	 * `cart.extensions['chic-mycred-balance']` — `null` sur les deux champs si myCred
	 * n'est pas configuré/actif pour ce client (voir Mycred_Helper::get_gateway()).
	 */
	public function get_data(): array {
		$gateway = Mycred_Helper::get_gateway();
		$values  = $gateway ? Mycred_Helper::get_cost_and_balance( $gateway ) : null;

		if ( ! $gateway || ! $values ) {
			return array(
				'cost'          => null,
				'balance'       => null,
				'total_label'   => '',
				'balance_label' => '',
			);
		}

		// Mêmes réglages marchand que la passerelle myCred elle-même (Réglages WooCommerce
		// > Paiements > myCRED > Label / Balance Label) — jamais de texte français
		// recodé ici, au cas où le marchand les a personnalisés. total_label passe par
		// template_tags_general() (résout %_plural% etc.), balance_label est utilisé tel
		// quel : même traitement que myCred_Woo_Extend_Store_Endpoint::
		// extend_checkout_block_data(), pour rester cohérent avec ce qu'affichait déjà
		// le checkout WooCommerce classique.
		$mycred      = function_exists( 'mycred' ) ? mycred( $values['point_type'] ) : null;
		$total_label = $gateway->get_option( 'total_label' );
		if ( $mycred ) {
			$total_label = $mycred->template_tags_general( $total_label );
		}

		return array(
			'cost'          => $values['cost'],
			'balance'       => $values['balance'],
			'total_label'   => $total_label,
			'balance_label' => $gateway->get_option( 'balance_format' ),
		);
	}

	public function get_schema(): array {
		return array(
			'cost'          => array(
				'description' => __( 'Coût de la commande en cours en points myCred, quel que soit le solde du client.', 'chic-checkout' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'balance'       => array(
				'description' => __( 'Solde actuel du client en points myCred (avant cette commande).', 'chic-checkout' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total_label'   => array(
				'description' => __( 'Libellé configuré par le marchand pour le coût de la commande en points.', 'chic-checkout' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'balance_label' => array(
				'description' => __( 'Libellé configuré par le marchand pour le solde en points.', 'chic-checkout' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
}
