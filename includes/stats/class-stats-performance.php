<?php

/**
 * Écran « Performances » : indicateurs de la période choisie (7 jours, 30 jours, 90 jours,
 * 12 mois ou dates personnalisées), calculés à partir des compteurs locaux, comparés à la
 * période précédente, avec une bulle d'explication pour chaque indicateur. Version Pro :
 * courbes, tunnel de commande (tous les paniers, produits physiques ou digitaux), moyens
 * de paiement, upsell et option cadeau, et (plans Pro et Agence) moyenne + médiane des
 * boutiques comparables renvoyées par l'API benchmark, si elle les fournit.
 *
 * L'appel au benchmark n'a lieu qu'à l'ouverture de l'écran, au plus une fois par
 * 24 h et par période (transient).
 * Affichage : Stats_Dashboard (assets/css/performance.css, assets/js/performance.js).
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

    /** Périodes pour lesquelles l'API benchmark compare (paramètre days, 7 à 90). */
    const BENCHMARK_PERIODS = array(7, 30, 90);

    /** @var string */
    private $page_hook = '';

    public function __construct() {
        add_action( 'admin_menu', array($this, 'register_page'), 20 );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue') );
    }

    public function register_page() : void {
        $this->page_hook = (string) add_submenu_page(
            Settings::PAGE_SLUG,
            __( 'Performances', 'chic-checkout' ),
            __( 'Performances', 'chic-checkout' ),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array($this, 'render')
        );
    }

    public function enqueue( string $hook ) : void {
        if ( '' !== $this->page_hook ) {
            Stats_Dashboard::enqueue( $hook, $this->page_hook );
        }
    }

    /** Efface les comparatifs en cache (toutes périodes). */
    public static function clear_benchmark() : void {
        delete_transient( self::BENCHMARK_TRANSIENT );
        foreach ( self::BENCHMARK_PERIODS as $days ) {
            delete_transient( self::BENCHMARK_TRANSIENT . '_' . $days );
        }
    }

    /**
     * Compteurs d'une période, indexés « metric|dim_key=dim_value », et « metric|* » pour la
     * somme de toutes les dimensions d'une métrique.
     *
     * @return array<string, float>
     */
    public static function counts( string $from, string $to ) : array {
        $c = array();
        foreach ( Stats_Store::totals( $from, $to ) as $row ) {
            $key = $row['metric'] . (( '' !== $row['dim_key'] ? '|' . $row['dim_key'] . '=' . $row['dim_value'] : '' ));
            $c[$key] = ($c[$key] ?? 0) + $row['value'];
            $c[$row['metric'] . '|*'] = ($c[$row['metric'] . '|*'] ?? 0) + $row['value'];
        }
        return $c;
    }

    private static function ratio( float $num, float $den ) : ?float {
        return ( $den > 0 ? $num / $den : null );
    }

    /** Commandes payées en paiement express depuis le panier (ne traversent pas le checkout). */
    private static function express_orders( array $c ) : float {
        return (float) (($c['order_paid_by|flow=express_apple_pay'] ?? 0) + ($c['order_paid_by|flow=express_google_pay'] ?? 0) + ($c['order_paid_by|flow=express_paypal'] ?? 0));
    }

    /**
     * Indicateurs de la version gratuite : commandes payées, finalisation du panier
     * (commande payée après affichage du panier) et du checkout.
     *
     * @return array<string, array{label:string,kind:string,value:float|null}>
     */
    public static function free_indicators( string $from, string $to ) : array {
        $c = self::counts( $from, $to );
        $paid = (float) ($c['order_paid'] ?? 0);
        $checkout = max( 0, $paid - self::express_orders( $c ) );
        return array(
            'orders'              => array(
                'label' => __( 'Commandes payées', 'chic-checkout' ),
                'kind'  => 'count',
                'value' => $paid,
            ),
            'cart_to_order'       => array(
                'label' => __( 'Finalisation du panier (panier → commande)', 'chic-checkout' ),
                'kind'  => 'rate',
                'value' => self::ratio( $paid, (float) ($c['cart_viewed'] ?? 0) ),
            ),
            'checkout_completion' => array(
                'label' => __( 'Finalisation du checkout', 'chic-checkout' ),
                'kind'  => 'rate',
                'value' => self::ratio( $checkout, (float) ($c['checkout_started'] ?? 0) ),
            ),
        );
    }

    /** Explications communes aux deux versions (bulles « i »). */
    private static function free_explanations() : array {
        return array(
            'orders'              => __( 'Nombre de commandes réellement payées sur la période, quel que soit le moyen de paiement.', 'chic-checkout' ),
            'cart_to_order'       => __( 'Sur 100 visiteurs qui ouvrent leur panier, combien finissent par payer leur commande. C’est l’indicateur le plus global de votre tunnel de vente : plus il est haut, mieux c’est.', 'chic-checkout' ),
            'checkout_completion' => __( 'Parmi les personnes qui arrivent sur la page de commande (le checkout), la part qui achète réellement. Exemple : 100 personnes arrivent sur le checkout, 45 paient, le taux est de 45 %. Les paiements express depuis le panier ne sont pas comptés ici.', 'chic-checkout' ),
            'orders_chart'        => __( 'Nombre de commandes payées, jour par jour (ou semaine par semaine sur 12 mois). Survolez la courbe pour voir le détail.', 'chic-checkout' ),
        );
    }

    public function render() : void {
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        $this->render_free();
    }

    /** En-tête commun et écran « statistiques désactivées ». Renvoie false si rien à afficher. */
    private static function render_header( bool $pro ) : bool {
        ?>
		<header class="chic-perf__header">
			<h1><?php 
        esc_html_e( 'Performances du checkout', 'chic-checkout' );
        ?></h1>
			<p class="chic-perf__intro"><?php 
        esc_html_e( 'Ce qui se passe entre le panier et la commande payée. Cliquez ou survolez les « i » pour savoir ce que mesure chaque chiffre.', 'chic-checkout' );
        ?></p>
		</header>
		<?php 
        if ( Stats_Settings::is_enabled() ) {
            return true;
        }
        ?>
		<div class="chic-panel chic-empty">
			<span class="chic-empty__icon"><?php 
        Stats_Dashboard::icon( 'chart' );
        ?></span>
			<h2><?php 
        esc_html_e( 'Activez les statistiques pour voir vos performances', 'chic-checkout' );
        ?></h2>
			<p>
				<?php 
        echo esc_html( ( $pro ? __( 'Suivez ici votre conversion panier → commande, l’abandon à chaque étape, votre panier moyen et l’effet de l’upsell et de l’option cadeau, avec des courbes sur la période de votre choix.', 'chic-checkout' ) : __( 'Suivez ici vos commandes payées et le taux de finalisation de votre panier et de votre checkout. Calculées sur votre site, rien n’est envoyé.', 'chic-checkout' ) ) );
        ?>
			</p>
			<a class="button button-primary" href="<?php 
        echo esc_url( Settings::tab_url( 'stats' ) );
        ?>"><?php 
        esc_html_e( 'Activer dans Réglages → Statistiques', 'chic-checkout' );
        ?></a>
		</div>
		<?php 
        return false;
    }

    /** Écran de la version gratuite (ou Pro sans licence valide). */
    private function render_free() : void {
        ?>
		<div class="wrap chic-perf">
			<?php 
        if ( self::render_header( false ) ) {
            ?>
				<?php 
            $period = Stats_Dashboard::period();
            $current = self::free_indicators( $period['from'], $period['to'] );
            $previous = ( $period['comparable'] ? self::free_indicators( $period['prev_from'], $period['prev_to'] ) : array() );
            $texts = self::free_explanations();
            $icons = array(
                'orders'              => 'bag',
                'cart_to_order'       => 'target',
                'checkout_completion' => 'check',
            );
            Stats_Dashboard::render_period_nav( $period, self::PAGE_SLUG );
            ?>
				<div class="chic-grid chic-grid--3">
					<?php 
            $i = 0;
            foreach ( $current as $id => $indicator ) {
                Stats_Dashboard::render_card( array(
                    'icon'  => $icons[$id],
                    'label' => $indicator['label'],
                    'info'  => $texts[$id],
                    'value' => Stats_Dashboard::format( $indicator['kind'], $indicator['value'] ),
                    'delta' => ( isset( $previous[$id] ) ? Stats_Dashboard::delta( $indicator['kind'], $indicator['value'], $previous[$id]['value'] ) : null ),
                ), $i++ );
            }
            ?>
				</div>
				<?php 
            $rows = Stats_Store::daily_totals( $period['from'], $period['to'], array('order_paid') );
            Stats_Dashboard::render_chart(
                array(array(
                    'id'     => 'orders',
                    'label'  => __( 'Commandes payées', 'chic-checkout' ),
                    'kind'   => 'count',
                    'values' => Stats_Dashboard::buckets( $period, $rows, static fn( $row ) => (float) $row['value'] ),
                    'total'  => Stats_Dashboard::format( 'count', $current['orders']['value'] ),
                )),
                $period,
                __( 'Évolution des commandes', 'chic-checkout' ),
                $texts['orders_chart']
            );
            ?>
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
