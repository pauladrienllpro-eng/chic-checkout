<?php
/**
 * Copie du contrat d'envoi des statistiques (Chic Insights, INGEST.md, schema_version 1).
 *
 * Le plugin vérifie lui-même chaque envoi contre ces listes avant de l'émettre : une
 * valeur hors contrat n'est jamais envoyée (elle serait de toute façon refusée en bloc).
 * Toute modification doit d'abord être faite et déployée côté Chic Insights
 * (supabase/functions/_shared/contract.ts).
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stats_Contract {

	const SCHEMA_VERSION    = 1;
	const MAX_COUNTER_VALUE = 1000000;
	const MAX_AMOUNT_CENTS  = 1000000000;
	const MAX_COUNTERS      = 500;

	const PLANS = array( 'starter', 'pro', 'agence' );

	const CATEGORIES = array(
		'mode_accessoires',
		'maroquinerie_luxe',
		'bijoux_horlogerie',
		'beaute_cosmetique',
		'maison_deco',
		'alimentation_boissons',
		'bien_etre',
		'sport_loisirs',
		'enfants',
		'culture_creation',
		'services_reservations',
		'produits_digitaux',
		'autre',
	);

	const DETECTED = array( 'stripe_payment_plugins', 'paypal_payment_plugins', 'polylang', 'wp_consent_api' );

	const THEMES = array(
		'astra', 'avada', 'betheme', 'blocksy', 'botiga', 'bricks', 'divi', 'enfold', 'flatsome', 'generatepress',
		'hello-elementor', 'kadence', 'neve', 'oceanwp', 'porto', 'salient', 'shoptimizer', 'storefront', 'the7',
		'twentytwentythree', 'twentytwentyfour', 'twentytwentyfive', 'woodmart', 'other',
	);

	const CACHE_PLUGINS = array(
		'wp_rocket', 'litespeed_cache', 'autoptimize', 'jetpack_boost', 'w3_total_cache', 'wp_super_cache',
		'wp_fastest_cache', 'sg_optimizer', 'breeze', 'wp_optimize', 'nitropack', 'perfmatters', 'flying_press',
		'cloudflare', 'other',
	);

	/** 'unknown' : mesure sans appareil (config_missing, commande dont l'appareil n'est pas connu). */
	const DEVICES = array( 'mobile', 'desktop', 'tablet', 'unknown' );

	/**
	 * Métrique => array( 'extra' => clés de dimension permises (une exigée), 'amount' => montant,
	 * 'browser' => niveau 2 ).
	 */
	public static function counters(): array {
		return array(
			'cart_viewed'                     => array(),
			'checkout_started'                => array(),
			'step_reached'                    => array( 'extra' => array( 'step' ) ),
			// Part « paniers sans livraison » des compteurs du tunnel (parcours digital, sans étape Livraison).
			'digital_funnel'                  => array( 'extra' => array( 'stage' ) ),
			'order_placed'                    => array(),
			'order_paid'                      => array(),
			'order_paid_by'                   => array( 'extra' => array( 'method', 'shipping', 'customer', 'ship_country', 'hour', 'weekday', 'upsell', 'gift', 'coupon', 'free_product', 'flow' ) ),
			'payment_attempted'               => array( 'extra' => array( 'method' ) ),
			'payment_failed'                  => array( 'extra' => array( 'method' ) ),
			'payment_failed_by'               => array( 'extra' => array( 'failure' ) ),
			'revenue_cents'                   => array( 'amount' => true ),
			'revenue_cents_by'                => array( 'extra' => array( 'upsell', 'gift', 'flow' ), 'amount' => true ),
			// Paiement express du panier (Apple Pay, PayPal), mesuré à part du checkout.
			'express_attempted'               => array( 'extra' => array( 'method' ) ),
			'express_failed'                  => array( 'extra' => array( 'method' ) ),
			'upsell_revenue_cents'            => array( 'amount' => true ),
			'gift_option_orders'              => array( 'extra' => array( 'option' ) ),
			'loyalty_orders'                  => array(),
			'loyalty_points_used'             => array( 'amount' => true ),
			'login'                           => array( 'extra' => array( 'via' ) ),
			'error_boundary'                  => array( 'extra' => array( 'app' ) ),
			'fallback_standard'               => array( 'extra' => array( 'reason' ) ),
			'config_missing'                  => array( 'extra' => array( 'key' ) ),
			'browser_checkout_views'          => array( 'browser' => true ),
			'upsell_shown'                    => array( 'browser' => true ),
			'upsell_added'                    => array( 'browser' => true ),
			'free_shipping_bar_shown'         => array( 'browser' => true ),
			'free_shipping_threshold_crossed' => array( 'browser' => true ),
			'gift_module_opened'              => array( 'browser' => true ),
			'relay_map_opened'                => array( 'browser' => true ),
			'relay_point_changed'             => array( 'browser' => true ),
			'coupon_applied'                  => array( 'extra' => array( 'result' ), 'browser' => true ),
			'address_autocomplete_used'       => array( 'browser' => true ),
			'contact_clicked'                 => array( 'extra' => array( 'channel' ), 'browser' => true ),
			'high_contrast_used'              => array( 'browser' => true ),
			'badge_shown'                     => array( 'extra' => array( 'place' ), 'browser' => true ),
			'badge_clicked'                   => array( 'extra' => array( 'place' ), 'browser' => true ),
			'express_shown'                   => array( 'extra' => array( 'method' ), 'browser' => true ),
			'express_clicked'                 => array( 'extra' => array( 'method' ), 'browser' => true ),
		);
	}

	/** Valeurs permises de chaque dimension (hors device). */
	public static function dimensions(): array {
		return array(
			'step'         => array( 'identification', 'shipping', 'payment' ),
			'method'       => array( 'card', 'apple_pay', 'google_pay', 'klarna', 'wero', 'alma', 'paypal', 'mycred', 'bacs', 'cheque', 'cod', 'other' ),
			'shipping'     => array( 'home', 'carrier', 'relay', 'pickup' ),
			'customer'     => array( 'new', 'returning' ),
			'ship_country' => self::countries(),
			'hour'         => array_map( 'strval', range( 0, 23 ) ),
			'weekday'      => array( '1', '2', '3', '4', '5', '6', '7' ),
			'upsell'       => array( 'with', 'without' ),
			'gift'         => array( 'with', 'without' ),
			'coupon'       => array( 'with', 'without' ),
			'free_product' => array( 'with', 'without' ),
			'option'       => array( 'gift', 'wrapping', 'message' ),
			'result'       => array( 'success', 'failure' ),
			'via'          => array( 'google', 'apple', 'password', 'guest' ),
			'app'          => array( 'checkout', 'cart', 'thankyou' ),
			'reason'       => array( 'license', 'classic_param', 'missing_build', 'filter' ),
			'key'          => array( 'google_places_api_key', 'social_google_client_id', 'social_apple_services_id' ),
			'failure'      => array( 'declined', 'authentication', 'technical' ),
			'channel'      => array( 'page', 'email', 'whatsapp', 'phone' ),
			'place'        => array( 'confirmation', 'checkout' ),
			// Parcours de la commande : checkout, ou paiement express depuis le panier.
			'flow'         => array( 'checkout', 'express_apple_pay', 'express_google_pay', 'express_paypal' ),
			// Parcours digital : panier, checkout, étapes, commande payée via le checkout.
			'stage'        => array( 'cart', 'checkout', 'identification', 'payment', 'paid' ),
		);
	}

	/** Bornes inférieures des tranches ; la dernière est ouverte. */
	public static function histograms(): array {
		return array(
			'checkout_duration_s'            => array( 0, 30, 60, 120, 180, 300, 600, 1200, 1800 ),
			'step_duration_s_identification' => array( 0, 10, 20, 30, 60, 90, 120, 180, 300, 600 ),
			'step_duration_s_shipping'       => array( 0, 10, 20, 30, 60, 90, 120, 180, 300, 600 ),
			'step_duration_s_payment'        => array( 0, 10, 20, 30, 60, 90, 120, 180, 300, 600 ),
			'app_ready_ms'                   => array( 0, 250, 500, 750, 1000, 1500, 2000, 3000, 5000, 8000 ),
			'cart_value_eur'                 => array( 0, 20, 40, 60, 80, 100, 150, 200, 300, 500, 1000 ),
			'items_count'                    => array( 1, 2, 3, 4, 5, 6, 8, 10, 15, 20 ),
		);
	}

	const BROWSER_HISTOGRAMS = array( 'checkout_duration_s', 'step_duration_s_identification', 'step_duration_s_shipping', 'step_duration_s_payment', 'app_ready_ms' );

	public static function is_browser_counter( string $metric ): bool {
		return ! empty( self::counters()[ $metric ]['browser'] );
	}

	public static function is_browser_metric( string $metric ): bool {
		return self::is_browser_counter( $metric ) || in_array( $metric, self::BROWSER_HISTOGRAMS, true );
	}

	/** Index de la tranche contenant $value (tranche la plus basse si $value est sous la première borne). */
	public static function bucket_index( string $metric, float $value ): int {
		$edges = self::histograms()[ $metric ];
		$index = 0;
		foreach ( $edges as $i => $edge ) {
			if ( $value >= $edge ) {
				$index = $i;
			}
		}
		return $index;
	}

	/**
	 * Une ligne de compteur est-elle conforme ? (métrique connue, device, une dimension
	 * permise au plus et exigée si la métrique en attend une, valeur bornée)
	 */
	public static function is_valid_counter( string $metric, string $device, string $dim_key, string $dim_value, int $value ): bool {
		$spec = self::counters()[ $metric ] ?? null;
		if ( null === $spec || ! in_array( $device, self::DEVICES, true ) || $value < 0 ) {
			return false;
		}
		$max = empty( $spec['amount'] ) ? self::MAX_COUNTER_VALUE : self::MAX_AMOUNT_CENTS;
		if ( $value > $max ) {
			return false;
		}
		if ( empty( $spec['extra'] ) ) {
			return '' === $dim_key && '' === $dim_value;
		}
		return in_array( $dim_key, $spec['extra'], true ) && in_array( $dim_value, self::dimensions()[ $dim_key ] ?? array(), true );
	}

	public static function clean_version( string $version ): string {
		return preg_match( '/^\d{1,4}(\.\d{1,4}){0,3}/', $version, $match ) ? $match[0] : '0';
	}

	public static function countries(): array {
		return explode(
			' ',
			'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ ' .
			'CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR ' .
			'GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP ' .
			'KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT ' .
			'MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW ' .
			'SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG ' .
			'UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW'
		);
	}
}
