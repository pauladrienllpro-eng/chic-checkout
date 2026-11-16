<?php

/**
 * Bootstrap principal du plugin.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Plugin {
    private static ?Plugin $instance = null;

    public static function instance() : Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        require_once CHIC_CHECKOUT_DIR . 'includes/class-edition.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-compat.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-pro-upsell.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-settings.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-integrations.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-polylang-strings.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-seo.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-checkout-override.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-assets.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-thankyou-override.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-thankyou-assets.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-cart-override.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-cart-assets.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-mycred-helper.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-mycred-balance-api.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-rate-limiter.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-auth-api.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-shipping-lead-time.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-payment-support.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-payment-messages.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-plugin-links.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-releases.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-relay-shipping-notice.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-payment-methods-api.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-privacy.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/class-branding.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-contract.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-store.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-client.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-settings.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-server.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-rest.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-sender.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-performance.php';
        require_once CHIC_CHECKOUT_DIR . 'includes/stats/class-stats-onboarding.php';
        Integrations::maybe_migrate();
        new Privacy();
        new Compat();
        new Settings();
        new Polylang_Strings();
        new Seo();
        new Checkout_Override();
        new Assets();
        new Thankyou_Override();
        new Thankyou_Assets();
        new Cart_Override();
        new Cart_Assets();
        new Mycred_Balance_Api();
        new Auth_Api();
        new Shipping_Lead_Time();
        new Payment_Support();
        new Payment_Messages();
        new Releases();
        new Relay_Shipping_Notice();
        new Payment_Methods_Api();
        // Statistiques (désactivées par défaut, voir Stats_Settings).
        new Stats_Settings();
        new Stats_Server();
        new Stats_Rest();
        new Stats_Sender();
        new Stats_Performance();
        new Stats_Onboarding();
        new Branding();
    }

}
