<?php

/**
 * Plugin Name:       Chic-checkout
 * Plugin URI:        https://pauladrien.fr/chic-checkout/
 * Description:       Remplace le panier, le checkout et la page de confirmation WooCommerce par un parcours en 3 étapes (Identification, Livraison, Paiement), basé sur la Store API.
 * Version:           1.12.1
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * Author:            Paul-Adrien
 * Author URI:        https://pauladrien.fr/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 6.4
 * WC tested up to:   11.2.1
 * Text Domain:       chic-checkout
 * Domain Path:       /languages
 *
 *
 * @package ChicCheckout
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/*
 * Une seule base de code, deux éditions : Freemius génère la version gratuite
 * (WordPress.org) en retirant le code Pro (dossier includes/pro/, bundle build-pro/,
 * fichiers, méthodes et blocs réservés à la version Pro). Le dossier includes/pro/
 * n'existe donc que dans la version Pro.
 */
$chic_checkout_has_pro = is_dir( __DIR__ . '/includes/pro' );
// Deux copies chargées. Version gratuite + version Pro (passage à la Pro) : on laisse le
// SDK Freemius prendre la main, il désactive la version gratuite quand la Pro est
// activée et les réglages (mêmes options) sont repris tels quels. Deux copies de la même
// édition (ex. dossier chic-checkout-premium à côté d'un ancien chic-checkout) : la
// seconde s'efface avec une notice plutôt que de provoquer une erreur fatale. Les
// fonctions de ce fichier sont toutes déclarées sous condition : une fonction déclarée
// au premier niveau existe dès la compilation, avant ce return.
if ( defined( 'CHIC_CHECKOUT_VERSION' ) ) {
    if ( function_exists( 'chic_checkout_fs' ) && defined( 'CHIC_CHECKOUT_HAS_PRO' ) && CHIC_CHECKOUT_HAS_PRO !== $chic_checkout_has_pro ) {
        chic_checkout_fs()->set_basename( $chic_checkout_has_pro, __FILE__ );
        return;
    }
    add_action( 'admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'Chic-checkout est installé deux fois (deux dossiers dans wp-content/plugins). Désactivez puis supprimez la copie en double depuis la page Extensions.', 'chic-checkout' );
        echo '</p></div>';
    } );
    return;
}
define( 'CHIC_CHECKOUT_VERSION', '1.12.1' );
define( 'CHIC_CHECKOUT_FILE', __FILE__ );
define( 'CHIC_CHECKOUT_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHIC_CHECKOUT_URL', plugin_dir_url( __FILE__ ) );
define( 'CHIC_CHECKOUT_HAS_PRO', $chic_checkout_has_pro );
// Portail d'idées public (version gratuite, onglet Support). Vide : seul le forum
// WordPress.org est proposé.
if ( !defined( 'CHIC_CHECKOUT_FEEDBACK_URL' ) ) {
    define( 'CHIC_CHECKOUT_FEEDBACK_URL', '' );
}
if ( !function_exists( 'chic_checkout_fs' ) ) {
    /**
     * Accès unique au SDK Freemius : compte, partage de données (opt-in), page de
     * tarifs et, dans la version Pro seulement, licence et mises à jour. Le code qui
     * dépend de la licence n'existe que dans la version Pro (Edition, License).
     */
    function chic_checkout_fs() {
        global $chic_checkout_fs;
        if ( !isset( $chic_checkout_fs ) ) {
            // SDK dans vendor/ (emplacement recommandé par Freemius) : les contrôles automatiques
            // de WordPress.org le traitent comme une dépendance tierce.
            require_once __DIR__ . '/vendor/freemius/start.php';
            $chic_checkout_fs = fs_dynamic_init( array(
                'id'               => '42241',
                'slug'             => 'chic-checkout',
                'premium_slug'     => 'chic-checkout-premium',
                'type'             => 'plugin',
                'public_key'       => 'pk_92eff77f1809e658fc00a4e5e746d',
                'is_premium'       => false,
                'premium_suffix'   => 'Pro',
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'is_org_compliant' => true,
                'menu'             => array(
                    'slug'           => 'chic-checkout-settings',
                    'support'        => false,
                    'contact'        => false,
                    'override_exact' => true,
                ),
                'is_live'          => true,
            ) );
        }
        return $chic_checkout_fs;
    }

    chic_checkout_fs();
    // Rien au-delà du strict nécessaire (permissions « essentials » et « events ») :
    // diagnostic et liste des extensions décochés par défaut.
    chic_checkout_fs()->add_filter( 'permission_diagnostic_default', '__return_false' );
    chic_checkout_fs()->add_filter( 'permission_extensions_default', '__return_false' );
    chic_checkout_fs()->add_filter( 'show_deactivation_feedback_form', '__return_false' );
    // Notices du SDK (proposition d'opt-in, mises à niveau…) : seulement sur les écrans
    // Chic-checkout, jamais sur le reste de l'administration (règle 11 de WordPress.org).
    chic_checkout_fs()->add_filter( 'show_admin_notice', static function ( $show ) {
        $page = ( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return $show && 0 === strpos( $page, 'chic-checkout' );
    }, 5 );
    // Pas d'installation automatique de la version Pro depuis la version gratuite (règle 8
    // de WordPress.org) : après l'achat, le marchand télécharge le zip Pro et l'installe
    // lui-même (guide pas à pas dans l'onglet Support). Le SDK propose cette installation
    // quand la page Compte reçoit auto_install : le paramètre est retiré pour nos pages.
    if ( is_admin() && isset( $_REQUEST['auto_install'], $_GET['page'] ) && 0 === strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'chic-checkout' ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification
        unset($_GET['auto_install'], $_POST['auto_install'], $_REQUEST['auto_install']);
    }
    // Remplace uninstall.php (recommandation Freemius : le SDK doit pouvoir signaler la
    // désinstallation, ce qu'un uninstall.php l'empêcherait de faire).
    chic_checkout_fs()->add_action( 'after_uninstall', 'chic_checkout_uninstall' );
    do_action( 'chic_checkout_fs_loaded' );
}
require_once CHIC_CHECKOUT_DIR . 'includes/class-plugin.php';
/**
 * Boot the plugin once WooCommerce is loaded.
 */
if ( !function_exists( 'chic_checkout_bootstrap' ) ) {
    function chic_checkout_bootstrap() {
        if ( !class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                esc_html_e( 'Chic-checkout requiert WooCommerce pour fonctionner.', 'chic-checkout' );
                echo '</p></div>';
            } );
            return;
        }
        \ChicCheckout\Plugin::instance();
    }

}
add_action( 'plugins_loaded', 'chic_checkout_bootstrap' );
/**
 * Compatibilité HPOS : le plugin n'accède aux commandes que par l'API CRUD de
 * WooCommerce (wc_get_order(), wc_get_orders(), get_meta()/update_meta_data()).
 */
