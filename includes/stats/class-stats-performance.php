<?php

/**
 * Écran « Performances » : indicateurs des 30 derniers jours calculés à partir des
 * compteurs locaux, et (plans Pro et Agence) moyenne + médiane des boutiques comparables
 * renvoyées par l'API benchmark, affichées seulement si l'API les fournit.
 *
 * L'appel au benchmark n'a lieu qu'à l'ouverture de l'écran, au plus une fois par
 * 24 h (transient). En marque blanche, aucune mention de Chic-checkout.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Stats_Performance {
    const PAGE_SLUG = 'chic-checkout-performance';

    const BENCHMARK_TRANSIENT = 'chic_checkout_stats_benchmark';

    const DAYS = 30;

    public function __construct() {
        add_action( 'admin_menu', array($this, 'register_page'), 20 );
    }

    public function register_page() : void {
        add_submenu_page(
            Settings::PAGE_SLUG,
            __( 'Performances', 'chic-checkout' ),
            __( 'Performances', 'chic-checkout' ),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array($this, 'render')
        );
    }

    private static function format( string $kind, ?float $value ) : string {
        if ( null === $value ) {
            return '—';
        }
        if ( 'rate' === $kind ) {
            return number_format_i18n( $value * 100, 1 ) . ' %';
        }
        return wp_strip_all_tags( html_entity_decode( wc_price( $value ) ) );
    }

    /**
     * Indicateurs de la version gratuite : finalisation du panier (commande payée après
     * affichage du panier) et du checkout, calculés sur les compteurs du site.
     *
     * @return array<string, array{label:string,kind:string,value:float|null}>
     */
    public static function free_indicators( string $from, string $to ) : array {
        $c = array();
        foreach ( Stats_Store::totals( $from, $to ) as $row ) {
            $key = $row['metric'] . (( '' !== $row['dim_key'] ? '|' . $row['dim_key'] . '=' . $row['dim_value'] : '' ));
            $c[$key] = ($c[$key] ?? 0) + $row['value'];
        }
        $get = static fn( string $key ) => (float) ($c[$key] ?? 0);
        $ratio = static fn( float $num, float $den ) => ( $den > 0 ? $num / $den : null );
        $paid = $get( 'order_paid' );
        // Commandes payées sans passer par le checkout (paiement express du panier, Pro).
        $express = $get( 'order_paid_by|flow=express_apple_pay' ) + $get( 'order_paid_by|flow=express_google_pay' ) + $get( 'order_paid_by|flow=express_paypal' );
        $checkout = max( 0, $paid - $express );
        return array(
            'cart_to_order'       => array(
                'label' => __( 'Finalisation du panier (panier → commande)', 'chic-checkout' ),
                'kind'  => 'rate',
                'value' => $ratio( $paid, $get( 'cart_viewed' ) ),
            ),
            'checkout_completion' => array(
                'label' => __( 'Finalisation du checkout', 'chic-checkout' ),
                'kind'  => 'rate',
                'value' => $ratio( $checkout, $get( 'checkout_started' ) ),
            ),
        );
    }

    public function render() : void {
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        $this->render_free();
    }

    /** Écran de la version gratuite (ou Pro sans licence valide). */
    private function render_free() : void {
        $to = wp_date( 'Y-m-d', strtotime( Stats_Store::today() . ' -1 day' ) );
        $from = wp_date( 'Y-m-d', strtotime( $to . ' -' . (self::DAYS - 1) . ' days' ) );
        ?>
		<div class="wrap">
			<h1><?php 
        esc_html_e( 'Performances du checkout', 'chic-checkout' );
        ?></h1>
			<?php 
        if ( !Stats_Settings::is_enabled() ) {
            ?>
				<div class="notice notice-info inline" style="padding:12px;">
					<p style="margin:0;">
						<?php 
            esc_html_e( 'Activez les statistiques pour suivre ici le taux de finalisation de votre panier et de votre checkout. Calculées sur votre site, rien n’est envoyé.', 'chic-checkout' );
            ?>
						<a href="<?php 
            echo esc_url( Settings::tab_url( 'stats' ) );
            ?>"><?php 
            esc_html_e( 'Réglages → onglet Statistiques', 'chic-checkout' );
            ?></a>
					</p>
				</div>
			<?php 
        } else {
            ?>
				<p class="description">
					<?php 
            printf( 
                /* translators: 1: date de début, 2: date de fin */
                esc_html__( 'Du %1$s au %2$s (30 derniers jours complets), calculé sur ce site.', 'chic-checkout' ),
                esc_html( date_i18n( get_option( 'date_format' ), strtotime( $from ) ) ),
                esc_html( date_i18n( get_option( 'date_format' ), strtotime( $to ) ) )
             );
            ?>
				</p>
				<table class="widefat striped" style="max-width:600px;">
					<tbody>
					<?php 
            foreach ( self::free_indicators( $from, $to ) as $indicator ) {
                ?>
						<tr>
							<td><?php 
                echo esc_html( $indicator['label'] );
                ?></td>
							<td style="text-align:right;"><?php 
                echo esc_html( self::format( $indicator['kind'], $indicator['value'] ) );
                ?></td>
						</tr>
					<?php 
            }
            ?>
					</tbody>
				</table>
			<?php 
        }
        ?>
			<?php 
        if ( !Edition::has_pro_code() ) {
            Pro_Upsell::render( 'stats' );
        }
        ?>
		</div>
		<?php 
    }

}
