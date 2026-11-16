<?php

/**
 * Écran de réglages du plugin (wp-admin) : apparence, retour panier, contact.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Settings {
    const OPTION_KEY = 'chic_checkout_settings';

    const PAGE_SLUG = 'chic-checkout-settings';

    /**
     * Organisation de la page de réglages : menu latéral par groupes, et sous-onglets pour
     * les pages Panier et Checkout. Les champs restent enregistrés par page « historique »
     * (PAGE_SLUG . '_steps', '_cart'…, voir register_settings()) : seule leur place à
     * l'écran change (PANEL_FIELDS), aucun réglage n'est renommé.
     *
     * @return array<int, array{label: string, items: array<string, array{label: string, subs: array<string, string>}>}>
     */
    public static function layout() : array {
        $cart_subs = array(
            'general' => __( 'Général', 'chic-checkout' ),
            'upsell'  => __( 'Ventes additionnelles', 'chic-checkout' ),
        );
        $thankyou_subs = array(
            'general' => __( 'Général', 'chic-checkout' ),
        );
        $general = array(
            'appearance'  => array(
                'label' => __( 'Apparence', 'chic-checkout' ),
                'subs'  => array(),
            ),
            'reassurance' => array(
                'label' => __( 'Réassurance', 'chic-checkout' ),
                'subs'  => array(),
            ),
            'footer'      => array(
                'label' => __( 'Footer', 'chic-checkout' ),
                'subs'  => array(),
            ),
            'contact'     => array(
                'label' => __( 'Contact', 'chic-checkout' ),
                'subs'  => array(),
            ),
        );
        $system = array(
            'integrations' => array(
                'label' => __( 'Intégrations', 'chic-checkout' ),
                'subs'  => array(),
            ),
            'news'         => array(
                'label' => __( 'Nouveautés', 'chic-checkout' ),
                'subs'  => array(),
            ),
            'support'      => array(
                'label' => __( 'Support', 'chic-checkout' ),
                'subs'  => array(),
            ),
        );
        return array(
            array(
                'label' => __( 'Pages', 'chic-checkout' ),
                'items' => array(
                    'cart'     => array(
                        'label' => __( 'Panier', 'chic-checkout' ),
                        'subs'  => $cart_subs,
                    ),
                    'checkout' => array(
                        'label' => __( 'Checkout', 'chic-checkout' ),
                        'subs'  => array(
                            'general'  => __( 'Général', 'chic-checkout' ),
                            'login'    => __( 'Identification', 'chic-checkout' ),
                            'shipping' => __( 'Livraison', 'chic-checkout' ),
                            'payment'  => __( 'Paiement', 'chic-checkout' ),
                        ),
                    ),
                    'thankyou' => array(
                        'label' => __( 'Page de confirmation', 'chic-checkout' ),
                        'subs'  => $thankyou_subs,
                    ),
                ),
            ),
            array(
                'label' => __( 'Général', 'chic-checkout' ),
                'items' => $general,
            ),
            array(
                'label' => __( 'Données & SEO', 'chic-checkout' ),
                'items' => array(
                    'seo'      => array(
                        'label' => __( 'SEO', 'chic-checkout' ),
                        'subs'  => array(),
                    ),
                    'tracking' => array(
                        'label' => __( 'Tracking', 'chic-checkout' ),
                        'subs'  => array(),
                    ),
                    'stats'    => array(
                        'label' => __( 'Statistiques', 'chic-checkout' ),
                        'subs'  => array(),
                    ),
                ),
            ),
            array(
                'label' => __( 'Système', 'chic-checkout' ),
                'items' => $system,
            )
        );
    }

    /**
     * Champs de chaque panneau (« page » ou « page-sousonglet »), dans l'ordre d'affichage.
     * Un panneau absent d'ici affiche tels quels les champs de la page historique du même
     * nom (reassurance, footer, contact, texts, seo, tracking, stats, integrations, advanced, news).
     */
    const PANEL_FIELDS = array(
        'cart-general'      => array(
            'cart_promo_banner',
            'cart_back',
            'empty_cart_text',
            'cart_image_size',
            'cart_promo_code_enabled',
            'cart_express_applepay',
            'cart_payment_icons',
            'cart_blocks_order'
        ),
        'cart-gifts'        => array('cart_packaging', 'cart_gift_message', 'cart_gift_carousel'),
        'cart-upsell'       => array('cart_upsell', 'cart_free_shipping', 'cart_loyalty'),
        'checkout-general'  => array(
            'show_stepper',
            'step_numbering',
            'step_name_1',
            'step_name_2',
            'step_name_3',
            'order_summary_label',
            'back_label',
            'back_target'
        ),
        'checkout-login'    => array(
            'login_intro_title',
            'login_intro_text',
            'social_logins',
            'cta_login_email_label',
            'cta_login_password_label',
            'cta_login_guest_label',
            'cta_login_register_label'
        ),
        'checkout-shipping' => array(
            'shipping_pickup_points_info',
            'progressive_shipping_form',
            'google_places_api_key',
            'free_shipping_label',
            'shipping_lead_times',
            'shipping_info_banner',
            'cta_shipping_address_label',
            'cta_shipping_method_label'
        ),
        'checkout-payment'  => array(
            'cta_billing_label',
            'cta_payment_label',
            'show_terms_checkbox',
            'terms_checkbox_label',
            'payment_logo_mode',
            'payment_logos'
        ),
        'thankyou-general'  => array(
            'thankyou_intro_text',
            'thankyou_show_delivery_estimate',
            'thankyou_back_label',
            'thankyou_back_target'
        ),
        'thankyou-reviews'  => array(
            'review_enabled',
            'review_delay',
            'review_fields',
            'review_title'
        ),
        'appearance'        => array(
            'logo_id',
            'logo_text',
            'style_mode',
            'bg_color',
            'accent_color',
            'cta_color',
            'branding_show'
        ),
    );

    /**
     * Réglages des fonctions Pro et leur valeur dans l'édition gratuite (fonction
     * désactivée, ou comportement par défaut). Simple liste de clés : aucune logique Pro.
     */
    const FREE_VALUES = array(
        'style_mode'                     => 'theme',
        'bg_color'                       => '#ffffff',
        'accent_color'                   => '#1c1917',
        'cta_color'                      => '#44403c',
        'cart_image_size'                => 'lg',
        'payment_logo_mode'              => 'beside',
        'payment_logos'                  => array(),
        'shipping_info_banner_text'      => '',
        'social_google_enabled'          => false,
        'social_apple_enabled'           => false,
        'contact_icon_id'                => 0,
        'field_patterns'                 => array(),
        'text_overrides'                 => array(),
        'review_enabled'                 => false,
        'cart_promo_banner_enabled'      => false,
        'cart_packaging_enabled'         => false,
        'cart_gift_message_enabled'      => false,
        'cart_gift_carousel_enabled'     => false,
        'cart_free_shipping_enabled'     => false,
        'cart_loyalty_enabled'           => false,
        'checkout_loyalty_enabled'       => false,
        'cart_express_login_required'    => false,
        'cart_express_applepay_enabled'  => false,
        'cart_express_googlepay_enabled' => false,
        'cart_express_paypal_enabled'    => false,
        'cart_upsell_count'              => 4,
    );

    /** Toutes les clés de réglages propres aux fonctions Pro (conservées par la version gratuite). */
    const PRO_KEYS = array(
        'style_mode',
        'bg_color',
        'accent_color',
        'cta_color',
        'payment_logo_mode',
        'payment_logos',
        'shipping_info_banner_text',
        'shipping_info_banner_link_url',
        'social_google_enabled',
        'social_google_client_id',
        'social_apple_enabled',
        'social_apple_services_id',
        'contact_icon_id',
        'field_patterns',
        'review_enabled',
        'review_delay',
        'review_rating_enabled',
        'review_comment_enabled',
        'review_comment_required',
        'review_title',
        'cart_promo_banner_enabled',
        'cart_promo_banner_text',
        'cart_promo_banner_link_url',
        'cart_promo_banner_ends_at',
        'cart_promo_banner_bg_color',
        'cart_promo_banner_position',
        'cart_promo_banner_sticky',
        'cart_promo_banner_countdown',
        'cart_image_size',
        'cart_packaging_enabled',
        'cart_packaging_product_ids',
        'cart_packaging_rules',
        'cart_packaging_auto_select',
        'cart_gift_message_enabled',
        'cart_gift_message_max_length',
        'cart_gift_message_allow_empty',
        'cart_gift_message_image_id',
        'cart_gift_carousel_enabled',
        'cart_gift_carousel_product_ids',
        'cart_gift_carousel_threshold',
        'cart_gift_carousel_title',
        'cart_gift_carousel_text',
        'cart_free_shipping_enabled',
        'cart_free_shipping_icon_id',
        'cart_free_shipping_threshold',
        'cart_free_shipping_text',
        'cart_free_shipping_text_unlocked',
        'cart_loyalty_enabled',
        'checkout_loyalty_enabled',
        'cart_loyalty_text',
        'cart_express_login_required',
        'cart_express_applepay_enabled',
        'cart_express_googlepay_enabled',
        'cart_express_paypal_enabled',
        'text_overrides',
        'cart_blocks_desktop',
        'cart_blocks_mobile',
        'cart_upsell_count',
        'cart_upsell_cheaper_enabled',
        'cart_upsell_related_enabled',
        'cart_upsell_forced_tag_ids',
        'cart_upsell_excluded_tag_ids',
        'cart_upsell_excluded_category_ids'
    );

    /** Anciens noms d'onglet (liens ?chic-tab=…) : page et sous-onglet actuels. */
    const LEGACY_TABS = array(
        'steps'    => array('checkout', 'general'),
        'back'     => array('checkout', 'general'),
        'login'    => array('checkout', 'login'),
        'shipping' => array('checkout', 'shipping'),
    );

    /** Blocs du panier réordonnables (onglet Panier → Ordre des blocs). */
    const CART_BLOCKS = array(
        'packaging',
        'gift_message',
        'gift_carousel',
        'upsell'
    );

    /** Ordre par défaut : desktop (colonne principale) et mobile (« summary » = résumé et bouton « Aller au paiement »). */
    const CART_BLOCKS_DESKTOP_DEFAULT = array(
        'packaging',
        'gift_message',
        'gift_carousel',
        'upsell'
    );

    const CART_BLOCKS_MOBILE_DEFAULT = array(
        'packaging',
        'gift_message',
        'gift_carousel',
        'summary',
        'upsell'
    );

    /** Nombre maximum de liens configurables dans l'onglet Footer. */
    const FOOTER_LINKS_MAX = 5;

    /** Nombre maximum de blocs configurables dans l'onglet Réassurance. */
    const REASSURANCE_ITEMS_MAX = 4;

    /** Nombre maximum de produits sélectionnables (emballages / cadeaux du carrousel). */
    const CART_PRODUCTS_MAX = 6;

    /** Au-delà, le texte casse la mise en page à 2-3 lignes visée par le design. */
    const REASSURANCE_TITLE_MAX_LENGTH = 40;

    const REASSURANCE_TEXT_MAX_LENGTH = 140;

    /**
     * Icônes prédéfinies (tracés originaux, même style que le reste du plugin — pas de
     * reprise d'assets tiers) proposées en plus de l'upload d'une image personnalisée.
     */
    public static function reassurance_icon_presets() : array {
        return array(
            'card'     => __( 'Paiement (carte)', 'chic-checkout' ),
            'shield'   => __( 'Sécurité', 'chic-checkout' ),
            'delivery' => __( 'Livraison', 'chic-checkout' ),
            'return'   => __( 'Retours', 'chic-checkout' ),
            'support'  => __( 'Service client', 'chic-checkout' ),
            'gift'     => __( 'Cadeau', 'chic-checkout' ),
        );
    }

    /** Jours ISO-8601 (1 = lundi ... 7 = dimanche), même convention que date('N'). */
    public static function weekdays() : array {
        return array(
            1 => __( 'Lundi', 'chic-checkout' ),
            2 => __( 'Mardi', 'chic-checkout' ),
            3 => __( 'Mercredi', 'chic-checkout' ),
            4 => __( 'Jeudi', 'chic-checkout' ),
            5 => __( 'Vendredi', 'chic-checkout' ),
            6 => __( 'Samedi', 'chic-checkout' ),
            7 => __( 'Dimanche', 'chic-checkout' ),
        );
    }

    public function __construct() {
        add_action( 'admin_menu', array($this, 'register_page') );
        add_action( 'admin_init', array($this, 'register_settings') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_assets') );
    }

    /**
     * Lecture brute d'un réglage, sans les valeurs par défaut (traduites, donc à ne pas
     * calculer avant `init`) : pour les tests faits très tôt, comme l'enregistrement des
     * données Store API sur woocommerce_blocks_loaded.
     *
     * @param mixed $fallback Valeur si le réglage n'a jamais été enregistré.
     * @return mixed
     */
    public static function raw( string $key, $fallback = null ) {
        $stored = get_option( self::OPTION_KEY, array() );
        return ( is_array( $stored ) && array_key_exists( $key, $stored ) ? $stored[$key] : $fallback );
    }

    /**
     * Réglages appliqués à la boutique. Sans fonctions Pro (version gratuite, ou version
     * Pro sans licence valide), les réglages Pro prennent leur valeur de l'édition
     * gratuite : les fonctions Pro sont inactives, mais leurs réglages restent enregistrés
     * (stored()) et reviennent tels quels avec une licence valide.
     */
    public static function get() : array {
        $settings = self::stored();
        if ( Edition::is_pro() ) {
            return $settings;
        }
        $free = self::FREE_VALUES;
        $free['cart_blocks_desktop'] = self::CART_BLOCKS_DESKTOP_DEFAULT;
        $free['cart_blocks_mobile'] = self::CART_BLOCKS_MOBILE_DEFAULT;
        return array_merge( $settings, $free );
    }

    /**
     * Réglages enregistrés (valeurs par défaut comprises), tels qu'affichés à l'écran de
     * réglages, quelle que soit l'édition.
     */
    public static function stored() : array {
        return wp_parse_args( get_option( self::OPTION_KEY, array() ), array(
            'logo_id'                           => 0,
            'logo_text'                         => '',
            'order_summary_label'               => __( 'Récapitulatif de commande', 'chic-checkout' ),
            'style_mode'                        => 'custom',
            'bg_color'                          => '#ffffff',
            'accent_color'                      => '#1c1917',
            'cta_color'                         => '#44403c',
            'show_stepper'                      => true,
            'step_name_1'                       => __( 'Identification', 'chic-checkout' ),
            'step_name_2'                       => __( 'Livraison', 'chic-checkout' ),
            'step_name_3'                       => __( 'Paiement', 'chic-checkout' ),
            'step_numbering'                    => false,
            'cta_billing_label'                 => __( 'Continuer', 'chic-checkout' ),
            'cta_payment_label'                 => __( 'Payer', 'chic-checkout' ),
            'show_terms_checkbox'               => true,
            'payment_logo_mode'                 => 'beside',
            'payment_logos'                     => array(),
            'terms_checkbox_label'              => __( "J'ai lu et j'accepte les [conditions générales de vente] du site.", 'chic-checkout' ),
            'progressive_shipping_form'         => true,
            'google_places_api_key'             => '',
            'free_shipping_label'               => __( 'Offerte', 'chic-checkout' ),
            'cta_shipping_address_label'        => __( 'Continuer', 'chic-checkout' ),
            'cta_shipping_method_label'         => __( 'Continuer', 'chic-checkout' ),
            'shipping_lead_times'               => array(),
            'shipping_info_banner_text'         => '',
            'shipping_info_banner_link_url'     => '',
            'back_label'                        => __( 'Panier', 'chic-checkout' ),
            'back_target'                       => 'default',
            'back_custom_url'                   => '',
            'login_intro_title'                 => '',
            'login_intro_text'                  => '',
            'social_google_enabled'             => true,
            'social_google_client_id'           => '',
            'social_apple_enabled'              => true,
            'social_apple_services_id'          => '',
            'cta_login_email_label'             => __( 'Continuer', 'chic-checkout' ),
            'cta_login_password_label'          => __( 'Se connecter', 'chic-checkout' ),
            'cta_login_guest_label'             => __( 'Continuer en invité', 'chic-checkout' ),
            'cta_login_register_label'          => __( 'Créer mon compte', 'chic-checkout' ),
            'contact_style'                     => 'icon',
            'contact_label'                     => __( 'Contact', 'chic-checkout' ),
            'contact_icon_id'                   => 0,
            'contact_popup_title'               => __( 'Nous contacter', 'chic-checkout' ),
            'contact_popup_text'                => '',
            'contact_page_enabled'              => false,
            'contact_page_url'                  => '',
            'contact_email_enabled'             => false,
            'contact_email_address'             => '',
            'contact_whatsapp_enabled'          => false,
            'contact_whatsapp_number'           => '',
            'contact_phone_enabled'             => false,
            'contact_phone_number'              => '',
            'contact_phone_days'                => array(),
            'contact_phone_start'               => '',
            'contact_phone_end'                 => '',
            'field_patterns'                    => array(),
            'footer_links'                      => array(),
            'footer_contrast_toggle_enabled'    => true,
            'footer_contrast_toggle_label'      => __( 'Contraste élevé', 'chic-checkout' ),
            'reassurance_items'                 => array(),
            'reassurance_mobile_enabled'        => false,
            'thankyou_intro_text'               => __( 'Merci pour votre commande, {prenom} !', 'chic-checkout' ),
            'thankyou_show_delivery_estimate'   => true,
            'thankyou_back_label'               => __( 'Continuer mes achats', 'chic-checkout' ),
            'thankyou_back_target'              => 'default',
            'thankyou_back_custom_url'          => '',
            'review_enabled'                    => false,
            'review_delay'                      => 2,
            'review_rating_enabled'             => true,
            'review_comment_enabled'            => true,
            'review_comment_required'           => false,
            'review_title'                      => __( 'Comment s’est passée votre commande ?', 'chic-checkout' ),
            'cart_promo_banner_enabled'         => false,
            'cart_promo_banner_text'            => '',
            'cart_promo_banner_link_url'        => '',
            'cart_promo_banner_ends_at'         => '',
            'cart_promo_banner_bg_color'        => '#e7e5e4',
            'cart_promo_banner_position'        => 'inline',
            'cart_promo_banner_sticky'          => false,
            'cart_promo_banner_countdown'       => 'days',
            'cart_image_size'                   => 'md',
            'cart_packaging_enabled'            => false,
            'cart_packaging_product_ids'        => array(),
            'cart_packaging_rules'              => array(),
            'cart_packaging_auto_select'        => false,
            'cart_gift_message_enabled'         => false,
            'cart_gift_message_max_length'      => 200,
            'cart_gift_message_allow_empty'     => true,
            'cart_gift_message_image_id'        => 0,
            'cart_gift_carousel_enabled'        => false,
            'cart_gift_carousel_product_ids'    => array(),
            'cart_gift_carousel_threshold'      => 0,
            'cart_gift_carousel_title'          => __( 'Un petit cadeau avec votre commande', 'chic-checkout' ),
            'cart_gift_carousel_text'           => __( 'Choisissez un échantillon offert à glisser dans votre colis.', 'chic-checkout' ),
            'cart_back_target'                  => 'shop',
            'cart_back_custom_url'              => '',
            'cart_back_label'                   => __( 'Continuer mes achats', 'chic-checkout' ),
            'empty_cart_text'                   => __( 'Votre panier est vide pour le moment.', 'chic-checkout' ),
            'cart_payment_icons_enabled'        => false,
            'cart_payment_icons_label'          => __( 'Paiement 100% sécurisé', 'chic-checkout' ),
            'cart_free_shipping_enabled'        => false,
            'cart_free_shipping_icon_id'        => 0,
            'cart_free_shipping_threshold'      => 0,
            'cart_free_shipping_text'           => __( 'Il vous reste {montant} à ajouter avant de débloquer la livraison gratuite', 'chic-checkout' ),
            'cart_free_shipping_text_unlocked'  => __( 'Livraison gratuite débloquée', 'chic-checkout' ),
            'cart_loyalty_enabled'              => false,
            'checkout_loyalty_enabled'          => false,
            'cart_loyalty_text'                 => __( 'Cet achat vous rapportera {points} points de fidélité', 'chic-checkout' ),
            'cart_promo_code_enabled'           => true,
            'cart_express_login_required'       => false,
            'cart_express_applepay_enabled'     => false,
            'cart_express_googlepay_enabled'    => false,
            'cart_express_paypal_enabled'       => false,
            'cart_upsell_enabled'               => false,
            'cart_upsell_title'                 => __( 'Vous aimerez aussi', 'chic-checkout' ),
            'cart_blocks_desktop'               => self::CART_BLOCKS_DESKTOP_DEFAULT,
            'text_overrides'                    => array(),
            'cart_blocks_mobile'                => self::CART_BLOCKS_MOBILE_DEFAULT,
            'cart_upsell_count'                 => 4,
            'cart_upsell_cheaper_enabled'       => true,
            'cart_upsell_related_enabled'       => true,
            'cart_upsell_forced_tag_ids'        => array(),
            'cart_upsell_excluded_tag_ids'      => array(),
            'cart_upsell_excluded_category_ids' => array(),
            'seo_noindex'                       => true,
            'seo_nofollow'                      => true,
            'seo_meta_title'                    => '',
            'seo_meta_description'              => '',
            'tracking_ga4_enabled'              => false,
            'tracking_debug_mode'               => false,
            'integrations'                      => array_fill_keys( Integrations::keys(), false ),
        ) );
    }

    public function register_page() : void {
        add_menu_page(
            __( 'Chic-checkout', 'chic-checkout' ),
            __( 'Chic-checkout', 'chic-checkout' ),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array($this, 'render_page'),
            'dashicons-cart',
            56
        );
    }

    public function enqueue_admin_assets( string $hook ) : void {
        if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        // Champ "Rechercher un produit…" de l'onglet Panier (.wc-product-search) : script
        // + styles déjà enregistrés par WooCommerce sur tout wp-admin (WC_Admin_Assets),
        // on ne fait que les enqueue ici, rien à écrire nous-mêmes.
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_style( 'woocommerce_admin_styles' );
        wp_enqueue_script(
            'chic-checkout-admin',
            CHIC_CHECKOUT_URL . 'includes/admin-settings.js',
            array('jquery', 'wp-color-picker'),
            // filemtime() plutôt que CHIC_CHECKOUT_VERSION (constant depuis plusieurs
            // sessions malgré de nombreuses modifs de ce fichier) : le cache-busting via
            // la version du plugin exige de penser à l'incrémenter à chaque modif de ce
            // fichier précis, ce qui n'a pas été fait — symptôme observé en direct
            // (bascule "Lien personnalisé" sans effet, JS resté en cache navigateur). Le
            // timestamp du fichier s'auto-invalide à chaque modification, sans y penser.
            (string) filemtime( CHIC_CHECKOUT_DIR . 'includes/admin-settings.js' ),
            true
        );
        $admin = array(
            'chooseMedia'     => __( 'Choisir un média', 'chic-checkout' ),
            'releasesSeenUrl' => ( Releases::has_unseen() ? Releases::seen_url() : '' ),
            'supportUrl'      => admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&chic-tab=support' ),
        );
        wp_localize_script( 'chic-checkout-admin', 'chicCheckoutAdmin', $admin );
    }

    public function register_settings() : void {
        register_setting( self::PAGE_SLUG, self::OPTION_KEY, array($this, 'sanitize') );
        // --- Onglet Apparence ---
        add_settings_section(
            'chic_tab_appearance',
            '',
            '__return_false',
            self::PAGE_SLUG . '_appearance'
        );
        add_settings_field(
            'logo_id',
            __( 'Logo', 'chic-checkout' ),
            array($this, 'render_logo_field'),
            self::PAGE_SLUG . '_appearance',
            'chic_tab_appearance'
        );
        add_settings_field(
            'logo_text',
            __( 'Texte alternatif', 'chic-checkout' ),
            array($this, 'render_logo_text_field'),
            self::PAGE_SLUG . '_appearance',
            'chic_tab_appearance'
        );
        add_settings_field(
            'order_summary_label',
            __( 'Texte du récapitulatif', 'chic-checkout' ),
            array($this, 'render_order_summary_label_field'),
            self::PAGE_SLUG . '_appearance',
            'chic_tab_appearance'
        );
        // --- Onglet Étapes ---
        add_settings_section(
            'chic_tab_steps',
            '',
            '__return_false',
            self::PAGE_SLUG . '_steps'
        );
        add_settings_field(
            'show_stepper',
            __( 'Stepper', 'chic-checkout' ),
            array($this, 'render_show_stepper_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps'
        );
        add_settings_field(
            'step_numbering',
            __( 'Numérotation', 'chic-checkout' ),
            array($this, 'render_step_numbering_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps'
        );
        add_settings_field(
            'step_name_1',
            __( 'Nom - Étape 1', 'chic-checkout' ),
            array($this, 'render_step_name_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps',
            array(
                'index'   => 1,
                'default' => __( 'Identification', 'chic-checkout' ),
            )
        );
        add_settings_field(
            'step_name_2',
            __( 'Nom - Étape 2', 'chic-checkout' ),
            array($this, 'render_step_name_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps',
            array(
                'index'   => 2,
                'default' => __( 'Livraison', 'chic-checkout' ),
            )
        );
        add_settings_field(
            'step_name_3',
            __( 'Nom - Étape 3', 'chic-checkout' ),
            array($this, 'render_step_name_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps',
            array(
                'index'   => 3,
                'default' => __( 'Paiement', 'chic-checkout' ),
            )
        );
        add_settings_field(
            'cta_billing_label',
            __( 'Texte bouton - Facturation', 'chic-checkout' ),
            array($this, 'render_cta_billing_label_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps'
        );
        add_settings_field(
            'cta_payment_label',
            __( 'Texte bouton - Paiement', 'chic-checkout' ),
            array($this, 'render_cta_payment_label_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps'
        );
        add_settings_field(
            'show_terms_checkbox',
            __( 'Case CGV', 'chic-checkout' ),
            array($this, 'render_show_terms_checkbox_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps'
        );
        add_settings_field(
            'terms_checkbox_label',
            __( 'Texte de la case CGV', 'chic-checkout' ),
            array($this, 'render_terms_checkbox_label_field'),
            self::PAGE_SLUG . '_steps',
            'chic_tab_steps'
        );
        // --- Onglet Livraison ---
        add_settings_section(
            'chic_tab_shipping',
            '',
            '__return_false',
            self::PAGE_SLUG . '_shipping'
        );
        add_settings_field(
            'shipping_pickup_points_info',
            __( 'Points relais', 'chic-checkout' ),
            array($this, 'render_shipping_pickup_points_info_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        add_settings_field(
            'progressive_shipping_form',
            __( 'Formulaire progressif', 'chic-checkout' ),
            array($this, 'render_progressive_shipping_form_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        add_settings_field(
            'google_places_api_key',
            __( 'Clé API Google Places', 'chic-checkout' ),
            array($this, 'render_google_places_api_key_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        add_settings_field(
            'free_shipping_label',
            __( 'Libellé livraison gratuite', 'chic-checkout' ),
            array($this, 'render_free_shipping_label_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        add_settings_field(
            'cta_shipping_address_label',
            __( 'Texte bouton - Adresse', 'chic-checkout' ),
            array($this, 'render_cta_shipping_address_label_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        add_settings_field(
            'cta_shipping_method_label',
            __( 'Texte bouton - Mode de livraison', 'chic-checkout' ),
            array($this, 'render_cta_shipping_method_label_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        add_settings_field(
            'shipping_lead_times',
            __( 'Délais de livraison', 'chic-checkout' ),
            array($this, 'render_shipping_lead_times_field'),
            self::PAGE_SLUG . '_shipping',
            'chic_tab_shipping'
        );
        // --- Onglet Retour panier ---
        add_settings_section(
            'chic_tab_back',
            '',
            '__return_false',
            self::PAGE_SLUG . '_back'
        );
        add_settings_field(
            'back_label',
            __( 'Texte du lien', 'chic-checkout' ),
            array($this, 'render_back_label_field'),
            self::PAGE_SLUG . '_back',
            'chic_tab_back'
        );
        add_settings_field(
            'back_target',
            __( 'Destination', 'chic-checkout' ),
            array($this, 'render_back_target_field'),
            self::PAGE_SLUG . '_back',
            'chic_tab_back'
        );
        // --- Onglet Connexion ---
        add_settings_section(
            'chic_tab_login',
            '',
            '__return_false',
            self::PAGE_SLUG . '_login'
        );
        add_settings_field(
            'login_intro_title',
            __( 'Titre d\'introduction', 'chic-checkout' ),
            array($this, 'render_login_intro_title_field'),
            self::PAGE_SLUG . '_login',
            'chic_tab_login'
        );
        add_settings_field(
            'login_intro_text',
            __( 'Texte d\'introduction', 'chic-checkout' ),
            array($this, 'render_login_intro_text_field'),
            self::PAGE_SLUG . '_login',
            'chic_tab_login'
        );
        add_settings_field(
            'cta_login_email_label',
            __( 'Texte bouton - Vérifier l\'email', 'chic-checkout' ),
            array($this, 'render_cta_login_email_label_field'),
            self::PAGE_SLUG . '_login',
            'chic_tab_login'
        );
        add_settings_field(
            'cta_login_password_label',
            __( 'Texte bouton - Connexion', 'chic-checkout' ),
            array($this, 'render_cta_login_password_label_field'),
            self::PAGE_SLUG . '_login',
            'chic_tab_login'
        );
        add_settings_field(
            'cta_login_guest_label',
            __( 'Texte bouton - Continuer en invité', 'chic-checkout' ),
            array($this, 'render_cta_login_guest_label_field'),
            self::PAGE_SLUG . '_login',
            'chic_tab_login'
        );
        add_settings_field(
            'cta_login_register_label',
            __( 'Texte bouton - Créer un compte', 'chic-checkout' ),
            array($this, 'render_cta_login_register_label_field'),
            self::PAGE_SLUG . '_login',
            'chic_tab_login'
        );
        // --- Onglet Contact ---
        add_settings_section(
            'chic_tab_contact',
            '',
            '__return_false',
            self::PAGE_SLUG . '_contact'
        );
        add_settings_field(
            'contact_style',
            __( 'Style', 'chic-checkout' ),
            array($this, 'render_contact_style_field'),
            self::PAGE_SLUG . '_contact',
            'chic_tab_contact'
        );
        add_settings_field(
            'contact_label',
            __( 'Texte / info-bulle', 'chic-checkout' ),
            array($this, 'render_contact_label_field'),
            self::PAGE_SLUG . '_contact',
            'chic_tab_contact'
        );
        add_settings_field(
            'contact_popup_content',
            __( 'Titre et texte de la popin', 'chic-checkout' ),
            array($this, 'render_contact_popup_content_field'),
            self::PAGE_SLUG . '_contact',
            'chic_tab_contact'
        );
        add_settings_field(
            'contact_channels',
            __( 'Canaux', 'chic-checkout' ),
            array($this, 'render_contact_channels_field'),
            self::PAGE_SLUG . '_contact',
            'chic_tab_contact'
        );
        // --- Onglet Footer ---
        add_settings_section(
            'chic_tab_footer',
            '',
            '__return_false',
            self::PAGE_SLUG . '_footer'
        );
        add_settings_field(
            'footer_links',
            __( 'Liens', 'chic-checkout' ),
            array($this, 'render_footer_links_field'),
            self::PAGE_SLUG . '_footer',
            'chic_tab_footer'
        );
        add_settings_field(
            'footer_contrast_toggle',
            __( 'Toggle contraste élevé', 'chic-checkout' ),
            array($this, 'render_footer_contrast_toggle_field'),
            self::PAGE_SLUG . '_footer',
            'chic_tab_footer'
        );
        // --- Onglet Réassurance ---
        add_settings_section(
            'chic_tab_reassurance',
            '',
            '__return_false',
            self::PAGE_SLUG . '_reassurance'
        );
        add_settings_field(
            'reassurance_items',
            __( 'Blocs de réassurance', 'chic-checkout' ),
            array($this, 'render_reassurance_items_field'),
            self::PAGE_SLUG . '_reassurance',
            'chic_tab_reassurance'
        );
        // --- Onglet Page de confirmation ---
        add_settings_section(
            'chic_tab_thankyou',
            '',
            '__return_false',
            self::PAGE_SLUG . '_thankyou'
        );
        add_settings_field(
            'thankyou_intro_text',
            __( "Texte d'introduction", 'chic-checkout' ),
            array($this, 'render_thankyou_intro_text_field'),
            self::PAGE_SLUG . '_thankyou',
            'chic_tab_thankyou'
        );
        add_settings_field(
            'thankyou_show_delivery_estimate',
            __( 'Délai de livraison estimé', 'chic-checkout' ),
            array($this, 'render_thankyou_show_delivery_estimate_field'),
            self::PAGE_SLUG . '_thankyou',
            'chic_tab_thankyou'
        );
        add_settings_field(
            'thankyou_back_label',
            __( 'Texte du lien retour', 'chic-checkout' ),
            array($this, 'render_thankyou_back_label_field'),
            self::PAGE_SLUG . '_thankyou',
            'chic_tab_thankyou'
        );
        add_settings_field(
            'thankyou_back_target',
            __( 'Destination du lien retour', 'chic-checkout' ),
            array($this, 'render_thankyou_back_target_field'),
            self::PAGE_SLUG . '_thankyou',
            'chic_tab_thankyou'
        );
        // --- Onglet Panier ---
        add_settings_section(
            'chic_tab_cart',
            '',
            '__return_false',
            self::PAGE_SLUG . '_cart'
        );
        add_settings_field(
            'cart_back',
            __( 'Flèche retour', 'chic-checkout' ),
            array($this, 'render_cart_back_field'),
            self::PAGE_SLUG . '_cart',
            'chic_tab_cart'
        );
        add_settings_field(
            'empty_cart_text',
            __( 'Panier vide', 'chic-checkout' ),
            array($this, 'render_empty_cart_text_field'),
            self::PAGE_SLUG . '_cart',
            'chic_tab_cart'
        );
        add_settings_field(
            'cart_promo_code_enabled',
            __( 'Code promo', 'chic-checkout' ),
            array($this, 'render_cart_promo_code_field'),
            self::PAGE_SLUG . '_cart',
            'chic_tab_cart'
        );
        add_settings_field(
            'cart_upsell',
            __( 'Carousel upsell', 'chic-checkout' ),
            array($this, 'render_cart_upsell_field'),
            self::PAGE_SLUG . '_cart',
            'chic_tab_cart'
        );
        add_settings_field(
            'cart_payment_icons',
            __( 'Moyens de paiement sous le bouton', 'chic-checkout' ),
            array($this, 'render_cart_payment_icons_field'),
            self::PAGE_SLUG . '_cart',
            'chic_tab_cart'
        );
        // --- Onglet Textes ---
        add_settings_section(
            'chic_tab_texts',
            '',
            '__return_false',
            self::PAGE_SLUG . '_texts'
        );
        // --- Onglet Avancé ---
        add_settings_section(
            'chic_tab_advanced',
            '',
            '__return_false',
            self::PAGE_SLUG . '_advanced'
        );
        // --- Onglet SEO ---
        add_settings_section(
            'chic_tab_seo',
            '',
            '__return_false',
            self::PAGE_SLUG . '_seo'
        );
        add_settings_field(
            'seo_robots',
            __( 'Indexation', 'chic-checkout' ),
            array($this, 'render_seo_robots_field'),
            self::PAGE_SLUG . '_seo',
            'chic_tab_seo'
        );
        add_settings_field(
            'seo_meta_title',
            __( 'Titre de la page', 'chic-checkout' ),
            array($this, 'render_seo_meta_title_field'),
            self::PAGE_SLUG . '_seo',
            'chic_tab_seo'
        );
        add_settings_field(
            'seo_meta_description',
            __( 'Meta description', 'chic-checkout' ),
            array($this, 'render_seo_meta_description_field'),
            self::PAGE_SLUG . '_seo',
            'chic_tab_seo'
        );
        // --- Onglet Intégrations ---
        add_settings_section(
            'chic_tab_integrations',
            '',
            '__return_false',
            self::PAGE_SLUG . '_integrations'
        );
        add_settings_field(
            'integrations',
            __( 'Intégrations tierces', 'chic-checkout' ),
            array($this, 'render_integrations_field'),
            self::PAGE_SLUG . '_integrations',
            'chic_tab_integrations'
        );
        add_settings_field(
            'payment_support',
            __( 'Moyens de paiement compatibles', 'chic-checkout' ),
            array($this, 'render_payment_support_field'),
            self::PAGE_SLUG . '_integrations',
            'chic_tab_integrations'
        );
        // --- Onglet Nouveautés (lecture seule, voir Releases) ---
        add_settings_section(
            'chic_tab_news',
            '',
            '__return_false',
            self::PAGE_SLUG . '_news'
        );
        add_settings_field(
            'releases',
            __( 'Notes de version', 'chic-checkout' ),
            array($this, 'render_releases_field'),
            self::PAGE_SLUG . '_news',
            'chic_tab_news'
        );
        // --- Onglet Support : forum (gratuit) ou e-mail, signalement de bugs et accès
        // support temporaire (Pro) ---
        add_settings_section(
            'chic_tab_support',
            '',
            '__return_false',
            self::PAGE_SLUG . '_support'
        );
        add_settings_field(
            'support_contact',
            __( 'Obtenir de l’aide', 'chic-checkout' ),
            array($this, 'render_support_contact_field'),
            self::PAGE_SLUG . '_support',
            'chic_tab_support'
        );
        // --- Onglet Tracking ---
        add_settings_section(
            'chic_tab_tracking',
            '',
            '__return_false',
            self::PAGE_SLUG . '_tracking'
        );
        add_settings_field(
            'tracking_ga4_enabled',
            __( 'Google Analytics 4', 'chic-checkout' ),
            array($this, 'render_tracking_ga4_field'),
            self::PAGE_SLUG . '_tracking',
            'chic_tab_tracking'
        );
        add_settings_field(
            'tracking_debug_mode',
            __( 'Mode debug', 'chic-checkout' ),
            array($this, 'render_tracking_debug_field'),
            self::PAGE_SLUG . '_tracking',
            'chic_tab_tracking'
        );
    }

    /**
     * @param mixed $value
     */
    public function sanitize( $value ) : array {
        $value = ( is_array( $value ) ? $value : array() );
        $clean = array(
            'logo_id'                         => ( isset( $value['logo_id'] ) ? absint( $value['logo_id'] ) : 0 ),
            'logo_text'                       => sanitize_text_field( $value['logo_text'] ?? '' ),
            'order_summary_label'             => ( sanitize_text_field( $value['order_summary_label'] ?? '' ) ?: __( 'Récapitulatif de commande', 'chic-checkout' ) ),
            'show_stepper'                    => !empty( $value['show_stepper'] ),
            'step_name_1'                     => ( sanitize_text_field( $value['step_name_1'] ?? '' ) ?: __( 'Identification', 'chic-checkout' ) ),
            'step_name_2'                     => ( sanitize_text_field( $value['step_name_2'] ?? '' ) ?: __( 'Livraison', 'chic-checkout' ) ),
            'step_name_3'                     => ( sanitize_text_field( $value['step_name_3'] ?? '' ) ?: __( 'Paiement', 'chic-checkout' ) ),
            'step_numbering'                  => !empty( $value['step_numbering'] ),
            'cta_billing_label'               => ( sanitize_text_field( $value['cta_billing_label'] ?? '' ) ?: __( 'Continuer', 'chic-checkout' ) ),
            'cta_payment_label'               => ( sanitize_text_field( $value['cta_payment_label'] ?? '' ) ?: __( 'Payer', 'chic-checkout' ) ),
            'show_terms_checkbox'             => !empty( $value['show_terms_checkbox'] ),
            'terms_checkbox_label'            => ( sanitize_text_field( $value['terms_checkbox_label'] ?? '' ) ?: __( "J'ai lu et j'accepte les [conditions générales de vente] du site.", 'chic-checkout' ) ),
            'progressive_shipping_form'       => !empty( $value['progressive_shipping_form'] ),
            'google_places_api_key'           => sanitize_text_field( $value['google_places_api_key'] ?? '' ),
            'free_shipping_label'             => ( sanitize_text_field( $value['free_shipping_label'] ?? '' ) ?: __( 'Offerte', 'chic-checkout' ) ),
            'cta_shipping_address_label'      => ( sanitize_text_field( $value['cta_shipping_address_label'] ?? '' ) ?: __( 'Continuer', 'chic-checkout' ) ),
            'cta_shipping_method_label'       => ( sanitize_text_field( $value['cta_shipping_method_label'] ?? '' ) ?: __( 'Continuer', 'chic-checkout' ) ),
            'shipping_lead_times'             => array_filter( array_map( 'sanitize_text_field', (array) ($value['shipping_lead_times'] ?? array()) ) ),
            'back_label'                      => ( sanitize_text_field( $value['back_label'] ?? '' ) ?: __( 'Panier', 'chic-checkout' ) ),
            'back_target'                     => ( in_array( $value['back_target'] ?? '', array('default', 'custom'), true ) ? $value['back_target'] : 'default' ),
            'back_custom_url'                 => esc_url_raw( $value['back_custom_url'] ?? '' ),
            'login_intro_title'               => sanitize_text_field( $value['login_intro_title'] ?? '' ),
            'login_intro_text'                => sanitize_textarea_field( $value['login_intro_text'] ?? '' ),
            'cta_login_email_label'           => ( sanitize_text_field( $value['cta_login_email_label'] ?? '' ) ?: __( 'Continuer', 'chic-checkout' ) ),
            'cta_login_password_label'        => ( sanitize_text_field( $value['cta_login_password_label'] ?? '' ) ?: __( 'Se connecter', 'chic-checkout' ) ),
            'cta_login_guest_label'           => ( sanitize_text_field( $value['cta_login_guest_label'] ?? '' ) ?: __( 'Continuer en invité', 'chic-checkout' ) ),
            'cta_login_register_label'        => ( sanitize_text_field( $value['cta_login_register_label'] ?? '' ) ?: __( 'Créer mon compte', 'chic-checkout' ) ),
            'contact_style'                   => ( in_array( $value['contact_style'] ?? '', array('icon', 'icon-filled', 'text'), true ) ? $value['contact_style'] : 'icon' ),
            'contact_label'                   => ( sanitize_text_field( $value['contact_label'] ?? '' ) ?: __( 'Contact', 'chic-checkout' ) ),
            'contact_popup_title'             => ( sanitize_text_field( $value['contact_popup_title'] ?? '' ) ?: __( 'Nous contacter', 'chic-checkout' ) ),
            'contact_popup_text'              => sanitize_textarea_field( $value['contact_popup_text'] ?? '' ),
            'contact_page_enabled'            => !empty( $value['contact_page_enabled'] ),
            'contact_page_url'                => esc_url_raw( $value['contact_page_url'] ?? '' ),
            'contact_email_enabled'           => !empty( $value['contact_email_enabled'] ),
            'contact_email_address'           => sanitize_email( $value['contact_email_address'] ?? '' ),
            'contact_whatsapp_enabled'        => !empty( $value['contact_whatsapp_enabled'] ),
            'contact_whatsapp_number'         => sanitize_text_field( $value['contact_whatsapp_number'] ?? '' ),
            'contact_phone_enabled'           => !empty( $value['contact_phone_enabled'] ),
            'contact_phone_number'            => sanitize_text_field( $value['contact_phone_number'] ?? '' ),
            'contact_phone_days'              => $this->sanitize_weekdays( $value['contact_phone_days'] ?? array() ),
            'contact_phone_start'             => $this->sanitize_time( $value['contact_phone_start'] ?? '' ),
            'contact_phone_end'               => $this->sanitize_time( $value['contact_phone_end'] ?? '' ),
            'footer_links'                    => $this->sanitize_footer_links( $value['footer_links'] ?? array() ),
            'footer_contrast_toggle_enabled'  => !empty( $value['footer_contrast_toggle_enabled'] ),
            'footer_contrast_toggle_label'    => ( sanitize_text_field( $value['footer_contrast_toggle_label'] ?? '' ) ?: __( 'Contraste élevé', 'chic-checkout' ) ),
            'reassurance_items'               => $this->sanitize_reassurance_items( $value['reassurance_items'] ?? array() ),
            'reassurance_mobile_enabled'      => !empty( $value['reassurance_mobile_enabled'] ),
            'thankyou_intro_text'             => ( sanitize_text_field( $value['thankyou_intro_text'] ?? '' ) ?: __( 'Merci pour votre commande, {prenom} !', 'chic-checkout' ) ),
            'thankyou_show_delivery_estimate' => !empty( $value['thankyou_show_delivery_estimate'] ),
            'thankyou_back_label'             => ( sanitize_text_field( $value['thankyou_back_label'] ?? '' ) ?: __( 'Continuer mes achats', 'chic-checkout' ) ),
            'thankyou_back_target'            => ( in_array( $value['thankyou_back_target'] ?? '', array('default', 'custom'), true ) ? $value['thankyou_back_target'] : 'default' ),
            'thankyou_back_custom_url'        => esc_url_raw( $value['thankyou_back_custom_url'] ?? '' ),
            'cart_back_target'                => ( in_array( $value['cart_back_target'] ?? '', array('last_product', 'shop', 'custom'), true ) ? $value['cart_back_target'] : 'shop' ),
            'cart_back_custom_url'            => esc_url_raw( $value['cart_back_custom_url'] ?? '' ),
            'cart_back_label'                 => ( sanitize_text_field( $value['cart_back_label'] ?? '' ) ?: __( 'Continuer mes achats', 'chic-checkout' ) ),
            'empty_cart_text'                 => ( sanitize_text_field( $value['empty_cart_text'] ?? '' ) ?: __( 'Votre panier est vide pour le moment.', 'chic-checkout' ) ),
            'cart_payment_icons_enabled'      => !empty( $value['cart_payment_icons_enabled'] ),
            'cart_payment_icons_label'        => sanitize_text_field( $value['cart_payment_icons_label'] ?? '' ),
            'cart_promo_code_enabled'         => !empty( $value['cart_promo_code_enabled'] ),
            'cart_upsell_enabled'             => !empty( $value['cart_upsell_enabled'] ),
            'cart_upsell_title'               => ( sanitize_text_field( $value['cart_upsell_title'] ?? '' ) ?: __( 'Vous aimerez aussi', 'chic-checkout' ) ),
            'seo_noindex'                     => !empty( $value['seo_noindex'] ),
            'seo_nofollow'                    => !empty( $value['seo_nofollow'] ),
            'seo_meta_title'                  => sanitize_text_field( $value['seo_meta_title'] ?? '' ),
            'seo_meta_description'            => sanitize_textarea_field( $value['seo_meta_description'] ?? '' ),
            'tracking_debug_mode'             => !empty( $value['tracking_debug_mode'] ),
            'integrations'                    => $this->sanitize_integrations( $value['integrations'] ?? array() ),
        );
        // Version gratuite : les réglages d'une version Pro installée auparavant restent
        // enregistrés tels quels (ignorés ici, repris si la version Pro revient).
        return array_merge( array_intersect_key( self::stored(), array_flip( self::PRO_KEYS ) ), $clean );
    }

    /**
     * Interrupteur d'une intégration dont le plugin n'est pas détecté : grisé, donc
     * absent du formulaire envoyé — on garde la valeur enregistrée plutôt que de
     * l'éteindre, pour qu'elle reprenne telle quelle si le plugin est réactivé.
     *
     * @param mixed $raw
     * @return array<string, bool>
     */
    private function sanitize_integrations( $raw ) : array {
        $raw = ( is_array( $raw ) ? $raw : array() );
        $previous = self::stored()['integrations'];
        $result = array();
        $shown = Integrations::definitions();
        foreach ( Integrations::keys() as $key ) {
            // Absente de l'écran (intégration Pro dans la version gratuite) : valeur conservée.
            $result[$key] = ( isset( $shown[$key] ) && Integrations::is_detected( $key ) ? !empty( $raw[$key] ) : !empty( $previous[$key] ) );
        }
        return $result;
    }

    /**
     * @param mixed $raw
     * @return array<int, array{icon_type: string, icon_preset: string, icon_id: int, title: string, text: string, link_url: string, show_payment_icons: bool}>
     */
    private function sanitize_reassurance_items( $raw ) : array {
        $raw = ( is_array( $raw ) ? array_values( $raw ) : array() );
        $result = array();
        foreach ( array_slice( $raw, 0, self::REASSURANCE_ITEMS_MAX ) as $row ) {
            $row = ( is_array( $row ) ? $row : array() );
            $icon_type = ( 'preset' === ($row['icon_type'] ?? '') ? 'preset' : 'custom' );
            $result[] = array(
                'icon_type'          => $icon_type,
                'icon_preset'        => ( array_key_exists( $row['icon_preset'] ?? '', self::reassurance_icon_presets() ) ? $row['icon_preset'] : '' ),
                'icon_id'            => ( isset( $row['icon_id'] ) ? absint( $row['icon_id'] ) : 0 ),
                'title'              => mb_substr( sanitize_text_field( $row['title'] ?? '' ), 0, self::REASSURANCE_TITLE_MAX_LENGTH ),
                'text'               => mb_substr( sanitize_textarea_field( $row['text'] ?? '' ), 0, self::REASSURANCE_TEXT_MAX_LENGTH ),
                'link_url'           => esc_url_raw( $row['link_url'] ?? '' ),
                'show_payment_icons' => !empty( $row['show_payment_icons'] ),
            );
        }
        return $result;
    }

    /**
     * @param mixed $raw
     * @return int[] jours ISO (1-7) uniques, triés.
     */
    private function sanitize_weekdays( $raw ) : array {
        $raw = ( is_array( $raw ) ? $raw : array() );
        $days = array_values( array_unique( array_map( 'absint', $raw ) ) );
        $days = array_values( array_filter( $days, fn( $day ) => $day >= 1 && $day <= 7 ) );
        sort( $days );
        return $days;
    }

    /**
     * @param mixed $raw
     * @return string Heure au format HH:MM, ou '' si invalide/absente.
     */
    private function sanitize_time( $raw ) : string {
        $raw = ( is_string( $raw ) ? $raw : '' );
        return ( preg_match( '/^([01]\\d|2[0-3]):[0-5]\\d$/', $raw ) ? $raw : '' );
    }

    /**
     * @param mixed $raw
     * @return array<int, array{label: string, type: string, page_id: int, custom_url: string}>
     */
    private function sanitize_footer_links( $raw ) : array {
        $raw = ( is_array( $raw ) ? array_values( $raw ) : array() );
        $result = array();
        foreach ( array_slice( $raw, 0, self::FOOTER_LINKS_MAX ) as $row ) {
            $row = ( is_array( $row ) ? $row : array() );
            $result[] = array(
                'label'      => sanitize_text_field( $row['label'] ?? '' ),
                'type'       => ( in_array( $row['type'] ?? '', array('page', 'custom'), true ) ? $row['type'] : 'page' ),
                'page_id'    => ( isset( $row['page_id'] ) ? absint( $row['page_id'] ) : 0 ),
                'custom_url' => esc_url_raw( $row['custom_url'] ?? '' ),
            );
        }
        return $result;
    }

    /**
     * Champ media picker générique (logo, icône contact...) réutilisé par admin-settings.js
     * via les classes .chic-media-* plutôt que dupliquer le JS par champ.
     */
    /**
     * Champ image (bibliothèque de médias). $hint est obligatoire : chaque image proposée
     * dans les réglages indique son format et sa taille conseillés, d'après la façon dont
     * elle est réellement affichée (taille, recadrage).
     */
    private function render_media_field(
        string $field_key,
        int $attachment_id,
        string $picker_title,
        string $button_label,
        string $hint
    ) : void {
        $image_url = ( $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '' );
        ?>
		<div class="chic-media-field">
			<img
				class="chic-media-preview"
				src="<?php 
        echo esc_url( $image_url );
        ?>"
				style="max-width:150px;display:<?php 
        echo ( $image_url ? 'block' : 'none' );
        ?>;margin-bottom:10px;"
				alt=""
			/>
			<input
				type="hidden"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[<?php 
        echo esc_attr( $field_key );
        ?>]"
				value="<?php 
        echo esc_attr( (string) $attachment_id );
        ?>"
				class="chic-media-id"
			/>
			<button type="button" class="button chic-media-select" data-title="<?php 
        echo esc_attr( $picker_title );
        ?>">
				<?php 
        echo esc_html( $button_label );
        ?>
			</button>
			<button
				type="button"
				class="button chic-media-remove"
				style="<?php 
        echo ( $image_url ? '' : 'display:none;' );
        ?>"
			>
				<?php 
        esc_html_e( 'Retirer', 'chic-checkout' );
        ?>
			</button>
			<p class="description chic-media-hint" style="margin-top:6px;"><?php 
        echo esc_html( $hint );
        ?></p>
		</div>
		<?php 
    }

    public function render_logo_field() : void {
        $settings = self::stored();
        $this->render_media_field(
            'logo_id',
            (int) $settings['logo_id'],
            __( 'Choisir un logo', 'chic-checkout' ),
            __( 'Choisir un logo', 'chic-checkout' ),
            __( 'Format conseillé : PNG à fond transparent (ou SVG si votre site l’accepte), 64 px de haut, largeur libre (affiché à 32 px de haut, doublé pour les écrans haute définition). Moins de 100 Ko. Un seul logo pour toutes les tailles d’écran.', 'chic-checkout' )
        );
    }

    public function render_logo_text_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[logo_text]"
			value="<?php 
        echo esc_attr( $settings['logo_text'] );
        ?>"
			placeholder="<?php 
        echo esc_attr( get_bloginfo( 'name' ) );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( "Utilisé si aucun logo n'est choisi ci-dessus. Ex : le nom de votre marque, ou un message comme \"Terminer ma commande\". Sans réglage, le nom ou le logo déjà configuré dans WordPress (Réglages > Général / Personnaliser) est utilisé automatiquement.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_order_summary_label_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[order_summary_label]"
			value="<?php 
        echo esc_attr( $settings['order_summary_label'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Récapitulatif de commande', 'chic-checkout' );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( "Affiché replié, avant que le client ne déplie le détail de sa commande.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_show_stepper_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[show_stepper]"
				value="1"
				<?php 
        checked( $settings['show_stepper'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher la barre de progression (étapes)', 'chic-checkout' );
        ?>
		</label>
		<p class="description">
			<?php 
        esc_html_e( "D'autres présentations du stepper (templates au choix) pourront être proposées ici plus tard.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_step_numbering_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[step_numbering]"
				value="1"
				<?php 
        checked( $settings['step_numbering'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Numéroter les étapes (ex : "1 - Identification")', 'chic-checkout' );
        ?>
		</label>
		<?php 
    }

    /**
     * @param array{index:int,default:string} $args
     */
    public function render_step_name_field( array $args ) : void {
        $settings = self::stored();
        $key = 'step_name_' . $args['index'];
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[<?php 
        echo esc_attr( $key );
        ?>]"
			value="<?php 
        echo esc_attr( $settings[$key] );
        ?>"
			placeholder="<?php 
        echo esc_attr( $args['default'] );
        ?>"
		/>
		<?php 
    }

    /**
     * Champ texte générique pour les libellés de boutons CTA, réutilisé par les
     * render_cta_*_label_field() ci-dessous plutôt que de dupliquer le même markup.
     * L'affichage final (frontend) force toujours une casse "Première lettre en
     * majuscule, reste en minuscule" quoi que l'admin saisisse ici — jamais de tout
     * en majuscule dans les CTA (voir toSentenceCase côté React).
     */
    private function render_cta_label_field( string $field_key, string $placeholder ) : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[<?php 
        echo esc_attr( $field_key );
        ?>]"
			value="<?php 
        echo esc_attr( $settings[$field_key] );
        ?>"
			placeholder="<?php 
        echo esc_attr( $placeholder );
        ?>"
		/>
		<?php 
    }

    public function render_cta_billing_label_field() : void {
        $this->render_cta_label_field( 'cta_billing_label', __( 'Continuer', 'chic-checkout' ) );
    }

    public function render_cta_payment_label_field() : void {
        $this->render_cta_label_field( 'cta_payment_label', __( 'Payer', 'chic-checkout' ) );
    }

    public function render_show_terms_checkbox_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[show_terms_checkbox]"
				value="1"
				<?php 
        checked( $settings['show_terms_checkbox'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher une case à cocher CGV avant le bouton de paiement', 'chic-checkout' );
        ?>
		</label>
		<p class="description">
			<?php 
        esc_html_e( "Le contenu déplié au clic reprend automatiquement la page \"Conditions générales de vente\" configurée dans Réglages WooCommerce > Avancé > Pages. Sans page configurée, la case n'est pas affichée.", 'chic-checkout' );
        ?>
		</p>
		<?php 
        // Même condition que l'affichage au checkout (Assets::resolve_terms_checkbox()) :
        // une case cochée ici mais absente du checkout, sans explication, passait pour un bug.
        $terms_page_id = ( function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0 );
        $terms_page = ( $terms_page_id ? get_post( $terms_page_id ) : null );
        if ( !$terms_page || 'publish' !== $terms_page->post_status || !$terms_page->post_content ) {
            ?>
			<div class="notice notice-warning inline" style="margin:10px 0 0;padding:8px 12px;">
				<p style="margin:0;">
					<strong><?php 
            esc_html_e( 'La case CGV ne s’affiche pas au checkout :', 'chic-checkout' );
            ?></strong>
					<?php 
            echo ( $terms_page ? esc_html__( 'la page CGV choisie dans WooCommerce n’est pas publiée ou est vide.', 'chic-checkout' ) : esc_html__( 'aucune page CGV n’est choisie dans WooCommerce.', 'chic-checkout' ) );
            ?>
					<a href="<?php 
            echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced' ) );
            ?>"><?php 
            esc_html_e( 'Choisir la page CGV (WooCommerce → Réglages → Avancé)', 'chic-checkout' );
            ?></a>
				</p>
			</div>
			<?php 
        }
    }

    public function render_terms_checkbox_label_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="large-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[terms_checkbox_label]"
			value="<?php 
        echo esc_attr( $settings['terms_checkbox_label'] );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( 'Le texte entre crochets devient un lien cliquable qui déplie le contenu de la page CGV en dessous.', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    /**
     * Purement informatif (pas un réglage) : la sélection de point relais elle-même se
     * configure entièrement dans Boxtal Connect / La Poste Pro Expéditions (identifiants,
     * réseau de points...), voir Assets::maybe_enqueue_boxtal_connect() et
     * maybe_enqueue_laposte() côté code — ce plugin se contente d'afficher leur widget
     * dans le checkout une fois la méthode de livraison activée sur une zone.
     */
    public function render_shipping_pickup_points_info_field() : void {
        ?>
		<p class="description" style="margin-top:4px;">
			<?php 
        esc_html_e( "Ce plugin affiche automatiquement le sélecteur de point relais dans le checkout dès que l'un de ces plugins est installé, actif, et sa méthode de livraison activée sur une zone (Réglages WooCommerce > Livraison) :", 'chic-checkout' );
        ?>
		</p>
		<ul style="list-style:disc;margin-left:1.2em;">
			<li>
				<a href="https://wordpress.org/plugins/boxtal-connect/" target="_blank" rel="noopener noreferrer">Boxtal Connect</a>
				— <?php 
        esc_html_e( 'points relais Mondial Relay, Chronopost, UPS...', 'chic-checkout' );
        ?>
			</li>
			<li>
				<a href="https://wordpress.org/plugins/la-poste-pro-expeditions-woocommerce/" target="_blank" rel="noopener noreferrer">La Poste Pro Expéditions</a>
				— <?php 
        esc_html_e( 'points relais La Poste / Colissimo', 'chic-checkout' );
        ?>
			</li>
		</ul>
		<p class="description">
			<?php 
        esc_html_e( "Rien à configurer ici : identifiants, réseau de points relais, tarifs... se règlent entièrement dans ces plugins eux-mêmes.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_progressive_shipping_form_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[progressive_shipping_form]"
				value="1"
				<?php 
        checked( $settings['progressive_shipping_form'] );
        ?>
			/>
			<?php 
        esc_html_e( "N'afficher que prénom, nom, pays et adresse au départ", 'chic-checkout' );
        ?>
		</label>
		<p class="description">
			<?php 
        esc_html_e( "Guide le client pas à pas et réduit le risque d'abandon en ne montrant que l'essentiel au départ. Le reste (adresse ligne 2, ville, code postal, téléphone) se déplie via un bouton \"Entrer mon adresse manuellement\", ou automatiquement si le client choisit une suggestion Google (voir ci-dessous).", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_google_places_api_key_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[google_places_api_key]"
			value="<?php 
        echo esc_attr( $settings['google_places_api_key'] );
        ?>"
			placeholder="AIza..."
		/>
		<p class="description">
			<?php 
        esc_html_e( "Active l'autocomplétion d'adresse (uniquement en mode formulaire progressif). Sans clé, le champ adresse reste un champ texte classique.", 'chic-checkout' );
        ?>
			<br />
			<?php 
        esc_html_e( 'Cette clé est destinée à être visible côté client (normal pour cette API) — restreins-la par référent HTTP dans Google Cloud Console.', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_free_shipping_label_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[free_shipping_label]"
			value="<?php 
        echo esc_attr( $settings['free_shipping_label'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Offerte', 'chic-checkout' );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( 'Texte affiché à la place du prix quand un mode de livraison est gratuit (0€).', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_cta_shipping_address_label_field() : void {
        $this->render_cta_label_field( 'cta_shipping_address_label', __( 'Continuer', 'chic-checkout' ) );
    }

    public function render_cta_shipping_method_label_field() : void {
        $this->render_cta_label_field( 'cta_shipping_method_label', __( 'Continuer', 'chic-checkout' ) );
    }

    public function render_shipping_lead_times_field() : void {
        $settings = self::stored();
        $methods = Shipping_Lead_Time::get_configured_methods();
        if ( !$methods ) {
            ?>
			<p class="description">
				<?php 
            esc_html_e( "Aucune méthode de livraison active pour le moment (Réglages WooCommerce > Livraison).", 'chic-checkout' );
            ?>
			</p>
			<?php 
            return;
        }
        foreach ( $methods as $method ) {
            $field_name = sprintf( '%s[shipping_lead_times][%d]', self::OPTION_KEY, $method['instance_id'] );
            $value = $settings['shipping_lead_times'][$method['instance_id']] ?? '';
            ?>
			<p style="margin: 0 0 10px;">
				<label style="display:block;font-weight:600;margin-bottom:4px;">
					<?php 
            echo esc_html( $method['zone_name'] . ' — ' . $method['method_title'] );
            ?>
				</label>
				<input
					type="text"
					class="regular-text"
					name="<?php 
            echo esc_attr( $field_name );
            ?>"
					value="<?php 
            echo esc_attr( $value );
            ?>"
					placeholder="<?php 
            esc_attr_e( 'ex : 6-8 jours', 'chic-checkout' );
            ?>"
				/>
			</p>
			<?php 
        }
        ?>
		<p class="description">
			<?php 
        esc_html_e( "Texte libre affiché tel quel sous le nom du mode de livraison (aucun calcul de date). Laissez vide pour ne rien afficher.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_back_label_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[back_label]"
			value="<?php 
        echo esc_attr( $settings['back_label'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Panier', 'chic-checkout' );
        ?>"
		/>
		<?php 
    }

    public function render_back_target_field() : void {
        $settings = self::stored();
        ?>
		<label style="display:block;margin-bottom:8px;">
			<input
				type="radio"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[back_target]"
				value="default"
				<?php 
        checked( $settings['back_target'], 'default' );
        ?>
			/>
			<?php 
        esc_html_e( 'Page panier WooCommerce par défaut', 'chic-checkout' );
        ?>
		</label>
		<label style="display:flex;align-items:center;gap:8px;">
			<input
				type="radio"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[back_target]"
				value="custom"
				<?php 
        checked( $settings['back_target'], 'custom' );
        ?>
			/>
			<?php 
        esc_html_e( 'URL personnalisée :', 'chic-checkout' );
        ?>
			<input
				type="url"
				class="regular-text"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[back_custom_url]"
				value="<?php 
        echo esc_attr( $settings['back_custom_url'] );
        ?>"
				placeholder="https://..."
			/>
		</label>
		<?php 
    }

    public function render_login_intro_title_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[login_intro_title]"
			value="<?php 
        echo esc_attr( $settings['login_intro_title'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Identification', 'chic-checkout' );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( 'Affiché au-dessus du champ email. Vide = "Identification".', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_login_intro_text_field() : void {
        $settings = self::stored();
        ?>
		<textarea
			class="large-text"
			rows="3"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[login_intro_text]"
			placeholder="<?php 
        esc_attr_e( 'Connectez-vous pour continuer votre commande.', 'chic-checkout' );
        ?>"
		><?php 
        echo esc_textarea( $settings['login_intro_text'] );
        ?></textarea>
		<?php 
    }

    public function render_cta_login_email_label_field() : void {
        $this->render_cta_label_field( 'cta_login_email_label', __( 'Continuer', 'chic-checkout' ) );
    }

    public function render_cta_login_password_label_field() : void {
        $this->render_cta_label_field( 'cta_login_password_label', __( 'Se connecter', 'chic-checkout' ) );
    }

    public function render_cta_login_guest_label_field() : void {
        $this->render_cta_label_field( 'cta_login_guest_label', __( 'Continuer en invité', 'chic-checkout' ) );
    }

    public function render_cta_login_register_label_field() : void {
        $this->render_cta_label_field( 'cta_login_register_label', __( 'Créer mon compte', 'chic-checkout' ) );
    }

    public function render_contact_style_field() : void {
        $settings = self::stored();
        $options = array(
            'icon'        => __( 'Icône simple', 'chic-checkout' ),
            'icon-filled' => __( 'Icône avec fond', 'chic-checkout' ),
            'text'        => __( 'Texte en toutes lettres', 'chic-checkout' ),
        );
        foreach ( $options as $value => $option_label ) {
            ?>
			<label style="display:block;margin-bottom:6px;">
				<input
					type="radio"
					name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[contact_style]"
					value="<?php 
            echo esc_attr( $value );
            ?>"
					<?php 
            checked( $settings['contact_style'], $value );
            ?>
				/>
				<?php 
            echo esc_html( $option_label );
            ?>
			</label>
			<?php 
        }
    }

    public function render_contact_label_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_label]"
			value="<?php 
        echo esc_attr( $settings['contact_label'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Contact', 'chic-checkout' );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( "Affiché tel quel en mode 'texte', utilisé comme info-bulle en mode icône.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_contact_popup_content_field() : void {
        $settings = self::stored();
        ?>
		<p>
			<label>
				<?php 
        esc_html_e( 'Titre', 'chic-checkout' );
        ?><br />
				<input
					type="text"
					class="regular-text"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_popup_title]"
					value="<?php 
        echo esc_attr( $settings['contact_popup_title'] );
        ?>"
					placeholder="<?php 
        esc_attr_e( 'Nous contacter', 'chic-checkout' );
        ?>"
				/>
			</label>
		</p>
		<p>
			<label>
				<?php 
        esc_html_e( 'Texte (facultatif)', 'chic-checkout' );
        ?><br />
				<textarea
					class="large-text"
					rows="2"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_popup_text]"
					placeholder="<?php 
        esc_attr_e( 'ex. Vous pouvez nous contacter par WhatsApp ou par téléphone, et via le formulaire de contact', 'chic-checkout' );
        ?>"
				><?php 
        echo esc_textarea( $settings['contact_popup_text'] );
        ?></textarea>
			</label>
		</p>
		<p class="description">
			<?php 
        esc_html_e( "Affichés en haut de la fenêtre de contact, si au moins deux canaux sont activés ci-dessous.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_contact_channels_field() : void {
        $settings = self::stored();
        ?>
		<table class="chic-contact-channels">
			<tr>
				<td>
					<label>
						<input
							type="checkbox"
							name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_page_enabled]"
							value="1"
							<?php 
        checked( $settings['contact_page_enabled'] );
        ?>
						/>
						<?php 
        esc_html_e( 'Page de contact', 'chic-checkout' );
        ?>
					</label>
				</td>
				<td>
					<input
						type="url"
						class="regular-text"
						name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_page_url]"
						value="<?php 
        echo esc_attr( $settings['contact_page_url'] );
        ?>"
						placeholder="https://.../contact"
					/>
				</td>
			</tr>
			<tr>
				<td>
					<label>
						<input
							type="checkbox"
							name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_email_enabled]"
							value="1"
							<?php 
        checked( $settings['contact_email_enabled'] );
        ?>
						/>
						<?php 
        esc_html_e( 'Email', 'chic-checkout' );
        ?>
					</label>
				</td>
				<td>
					<input
						type="email"
						class="regular-text"
						name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_email_address]"
						value="<?php 
        echo esc_attr( $settings['contact_email_address'] );
        ?>"
						placeholder="contact@exemple.com"
					/>
				</td>
			</tr>
			<tr>
				<td>
					<label>
						<input
							type="checkbox"
							name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_whatsapp_enabled]"
							value="1"
							<?php 
        checked( $settings['contact_whatsapp_enabled'] );
        ?>
						/>
						<?php 
        esc_html_e( 'WhatsApp', 'chic-checkout' );
        ?>
					</label>
				</td>
				<td>
					<input
						type="text"
						class="regular-text"
						name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_whatsapp_number]"
						value="<?php 
        echo esc_attr( $settings['contact_whatsapp_number'] );
        ?>"
						placeholder="+33612345678"
					/>
				</td>
			</tr>
			<tr>
				<td>
					<label>
						<input
							type="checkbox"
							name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_phone_enabled]"
							value="1"
							<?php 
        checked( $settings['contact_phone_enabled'] );
        ?>
						/>
						<?php 
        esc_html_e( 'Téléphone', 'chic-checkout' );
        ?>
					</label>
				</td>
				<td>
					<input
						type="text"
						class="regular-text"
						name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_phone_number]"
						value="<?php 
        echo esc_attr( $settings['contact_phone_number'] );
        ?>"
						placeholder="+33612345678"
					/>
					<p class="description" style="margin:10px 0 4px;">
						<?php 
        esc_html_e( 'Jours et horaires de disponibilité (affichés dans la popin, avec un point vert/rouge selon la disponibilité au moment où le client ouvre la fenêtre) :', 'chic-checkout' );
        ?>
					</p>
					<div style="display:flex;flex-wrap:wrap;gap:2px 12px;margin-bottom:8px;">
						<?php 
        foreach ( self::weekdays() as $day_number => $day_label ) {
            ?>
							<label style="white-space:nowrap;">
								<input
									type="checkbox"
									name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[contact_phone_days][]"
									value="<?php 
            echo esc_attr( $day_number );
            ?>"
									<?php 
            checked( in_array( $day_number, $settings['contact_phone_days'], true ) );
            ?>
								/>
								<?php 
            echo esc_html( mb_substr( $day_label, 0, 3 ) );
            ?>
							</label>
						<?php 
        }
        ?>
					</div>
					<label>
						<?php 
        esc_html_e( 'De', 'chic-checkout' );
        ?>
						<input
							type="time"
							name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_phone_start]"
							value="<?php 
        echo esc_attr( $settings['contact_phone_start'] );
        ?>"
						/>
					</label>
					<label>
						<?php 
        esc_html_e( 'à', 'chic-checkout' );
        ?>
						<input
							type="time"
							name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[contact_phone_end]"
							value="<?php 
        echo esc_attr( $settings['contact_phone_end'] );
        ?>"
						/>
					</label>
				</td>
			</tr>
		</table>
		<p class="description">
			<?php 
        esc_html_e( "Si plusieurs canaux sont activés, une fenêtre proposera le choix au client. Le chatbot arrivera dans une prochaine version.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    /**
     * Jusqu'à FOOTER_LINKS_MAX liens (CGV, mentions légales, politique de
     * confidentialité, politique de retour...), chacun soit vers une page WordPress
     * existante (toujours à jour si son URL change), soit vers un lien personnalisé
     * (page externe, PDF...). Lignes fixes plutôt qu'ajout/suppression dynamique —
     * même parti pris que le tableau des canaux de contact ci-dessus.
     */
    public function render_footer_links_field() : void {
        $settings = self::stored();
        $rows = $settings['footer_links'];
        $pages = get_pages( array(
            'post_status' => 'publish',
            'sort_column' => 'post_title',
        ) );
        ?>
		<p class="description">
			<?php 
        esc_html_e( 'Jusqu\'à 5 liens (CGV, mentions légales, politique de confidentialité, politique de retour...). Une ligne sans page ni lien choisi est simplement ignorée. Le footer ne s\'affiche que si au moins un lien est configuré.', 'chic-checkout' );
        ?>
		</p>
		<table class="widefat chic-footer-links-table">
			<thead>
				<tr>
					<th><?php 
        esc_html_e( 'Nom affiché', 'chic-checkout' );
        ?></th>
					<th><?php 
        esc_html_e( 'Type', 'chic-checkout' );
        ?></th>
					<th><?php 
        esc_html_e( 'Page ou lien', 'chic-checkout' );
        ?></th>
				</tr>
			</thead>
			<tbody>
				<?php 
        for ($index = 0; $index < self::FOOTER_LINKS_MAX; $index++) {
            ?>
					<?php 
            $row = $rows[$index] ?? array();
            $label = $row['label'] ?? '';
            $type = ( ($row['type'] ?? 'page') === 'custom' ? 'custom' : 'page' );
            $page_id = (int) ($row['page_id'] ?? 0);
            $custom_url = $row['custom_url'] ?? '';
            $name_base = sprintf( '%s[footer_links][%d]', self::OPTION_KEY, $index );
            ?>
					<tr class="chic-footer-link-row">
						<td>
							<input
								type="text"
								class="regular-text"
								name="<?php 
            echo esc_attr( $name_base );
            ?>[label]"
								value="<?php 
            echo esc_attr( $label );
            ?>"
								placeholder="<?php 
            esc_attr_e( 'ex. Politique de retour', 'chic-checkout' );
            ?>"
							/>
						</td>
						<td>
							<select class="chic-footer-link-type" name="<?php 
            echo esc_attr( $name_base );
            ?>[type]">
								<option value="page" <?php 
            selected( $type, 'page' );
            ?>><?php 
            esc_html_e( 'Page WordPress', 'chic-checkout' );
            ?></option>
								<option value="custom" <?php 
            selected( $type, 'custom' );
            ?>><?php 
            esc_html_e( 'Lien personnalisé', 'chic-checkout' );
            ?></option>
							</select>
						</td>
						<td>
							<select
								class="chic-footer-link-page"
								name="<?php 
            echo esc_attr( $name_base );
            ?>[page_id]"
								style="<?php 
            echo ( 'page' === $type ? '' : 'display:none;' );
            ?>"
							>
								<option value="0">— <?php 
            esc_html_e( 'Choisir une page', 'chic-checkout' );
            ?> —</option>
								<?php 
            foreach ( $pages as $page ) {
                ?>
									<option value="<?php 
                echo esc_attr( $page->ID );
                ?>" <?php 
                selected( $page_id, $page->ID );
                ?>>
										<?php 
                echo esc_html( $page->post_title );
                ?>
									</option>
								<?php 
            }
            ?>
							</select>
							<input
								type="url"
								class="regular-text chic-footer-link-custom"
								name="<?php 
            echo esc_attr( $name_base );
            ?>[custom_url]"
								value="<?php 
            echo esc_attr( $custom_url );
            ?>"
								placeholder="https://…"
								style="<?php 
            echo ( 'custom' === $type ? '' : 'display:none;' );
            ?>"
							/>
						</td>
					</tr>
				<?php 
        }
        ?>
			</tbody>
		</table>
		<?php 
    }

    public function render_footer_contrast_toggle_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[footer_contrast_toggle_enabled]"
				value="1"
				<?php 
        checked( $settings['footer_contrast_toggle_enabled'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher le toggle contraste élevé dans le footer', 'chic-checkout' );
        ?>
		</label>
		<p class="description" style="color:#b91c1c;">
			<?php 
        esc_html_e( "Le masquer n'est pas recommandé : ce toggle aide les clients malvoyants ou sensibles aux forts contrastes à utiliser le checkout.", 'chic-checkout' );
        ?>
		</p>
		<p>
			<label for="chic-footer-contrast-toggle-label"><?php 
        esc_html_e( 'Texte affiché', 'chic-checkout' );
        ?></label><br />
			<input
				type="text"
				id="chic-footer-contrast-toggle-label"
				class="regular-text"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[footer_contrast_toggle_label]"
				value="<?php 
        echo esc_attr( $settings['footer_contrast_toggle_label'] );
        ?>"
				placeholder="<?php 
        esc_attr_e( 'Contraste élevé', 'chic-checkout' );
        ?>"
			/>
		</p>
		<?php 
    }

    public function render_reassurance_items_field() : void {
        $settings = self::stored();
        $rows = $settings['reassurance_items'];
        ?>
		<p class="description">
			<?php 
        printf( 
            /* translators: 1: nombre max de caractères pour le titre, 2: pour le texte */
            esc_html__( "Jusqu'à 4 blocs (ex. Paiement sécurisé, Livraison offerte, Retour sans frais, Retrait en magasin), affichés dans l'ordre ci-dessous (flèches ↑ ↓ pour le changer). Titre limité à %1\$d caractères, texte à %2\$d — au-delà, la mise en page casse. Un bloc sans titre est ignoré.", 'chic-checkout' ),
            (int) self::REASSURANCE_TITLE_MAX_LENGTH,
            (int) self::REASSURANCE_TEXT_MAX_LENGTH
         );
        ?>
		</p>
		<div class="chic-reassurance-items">
		<?php 
        for ($index = 0; $index < self::REASSURANCE_ITEMS_MAX; $index++) {
            ?>
			<?php 
            $row = $rows[$index] ?? array();
            $icon_type = ( ($row['icon_type'] ?? 'custom') === 'preset' ? 'preset' : 'custom' );
            $icon_preset = $row['icon_preset'] ?? '';
            $icon_id = (int) ($row['icon_id'] ?? 0);
            $title = $row['title'] ?? '';
            $text = $row['text'] ?? '';
            $link_url = $row['link_url'] ?? '';
            $name_base = sprintf( '%s[reassurance_items][%d]', self::OPTION_KEY, $index );
            ?>
			<div class="chic-reassurance-item-row" style="display:flex;gap:20px;padding:16px 0;border-top:1px solid #dcdcde;">
				<div style="flex:0 0 170px;">
					<p style="margin-top:0;display:flex;align-items:center;gap:6px;">
						<strong class="chic-reassurance-item-number" style="flex:1;">
							<?php 
            /* translators: %d: position du bloc (1, 2, 3…) */
            echo esc_html( sprintf( __( 'Bloc %d', 'chic-checkout' ), $index + 1 ) );
            ?>
						</strong>
						<button type="button" class="button button-small chic-reassurance-item-up" aria-label="<?php 
            esc_attr_e( 'Monter ce bloc', 'chic-checkout' );
            ?>">↑</button>
						<button type="button" class="button button-small chic-reassurance-item-down" aria-label="<?php 
            esc_attr_e( 'Descendre ce bloc', 'chic-checkout' );
            ?>">↓</button>
					</p>
					<?php 
            // Version gratuite : icônes prédéfinies seulement. L'image personnalisée
            // éventuellement choisie avec la version Pro reste enregistrée.
            $has_custom = false;
            if ( !$has_custom ) {
                $icon_type = 'preset';
                printf( '<input type="hidden" name="%1$s[icon_type]" value="preset" /><input type="hidden" name="%1$s[icon_id]" value="%2$d" />', esc_attr( $name_base ), (int) $icon_id );
            }
            ?>
					<div class="chic-reassurance-icon-preset" style="<?php 
            echo ( 'preset' === $icon_type ? '' : 'display:none;' );
            ?>">
						<select name="<?php 
            echo esc_attr( $name_base );
            ?>[icon_preset]">
							<option value="">— <?php 
            esc_html_e( 'Choisir', 'chic-checkout' );
            ?> —</option>
							<?php 
            foreach ( self::reassurance_icon_presets() as $preset_key => $preset_label ) {
                ?>
								<option value="<?php 
                echo esc_attr( $preset_key );
                ?>" <?php 
                selected( $icon_preset, $preset_key );
                ?>>
									<?php 
                echo esc_html( $preset_label );
                ?>
								</option>
							<?php 
            }
            ?>
						</select>
					</div>
					<?php 
            ?>
				</div>
				<div style="flex:1;">
					<p>
						<label>
							<?php 
            esc_html_e( 'Titre', 'chic-checkout' );
            ?>
							<input
								type="text"
								class="regular-text"
								maxlength="<?php 
            echo esc_attr( self::REASSURANCE_TITLE_MAX_LENGTH );
            ?>"
								name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[reassurance_items][<?php 
            echo esc_attr( $index );
            ?>][title]"
								value="<?php 
            echo esc_attr( $title );
            ?>"
								placeholder="<?php 
            esc_attr_e( 'ex. Paiement sécurisé', 'chic-checkout' );
            ?>"
							/>
						</label>
					</p>
					<p>
						<label>
							<?php 
            esc_html_e( 'Texte', 'chic-checkout' );
            ?>
							<textarea
								class="large-text"
								rows="2"
								maxlength="<?php 
            echo esc_attr( self::REASSURANCE_TEXT_MAX_LENGTH );
            ?>"
								name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[reassurance_items][<?php 
            echo esc_attr( $index );
            ?>][text]"
								placeholder="<?php 
            esc_attr_e( 'ex. Carte de crédit et de débit, PayPal et Apple Pay', 'chic-checkout' );
            ?>"
							><?php 
            echo esc_textarea( $text );
            ?></textarea>
						</label>
					</p>
					<p>
						<label>
							<?php 
            esc_html_e( 'Lien (facultatif)', 'chic-checkout' );
            ?>
							<input
								type="url"
								class="regular-text"
								name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[reassurance_items][<?php 
            echo esc_attr( $index );
            ?>][link_url]"
								value="<?php 
            echo esc_attr( $link_url );
            ?>"
								placeholder="https://…"
							/>
						</label>
					</p>
					<p class="description">
						<?php 
            esc_html_e( "Si renseigné, un bouton (chevron) apparaît sur le bloc pour ouvrir ce lien dans un nouvel onglet.", 'chic-checkout' );
            ?>
					</p>
					<p>
						<label>
							<input
								type="checkbox"
								name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[reassurance_items][<?php 
            echo esc_attr( $index );
            ?>][show_payment_icons]"
								value="1"
								<?php 
            checked( !empty( $row['show_payment_icons'] ) );
            ?>
							/>
							<?php 
            esc_html_e( 'Afficher les logos des moyens de paiement disponibles', 'chic-checkout' );
            ?>
						</label>
					</p>
					<p class="description">
						<?php 
            esc_html_e( 'Sous le texte du bloc, mêmes logos que sous le bouton du panier (ceux fournis par chaque moyen de paiement activé dans WooCommerce).', 'chic-checkout' );
            ?>
					</p>
				</div>
			</div>
		<?php 
        }
        ?>
		</div>
		<p>
			<label>
				<input
					type="checkbox"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[reassurance_mobile_enabled]"
					value="1"
					<?php 
        checked( $settings['reassurance_mobile_enabled'] );
        ?>
				/>
				<?php 
        esc_html_e( 'Afficher aussi sur mobile (tout en bas de page, sous le bouton de paiement)', 'chic-checkout' );
        ?>
			</label>
		</p>
		<?php 
    }

    public function render_thankyou_intro_text_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[thankyou_intro_text]"
			value="<?php 
        echo esc_attr( $settings['thankyou_intro_text'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Merci pour votre commande, {prenom} !', 'chic-checkout' );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( 'Jetons disponibles : {prenom} et {nom}, remplacés par le prénom/nom du client à l\'affichage.', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_thankyou_show_delivery_estimate_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[thankyou_show_delivery_estimate]"
				value="1"
				<?php 
        checked( $settings['thankyou_show_delivery_estimate'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher le délai de livraison estimé (déjà configuré par méthode dans l\'onglet Livraison)', 'chic-checkout' );
        ?>
		</label>
		<?php 
    }

    public function render_thankyou_back_label_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="regular-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[thankyou_back_label]"
			value="<?php 
        echo esc_attr( $settings['thankyou_back_label'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Continuer mes achats', 'chic-checkout' );
        ?>"
		/>
		<?php 
    }

    public function render_thankyou_back_target_field() : void {
        $settings = self::stored();
        ?>
		<label style="display:block;margin-bottom:8px;">
			<input
				type="radio"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[thankyou_back_target]"
				value="default"
				<?php 
        checked( $settings['thankyou_back_target'], 'default' );
        ?>
			/>
			<?php 
        esc_html_e( 'Page boutique par défaut', 'chic-checkout' );
        ?>
		</label>
		<label style="display:flex;align-items:center;gap:8px;">
			<input
				type="radio"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[thankyou_back_target]"
				value="custom"
				<?php 
        checked( $settings['thankyou_back_target'], 'custom' );
        ?>
			/>
			<?php 
        esc_html_e( 'URL personnalisée :', 'chic-checkout' );
        ?>
			<input
				type="url"
				class="regular-text"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[thankyou_back_custom_url]"
				value="<?php 
        echo esc_attr( $settings['thankyou_back_custom_url'] );
        ?>"
				placeholder="https://..."
			/>
		</label>
		<?php 
    }

    public function render_empty_cart_text_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="large-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[empty_cart_text]"
			value="<?php 
        echo esc_attr( $settings['empty_cart_text'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Votre panier est vide pour le moment.', 'chic-checkout' );
        ?>"
		/>
		<p class="description"><?php 
        esc_html_e( 'Affiché sous « Votre panier est vide », au-dessus du bouton de retour à la boutique.', 'chic-checkout' );
        ?></p>
		<?php 
    }

    public function render_cart_back_field() : void {
        $settings = self::stored();
        ?>
		<p style="margin-top:0;">
			<label for="chic-cart-back-label" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php 
        esc_html_e( 'Texte du lien', 'chic-checkout' );
        ?>
			</label>
			<input
				id="chic-cart-back-label"
				type="text"
				class="regular-text"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_back_label]"
				value="<?php 
        echo esc_attr( $settings['cart_back_label'] );
        ?>"
				placeholder="<?php 
        esc_attr_e( 'Continuer mes achats', 'chic-checkout' );
        ?>"
			/>
		</p>
		<p>
			<label style="display:block;margin-bottom:8px;">
				<input
					type="radio"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_back_target]"
					value="last_product"
					<?php 
        checked( $settings['cart_back_target'], 'last_product' );
        ?>
				/>
				<?php 
        esc_html_e( 'Dernière page produit consultée (via le suivi "produits vus récemment" de WooCommerce ; repli sur la boutique si aucune)', 'chic-checkout' );
        ?>
			</label>
			<label style="display:block;margin-bottom:8px;">
				<input
					type="radio"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_back_target]"
					value="shop"
					<?php 
        checked( $settings['cart_back_target'], 'shop' );
        ?>
				/>
				<?php 
        esc_html_e( 'Page boutique', 'chic-checkout' );
        ?>
			</label>
			<label style="display:flex;align-items:center;gap:8px;">
				<input
					type="radio"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_back_target]"
					value="custom"
					<?php 
        checked( $settings['cart_back_target'], 'custom' );
        ?>
				/>
				<?php 
        esc_html_e( 'URL personnalisée :', 'chic-checkout' );
        ?>
				<input
					type="url"
					class="regular-text"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_back_custom_url]"
					value="<?php 
        echo esc_attr( $settings['cart_back_custom_url'] );
        ?>"
					placeholder="https://..."
				/>
			</label>
		</p>
		<?php 
    }

    public function render_cart_promo_code_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_promo_code_enabled]"
				value="1"
				<?php 
        checked( $settings['cart_promo_code_enabled'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher le champ code promo sur le panier', 'chic-checkout' );
        ?>
		</label>
		<?php 
    }

    /** Fichier qu'Apple lit pour autoriser Apple Pay sur un domaine (déposé par le plugin Stripe). */
    const APPLE_PAY_DOMAIN_FILE = '.well-known/apple-developer-merchantid-domain-association';

    public function render_cart_payment_icons_field() : void {
        $settings = self::stored();
        ?>
		<label style="display:block;margin-bottom:10px;">
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_payment_icons_enabled]"
				value="1"
				<?php 
        checked( $settings['cart_payment_icons_enabled'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher les icônes des moyens de paiement activés sous le bouton "Aller au paiement"', 'chic-checkout' );
        ?>
		</label>
		<p>
			<label style="display:block;">
				<?php 
        esc_html_e( 'Texte affiché au-dessus des icônes :', 'chic-checkout' );
        ?><br>
				<input
					type="text"
					class="regular-text"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_payment_icons_label]"
					value="<?php 
        echo esc_attr( $settings['cart_payment_icons_label'] );
        ?>"
					placeholder="<?php 
        esc_attr_e( 'ex. Paiement 100% sécurisé', 'chic-checkout' );
        ?>"
				/>
			</label>
			<p class="description">
				<?php 
        esc_html_e( 'Laisser vide pour n\'afficher que les icônes, sans texte.', 'chic-checkout' );
        ?>
				<?php 
        esc_html_e( 'Les icônes proviennent directement des moyens de paiement WooCommerce activés (Réglages > Paiements) — rien à téléverser ici. Un moyen de paiement sans icône configurée n\'apparaît simplement pas.', 'chic-checkout' );
        ?>
			</p>
		</p>
		<?php 
    }

    public function render_cart_upsell_field() : void {
        $settings = self::stored();
        ?>
		<label style="display:block;margin-bottom:10px;">
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_upsell_enabled]"
				value="1"
				<?php 
        checked( $settings['cart_upsell_enabled'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher un carousel de produits recommandés sur le panier', 'chic-checkout' );
        ?>
		</label>
		<p>
			<label style="display:block;">
				<?php 
        esc_html_e( 'Titre du bloc :', 'chic-checkout' );
        ?><br>
				<input
					type="text"
					class="regular-text"
					name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[cart_upsell_title]"
					value="<?php 
        echo esc_attr( $settings['cart_upsell_title'] );
        ?>"
				/>
			</label>
		</p>
		<?php 
        if ( !Edition::has_pro_code() ) {
            ?>
			<p class="description">
				<?php 
            esc_html_e( 'Produits proposés : les ventes croisées (onglet Produits liés de la fiche produit) des articles du panier, puis les produits liés de WooCommerce (même catégorie ou étiquette), 4 au maximum. Un produit déjà dans le panier n’est jamais reproposé.', 'chic-checkout' );
            ?>
			</p>
			<?php 
        }
    }

    /**
     * Canal de support : forum WordPress.org et portail d'idées (version gratuite), e-mail
     * prioritaire (version Pro), prestataire (marque blanche). Version gratuite : guide
     * pas à pas pour installer la version Pro après l'achat (jamais installée
     * automatiquement).
     */
    public function render_support_contact_field() : void {
        if ( Edition::is_whitelabeled() ) {
            echo '<p style="margin-top:0;">' . esc_html__( 'Pour toute question sur votre checkout, contactez votre prestataire.', 'chic-checkout' ) . '</p>';
            return;
        }
        if ( Edition::is_pro() ) {
            ?>
			<p style="margin-top:0;">
				<?php 
            esc_html_e( 'Support prioritaire par e-mail :', 'chic-checkout' );
            ?>
				<a href="<?php 
            echo esc_url( 'mailto:' . Edition::SUPPORT_EMAIL );
            ?>"><?php 
            echo esc_html( Edition::SUPPORT_EMAIL );
            ?></a>
			</p>
			<p class="description"><?php 
            esc_html_e( 'Pour un bug, le formulaire ci-dessous joint automatiquement les versions de votre boutique et vos captures.', 'chic-checkout' );
            ?></p>
			<?php 
            return;
        }
        ?>
		<p style="margin-top:0;">
			<?php 
        esc_html_e( 'Une question, un problème ? Posez-la sur le forum de support de l’extension : nous y répondons, et la réponse peut aider d’autres boutiques.', 'chic-checkout' );
        ?>
		</p>
		<p>
			<a class="button" href="<?php 
        echo esc_url( Edition::FORUM_URL );
        ?>" target="_blank" rel="noopener noreferrer"><?php 
        esc_html_e( 'Forum de support WordPress.org', 'chic-checkout' );
        ?><span class="screen-reader-text"> <?php 
        esc_html_e( '(nouvel onglet)', 'chic-checkout' );
        ?></span></a>
			<?php 
        if ( '' !== (string) CHIC_CHECKOUT_FEEDBACK_URL ) {
            ?>
				<a class="button" href="<?php 
            echo esc_url( (string) CHIC_CHECKOUT_FEEDBACK_URL );
            ?>" target="_blank" rel="noopener noreferrer"><?php 
            esc_html_e( 'Proposer une idée', 'chic-checkout' );
            ?><span class="screen-reader-text"> <?php 
            esc_html_e( '(nouvel onglet)', 'chic-checkout' );
            ?></span></a>
			<?php 
        }
        ?>
		</p>
		<?php 
        if ( !Edition::has_pro_code() ) {
            ?>
			<details style="margin-top:16px;max-width:720px;">
				<summary style="cursor:pointer;font-weight:600;"><?php 
            esc_html_e( 'Vous avez acheté la version Pro ? Installez-la en 4 étapes', 'chic-checkout' );
            ?></summary>
				<ol>
					<li><?php 
            esc_html_e( 'Téléchargez le zip de la version Pro : lien dans l’e-mail de confirmation d’achat, ou dans votre espace client Freemius (users.freemius.com).', 'chic-checkout' );
            ?></li>
					<li><?php 
            esc_html_e( 'Dans Extensions → Ajouter une extension → Téléverser une extension, choisissez ce zip, puis « Installer maintenant ».', 'chic-checkout' );
            ?></li>
					<li><?php 
            esc_html_e( 'Activez « Chic-checkout for WooCommerce Pro » : la version gratuite est désactivée automatiquement, vos réglages sont repris tels quels et le checkout reste en ligne.', 'chic-checkout' );
            ?></li>
					<li><?php 
            esc_html_e( 'Saisissez votre clé de licence si elle vous est demandée (même e-mail), puis supprimez la version gratuite depuis la page Extensions.', 'chic-checkout' );
            ?></li>
				</ol>
			</details>
		<?php 
        }
        ?>
		<?php 
    }

    /**
     * Écrase, uniquement sur cette page (voir Seo::class), ce que Yoast SEO / Rank
     * Math auraient calculé pour l'indexation — les filtres dédiés qu'ils exposent
     * eux-mêmes pour ce genre de cas sont utilisés côté Seo::class, pas de conflit ni
     * de balise robots dupliquée.
     */
    public function render_seo_robots_field() : void {
        $settings = self::stored();
        ?>
		<label style="display:block;margin-bottom:6px;">
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[seo_noindex]"
				value="1"
				<?php 
        checked( $settings['seo_noindex'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Ne pas indexer cette page (noindex)', 'chic-checkout' );
        ?>
		</label>
		<label style="display:block;">
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[seo_nofollow]"
				value="1"
				<?php 
        checked( $settings['seo_nofollow'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Ne pas suivre les liens présents sur cette page (nofollow)', 'chic-checkout' );
        ?>
		</label>
		<p class="description">
			<?php 
        esc_html_e( "Recommandé (coché par défaut) : le contenu de cette page est propre à chaque client et n'a jamais rien à faire dans des résultats de recherche.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_seo_meta_title_field() : void {
        $settings = self::stored();
        ?>
		<input
			type="text"
			class="large-text"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[seo_meta_title]"
			value="<?php 
        echo esc_attr( $settings['seo_meta_title'] );
        ?>"
			placeholder="<?php 
        esc_attr_e( 'Laisser vide pour garder le titre généré automatiquement', 'chic-checkout' );
        ?>"
		/>
		<p class="description">
			<?php 
        esc_html_e( "Remplace la balise <title> (onglet du navigateur, résultats de recherche) sur cette page uniquement.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_seo_meta_description_field() : void {
        $settings = self::stored();
        ?>
		<textarea
			class="large-text"
			rows="3"
			name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[seo_meta_description]"
			placeholder="<?php 
        esc_attr_e( 'Laisser vide pour garder la meta description générée automatiquement', 'chic-checkout' );
        ?>"
		><?php 
        echo esc_textarea( $settings['seo_meta_description'] );
        ?></textarea>
		<p class="description">
			<?php 
        esc_html_e( 'Sans effet ici si la page est réglée en noindex ci-dessus : les moteurs de recherche ignorent la meta description des pages non indexées.', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    /**
     * Rien n'est envoyé à GA4 tant que cette case n'est pas cochée explicitement —
     * contrairement au SEO (bénéfique par défaut), l'admin est seul à savoir si son
     * site a un bandeau de consentement cookies à respecter avant tout envoi.
     */
    public function render_tracking_ga4_field() : void {
        $settings = self::stored();
        ?>
		<p style="margin-top:0;">
			<?php 
        esc_html_e( 'Envoyer les événements du funnel de commande à Google Analytics 4', 'chic-checkout' );
        ?>
			<?php 
        $this->render_integration_state( 'ga4' );
        ?>
		</p>
		<p class="description">
			<?php 
        esc_html_e( "Ce plugin ne charge pas GA4 lui-même : il pousse les événements dans window.dataLayer (Google Tag Manager) et les envoie aussi directement à window.gtag si déjà présent sur le site (GA4 chargé sans GTM) — à vous de configurer GA4 comme d'habitude, ces événements viennent simplement s'y brancher.", 'chic-checkout' );
        ?>
		</p>
		<p class="description">
			<?php 
        esc_html_e( "Ces événements respectent automatiquement le consentement du visiteur (catégorie \"statistics\") si WP Consent API est installé — voir l'encart en haut de cet onglet.", 'chic-checkout' );
        ?>
		</p>
		<table class="widefat" style="max-width:640px;margin-top:10px;">
			<thead>
				<tr>
					<th><?php 
        esc_html_e( 'Événement', 'chic-checkout' );
        ?></th>
					<th><?php 
        esc_html_e( 'Déclenché', 'chic-checkout' );
        ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>begin_checkout</code></td>
					<td><?php 
        esc_html_e( "À l'arrivée sur le checkout, dès que le panier est disponible", 'chic-checkout' );
        ?></td>
				</tr>
				<tr>
					<td><code>login</code> / <code>sign_up</code></td>
					<td><?php 
        esc_html_e( 'Connexion ou création de compte réussie (email/mot de passe, Google, Apple)', 'chic-checkout' );
        ?></td>
				</tr>
				<tr>
					<td><code>add_shipping_info</code></td>
					<td><?php 
        esc_html_e( 'Mode de livraison confirmé (fin de l\'étape Livraison)', 'chic-checkout' );
        ?></td>
				</tr>
				<tr>
					<td><code>add_payment_info</code></td>
					<td><?php 
        esc_html_e( "Entrée dans l'étape Paiement, moyen de paiement sélectionné", 'chic-checkout' );
        ?></td>
				</tr>
				<tr>
					<td><code>purchase</code></td>
					<td><?php 
        esc_html_e( 'Commande validée — inclut un paramètre personnalisé checkout_duration_seconds (temps écoulé depuis begin_checkout)', 'chic-checkout' );
        ?></td>
				</tr>
			</tbody>
		</table>
		<?php 
    }

    public function render_tracking_debug_field() : void {
        $settings = self::stored();
        ?>
		<label>
			<input
				type="checkbox"
				name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[tracking_debug_mode]"
				value="1"
				<?php 
        checked( $settings['tracking_debug_mode'] );
        ?>
			/>
			<?php 
        esc_html_e( 'Afficher chaque événement envoyé dans la console du navigateur', 'chic-checkout' );
        ?>
		</label>
		<p class="description">
			<?php 
        esc_html_e( "Utile pour vérifier la configuration (GTM, gtag.js) sans avoir besoin d'ouvrir GA4 DebugView. À décocher une fois la mise en place terminée.", 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    /**
     * État d'une intégration rappelé dans l'onglet où se trouve sa configuration ;
     * l'interrupteur lui-même vit uniquement dans l'onglet Intégrations.
     */
    private function render_integration_state( string $key ) : void {
        if ( Integrations::is_enabled( $key ) ) {
            echo ' <span style="color:#008a20;font-weight:400;">● ' . esc_html__( 'activée', 'chic-checkout' ) . '</span>';
        } else {
            echo ' <span style="color:#787c82;font-weight:400;">○ ' . esc_html__( 'désactivée', 'chic-checkout' ) . '</span>';
        }
        echo ' <a href="#" class="chic-goto-tab" data-tab="integrations" style="font-weight:400;">' . esc_html__( "Gérer dans l'onglet Intégrations", 'chic-checkout' ) . '</a>';
    }

    public function render_integrations_field() : void {
        $settings = self::stored();
        ?>
		<p class="description" style="margin-top:0;max-width:720px;">
			<?php 
        esc_html_e( 'Une intégration ne fonctionne que si son plugin est installé et activé, et que son interrupteur est activé ici.', 'chic-checkout' );
        ?>
		</p>
		<table class="widefat striped" style="max-width:860px;margin-top:10px;">
			<tbody>
			<?php 
        foreach ( Integrations::definitions() as $key => $definition ) {
            ?>
				<?php 
            $detected = Integrations::is_detected( $key );
            ?>
				<tr>
					<td style="width:36px;">
						<input
							type="checkbox"
							id="chic-integration-<?php 
            echo esc_attr( $key );
            ?>"
							name="<?php 
            echo esc_attr( self::OPTION_KEY );
            ?>[integrations][<?php 
            echo esc_attr( $key );
            ?>]"
							value="1"
							<?php 
            checked( !empty( $settings['integrations'][$key] ) );
            ?>
							<?php 
            disabled( !$detected );
            ?>
						/>
					</td>
					<td>
						<label for="chic-integration-<?php 
            echo esc_attr( $key );
            ?>"><strong><?php 
            echo esc_html( $definition['label'] );
            ?></strong></label>
						<?php 
            if ( $definition['plugin'] ) {
                ?>
							<?php 
                if ( $detected ) {
                    ?>
								<span style="color:#008a20;margin-left:8px;">● <?php 
                    esc_html_e( 'Plugin détecté', 'chic-checkout' );
                    ?></span>
							<?php 
                } elseif ( !empty( $definition['slug'] ) ) {
                    ?>
								<span style="margin-left:8px;"><?php 
                    echo Plugin_Links::render( $definition['slug'] );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans Plugin_Links::render().
                    ?></span>
							<?php 
                } else {
                    ?>
								<span style="color:#787c82;margin-left:8px;">○ <?php 
                    esc_html_e( 'Plugin non installé', 'chic-checkout' );
                    ?></span>
							<?php 
                }
                ?>
						<?php 
            } else {
                ?>
							<span style="color:#787c82;margin-left:8px;"><?php 
                esc_html_e( 'Aucun plugin requis', 'chic-checkout' );
                ?></span>
						<?php 
            }
            ?>
						<p class="description" style="margin:4px 0 0;"><?php 
            echo esc_html( $definition['description'] );
            ?></p>
					</td>
				</tr>
			<?php 
        }
        ?>
			</tbody>
		</table>
		<?php 
    }

    /**
     * Lecture seule : les moyens de paiement se gèrent dans WooCommerce, ce tableau dit
     * seulement lesquels le checkout sait encaisser (voir Payment_Support).
     */
    public function render_payment_support_field() : void {
        $gateways = ( function_exists( 'WC' ) && WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array() );
        $enabled = array_filter( $gateways, static function ( $gateway ) {
            return $gateway instanceof \WC_Payment_Gateway && 'yes' === $gateway->enabled;
        } );
        ?>
		<p class="description" style="margin-top:0;max-width:720px;">
			<?php 
        esc_html_e( 'Pris en charge : la carte bancaire, Apple Pay, Google Pay, Klarna, Wero et Alma (via « Universal Payment Method », Alma coché) de Payment Plugins for Stripe WooCommerce, le bouton PayPal de Payment Plugins for PayPal WooCommerce, myCred, et les moyens de paiement sans formulaire (virement, chèque, paiement à la livraison, redirection vers la banque). Les autres sont masqués dans le checkout.', 'chic-checkout' );
        ?>
		</p>
		<p class="description" style="max-width:720px;">
			<?php 
        esc_html_e( 'Attention aux homonymes : les extensions officielles « WooCommerce Stripe Gateway », « WooPayments » et « WooCommerce PayPal Payments » ne sont pas compatibles. Installez celles de Payment Plugins ci-dessous.', 'chic-checkout' );
        ?>
		</p>
		<table class="widefat striped" style="max-width:860px;margin-top:10px;">
			<tbody>
				<?php 
        $compatible = array(
            Payment_Support::STRIPE_PLUGIN => __( 'Carte bancaire, Apple Pay, Google Pay, Klarna, Wero, Alma', 'chic-checkout' ),
            Payment_Support::PAYPAL_PLUGIN => __( 'Bouton PayPal', 'chic-checkout' ),
        );
        ?>
				<?php 
        foreach ( $compatible as $slug => $methods ) {
            ?>
					<tr>
						<td>
							<strong><?php 
            echo esc_html( Plugin_Links::name( $slug ) );
            ?></strong>
							<br /><span class="description"><?php 
            echo esc_html( $methods );
            ?></span>
						</td>
						<td><?php 
            echo Plugin_Links::render( $slug );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans Plugin_Links::render().
            ?></td>
					</tr>
				<?php 
        }
        ?>
			</tbody>
		</table>
		<h4 style="margin:1.5em 0 0;"><?php 
        esc_html_e( 'Moyens de paiement activés dans WooCommerce', 'chic-checkout' );
        ?></h4>
		<?php 
        if ( !$enabled ) {
            ?>
			<p><?php 
            esc_html_e( 'Aucun moyen de paiement activé dans WooCommerce.', 'chic-checkout' );
            ?></p>
		<?php 
        } else {
            ?>
			<table class="widefat striped" style="max-width:860px;margin-top:10px;">
				<tbody>
				<?php 
            foreach ( $enabled as $gateway ) {
                ?>
					<?php 
                $supported = Payment_Support::is_supported( $gateway );
                $plugin = ( $supported ? '' : Payment_Support::recommended_plugin( $gateway ) );
                ?>
					<tr>
						<td><strong><?php 
                echo esc_html( Payment_Support::label( $gateway ) );
                ?></strong> <code><?php 
                echo esc_html( $gateway->id );
                ?></code></td>
						<td>
							<?php 
                if ( $supported ) {
                    ?>
								<span style="color:#008a20;">✓ <?php 
                    esc_html_e( 'Compatible', 'chic-checkout' );
                    ?></span>
							<?php 
                } else {
                    ?>
								<span style="color:#b32d2e;">⚠ <?php 
                    esc_html_e( 'Masqué dans le checkout', 'chic-checkout' );
                    ?></span>
								<?php 
                    if ( $plugin ) {
                        ?>
									<br />
									<?php 
                        printf( 
                            /* translators: %s: plugin name */
                            esc_html__( 'Installez %s pour proposer ce moyen de paiement.', 'chic-checkout' ),
                            '<strong>' . esc_html( Plugin_Links::name( $plugin ) ) . '</strong>'
                         );
                        echo '<br />' . Plugin_Links::render( $plugin );
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans Plugin_Links::render().
                        ?>
								<?php 
                    }
                    ?>
							<?php 
                }
                ?>
						</td>
					</tr>
				<?php 
            }
            ?>
				</tbody>
			</table>
		<?php 
        }
        ?>
		<p>
			<a href="<?php 
        echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
        ?>"><?php 
        esc_html_e( 'Gérer les moyens de paiement dans WooCommerce', 'chic-checkout' );
        ?></a>
		</p>
		<?php 
    }

    /**
     * Onglet Nouveautés : versions publiées, la plus récente d'abord, avec la version
     * installée repérée et les plus récentes signalées « Disponible ».
     */
    public function render_releases_field() : void {
        $releases = Releases::all();
        $installed = CHIC_CHECKOUT_VERSION;
        ?>
		<p class="description" style="margin-top:0;max-width:720px;">
			<?php 
        esc_html_e( 'Chic-checkout s’améliore chaque mois. Retrouvez ici chaque version, sa date et ce qu’elle apporte.', 'chic-checkout' );
        ?>
		</p>
		<?php 
        if ( !$releases ) {
            ?>
			<p><?php 
            esc_html_e( 'Les notes de version ne sont pas disponibles pour le moment.', 'chic-checkout' );
            ?></p>
			<?php 
            return;
        }
        ?>
		<div style="max-width:760px;margin-top:12px;">
			<?php 
        foreach ( $releases as $index => $release ) {
            ?>
				<?php 
            $newer = version_compare( $release['version'], $installed, '>' );
            $is_installed = $release['version'] === $installed;
            if ( 6 === $index ) {
                echo '<details style="margin-top:4px;"><summary style="cursor:pointer;margin-bottom:12px;">' . esc_html__( 'Versions précédentes', 'chic-checkout' ) . '</summary>';
            }
            ?>
				<div style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:12px 16px;margin-bottom:10px;">
					<p style="margin:0 0 6px;">
						<strong>
							<?php 
            /* translators: %s: version number */
            echo esc_html( sprintf( __( 'Version %s', 'chic-checkout' ), $release['version'] ) );
            ?>
						</strong>
						<?php 
            if ( $release['date'] ) {
                ?>
							<span style="color:#646970;"> · <?php 
                echo esc_html( date_i18n( (string) get_option( 'date_format' ), (int) strtotime( $release['date'] . ' 12:00:00' ) ) );
                ?></span>
						<?php 
            }
            ?>
						<?php 
            if ( $is_installed ) {
                ?>
							<span style="margin-left:8px;color:#008a20;">● <?php 
                esc_html_e( 'Installée', 'chic-checkout' );
                ?></span>
						<?php 
            } elseif ( $newer ) {
                ?>
							<span style="margin-left:8px;color:#996800;">● <?php 
                esc_html_e( 'Disponible', 'chic-checkout' );
                ?></span>
							<a href="<?php 
                echo esc_url( admin_url( 'update-core.php' ) );
                ?>" style="margin-left:6px;"><?php 
                esc_html_e( 'Mettre à jour', 'chic-checkout' );
                ?></a>
						<?php 
            }
            ?>
					</p>
					<ul style="list-style:disc;margin:0 0 0 20px;">
						<?php 
            foreach ( $release['notes'] as $note ) {
                ?>
							<li style="margin:2px 0;"><?php 
                echo esc_html( $note );
                ?></li>
						<?php 
            }
            ?>
					</ul>
				</div>
			<?php 
        }
        ?>
			<?php 
        if ( count( $releases ) > 6 ) {
            ?>
				</details>
			<?php 
        }
        ?>
		</div>
		<?php 
    }

    /** Page historique (register_settings) → panneau où afficher un champ non listé dans PANEL_FIELDS. */
    const FALLBACK_PANELS = array(
        'appearance' => 'appearance',
        'steps'      => 'checkout-general',
        'back'       => 'checkout-general',
        'login'      => 'checkout-login',
        'shipping'   => 'checkout-shipping',
        'cart'       => 'cart-general',
        'thankyou'   => 'thankyou-general',
    );

    /**
     * Page et sous-onglet à ouvrir, d'après ?chic-tab=…&chic-sub=… (anciens noms d'onglet
     * acceptés, voir LEGACY_TABS). Par défaut : Panier → Général.
     *
     * @return array{0: string, 1: string}
     */
    private static function requested_tab( array $layout ) : array {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- simple navigation.
        $tab = ( isset( $_GET['chic-tab'] ) ? sanitize_key( wp_unslash( $_GET['chic-tab'] ) ) : '' );
        $sub = ( isset( $_GET['chic-sub'] ) ? sanitize_key( wp_unslash( $_GET['chic-sub'] ) ) : '' );
        // phpcs:enable
        if ( isset( self::LEGACY_TABS[$tab] ) ) {
            list( $tab, $sub ) = self::LEGACY_TABS[$tab];
        }
        // Réassurance était un sous-onglet de Panier avant d'avoir son propre onglet.
        if ( 'cart' === $tab && 'reassurance' === $sub ) {
            list( $tab, $sub ) = array('reassurance', '');
        }
        $items = array();
        foreach ( $layout as $group ) {
            $items += $group['items'];
        }
        if ( !isset( $items[$tab] ) ) {
            $tab = 'cart';
        }
        $subs = array_keys( $items[$tab]['subs'] );
        if ( $subs && !in_array( $sub, $subs, true ) ) {
            $sub = $subs[0];
        }
        return array($tab, ( $subs ? $sub : '' ));
    }

    /**
     * Champs de chaque panneau. Les champs de la page historique d'un panneau générique
     * (footer, seo…) y restent ; ceux des pages réparties sont placés d'après PANEL_FIELDS,
     * et un champ oublié dans PANEL_FIELDS s'affiche quand même (FALLBACK_PANELS).
     *
     * @return array<string, array<int, array>>
     */
    private static function panel_fields() : array {
        global $wp_settings_fields;
        $by_id = array();
        $panels = array();
        foreach ( (array) $wp_settings_fields as $page => $sections ) {
            if ( !str_starts_with( (string) $page, self::PAGE_SLUG . '_' ) ) {
                continue;
            }
            $legacy = substr( (string) $page, strlen( self::PAGE_SLUG ) + 1 );
            foreach ( (array) $sections as $fields ) {
                foreach ( (array) $fields as $id => $field ) {
                    if ( isset( self::FALLBACK_PANELS[$legacy] ) ) {
                        $by_id[$id] = array($field, self::FALLBACK_PANELS[$legacy]);
                    } else {
                        $panels[$legacy][] = $field;
                    }
                }
            }
        }
        foreach ( self::PANEL_FIELDS as $panel => $ids ) {
            foreach ( $ids as $id ) {
                if ( isset( $by_id[$id] ) ) {
                    $panels[$panel][] = $by_id[$id][0];
                    unset($by_id[$id]);
                }
            }
        }
        foreach ( $by_id as $entry ) {
            $panels[$entry[1]][] = $entry[0];
        }
        return $panels;
    }

    /** Lignes de formulaire, comme do_settings_fields(). */
    private static function render_fields( array $fields ) : void {
        foreach ( $fields as $field ) {
            $args = (array) ($field['args'] ?? array());
            $class = ( empty( $args['class'] ) ? '' : ' class="' . esc_attr( $args['class'] ) . '"' );
            echo '<tr' . $class . '><th scope="row">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé ci-dessus.
            if ( !empty( $args['label_for'] ) ) {
                echo '<label for="' . esc_attr( $args['label_for'] ) . '">' . wp_kses_post( $field['title'] ) . '</label>';
            } else {
                echo wp_kses_post( $field['title'] );
            }
            echo '</th><td>';
            call_user_func( $field['callback'], $args );
            echo '</td></tr>';
        }
    }

    /** Bandeaux d'information propres à certains panneaux. */
    private static function render_panel_notice( string $panel ) : void {
        if ( 'checkout-login' === $panel ) {
            ?>
			<div class="notice notice-info inline" style="margin:0 0 1em;padding:12px;">
				<p style="margin:0;">
					<?php 
            esc_html_e( 'Le "checkout invité" (proposer de continuer sans créer de compte) est géré par le réglage WooCommerce existant, pas par ce plugin.', 'chic-checkout' );
            ?>
					<?php 
            esc_html_e( 'Pour le désactiver, rendez-vous dans', 'chic-checkout' );
            ?>
					<a href="<?php 
            echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) );
            ?>">
						<?php 
            esc_html_e( 'WooCommerce → Réglages → Comptes et confidentialité', 'chic-checkout' );
            ?>
					</a>.
				</p>
			</div>
			<?php 
        } elseif ( 'appearance' === $panel && !Edition::is_pro() ) {
            ?>
			<p class="description" style="margin:0 0 1em;"><?php 
            esc_html_e( 'Le panier, le checkout et la page de confirmation reprennent les couleurs et les polices de votre thème actif (theme.json).', 'chic-checkout' );
            ?></p>
			<?php 
        } elseif ( 'reassurance' === $panel ) {
            ?>
			<p class="description" style="margin:0 0 1em;"><?php 
            esc_html_e( 'Les blocs de réassurance s’affichent sur le panier et sur le checkout.', 'chic-checkout' );
            ?></p>
			<?php 
        } elseif ( 'seo' === $panel && (defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' )) ) {
            ?>
			<div class="notice notice-info inline" style="margin:0 0 1em;padding:12px;">
				<p style="margin:0;">
					<?php 
            if ( defined( 'WPSEO_VERSION' ) ) {
                ?>
						<?php 
                esc_html_e( 'Yoast SEO détecté : les réglages ci-dessous remplacent, sur cette page uniquement, ce que Yoast aurait généré pour elle.', 'chic-checkout' );
                ?>
					<?php 
            } else {
                ?>
						<?php 
                esc_html_e( 'Rank Math détecté : les réglages ci-dessous remplacent, sur cette page uniquement, ce que Rank Math aurait généré pour elle.', 'chic-checkout' );
                ?>
					<?php 
            }
            ?>
				</p>
			</div>
			<?php 
        } elseif ( 'tracking' === $panel ) {
            ?>
			<div class="notice notice-<?php 
            echo ( function_exists( 'wp_has_consent' ) ? 'info' : 'warning' );
            ?> inline" style="margin:0 0 1em;padding:12px;">
				<p style="margin:0;">
					<?php 
            if ( function_exists( 'wp_has_consent' ) ) {
                ?>
						<?php 
                esc_html_e( "WP Consent API détecté : GA4, Google Places (autocomplétion d'adresse) et la connexion sociale (Google/Apple) ne se chargeront qu'après consentement du visiteur (catégories \"statistics\"/\"marketing\"), à condition que votre bandeau de consentement s'y branche (CookieYes le fait nativement).", 'chic-checkout' );
                ?>
					<?php 
            } else {
                ?>
						<?php 
                printf( 
                    /* translators: %s: lien vers la page d'installation du plugin WP Consent API */
                    esc_html__( "Aucune API de consentement détectée. Sans elle, GA4, Google Places et la connexion sociale (Google/Apple) se chargeront sans vérification de consentement dès qu'ils sont configurés — à réserver aux sites hors obligation RGPD, ou installez %s (compatible avec la plupart des bandeaux de consentement, dont CookieYes) pour que ce plugin respecte le choix du visiteur.", 'chic-checkout' ),
                    '<a href="' . esc_url( admin_url( 'plugin-install.php?s=wp-consent-api&tab=search&type=term' ) ) . '" target="_blank" rel="noopener noreferrer">WP Consent API</a>'
                 );
                ?>
					<?php 
            }
            ?>
				</p>
			</div>
			<?php 
        }
    }

    public static function tab_url( string $tab, string $sub = '' ) : string {
        $args = array(
            'page'     => self::PAGE_SLUG,
            'chic-tab' => $tab,
        );
        if ( '' !== $sub ) {
            $args['chic-sub'] = $sub;
        }
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    public function render_page() : void {
        $layout = self::layout();
        $panels = self::panel_fields();
        list( $active_tab, $active_sub ) = self::requested_tab( $layout );
        ?>
		<style>
			.chic-settings__layout { display: flex; gap: 24px; align-items: flex-start; margin-top: 16px; }
			.chic-settings__nav { flex: 0 0 210px; position: sticky; top: 46px; }
			.chic-settings__group { margin: 18px 0 6px; padding: 0 12px; font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #646970; }
			.chic-settings__group:first-child { margin-top: 0; }
			.chic-settings__nav ul { margin: 0; }
			.chic-settings__nav li { margin: 0; }
			.chic-settings__nav a { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 12px; border-left: 3px solid transparent; border-radius: 0 4px 4px 0; color: #1d2327; text-decoration: none; transition: background-color .15s; }
			.chic-settings__nav a:hover { background: #fff; color: #2271b1; }
			.chic-settings__nav a:focus { box-shadow: 0 0 0 2px #2271b1; outline: none; }
			.chic-settings__nav a.is-active { background: #fff; border-left-color: #2271b1; font-weight: 600; }
			.chic-settings__main { flex: 1; min-width: 0; }
			.chic-settings__title { margin: 0 0 12px; font-size: 1.4em; }
			.chic-settings__main .chic-subtabs { margin-bottom: 8px; }
			.chic-settings__save { position: sticky; bottom: 0; z-index: 10; margin-top: 8px; padding: 10px 0; background: #f0f0f1; border-top: 1px solid #dcdcde; }
			.chic-settings__save .submit { margin: 0; padding: 0; }
			.chic-news-badge { display: inline-block; padding: 1px 7px; border-radius: 9px; background: #d63638; color: #fff; font-size: 11px; font-weight: 400; line-height: 16px; }
			@media (max-width: 782px) {
				.chic-settings__layout { flex-direction: column; }
				.chic-settings__nav { position: static; flex-basis: auto; width: 100%; }
			}
		</style>
		<div class="wrap chic-settings">
			<h1><?php 
        esc_html_e( 'Chic-checkout', 'chic-checkout' );
        ?></h1>

			<form method="post" action="options.php">
				<?php 
        settings_fields( self::PAGE_SLUG );
        ?>
				<div class="chic-settings__layout">
					<nav class="chic-settings__nav" aria-label="<?php 
        esc_attr_e( 'Réglages Chic-checkout', 'chic-checkout' );
        ?>">
						<?php 
        foreach ( $layout as $group ) {
            ?>
							<p class="chic-settings__group"><?php 
            echo esc_html( $group['label'] );
            ?></p>
							<ul>
								<?php 
            foreach ( $group['items'] as $tab => $item ) {
                ?>
									<li>
										<a
											href="<?php 
                echo esc_url( self::tab_url( $tab ) );
                ?>"
											class="chic-nav-item<?php 
                echo ( $tab === $active_tab ? ' is-active' : '' );
                ?>"
											data-tab="<?php 
                echo esc_attr( $tab );
                ?>"
											<?php 
                echo ( $tab === $active_tab ? 'aria-current="page"' : '' );
                ?>
										>
											<span><?php 
                echo esc_html( $item['label'] );
                ?></span>
											<?php 
                if ( 'news' === $tab && Releases::has_unseen() ) {
                    ?>
												<span class="chic-news-badge"><?php 
                    esc_html_e( 'Nouveau', 'chic-checkout' );
                    ?></span>
											<?php 
                }
                ?>
											<?php 
                ?>
										</a>
									</li>
								<?php 
            }
            ?>
							</ul>
						<?php 
        }
        ?>
					</nav>

					<div class="chic-settings__main">
						<fieldset>
						<?php 
        foreach ( $layout as $group ) {
            ?>
							<?php 
            foreach ( $group['items'] as $tab => $item ) {
                ?>
								<section class="chic-tab-page" data-page="<?php 
                echo esc_attr( $tab );
                ?>"<?php 
                echo ( $tab === $active_tab ? '' : ' hidden' );
                ?>>
									<h2 class="chic-settings__title"><?php 
                echo esc_html( $item['label'] );
                ?></h2>
									<?php 
                if ( $item['subs'] ) {
                    ?>
										<nav class="nav-tab-wrapper chic-subtabs" aria-label="<?php 
                    echo esc_attr( $item['label'] );
                    ?>">
											<?php 
                    foreach ( $item['subs'] as $sub => $sub_label ) {
                        ?>
												<?php 
                        $current = $tab === $active_tab && $sub === $active_sub;
                        ?>
												<a
													href="<?php 
                        echo esc_url( self::tab_url( $tab, $sub ) );
                        ?>"
													class="nav-tab chic-subtab<?php 
                        echo ( $current || $tab !== $active_tab && array_key_first( $item['subs'] ) === $sub ? ' nav-tab-active' : '' );
                        ?>"
													data-tab="<?php 
                        echo esc_attr( $tab );
                        ?>"
													data-sub="<?php 
                        echo esc_attr( $sub );
                        ?>"
												><?php 
                        echo esc_html( $sub_label );
                        ?></a>
											<?php 
                    }
                    ?>
										</nav>
									<?php 
                }
                ?>
									<?php 
                $keys = ( $item['subs'] ? array_keys( $item['subs'] ) : array('') );
                foreach ( $keys as $sub ) {
                    $panel = ( '' === $sub ? $tab : $tab . '-' . $sub );
                    $visible = '' === $sub || (( $tab === $active_tab ? $sub === $active_sub : $sub === $keys[0] ));
                    ?>
										<div class="chic-tab-panel" data-tab-panel="<?php 
                    echo esc_attr( $panel );
                    ?>"<?php 
                    echo ( $visible ? '' : ' hidden' );
                    ?>>
											<?php 
                    self::render_panel_notice( $panel );
                    ?>
											<table class="form-table" role="presentation">
												<?php 
                    self::render_fields( $panels[$panel] ?? array() );
                    ?>
											</table>
											<?php 
                    if ( !Edition::has_pro_code() ) {
                        Pro_Upsell::render( $panel );
                    }
                    ?>
										</div>
									<?php 
                }
                ?>
								</section>
							<?php 
            }
            ?>
						<?php 
        }
        ?>
						</fieldset>

						<div class="chic-settings__save"><?php 
        submit_button(
            null,
            'primary',
            'submit',
            false
        );
        ?></div>
					</div>
				</div>
			</form>
		</div>
		<?php 
    }

}