add_action( 'before_woocommerce_init', static function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CHIC_CHECKOUT_FILE, true );
    }
} );
/**
 * Réglages initiaux (intégrations désactivées) uniquement sur une installation neuve :
 * un site qui a déjà des réglages n'est pas touché (voir Integrations::activate()).
 */
if ( !function_exists( 'chic_checkout_activate' ) ) {
    function chic_checkout_activate() {
        require_once CHIC_CHECKOUT_DIR . 'includes/class-edition.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-settings.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-integrations.php';
        \ChicCheckout\Integrations::activate();
    }

}
register_activation_hook( __FILE__, 'chic_checkout_activate' );
/**
 * Nettoyage à la désinstallation (hook after_uninstall de Freemius). Les classes du
 * plugin ne sont pas chargées à ce moment-là : noms d'options et de tables en dur, y
 * compris ceux de la version Pro (un site repassé en gratuit les a encore). Les
 * métadonnées de commande (message cadeau) sont conservées : elles font partie de
 * l'historique des commandes.
 */
if ( !function_exists( 'chic_checkout_uninstall' ) ) {
    function chic_checkout_uninstall() {
        global $wpdb;
        // Autre copie de Chic-checkout encore installée (version gratuite et version Pro
        // côte à côte, ou ancien dossier) : elles partagent les mêmes réglages, rien n'est
        // supprimé. Seule la suppression de la dernière copie nettoie le site.
        if ( !function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $copies = array_filter( get_plugins(), static fn( $plugin ) => 'chic-checkout' === ($plugin['TextDomain'] ?? '') );
        if ( count( $copies ) > 1 ) {
            return;
        }
        delete_option( 'chic_checkout_settings' );
        delete_option( 'chic_checkout_db_version' );
        delete_option( 'chic_checkout_license' );
        delete_option( 'chic_checkout_volume_state' );
        delete_option( 'chic_checkout_support_access' );
        delete_transient( 'chic_checkout_bugs' );
        delete_transient( 'chic_checkout_paid_orders_30d' );
        delete_metadata(
            'user',
            0,
            'chic_checkout_volume_notice_dismissed',
            '',
            true
        );
        // Statistiques : demande de suppression des données envoyées (meilleur effort,
        // 5 s max), puis tables et identité du site supprimées.
        foreach ( array(
            'contract',
            'store',
            'client',
            'settings',
            'sender'
        ) as $file ) {
            require_once __DIR__ . '/includes/stats/class-stats-' . $file . '.php';
        }
        if ( \ChicCheckout\Stats_Client::has_identity() ) {
            \ChicCheckout\Stats_Client::request( 'DELETE', \ChicCheckout\Stats_Client::endpoints()['site'] );
        }
        \ChicCheckout\Stats_Store::drop();
        \ChicCheckout\Stats_Sender::unschedule();
        foreach ( array(
            'chic_checkout_stats',
            'chic_checkout_stats_identity',
            'chic_checkout_stats_state',
            'chic_checkout_stats_slot',
            'chic_checkout_stats_pending_delete',
            'chic_checkout_stats_prompt',
            'chic_checkout_branding'
        ) as $option ) {
            delete_option( $option );
        }
        delete_transient( 'chic_checkout_stats_benchmark' );
        // Avis client (version Pro) : table propre au plugin, supprimée avec lui.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'chic_reviews' );
        delete_option( 'chic_checkout_reviews_db_version' );
        delete_transient( 'chic_checkout_reviews_keywords' );
    }

}