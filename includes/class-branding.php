<?php

/**
 * Mention « Checkout par Chic-checkout » et signature technique.
 *
 * Toutes les règles d'affichage passent par Branding::is_visible() :
 *  - version gratuite (ou Pro sans licence valide) : désactivée par défaut, le marchand
 *    peut l'activer (Réglages → Apparence) ; jamais imposée (règle 10 de WordPress.org) ;
 *  - Pro, plan Starter : toujours affichée, non désactivable ;
 *  - Pro, plan Pro : affichée par défaut, interrupteur dans Réglages → Apparence ;
 *  - Pro, plan Agence ou licence en marque blanche : jamais.
 *
 * Emplacements : page de confirmation (principal) et, en petit, sous le bouton de
 * paiement. Jamais en haut du checkout, à côté des moyens de paiement ni dans les
 * e-mails WooCommerce. Texte et éventuelle icône SVG en ligne uniquement : aucune image,
 * aucun script ni pixel distant.
 *
 * Signature (voir DETECTION.md) : attribut data-chic-checkout="<version>" sur le conteneur
 * racine du panier, du checkout et de la confirmation, et <meta name="generator"> sur ces
 * seules pages (invisibles, sans lien) — sauf en marque blanche, où rien ne nomme
 * Chic-checkout.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Branding {
    const OPTION_KEY = 'chic_checkout_branding';

    const BASE_URL = 'https://pauladrien.fr/chic-checkout/';

    public function __construct() {
        add_action( 'admin_init', array($this, 'register') );
    }

    /**
     * Interrupteur du marchand. Jamais enregistré : désactivé dans la version gratuite,
     * activé par défaut avec une licence Pro valide (plan Pro).
     *
     * @return array{show:bool}
     */
    public static function get() : array {
        $stored = get_option( self::OPTION_KEY, array() );
        $stored = ( is_array( $stored ) ? $stored : array() );
        return array(
            'show' => ( array_key_exists( 'show', $stored ) ? (bool) $stored['show'] : Edition::is_pro() ),
        );
    }

    public static function is_visible() : bool {
        if ( Edition::is_whitelabeled() ) {
            return false;
        }
        return self::get()['show'];
    }

    /** Signature technique (attribut et meta generator) : jamais en marque blanche. */
    public static function signature_enabled() : bool {
        return !Edition::is_whitelabeled();
    }

    /** Lien de la mention : page produit avec suivi de campagne. */
    public static function url( string $place ) : string {
        return add_query_arg( array(
            'utm_source'   => 'chic-checkout',
            'utm_medium'   => 'badge',
            'utm_campaign' => ( 'checkout' === $place ? 'checkout' : 'confirmation' ),
        ), self::BASE_URL );
    }

    /** Configuration transmise au checkout React (window.chicCheckout.branding). */
    public static function front_config() : array {
        $visible = self::is_visible();
        return array(
            'visible' => $visible,
            'url'     => ( $visible ? self::url( 'checkout' ) : '' ),
        );
    }

    /** Attribut data-chic-checkout du conteneur racine des templates (rien en marque blanche). */
    public static function print_root_attribute() : void {
        if ( self::signature_enabled() ) {
            echo ' data-chic-checkout="' . esc_attr( CHIC_CHECKOUT_VERSION ) . '"';
        }
    }

    /** Attribut du conteneur racine des templates. */
    public static function root_attribute() : string {
        return ( self::signature_enabled() ? ' data-chic-checkout="' . esc_attr( CHIC_CHECKOUT_VERSION ) . '"' : '' );
    }

    /** <meta name="generator"> : uniquement dans les templates panier, checkout et confirmation. */
    public static function generator_meta() : void {
        if ( self::signature_enabled() ) {
            echo '<meta name="generator" content="' . esc_attr( 'Chic-checkout ' . CHIC_CHECKOUT_VERSION ) . '">' . "\n";
        }
    }

    /** Mention de la page de confirmation (template PHP). */
    public static function render_confirmation() : void {
        if ( !self::is_visible() ) {
            return;
        }
        ?>
		<p class="chic-branding" id="chic-branding-confirmation">
			<svg viewBox="0 0 24 24" width="12" height="12" fill="none" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.8"/></svg>
			<a href="<?php 
        echo esc_url( self::url( 'confirmation' ) );
        ?>" target="_blank" rel="nofollow noopener" aria-label="<?php 
        esc_attr_e( 'Checkout par Chic-checkout (nouvel onglet)', 'chic-checkout' );
        ?>"><?php 
        esc_html_e( 'Checkout par Chic-checkout', 'chic-checkout' );
        ?></a>
		</p>
		<?php 
        self::print_confirmation_stats_script();
    }

    /**
     * Statistiques de la mention sur la confirmation (badge_shown / badge_clicked), page
     * sans app React : petit script en ligne, seulement si les mesures navigateur sont
     * possibles, et qui vérifie le consentement du visiteur avant chaque mesure. Envoi à
     * la route REST du site, jamais à un tiers.
     */
    private static function print_confirmation_stats_script() : void {
        if ( !Stats_Settings::browser_possible() ) {
            return;
        }
        $config = array(
            'url'           => add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), rest_url( Stats_Rest::NAMESPACE . Stats_Rest::ROUTE ) ),
            'withoutBanner' => Stats_Settings::get()['browser_without_banner'],
        );
        ?>
		<script>
		window.addEventListener('load', function () {
			var c = <?php 
        echo wp_json_encode( $config );
        ?>, el = document.getElementById('chic-branding-confirmation');
			function allowed() {
				return c.withoutBanner || (typeof window.wp_has_consent === 'function' && (window.wp_has_consent('statistics-anonymous') || window.wp_has_consent('statistics')));
			}
			function send(metric) {
				if (!el || !allowed() || !navigator.sendBeacon) { return; }
				navigator.sendBeacon(c.url, new Blob([JSON.stringify({ app: 'thankyou', browser: true, counters: [[metric, 'place', 'confirmation', 1]], histograms: [] })], { type: 'application/json' }));
			}
			send('badge_shown');
			if (el) { el.querySelector('a').addEventListener('click', function () { send('badge_clicked'); }); }
		});
		</script>
		<?php 
    }

    public function register() : void {
        register_setting( Settings::PAGE_SLUG, self::OPTION_KEY, array($this, 'sanitize') );
        add_settings_field(
            'branding_show',
            __( 'Mention Chic-checkout', 'chic-checkout' ),
            array($this, 'render_show_field'),
            Settings::PAGE_SLUG . '_thankyou',
            'chic_tab_thankyou'
        );
    }

    /** @param mixed $value */
    public function sanitize( $value ) : array {
        $value = ( is_array( $value ) ? $value : array() );
        return array(
            'show' => !empty( $value['show'] ),
        );
    }

    public function render_show_field() : void {
        $settings = self::get();
        // Champ caché : décocher la case doit bien enregistrer « non ».
        ?>
		<input type="hidden" name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[show]" value="0" />
		<label>
			<input type="checkbox" name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[show]" value="1" <?php 
        checked( $settings['show'] );
        ?> />
			<?php 
        esc_html_e( 'Afficher la mention « Checkout par Chic-checkout »', 'chic-checkout' );
        ?>
		</label>
		<p class="description"><?php 
        esc_html_e( 'Facultative. Un petit lien vers Chic-checkout sur la page de confirmation et sous le bouton de paiement, pour faire connaître l’extension. Jamais dans les e-mails ni à côté des moyens de paiement.', 'chic-checkout' );
        ?></p>
		<?php 
    }

}
