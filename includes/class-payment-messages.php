<?php
/**
 * Messages d'erreur de paiement affichés au client, en français et modifiables dans
 * l'onglet Textes (Text_Overrides : ce fichier est lu par front_strings()).
 *
 * Le plugin « Payment Plugins for Stripe WooCommerce » traduit lui-même les refus
 * renvoyés par Stripe (code puis decline_code, voir WC_Stripe_Gateway::get_error_message())
 * mais ne fournit pas de traduction française : le client lisait « The card has been
 * declined for an unknown reason. ». Son filtre wc_stripe_get_error_messages permet de
 * remplacer ces messages par code. Les mêmes textes servent côté React pour les erreurs
 * renvoyées par Stripe.js (src/lib/payment-errors.ts) : un remplacement dans l'onglet
 * Textes s'applique aux deux.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payment_Messages {

	public function __construct() {
		add_filter( 'wc_stripe_get_error_messages', array( $this, 'filter_stripe_messages' ), 20 );
	}

	/**
	 * Code d'erreur Stripe (code ou decline_code) => message client.
	 *
	 * Refus pour fraude, carte perdue ou volée : message de refus générique, comme le
	 * recommande Stripe (ne jamais en indiquer la raison au client).
	 *
	 * @return array<string, string>
	 */
	public static function messages(): array {
		$declined     = __( 'Votre carte a été refusée. Contactez votre banque ou choisissez un autre moyen de paiement.', 'chic-checkout' );
		$number       = __( 'Le numéro de carte est incorrect.', 'chic-checkout' );
		$cvc          = __( 'Le code de sécurité (CVC) est incorrect.', 'chic-checkout' );
		$expiry       = __( 'La date d’expiration de la carte est incorrecte.', 'chic-checkout' );
		$authenticate = __( 'Votre banque n’a pas pu confirmer le paiement. Réessayez ou choisissez un autre moyen de paiement.', 'chic-checkout' );

		return array(
			'stripe_cc_generic'                    => __( 'Le paiement par carte n’a pas abouti. Merci de réessayer.', 'chic-checkout' ),
			'processing_error'                     => __( 'Une erreur est survenue pendant le traitement de la carte. Merci de réessayer.', 'chic-checkout' ),
			'card_declined'                        => $declined,
			'generic_decline'                      => $declined,
			'do_not_honor'                         => $declined,
			'call_issuer'                          => $declined,
			'fraudulent'                           => $declined,
			'lost_card'                            => $declined,
			'stolen_card'                          => $declined,
			'pickup_card'                          => $declined,
			'restricted_card'                      => $declined,
			'security_violation'                   => $declined,
			'transaction_not_allowed'              => $declined,
			'insufficient_funds'                   => __( 'Votre carte a été refusée : solde insuffisant. Choisissez une autre carte ou un autre moyen de paiement.', 'chic-checkout' ),
			'expired_card'                         => __( 'Votre carte a expiré. Choisissez une autre carte.', 'chic-checkout' ),
			'card_not_supported'                   => __( 'Cette carte ne permet pas ce type d’achat. Choisissez une autre carte.', 'chic-checkout' ),
			'currency_not_supported'               => __( 'Cette carte ne permet pas de payer dans cette devise. Choisissez une autre carte.', 'chic-checkout' ),
			'card_velocity_exceeded'               => __( 'Trop de tentatives avec cette carte. Réessayez plus tard ou choisissez une autre carte.', 'chic-checkout' ),
			'card_decline_rate_limit_exceeded'     => __( 'Trop de tentatives avec cette carte. Réessayez plus tard ou choisissez une autre carte.', 'chic-checkout' ),
			'incorrect_number'                     => $number,
			'invalid_number'                       => $number,
			'incomplete_number'                    => __( 'Le numéro de carte est incomplet.', 'chic-checkout' ),
			'incorrect_cvc'                        => $cvc,
			'invalid_cvc'                          => $cvc,
			'incomplete_cvc'                       => __( 'Le code de sécurité (CVC) est incomplet.', 'chic-checkout' ),
			'invalid_expiry_month'                 => $expiry,
			'invalid_expiry_year'                  => $expiry,
			'invalid_expiry_year_past'             => $expiry,
			'incomplete_expiry'                    => __( 'La date d’expiration de la carte est incomplète.', 'chic-checkout' ),
			'authentication_required'              => $authenticate,
			'payment_intent_authentication_failure' => $authenticate,
			'payment_cancelled'                    => __( 'Le paiement a été annulé.', 'chic-checkout' ),
		);
	}

	/**
	 * @param mixed $messages Messages du plugin Stripe, indexés par code.
	 * @return mixed
	 */
	public function filter_stripe_messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			return $messages;
		}
		return array_merge( $messages, self::messages() );
	}
}
