<?php

/**
 * Intégrations tierces hors paiement (les moyens de paiement sont détectés
 * automatiquement par Payment_Support).
 *
 * Une intégration n'est active que si son plugin est détecté ET que son interrupteur
 * est activé dans l'onglet Intégrations : Integrations::is_enabled( 'boxtal' ) est le
 * seul test à utiliser, côté PHP comme côté React (config `integrations`).
 *
 * Nouvelle installation : tout est désactivé. Installation existante (réglages déjà en
 * base avant la 0.3.0) : la migration active chaque intégration détectée ou déjà
 * configurée, pour que rien ne change chez les marchands en place.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Integrations {
    const DB_VERSION_OPTION = 'chic_checkout_db_version';

    const DB_VERSION = '0.3.0';

    /**
     * @return array<string, array{label: string, description: string, plugin: string, slug?: string}>
     *         plugin : nom du plugin tiers requis, '' si aucun ; slug : son slug
     *         WordPress.org (lien d'installation, voir Plugin_Links).
     */
    public static function definitions() : array {
        $mycred_description = __( 'Paiement en points et solde de points du client sur le panier.', 'chic-checkout' );
        $definitions = array(
            'boxtal'        => array(
                'label'       => __( 'Boxtal Connect', 'chic-checkout' ),
                'description' => __( "Carte de choix du point relais Boxtal à l'étape Livraison. Désactivée : la carte n'est pas affichée, pensez à désactiver aussi les modes de livraison en point relais Boxtal dans WooCommerce.", 'chic-checkout' ),
                'plugin'      => 'Boxtal Connect',
                'slug'        => 'boxtal-connect',
            ),
            'laposte'       => array(
                'label'       => __( 'La Poste Pro Expéditions', 'chic-checkout' ),
                'description' => __( "Carte de choix du point relais La Poste à l'étape Livraison. Désactivée : la carte n'est pas affichée, pensez à désactiver aussi les modes de livraison en point relais La Poste dans WooCommerce.", 'chic-checkout' ),
                'plugin'      => 'La Poste Pro Expéditions',
                'slug'        => 'la-poste-pro-expeditions-woocommerce',
            ),
            'mycred'        => array(
                'label'       => __( 'myCred', 'chic-checkout' ),
                'description' => $mycred_description,
                'plugin'      => 'myCred',
                'slug'        => 'mycred',
            ),
            'bookacti'      => array(
                'label'       => __( 'Booking Activities', 'chic-checkout' ),
                'description' => __( 'Compte à rebours avant expiration des réservations dans le panier.', 'chic-checkout' ),
                'plugin'      => 'Booking Activities',
                'slug'        => 'booking-activities',
            ),
            'google_places' => array(
                'label'       => __( 'Google Places', 'chic-checkout' ),
                'description' => __( "Autocomplétion d'adresse à l'étape Livraison (formulaire progressif). Clé API à renseigner dans l'onglet Livraison.", 'chic-checkout' ),
                'plugin'      => '',
            ),
            'ga4'           => array(
                'label'       => __( 'Google Analytics 4', 'chic-checkout' ),
                'description' => __( "Événements du tunnel de commande envoyés à GA4 / Google Tag Manager. Options dans l'onglet Tracking.", 'chic-checkout' ),
                'plugin'      => '',
            ),
        );
        return $definitions;
    }

    /**
     * Liste figée plutôt que array_keys( definitions() ) : definitions() traduit ses
     * libellés, ce qui ne doit pas se produire avant `init` (migration, réglages par
     * défaut sont lus dès plugins_loaded).
     */
    public static function keys() : array {
        $keys = array(
            'boxtal',
            'laposte',
            'mycred',
            'bookacti',
            'google_places',
            'ga4'
        );
        return $keys;
    }

    /**
     * Le plugin tiers requis est-il chargé ? Toujours vrai pour une intégration qui
     * n'en demande pas (connexion sociale, Google Places, GA4).
     */
    public static function is_detected( string $key ) : bool {
        switch ( $key ) {
            case 'boxtal':
                return class_exists( '\\Boxtal\\BoxtalConnectWoocommerce\\Util\\Frontend_Util' );
            case 'laposte':
                return class_exists( '\\LaPoste\\LaPosteProExpeditionsWoocommerce\\Util\\Frontend_Util' );
            // Constantes posées dès le chargement du plugin ; ses fonctions ne sont
            // incluses que plus tard (myCred : plugins_loaded à priorité 999), après
            // notre migration.
            case 'mycred':
                return defined( 'myCRED_VERSION' ) || function_exists( 'mycred' );
            case 'bookacti':
                return defined( 'BOOKACTI_VERSION' ) || function_exists( 'bookacti_get_message' );
            default:
                return in_array( $key, self::keys(), true );
        }
    }

    public static function is_switched_on( string $key ) : bool {
        $integrations = Settings::raw( 'integrations', array() );
        return !empty( $integrations[$key] );
    }

    public static function is_enabled( string $key ) : bool {
        return self::is_detected( $key ) && self::is_switched_on( $key );
    }

    /**
     * Exposé à React (window.chicCheckout.integrations).
     *
     * @return array<string, bool>
     */
    public static function enabled_map() : array {
        $map = array();
        foreach ( self::keys() as $key ) {
            $map[$key] = self::is_enabled( $key );
        }
        return $map;
    }

    /**
     * Activation : réglages initiaux (tout désactivé) uniquement si aucun réglage
     * n'existe encore, et dans ce seul cas version de schéma à jour. Un site existant
     * réactivé (réglages sans clé `integrations`) doit passer par maybe_migrate(), pas
     * être marqué comme déjà migré.
     */
    public static function activate() : void {
        if ( false !== get_option( Settings::OPTION_KEY, false ) ) {
            return;
        }
        add_option( Settings::OPTION_KEY, array(
            'integrations' => array_fill_keys( self::keys(), false ),
        ) );
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Exécutée une seule fois par installation (plugins_loaded). Réglages présents sans
     * clé `integrations` = installation antérieure à la 0.3.0 : on reproduit le
     * comportement d'avant, où chaque intégration s'activait dès que son plugin était là
     * ou qu'elle était configurée.
     */
    public static function maybe_migrate() : void {
        $db_version = get_option( self::DB_VERSION_OPTION, '' );
        if ( $db_version && version_compare( $db_version, self::DB_VERSION, '>=' ) ) {
            return;
        }
        $stored = get_option( Settings::OPTION_KEY, false );
        if ( is_array( $stored ) && !isset( $stored['integrations'] ) ) {
            $stored['integrations'] = array(
                'boxtal'        => self::is_detected( 'boxtal' ),
                'laposte'       => self::is_detected( 'laposte' ),
                'mycred'        => self::is_detected( 'mycred' ),
                'bookacti'      => self::is_detected( 'bookacti' ),
                'google_places' => true,
                'ga4'           => (bool) ($stored['tracking_ga4_enabled'] ?? false),
            );
            update_option( Settings::OPTION_KEY, $stored );
        }
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

}
