<?php

/**
 * Enqueue du bundle React (dev server Vite ou build de prod).
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Assets {
    const HANDLE = 'chic-checkout-app';

    /**
     * Styles/scripts par défaut de WooCommerce et du thème actif à retirer sur notre
     * page checkout : on remplace entièrement l'UI, on ne veut aucun style ni JS
     * du formulaire classique (évite les collisions visuelles et le code mort).
     */
    const DEFAULT_STYLE_HANDLES = array(
        'woocommerce-general',
        'woocommerce-layout',
        'woocommerce-smallscreen',
        'woocommerce-blocktheme',
        'wc-blocks-style',
        'select2'
    );

    const DEFAULT_SCRIPT_HANDLES = array(
        'wc-add-to-cart',
        'selectWoo',
        'wc-js-cookie',
        'woocommerce',
        'wc-country-select',
        'wc-address-i18n',
        'wc-custom-place-order-button',
        'wc-checkout',
        'sourcebuster-js',
        'wc-order-attribution'
    );

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue') );
        add_action( 'wp_enqueue_scripts', array($this, 'dequeue_default_assets'), 9999 );
        add_action( 'wp_head', array($this, 'maybe_print_dev_preamble'), 1 );
    }

    public function dequeue_default_assets() : void {
        if ( 'checkout' !== Compat::current_app() || !Compat::should_render( 'checkout' ) ) {
            return;
        }
        foreach ( self::DEFAULT_STYLE_HANDLES as $handle ) {
            wp_dequeue_style( $handle );
        }
        foreach ( self::DEFAULT_SCRIPT_HANDLES as $handle ) {
            wp_dequeue_script( $handle );
        }
    }

    public function enqueue() : void {
        if ( 'checkout' !== Compat::current_app() || !Compat::should_render( 'checkout' ) ) {
            return;
        }
        if ( $this->is_dev_server_running() ) {
            // type="module" nécessaire uniquement ici : le dev server Vite sert du vrai
            // ESM (HMR, /@vite/client...). Le bundle de prod (enqueue_build_assets) est
            // désormais compilé en script classique (voir vite.config.ts et
            // scripts/build.mjs) — au moins un mécanisme sur l'hébergement de production
            // réécrit le HTML final et supprime tout type="module" des balises <script>,
            // quelle que soit la priorité du filtre qui l'ajoute (testé jusqu'à
            // PHP_INT_MAX, sans effet). Un script classique n'a rien à préserver.
            add_filter(
                'script_loader_tag',
                array($this, 'add_module_type'),
                10,
                2
            );
            $this->enqueue_dev_assets();
        } else {
            $this->enqueue_build_assets();
        }
        // Charge le fichier de traduction JSON correspondant à la locale active (celle
        // pilotée par Polylang/WPML si présent, WordPress lui-même sinon) pour les
        // chaînes wrappées avec __() côté React (voir lib/i18n.ts) — Loco Translate sait
        // générer ce format pour un script enregistré ainsi, pas de outillage propre à
        // construire ici.
        wp_set_script_translations( self::HANDLE, 'chic-checkout', CHIC_CHECKOUT_DIR . 'languages' );
        self::enqueue_shell_styles( true );
        $this->localize_config();
        $this->maybe_enqueue_boxtal_connect();
        $this->maybe_enqueue_laposte();
    }

    /**
     * Le plugin "Boxtal Connect" (retrait en point relais) ne charge son propre JS de
     * sélection de point relais que sur le checkout classique ou le bloc Panier/
     * Checkout officiel de WooCommerce (voir sa classe
     * Shipping_Method\Parcel_Point\Controller::run(), déclenchée uniquement par
     * woocommerce_after_checkout_form / woocommerce_blocks_checkout_block_registration).
     * Notre page ne rend ni l'un ni l'autre : on réplique donc ici l'enqueue qu'il
     * ferait lui-même (mêmes handles/fichiers, déjà présents sur le site — on ne les
     * duplique pas), pour que son propre script puisse s'accrocher aux marqueurs
     * (.bw-select-parcel, .bw-parcel-point) qu'on affiche dans ShippingStep.
     *
     * Rien à configurer côté Chic-checkout : les identifiants Boxtal, le réseau de
     * points relais à afficher etc. se règlent entièrement dans Boxtal Connect
     * lui-même (WooCommerce > Réglages > Expédition), pas ici.
     */
    /**
     * Classe Frontend_Util de Boxtal / La Poste présente avec les méthodes statiques
     * qu'on appelle : une version du plugin qui les renommerait ne doit pas provoquer
     * d'erreur fatale, seulement l'absence de carte.
     */
    private static function frontend_util_usable( string $frontend_util ) : bool {
        if ( !class_exists( $frontend_util ) ) {
            return false;
        }
        foreach ( array('inject_inline_data', 'get_frontend_data', 'get_map_translations') as $method ) {
            if ( !method_exists( $frontend_util, $method ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * URL du dossier assets/ voisin de Util/ (…/BoxtalConnectWoocommerce/assets),
     * déduite de l'emplacement réel de la classe : le dossier du plugin peut avoir été
     * renommé. null si introuvable (repli sur le chemin d'installation standard).
     */
    private static function assets_url_from_class( string $frontend_util ) : ?string {
        try {
            $file = ( new \ReflectionClass($frontend_util) )->getFileName();
        } catch ( \ReflectionException $e ) {
            return null;
        }
        if ( !$file || !is_dir( dirname( $file, 2 ) . '/assets' ) ) {
            return null;
        }
        // plugins_url() prend le dossier du fichier passé : Util/ → BoxtalConnectWoocommerce/.
        return plugins_url( 'assets', dirname( $file ) );
    }

    private function maybe_enqueue_boxtal_connect() : void {
        $frontend_util = '\\Boxtal\\BoxtalConnectWoocommerce\\Util\\Frontend_Util';
        if ( !Integrations::is_enabled( 'boxtal' ) || !self::frontend_util_usable( $frontend_util ) ) {
            return;
        }
        $assets_url = self::assets_url_from_class( $frontend_util ) ?? plugins_url( 'Boxtal/BoxtalConnectWoocommerce/assets', WP_PLUGIN_DIR . '/boxtal-connect/boxtal-connect.php' );
        wp_enqueue_script(
            'bw_polyfills',
            $assets_url . '/js/polyfills.min.js',
            array(),
            null,
            false
        );
        wp_enqueue_script(
            'bw_maplibre_gl',
            $assets_url . '/js/maplibre-gl.js',
            array('bw_polyfills'),
            null,
            false
        );
        wp_enqueue_script(
            'bw_shipping',
            $assets_url . '/js/parcel-point.min.js',
            array('bw_maplibre_gl', 'bw_polyfills'),
            null,
            false
        );
        wp_enqueue_style(
            'bw_maplibre_gl',
            $assets_url . '/css/maplibre-gl.min.css',
            array(),
            null
        );
        wp_enqueue_style(
            'bw_parcel_point',
            $assets_url . '/css/parcel-point.css',
            array(),
            null
        );
        $frontend_util::inject_inline_data( 'bw_shipping', 'bwData', $frontend_util::get_frontend_data() );
        wp_localize_script( 'bw_shipping', 'translations', $frontend_util::get_map_translations() );
    }

    /**
     * Même situation que pour Boxtal Connect ci-dessus, pour le plugin "La Poste Pro
     * Expéditions" : son propre JS de sélection de point relais (Shipping_Method\
     * Parcel_Point\Controller::run()) ne s'enqueue que sur le checkout classique ou
     * le bloc Panier/Checkout officiel — jamais sur notre page. On réplique donc ici
     * le même enqueue (mêmes fichiers, déjà présents sur le site), pour que son JS
     * puisse s'accrocher aux marqueurs (.laposteproexp-select-parcel,
     * .laposteproexp-parcel-point) qu'on affiche dans ShippingStep.
     *
     * Rien à configurer côté Chic-checkout : l'appairage du compte La Poste Pro
     * Expéditions, le réseau de points relais à afficher etc. se règlent entièrement
     * dans le plugin lui-même (WooCommerce > Réglages, onglet dédié), pas ici.
     */
    private function maybe_enqueue_laposte() : void {
        $frontend_util = '\\LaPoste\\LaPosteProExpeditionsWoocommerce\\Util\\Frontend_Util';
        if ( !Integrations::is_enabled( 'laposte' ) || !self::frontend_util_usable( $frontend_util ) ) {
            return;
        }
        $assets_url = self::assets_url_from_class( $frontend_util ) ?? plugins_url( 'LaPoste/LaPosteProExpeditionsWoocommerce/assets', WP_PLUGIN_DIR . '/la-poste-pro-expeditions-woocommerce/la-poste-pro-expeditions-woocommerce.php' );
        wp_enqueue_script(
            'laposteproexp_polyfills',
            $assets_url . '/js/polyfills.min.js',
            array(),
            null,
            false
        );
        wp_enqueue_script(
            'laposteproexp_maplibre_gl',
            $assets_url . '/js/maplibre-gl.js',
            array('laposteproexp_polyfills'),
            null,
            false
        );
        wp_enqueue_script(
            'laposteproexp_shipping',
            $assets_url . '/js/parcel-point.min.js',
            array('laposteproexp_maplibre_gl', 'laposteproexp_polyfills'),
            null,
            false
        );
        wp_enqueue_style(
            'laposteproexp_maplibre_gl',
            $assets_url . '/css/maplibre-gl.min.css',
            array(),
            null
        );
        wp_enqueue_style(
            'laposteproexp_parcel_point',
            $assets_url . '/css/parcel-point.css',
            array(),
            null
        );
        $frontend_util::inject_inline_data( 'laposteproexp_shipping', 'laposteproexpData', $frontend_util::get_frontend_data() );
        wp_localize_script( 'laposteproexp_shipping', 'translations', $frontend_util::get_map_translations() );
    }

    /**
     * Doit être accroché à 'wp_head' dès la construction (pas depuis l'intérieur du
     * hook 'wp_enqueue_scripts', qui se déclenche lui-même à la priorité 1 de 'wp_head' —
     * y ajouter un callback à la volée à cette même priorité ne s'exécute pas de manière fiable).
     */
    /**
     * Feuilles communes aux pages Chic-checkout : écran d'attente avant le montage de
     * React (panier, checkout) et police DM Sans du design maison (version Pro).
     */
    public static function enqueue_shell_styles( bool $with_boot ) : void {
        if ( $with_boot ) {
            wp_enqueue_style(
                'chic-checkout-boot',
                CHIC_CHECKOUT_URL . 'assets/css/boot.css',
                array(),
                self::asset_version( 'assets/css/boot.css' )
            );
        }
    }

    public function maybe_print_dev_preamble() : void {
        if ( !$this->is_dev_server_running() || !is_checkout() || is_wc_endpoint_url() ) {
            return;
        }
        $dev_server = 'http://localhost:5173';
        wp_print_inline_script_tag( sprintf( 'import RefreshRuntime from "%1$s/@react-refresh";RefreshRuntime.injectIntoGlobalHook(window);window.$RefreshReg$=()=>{};window.$RefreshSig$=()=>(type)=>type;window.__vite_plugin_react_preamble_installed__=true;', esc_url_raw( $dev_server ) ), array(
            'type' => 'module',
        ) );
    }

    /**
     * Active en définissant CHIC_CHECKOUT_VITE_DEV à true dans wp-config.php
     * pendant que `npm run dev` tourne.
     */
    private function is_dev_server_running() : bool {
        return defined( 'CHIC_CHECKOUT_VITE_DEV' ) && CHIC_CHECKOUT_VITE_DEV;
    }

    private function enqueue_dev_assets() : void {
        $dev_server = 'http://localhost:5173';
        wp_enqueue_script(
            'chic-checkout-vite-client',
            $dev_server . '/@vite/client',
            array(),
            null,
            false
        );
        wp_enqueue_script(
            self::HANDLE,
            $dev_server . '/src/main.tsx',
            array('chic-checkout-vite-client', 'wp-i18n'),
            null,
            true
        );
    }

    private function enqueue_build_assets() : void {
        $manifest_path = CHIC_CHECKOUT_DIR . self::build_dir() . '.vite/manifest.json';
        if ( !file_exists( $manifest_path ) ) {
            return;
        }
        $manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
        $entry = $manifest['src/main.tsx'] ?? null;
        if ( !$entry ) {
            return;
        }
        wp_enqueue_script(
            self::HANDLE,
            CHIC_CHECKOUT_URL . self::build_dir() . $entry['file'],
            // React de WordPress (le bundle ne l'embarque pas, voir vite.config.ts).
            array(
                'wp-i18n',
                'react',
                'react-dom',
                'react-jsx-runtime'
            ),
            self::asset_version( self::build_dir() . $entry['file'] ),
            true
        );
        foreach ( $entry['css'] ?? array() as $index => $css_file ) {
            wp_enqueue_style(
                self::HANDLE . '-' . $index,
                CHIC_CHECKOUT_URL . self::build_dir() . $css_file,
                array(),
                self::asset_version( self::build_dir() . $css_file )
            );
        }
    }

    /**
     * filemtime() du fichier plutôt que CHIC_CHECKOUT_VERSION (statique, jamais
     * incrémentée d'un déploiement à l'autre) : sans ça, l'URL enqueue ne change
     * jamais d'un déploiement à l'autre, et un cache statique en amont (CDN/cache
     * hébergeur, keyé sur l'URL complète) continue de servir l'ancien fichier
     * indéfiniment après une mise à jour — vécu en conditions réelles sur staging
     * (SiteGround) avec assets/css/thankyou.css après un remplacement de plugin.
     */
    /**
     * Dossier du bundle React : build-pro/ (fonctions Pro comprises) dans la version Pro,
     * build/ (sans aucun code Pro, retiré à la compilation) dans la version gratuite.
     */
    public static function build_dir() : string {
        return 'build/';
    }

    public static function asset_version( string $relative_path ) : string {
        $path = CHIC_CHECKOUT_DIR . $relative_path;
        $mtime = ( file_exists( $path ) ? filemtime( $path ) : false );
        return ( $mtime ? (string) $mtime : CHIC_CHECKOUT_VERSION );
    }

    /**
     * Uniquement utilisé pour le script du dev server Vite (voir enqueue()) — le
     * bundle de prod n'en a plus besoin depuis qu'il est compilé en IIFE (voir
     * vite.config.ts) plutôt qu'en module ES.
     *
     * Historique : `type="module"` protégeait le script contre les collisions de
     * variables internes minifiées (ex. `_`) avec d'autres scripts globaux du site
     * (ex. Underscore.js exposé en `window._` par WordPress) — observé en production
     * sous la forme d'un `_.useState is not a function`. Un IIFE offre la même
     * isolation de portée (aucune variable ne fuit vers `window`) sans dépendre de cet
     * attribut, qu'au moins un mécanisme sur l'hébergement de production supprime des
     * balises <script> quelle que soit la priorité du filtre qui l'ajoute (testé
     * jusqu'à PHP_INT_MAX, sans effet).
     */
    public function add_module_type( string $tag, string $handle ) : string {
        if ( 'chic-checkout-vite-client' !== $handle && self::HANDLE !== $handle ) {
            return $tag;
        }
        return self::module_script_tag( $tag );
    }

    /**
     * Pose type="module" sur la balise <script ... src="..."> d'OUVERTURE du fichier JS,
     * et uniquement elle.
     *
     * Le $tag reçu par script_loader_tag contient aussi les scripts inline du même handle
     * (wp_add_inline_script( ..., 'before' ) : le JSON window.chicCheckout=..., placé
     * AVANT la balise du fichier). On ne touche donc jamais à un contenu inline — un
     * remplacement sur toute la chaîne corrompait le JSON dès qu'il contenait du HTML
     * avec " src=" (icône d'un moyen de paiement, <img src="...">), vécu en production —
     * et on ne se limite pas non plus à la première balise de la chaîne : c'est celle du
     * script inline, sans src, et le fichier restait alors sans type="module" (app jamais
     * montée en mode développement Vite).
     *
     * D'autres plugins/thèmes peuvent déjà avoir ajouté un attribut type="..." (ex.
     * type="text/javascript") : le navigateur ne garde que le premier attribut dupliqué,
     * on retire donc l'existant avant d'ajouter le nôtre — observé en production sur un
     * site tiers où ça cassait le module.
     */
    public static function module_script_tag( string $tag ) : string {
        return (string) preg_replace_callback(
            '/<script\\b[^>]*\\ssrc=[^>]*>/i',
            static function ( array $match ) : string {
                $opening = (string) preg_replace( '/\\s+type=(["\'])[^"\']*\\1/i', '', $match[0] );
                return str_replace( ' src=', ' type="module" src=', $opening );
            },
            $tag,
            1
        );
    }

    private function localize_config() : void {
        $settings = Settings::get();
        $back_url = ( 'custom' === $settings['back_target'] && $settings['back_custom_url'] ? $settings['back_custom_url'] : wc_get_cart_url() );
        list( $logo_url, $logo_text ) = self::resolve_logo( $settings );
        $config = array(
            'storeApiUrl'                 => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
            'restNonce'                   => wp_create_nonce( 'wp_rest' ),
            'storeApiNonce'               => wp_create_nonce( 'wc_store_api' ),
            'homeUrl'                     => esc_url_raw( home_url( '/' ) ),
            'backUrl'                     => esc_url_raw( $back_url ),
            'backLabel'                   => Polylang_Strings::translate( $settings['back_label'] ),
            'logoUrl'                     => $logo_url,
            'logoText'                    => $logo_text,
            'orderSummaryLabel'           => Polylang_Strings::translate( $settings['order_summary_label'] ),
            'showStepper'                 => $settings['show_stepper'],
            'stepNames'                   => array(Polylang_Strings::translate( $settings['step_name_1'] ), Polylang_Strings::translate( $settings['step_name_2'] ), Polylang_Strings::translate( $settings['step_name_3'] )),
            'stepNumbering'               => $settings['step_numbering'],
            'progressiveShippingForm'     => $settings['progressive_shipping_form'],
            'googlePlacesApiKey'          => ( Integrations::is_enabled( 'google_places' ) ? $settings['google_places_api_key'] : '' ),
            'shippingCountries'           => WC()->countries->get_shipping_countries(),
            'addressLocales'              => self::resolve_address_locales( array_keys( WC()->countries->get_shipping_countries() ) ),
            'storeCountry'                => WC()->countries->get_base_country(),
            'freeShippingLabel'           => Polylang_Strings::translate( $settings['free_shipping_label'] ),
            'pricesIncludeTax'            => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
            'ctaLabels'                   => array(
                'loginEmail'      => Polylang_Strings::translate( $settings['cta_login_email_label'] ),
                'loginPassword'   => Polylang_Strings::translate( $settings['cta_login_password_label'] ),
                'loginGuest'      => Polylang_Strings::translate( $settings['cta_login_guest_label'] ),
                'loginRegister'   => Polylang_Strings::translate( $settings['cta_login_register_label'] ),
                'shippingAddress' => Polylang_Strings::translate( $settings['cta_shipping_address_label'] ),
                'shippingMethod'  => Polylang_Strings::translate( $settings['cta_shipping_method_label'] ),
                'billing'         => Polylang_Strings::translate( $settings['cta_billing_label'] ),
                'payment'         => Polylang_Strings::translate( $settings['cta_payment_label'] ),
            ),
            'termsCheckbox'               => $this->resolve_terms_checkbox( $settings ),
            'loginIntroTitle'             => ( Polylang_Strings::translate( $settings['login_intro_title'] ) ?: __( 'Identification', 'chic-checkout' ) ),
            'loginIntroText'              => ( Polylang_Strings::translate( $settings['login_intro_text'] ) ?: __( 'Connectez-vous pour continuer votre commande.', 'chic-checkout' ) ),
            'isLoggedIn'                  => is_user_logged_in(),
            'userEmail'                   => ( is_user_logged_in() ? wp_get_current_user()->user_email : '' ),
            'guestCheckoutEnabled'        => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ),
            'registrationEnabled'         => Auth_Api::registration_enabled(),
            'contactStyle'                => $settings['contact_style'],
            'contactLabel'                => Polylang_Strings::translate( $settings['contact_label'] ),
            'contactPopupTitle'           => Polylang_Strings::translate( $settings['contact_popup_title'] ),
            'contactPopupText'            => Polylang_Strings::translate( $settings['contact_popup_text'] ),
            'contactChannels'             => $this->resolve_contact_channels( $settings ),
            'stripePublishableKey'        => $this->resolve_stripe_publishable_key(),
            'initialCart'                 => $this->resolve_initial_cart(),
            'footerLinks'                 => $this->resolve_footer_links( $settings ),
            'footerContrastToggleEnabled' => $settings['footer_contrast_toggle_enabled'],
            'footerContrastToggleLabel'   => Polylang_Strings::translate( $settings['footer_contrast_toggle_label'] ),
            'reassuranceItems'            => $this->resolve_reassurance_items( $settings ),
            'reassuranceMobileEnabled'    => $settings['reassurance_mobile_enabled'],
            'trackingGa4Enabled'          => Integrations::is_enabled( 'ga4' ),
            'integrations'                => Integrations::enabled_map(),
            'trackingDebugMode'           => $settings['tracking_debug_mode'],
            'hasConsentApi'               => function_exists( 'wp_has_consent' ),
            'stats'                       => Stats_Settings::front_config(),
            'branding'                    => Branding::front_config(),
        );
        wp_add_inline_script( self::HANDLE, 'window.chicCheckout = ' . wp_json_encode( $config ) . ';', 'before' );
    }

    /**
     * Le récap de commande (OrderSummary) restait vide le temps que le front fasse
     * son propre GET /wc/store/v1/cart après le montage de React — décalage visible
     * par rapport au reste de la page, déjà rendue via ce même appel à
     * localize_config(). Le panier WooCommerce est pourtant déjà disponible ici,
     * côté serveur, dans la même requête PHP : on redispatch la route Store API en
     * interne (rest_do_request, pas d'appel HTTP réel) pour obtenir EXACTEMENT le
     * même JSON que le front recevrait de toute façon, et on l'embarque directement
     * dans la config initiale. useCart() s'en sert comme état de départ — premier
     * rendu déjà à jour, sans lancer ce premier fetch en double.
     */
    private function resolve_initial_cart() {
        $request = new \WP_REST_Request('GET', '/wc/store/v1/cart');
        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            return null;
        }
        return rest_get_server()->response_to_data( $response, false );
    }

    /**
     * Lit la clé publique déjà configurée dans le plugin "Payment Plugins for
     * Stripe WooCommerce" (woo-stripe-payment) s'il est actif — rien à saisir de
     * notre côté, mêmes identifiants que ceux utilisés par WooCommerce pour les
     * moyens de paiement Stripe déjà en place.
     */
    private function resolve_stripe_publishable_key() : string {
        if ( !function_exists( 'wc_stripe_get_publishable_key' ) ) {
            return '';
        }
        return (string) wc_stripe_get_publishable_key();
    }

    /**
     * Priorité : logo uploadé sur ce plugin > texte personnalisé > logo du site
     * (Réglages > Personnaliser) > nom du site. Toujours un résultat non vide.
     *
     * @return array{0: string, 1: string} [logo_url, logo_text]
     */
    /**
     * Le contenu déplié reprend systématiquement la page CGV configurée nativement dans
     * WooCommerce (Réglages > Avancé > Pages) — pas de duplication de contenu dans ce
     * plugin. Case masquée si le réglage admin est désactivé OU si aucune page CGV n'est
     * configurée (rien de pertinent à afficher/lier dans ce cas).
     *
     * @return array{enabled: bool, label: string, content: string}
     */
    private function resolve_terms_checkbox( array $settings ) : array {
        $terms_page_id = ( function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0 );
        $terms_page = ( $terms_page_id ? get_post( $terms_page_id ) : null );
        $has_content = $terms_page && 'publish' === $terms_page->post_status && $terms_page->post_content;
        return array(
            'enabled' => $settings['show_terms_checkbox'] && (bool) $has_content,
            'label'   => Polylang_Strings::translate( $settings['terms_checkbox_label'] ),
            'content' => ( $has_content ? $this->render_page_content( $terms_page->post_content ) : '' ),
        );
    }

    /**
     * Rendu volontairement plus étroit que le filtre `the_content` complet : on ne
     * veut que les blocs Gutenberg, les paragraphes et les shortcodes de MISE EN PAGE
     * (WPBakery...), pas déclencher tous les autres callbacks accrochés à
     * `the_content` par des plugins tiers. Observé en prod : un plugin de paiement
     * PayPal y injecte le HTML de son sélecteur de moyen de paiement quel que soit le
     * contenu filtré — comme on appelle ça hors de la Loop principale, sur le contenu
     * de la page CGV, ce HTML se retrouvait injecté tel quel dans notre config JSON,
     * cassant sa syntaxe. `the_content` ne doit jamais être invoqué manuellement hors
     * de la Loop pour cette raison précise.
     */
    private function render_page_content( string $content ) : string {
        $content = do_blocks( $content );
        $content = wptexturize( $content );
        $content = wpautop( $content );
        $content = shortcode_unautop( $content );
        return do_shortcode( $content );
    }

    public static function resolve_logo( array $settings ) : array {
        if ( $settings['logo_id'] ) {
            return array((string) wp_get_attachment_image_url( $settings['logo_id'], 'medium' ), '');
        }
        if ( $settings['logo_text'] ) {
            return array('', Polylang_Strings::translate( $settings['logo_text'] ));
        }
        $site_logo_id = get_theme_mod( 'custom_logo' );
        if ( $site_logo_id ) {
            return array((string) wp_get_attachment_image_url( $site_logo_id, 'medium' ), '');
        }
        return array('', get_bloginfo( 'name' ));
    }

    /**
     * Construit les hrefs finaux (mailto/tel/wa.me/URL) côté PHP pour que le
     * frontend n'ait qu'à afficher des liens, sans dupliquer la logique de formatage.
     */
    private function resolve_contact_channels( array $settings ) : array {
        $channels = array();
        if ( $settings['contact_page_enabled'] && $settings['contact_page_url'] ) {
            $channels[] = array(
                'type' => 'page',
                'href' => esc_url_raw( $settings['contact_page_url'] ),
            );
        }
        if ( $settings['contact_email_enabled'] && $settings['contact_email_address'] ) {
            // Pas de antispambot() ici : cette valeur part dans du JSON puis devient un
            // attribut React (href={...}), jamais parsée comme du HTML — ses entités
            // &#xxx; ne seraient donc jamais décodées et resteraient affichées telles quelles.
            $channels[] = array(
                'type' => 'email',
                'href' => 'mailto:' . sanitize_email( $settings['contact_email_address'] ),
            );
        }
        if ( $settings['contact_whatsapp_enabled'] && $settings['contact_whatsapp_number'] ) {
            $digits = preg_replace( '/\\D+/', '', $settings['contact_whatsapp_number'] );
            if ( $digits ) {
                $channels[] = array(
                    'type' => 'whatsapp',
                    'href' => 'https://wa.me/' . $digits,
                );
            }
        }
        if ( $settings['contact_phone_enabled'] && $settings['contact_phone_number'] ) {
            $channel = array(
                'type' => 'phone',
                'href' => 'tel:' . preg_replace( '/\\s+/', '', $settings['contact_phone_number'] ),
            );
            // N'attache les horaires que si les 3 informations sont renseignées — un jour
            // sans plage horaire (ou l'inverse) ne veut rien dire, le front n'affiche le
            // point de disponibilité et la ligne d'horaires que si `hours` est présent.
            if ( $settings['contact_phone_days'] && $settings['contact_phone_start'] && $settings['contact_phone_end'] ) {
                $channel['hours'] = array(
                    'days'  => $settings['contact_phone_days'],
                    'start' => $settings['contact_phone_start'],
                    'end'   => $settings['contact_phone_end'],
                );
            }
            $channels[] = $channel;
        }
        return $channels;
    }

    /**
     * Résout chaque ligne configurée (page WordPress ou lien personnalisé) vers son
     * URL/libellé final. Le libellé configuré prime toujours ; à défaut, on retombe
     * sur le titre de la page (résolu ici, pas mémorisé en base, pour rester à jour
     * si la page est renommée) ou sur l'URL elle-même pour un lien personnalisé.
     * Une ligne sans page valide (supprimée/dépubliée depuis) ou sans URL est ignorée
     * plutôt que de casser l'affichage.
     *
     * @return array<int, array{label: string, href: string}>
     */
    private function resolve_footer_links( array $settings ) : array {
        $links = array();
        foreach ( $settings['footer_links'] as $row ) {
            // Traduit uniquement le libellé configuré par l'admin — jamais son repli
            // (titre de page, déjà géré nativement par Polylang ; URL, non pertinente).
            $configured_label = Polylang_Strings::translate( $row['label'] );
            if ( 'page' === $row['type'] ) {
                if ( !$row['page_id'] || 'publish' !== get_post_status( $row['page_id'] ) ) {
                    continue;
                }
                $href = get_permalink( $row['page_id'] );
                $label = ( $configured_label ?: get_the_title( $row['page_id'] ) );
            } else {
                if ( !$row['custom_url'] ) {
                    continue;
                }
                $href = $row['custom_url'];
                $label = ( $configured_label ?: $row['custom_url'] );
            }
            $links[] = array(
                'label' => $label,
                'href'  => $href,
            );
        }
        return $links;
    }

    /**
     * Une entrée sans titre est ignorée (rien de sensé à afficher). Icône prédéfinie
     * (tracé dessiné côté front, voir ReassuranceList.tsx) ou image personnalisée
     * uploadée — au plus l'une des deux est renvoyée selon le choix admin, l'icône
     * reste facultative dans les deux cas (dégradation gracieuse). Le lien n'est
     * renvoyé que s'il est configuré — c'est sa présence côté front qui détermine
     * l'affichage du bouton chevron, pas un booléen séparé.
     *
     * @return array<int, array{iconUrl: string, iconPreset: string, title: string, text: string, linkUrl: string, paymentIcons: array}>
     */
    private function resolve_reassurance_items( array $settings ) : array {
        $items = array();
        $payment_icons = null;
        foreach ( $settings['reassurance_items'] as $row ) {
            if ( !$row['title'] ) {
                continue;
            }
            $is_preset = 'preset' === $row['icon_type'] && $row['icon_preset'];
            $item = array(
                'iconPreset'   => ( $is_preset ? $row['icon_preset'] : '' ),
                'title'        => Polylang_Strings::translate( $row['title'] ),
                'text'         => Polylang_Strings::translate( $row['text'] ),
                'linkUrl'      => $row['link_url'],
                'paymentIcons' => ( !empty( $row['show_payment_icons'] ) ? $payment_icons ??= Cart_Assets::resolve_payment_icons( true ) : array() ),
            );
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Règles d'adresse de WooCommerce pour chaque pays de livraison : champ région
     * (libellé, obligatoire, masqué, liste des régions), libellés propres au pays du code
     * postal et de la ville, et ordre code postal / ville. Seuls les écarts au cas
     * général sont transmis : le formulaire garde ses propres libellés sinon.
     *
     * Sans ça, le formulaire ne demandait jamais la région, que WooCommerce exige pour les
     * États-Unis, le Canada, l'Italie, l'Espagne… : la commande y était refusée.
     *
     * @param string[] $countries
     * @return array<string, array<string, mixed>>
     */
    public static function resolve_address_locales( array $countries ) : array {
        $locales = WC()->countries->get_country_locale();
        $defaults = $locales['default'] ?? array();
        $result = array();
        foreach ( $countries as $country ) {
            $own = $locales[$country] ?? array();
            $field = static function ( string $key ) use($own, $defaults) : array {
                return array_merge( (array) ($defaults[$key] ?? array()), (array) ($own[$key] ?? array()) );
            };
            $state = $field( 'state' );
            $states = WC()->countries->get_states( $country );
            $entry = array(
                'state'         => array(
                    'hidden'   => !empty( $state['hidden'] ) || is_array( $states ) && empty( $states ),
                    'required' => !array_key_exists( 'required', $state ) || (bool) $state['required'],
                    'label'    => ( isset( $own['state']['label'] ) ? (string) $own['state']['label'] : '' ),
                    'options'  => ( is_array( $states ) && $states ? array_map( 'html_entity_decode', $states ) : null ),
                ),
                'postcode'      => array(
                    'hidden'   => !empty( $field( 'postcode' )['hidden'] ),
                    'required' => !array_key_exists( 'required', $field( 'postcode' ) ) || (bool) $field( 'postcode' )['required'],
                    'label'    => ( isset( $own['postcode']['label'] ) ? (string) $own['postcode']['label'] : '' ),
                ),
                'city'          => array(
                    'hidden'   => !empty( $field( 'city' )['hidden'] ),
                    'required' => !array_key_exists( 'required', $field( 'city' ) ) || (bool) $field( 'city' )['required'],
                    'label'    => ( isset( $own['city']['label'] ) ? (string) $own['city']['label'] : '' ),
                ),
                'postcodeFirst' => (int) ($field( 'postcode' )['priority'] ?? 90) < (int) ($field( 'city' )['priority'] ?? 70),
            );
            if ( $entry['state']['hidden'] ) {
                $entry['state']['required'] = false;
                $entry['state']['options'] = null;
            }
            $result[$country] = $entry;
        }
        return $result;
    }

}
