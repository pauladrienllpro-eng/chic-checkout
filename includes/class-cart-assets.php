<?php

/**
 * Enqueue du bundle React de la page panier (dev server Vite ou build de prod) —
 * entrée séparée de celle du checkout (src/main-cart.tsx), même raisonnement que
 * Thankyou_Assets qui évite déjà de charger du code inutile à une page donnée : le
 * panier n'a besoin ni du flux Connexion/Livraison/Paiement, ni de Stripe/PayPal.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Cart_Assets {
    const HANDLE = 'chic-checkout-cart-app';

    /** Mêmes handles que Assets::DEFAULT_STYLE_HANDLES/DEFAULT_SCRIPT_HANDLES, sans les
     * scripts propres au formulaire de checkout classique (absent de cette page). */
    const DEFAULT_STYLE_HANDLES = array(
        'woocommerce-general',
        'woocommerce-layout',
        'woocommerce-smallscreen',
        'woocommerce-blocktheme',
        'wc-blocks-style',
        'select2'
    );

    const DEFAULT_SCRIPT_HANDLES = array(
        'wc-cart',
        'wc-cart-fragments',
        'wc-add-to-cart',
        'selectWoo',
        'wc-js-cookie',
        'woocommerce'
    );

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue') );
        add_action( 'wp_enqueue_scripts', array($this, 'dequeue_default_assets'), 9999 );
        add_action( 'wp_head', array($this, 'maybe_print_dev_preamble'), 1 );
    }

    public function dequeue_default_assets() : void {
        if ( 'cart' !== Compat::current_app() || !Compat::should_render( 'cart' ) ) {
            return;
        }
        foreach ( self::DEFAULT_STYLE_HANDLES as $handle ) {
            wp_dequeue_style( $handle );
        }
        foreach ( self::DEFAULT_SCRIPT_HANDLES as $handle ) {
            wp_dequeue_script( $handle );
        }
        // Scripts du panier de « Passerelle PayPal par Payment Plugins » (wc-ppcp-cart,
        // wc-ppcp-cart-gateway…) : ils chargent leur propre SDK PayPal, qui remplace le
        // nôtre s'il est arrivé avant et efface alors notre bouton PayPal express (bouton
        // présent ou non selon l'ordre de chargement). Leurs boutons n'ont de toute façon
        // pas où s'afficher dans notre panier ; PaypalExpress n'utilise que leurs routes.
        foreach ( wp_scripts()->queue as $handle ) {
            if ( 0 === strpos( $handle, 'wc-ppcp-' ) ) {
                wp_dequeue_script( $handle );
            }
        }
    }

    public function enqueue() : void {
        if ( 'cart' !== Compat::current_app() || !Compat::should_render( 'cart' ) ) {
            return;
        }
        if ( $this->is_dev_server_running() ) {
            // type="module" nécessaire uniquement ici (dev server Vite, vrai ESM) — voir
            // la note équivalente dans Assets::enqueue().
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
        wp_set_script_translations( self::HANDLE, 'chic-checkout', CHIC_CHECKOUT_DIR . 'languages' );
        Assets::enqueue_shell_styles( true );
        $this->localize_config();
    }

    public function maybe_print_dev_preamble() : void {
        if ( !$this->is_dev_server_running() || !is_cart() ) {
            return;
        }
        $dev_server = 'http://localhost:5173';
        wp_print_inline_script_tag( sprintf( 'import RefreshRuntime from "%1$s/@react-refresh";RefreshRuntime.injectIntoGlobalHook(window);window.$RefreshReg$=()=>{};window.$RefreshSig$=()=>(type)=>type;window.__vite_plugin_react_preamble_installed__=true;', esc_url_raw( $dev_server ) ), array(
            'type' => 'module',
        ) );
    }

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
            $dev_server . '/src/main-cart.tsx',
            array('chic-checkout-vite-client', 'wp-i18n'),
            null,
            true
        );
    }

    private function enqueue_build_assets() : void {
        $manifest_path = CHIC_CHECKOUT_DIR . Assets::build_dir() . '.vite/manifest.json';
        if ( !file_exists( $manifest_path ) ) {
            return;
        }
        $manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
        $entry = $manifest['src/main-cart.tsx'] ?? null;
        if ( !$entry ) {
            return;
        }
        wp_enqueue_script(
            self::HANDLE,
            CHIC_CHECKOUT_URL . Assets::build_dir() . $entry['file'],
            // React de WordPress (le bundle ne l'embarque pas, voir vite.config.ts).
            array(
                'wp-i18n',
                'react',
                'react-dom',
                'react-jsx-runtime'
            ),
            Assets::asset_version( Assets::build_dir() . $entry['file'] ),
            true
        );
        foreach ( $entry['css'] ?? array() as $index => $css_file ) {
            wp_enqueue_style(
                self::HANDLE . '-' . $index,
                CHIC_CHECKOUT_URL . Assets::build_dir() . $css_file,
                array(),
                Assets::asset_version( Assets::build_dir() . $css_file )
            );
        }
    }

    public function add_module_type( string $tag, string $handle ) : string {
        if ( 'chic-checkout-vite-client' !== $handle && self::HANDLE !== $handle ) {
            return $tag;
        }
        // Voir Assets::module_script_tag() : seule la balise du fichier JS est modifiée.
        return Assets::module_script_tag( $tag );
    }

    /**
     * Sur la page panier, "retourner au panier" (réglage utilisé par le checkout) n'a pas
     * de sens : on y est déjà. Réglage dédié à 3 destinations, la page produit la plus
     * récente s'appuyant sur le cookie natif WooCommerce `woocommerce_recently_viewed`
     * (posé par wc_track_product_view() à chaque visite de fiche produit) plutôt qu'un
     * suivi maison — évite un cookie de plus et reste cohérent avec le widget "Produits
     * vus récemment" natif si le site l'utilise déjà.
     */
    private function resolve_back_url( array $settings ) : string {
        if ( 'custom' === $settings['cart_back_target'] && $settings['cart_back_custom_url'] ) {
            return $settings['cart_back_custom_url'];
        }
        if ( 'last_product' === $settings['cart_back_target'] && !empty( $_COOKIE['woocommerce_recently_viewed'] ) ) {
            $viewed = array_filter( array_map( 'absint', explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) ) );
            $last_id = end( $viewed );
            if ( $last_id && 'product' === get_post_type( $last_id ) && 'publish' === get_post_status( $last_id ) ) {
                return (string) get_permalink( $last_id );
            }
        }
        return (string) wc_get_page_permalink( 'shop' );
    }

    private function localize_config() : void {
        $settings = Settings::get();
        $back_url = $this->resolve_back_url( $settings );
        list( $logo_url, $logo_text ) = Assets::resolve_logo( $settings );
        // Une seule résolution du panier pour toute cette méthode — resolve_upsell_pool()
        // en a besoin (IDs des produits déjà dans le panier, prix pour le critère "moins
        // cher") et réutilise ce résultat plutôt que de refaire un second appel REST
        // interne à /wc/store/v1/cart.
        $initial_cart = $this->resolve_initial_cart();
        $config = array(
            'storeApiUrl'                 => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
            'restNonce'                   => wp_create_nonce( 'wp_rest' ),
            'storeApiNonce'               => wp_create_nonce( 'wc_store_api' ),
            'homeUrl'                     => esc_url_raw( home_url( '/' ) ),
            'checkoutUrl'                 => esc_url_raw( wc_get_checkout_url() ),
            'backUrl'                     => esc_url_raw( $back_url ),
            'backLabel'                   => Polylang_Strings::translate( $settings['cart_back_label'] ),
            'emptyCartText'               => Polylang_Strings::translate( $settings['empty_cart_text'] ),
            'currencyCode'                => get_woocommerce_currency(),
            'logoUrl'                     => $logo_url,
            'logoText'                    => $logo_text,
            'fieldStyle'                  => $settings['field_style'],
            'contactStyle'                => $settings['contact_style'],
            'contactLabel'                => Polylang_Strings::translate( $settings['contact_label'] ),
            'contactPopupTitle'           => Polylang_Strings::translate( $settings['contact_popup_title'] ),
            'contactPopupText'            => Polylang_Strings::translate( $settings['contact_popup_text'] ),
            'contactChannels'             => $this->resolve_contact_channels( $settings ),
            'reassuranceItems'            => $this->resolve_reassurance_items( $settings ),
            'pricesIncludeTax'            => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
            'initialCart'                 => $initial_cart,
            'cartPromoCodeEnabled'        => $settings['cart_promo_code_enabled'],
            'account'                     => $this->resolve_account(),
            'login'                       => array(
                'introTitle'          => ( Polylang_Strings::translate( $settings['login_intro_title'] ) ?: __( 'Identification', 'chic-checkout' ) ),
                'registrationEnabled' => Auth_Api::registration_enabled(),
                'ctaLabels'           => array(
                    'loginEmail'    => Polylang_Strings::translate( $settings['cta_login_email_label'] ),
                    'loginPassword' => Polylang_Strings::translate( $settings['cta_login_password_label'] ),
                    'loginGuest'    => Polylang_Strings::translate( $settings['cta_login_guest_label'] ),
                    'loginRegister' => Polylang_Strings::translate( $settings['cta_login_register_label'] ),
                ),
            ),
            'cartPaymentIcons'            => array(
                'enabled'  => $settings['cart_payment_icons_enabled'],
                'label'    => Polylang_Strings::translate( $settings['cart_payment_icons_label'] ),
                'gateways' => ( $settings['cart_payment_icons_enabled'] ? self::resolve_payment_icons() : array() ),
            ),
            'cartUpsell'                  => array(
                'enabled'  => $settings['cart_upsell_enabled'],
                'title'    => Polylang_Strings::translate( $settings['cart_upsell_title'] ),
                'maxCount' => (int) $settings['cart_upsell_count'],
                'products' => $this->resolve_upsell_pool( $settings, $initial_cart ),
            ),
            'cartExpirationMessage'       => ( Integrations::is_enabled( 'bookacti' ) && function_exists( 'bookacti_get_message' ) ? bookacti_get_message( 'cart_countdown' ) : '' ),
            'integrations'                => Integrations::enabled_map(),
            'footerLinks'                 => $this->resolve_footer_links( $settings ),
            'footerContrastToggleEnabled' => $settings['footer_contrast_toggle_enabled'],
            'footerContrastToggleLabel'   => Polylang_Strings::translate( $settings['footer_contrast_toggle_label'] ),
            'stats'                       => Stats_Settings::front_config(),
        );
        wp_add_inline_script( self::HANDLE, 'window.chicCheckout = ' . wp_json_encode( $config ) . ';', 'before' );
    }

    /**
     * Même raisonnement que Assets::resolve_initial_cart() : le panier est déjà
     * disponible côté serveur dans cette même requête PHP, pas besoin d'attendre un
     * premier GET /cart après le montage de React.
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
     * Résout les IDs produit configurés (emballages, cadeaux du carrousel) en
     * titre/photo/prix — un produit supprimé/dépublié depuis la configuration est
     * simplement ignoré plutôt que de casser l'affichage.
     *
     * @param int[]  $product_ids
     * @param string $image_size Taille d'image WordPress de la photo affichée.
     * @return array<int, array{id:int,name:string,image:string,price:string}>
     */
    private function resolve_products( array $product_ids, string $image_size = 'thumbnail' ) : array {
        $products = array();
        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( !$product || 'publish' !== $product->get_status() ) {
                continue;
            }
            // L'ajout au panier ici est un simple clic, sans sélection de variation — un
            // produit variable échoue systématiquement à l'ajout côté Store API
            // ("Attributs manquants pour le produit variable", confirmé en conditions
            // réelles sur staging avec un produit variable configuré par erreur comme emballage).
            // Pour l'emballage/le cadeau (toujours de vrais SIMPLES, imposé côté admin),
            // ce filtre suffit. Pour le carousel upsell en revanche, resolve_upsell_pool()
            // passe ici directement l'ID d'une VARIATION précise (déjà résolue comme
            // achetable en un clic, voir resolve_addable_product_id()) — un catalogue
            // 100% à variations (constaté en réel : chaque produit a un choix de format)
            // n'a sinon jamais aucun candidat qui passe ce filtre.
            if ( !$product->is_type( 'simple' ) && !$product->is_type( 'variation' ) ) {
                continue;
            }
            // Nom/photo/lien affichés restent ceux du produit PARENT (reconnaissable pour
            // le client — personne ne reconnaît "Boutique du quartier Dotonbori #24") ;
            // seul le PRIX ajouté au clic est celui de cette variation précise.
            $display_product = ( $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product );
            if ( !$display_product ) {
                continue;
            }
            $price_numeric = (float) $product->get_price();
            // wp_strip_all_tags() retire les balises HTML mais PAS les entités
            // (wc_price()/get_name()/get_short_description() peuvent renvoyer "&nbsp;",
            // "&euro;", "&rsquo;"...) — sans html_entity_decode(), ces entités s'affichent
            // littéralement côté front au lieu d'être interprétées (constaté en réel sur
            // staging : "45,00&nbsp;&euro;" et "d&rsquo;Osaka" affichés tels quels).
            $products[] = array(
                'id'          => $product->get_id(),
                'name'        => html_entity_decode( $display_product->get_name(), ENT_QUOTES, 'UTF-8' ),
                'description' => html_entity_decode( wp_strip_all_tags( $display_product->get_short_description() ), ENT_QUOTES, 'UTF-8' ),
                'image'       => ( wp_get_attachment_image_url( ( $product->get_image_id() ?: $display_product->get_image_id() ), $image_size ) ?: wc_placeholder_img_src( $image_size ) ),
                'url'         => (string) get_permalink( $display_product->get_id() ),
                'price'       => ( $price_numeric > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $price_numeric ) ), ENT_QUOTES, 'UTF-8' ) : __( 'Offert', 'chic-checkout' ) ),
            );
        }
        return $products;
    }

    /**
     * Constitue le pool de produits recommandés du carousel upsell (voir Settings,
     * onglet Panier). Calculé une fois par chargement de page à partir du panier ACTUEL
     * — pas recalculé à chaque ajout/retrait côté client, qui se contente de filtrer ce
     * pool par les IDs déjà dans le panier (voir UpsellCarousel.tsx). Un produit ajouté
     * disparaît donc immédiatement sans aller-retour réseau ; seule une vraie
     * navigation/rechargement de la page panier régénère le pool.
     *
     * Ordre de priorité, respecté par le simple ordre du tableau retourné (le front
     * tronque à `cart_upsell_count` après avoir filtré le panier) :
     *   1. Étiquette(s) forcées par l'admin — priorité absolue.
     *   2. Moins cher que l'article le moins cher du panier (marchandise réelle,
     *      emballage/cadeau exclus du calcul du seuil comme partout ailleurs sur cette
     *      page — voir merchandiseSubtotal côté front).
     *   3. Même catégorie/étiquette qu'un produit du panier (wc_get_related_products(),
     *      mécanisme natif WooCommerce — aucune configuration produit par produit).
     *
     * @param array{items?: array<int, array<string, mixed>>} $initial_cart|null
     */
    private function resolve_upsell_pool( array $settings, $initial_cart ) : array {
        if ( !$settings['cart_upsell_enabled'] ) {
            return array();
        }
        // Emballages et produits offerts (version Pro) : jamais proposés en upsell.
        $hidden_product_ids = array();
        $cart_product_ids = array();
        $regular_product_ids = array();
        $cart_prices = array();
        foreach ( $initial_cart['items'] ?? array() as $item ) {
            // La Store API renvoie certains sous-groupes (dont "prices") en stdClass même
            // quand le reste de la structure est un tableau associatif — constaté en
            // conditions réelles ("Cannot use object of type stdClass as array" sur
            // $item['prices']['price']). Normalise tout en tableau plutôt que de deviner
            // quels champs sont concernés.
            $item = json_decode( (string) wp_json_encode( $item ), true );
            $product_id = ( isset( $item['id'] ) ? absint( $item['id'] ) : 0 );
            if ( !$product_id ) {
                continue;
            }
            // Pour un produit à variations, la Store API renvoie l'ID de la VARIATION en
            // "id" (vérifié dans CartItemSchema::get_item_response() — $product->get_id()
            // où $product = $cart_item['data'], un WC_Product_Variation) — jamais l'ID du
            // produit parent. Or les catégories/étiquettes vivent sur le produit PARENT,
            // pas sur la variation (get_the_terms() sur une variation renvoie toujours
            // vide) : sans cette résolution, wc_get_related_products() et l'exclusion
            // panier ne trouvaient jamais rien sur un catalogue 100% à variations —
            // constaté en réel sur staging2/production, pool systématiquement vide malgré
            // "Même catégorie" coché.
            $product_or_variation = wc_get_product( $product_id );
            if ( $product_or_variation && $product_or_variation->is_type( 'variation' ) ) {
                $product_id = $product_or_variation->get_parent_id();
            }
            $cart_product_ids[] = $product_id;
            if ( in_array( $product_id, $hidden_product_ids, true ) ) {
                continue;
            }
            $regular_product_ids[] = $product_id;
            $price_minor = ( isset( $item['prices']['price'] ) ? (float) $item['prices']['price'] : null );
            $minor_unit = ( isset( $item['prices']['currency_minor_unit'] ) ? (int) $item['prices']['currency_minor_unit'] : 2 );
            if ( null !== $price_minor ) {
                $cart_prices[] = $price_minor / 10 ** $minor_unit;
            }
        }
        // Ni un produit déjà dans le panier, ni l'emballage/le cadeau eux-mêmes
        // (masqués du catalogue exprès — jamais proposés comme "recommandation"), ni un
        // produit portant une étiquette/catégorie exclue — priorité absolue sur tout le
        // reste (voir description du réglage), donc mergé ici en amont des trois
        // branches ci-dessous plutôt qu'appliqué en filtre après coup : même l'étiquette
        // forcée ne peut pas passer outre.
        $excluded_ids = array_values( array_unique( array_merge( $cart_product_ids, $hidden_product_ids ) ) );
        $pool_limit = max( (int) $settings['cart_upsell_count'] * 3, 12 );
        $pool_ids = null;
        // Version gratuite : ventes croisées des articles du panier (onglet Produits liés de
        // la fiche produit), puis produits liés de WooCommerce (catégories / étiquettes).
        if ( null === $pool_ids ) {
            $pool_ids = array();
            foreach ( $regular_product_ids as $product_id ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $pool_ids = array_merge( $pool_ids, array_diff( array_map( 'absint', $product->get_cross_sell_ids() ), $excluded_ids ) );
                }
            }
            foreach ( $regular_product_ids as $product_id ) {
                $pool_ids = array_merge( $pool_ids, wc_get_related_products( $product_id, $pool_limit, array_merge( $excluded_ids, $pool_ids ) ) );
            }
        }
        $pool_ids = array_slice( array_unique( $pool_ids ), 0, $pool_limit );
        // Les trois critères ci-dessus raisonnent au niveau PRODUIT (catégories/
        // étiquettes/prix vivent sur le parent) mais un produit à variations n'est pas
        // directement ajoutable en un clic — résolu ici vers UNE variation précise
        // (achetable, en stock) avant le formatage final. Un candidat sans variation
        // achetable est simplement écarté plutôt que d'afficher un bouton qui échouerait.
        $addable_ids = array();
        foreach ( $pool_ids as $candidate_id ) {
            $addable_id = $this->resolve_addable_product_id( $candidate_id );
            if ( $addable_id ) {
                $addable_ids[] = $addable_id;
            }
        }
        return $this->resolve_products( $addable_ids );
    }

    /**
     * Un produit SIMPLE est directement ajoutable s'il est achetable et en stock. Un
     * produit à VARIATIONS doit d'abord être résolu vers une variation précise — la
     * moins chère parmi celles achetables/en stock, cohérent avec la logique "ajout
     * impulsif" du reste de ce carousel. Les autres types (groupé, externe...) ne sont
     * jamais ajoutables en un clic.
     */
    private function resolve_addable_product_id( int $product_id ) : ?int {
        $product = wc_get_product( $product_id );
        if ( !$product ) {
            return null;
        }
        if ( $product->is_type( 'simple' ) ) {
            return ( $product->is_purchasable() && $product->is_in_stock() ? $product->get_id() : null );
        }
        if ( !$product->is_type( 'variable' ) ) {
            return null;
        }
        $cheapest_variation = null;
        $cheapest_price = null;
        foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
            if ( !$variation->is_purchasable() || !$variation->is_in_stock() ) {
                continue;
            }
            $price = (float) $variation->get_price();
            if ( null === $cheapest_price || $price < $cheapest_price ) {
                $cheapest_price = $price;
                $cheapest_variation = $variation;
            }
        }
        return ( $cheapest_variation ? $cheapest_variation->get_id() : null );
    }

    /** Copie de Assets::resolve_contact_channels() — même logique, pas de raison de la
     * factoriser hors d'Assets pour l'instant (une seule autre page l'utilise). */
    private function resolve_contact_channels( array $settings ) : array {
        $channels = array();
        if ( $settings['contact_page_enabled'] && $settings['contact_page_url'] ) {
            $channels[] = array(
                'type' => 'page',
                'href' => esc_url_raw( $settings['contact_page_url'] ),
            );
        }
        if ( $settings['contact_email_enabled'] && $settings['contact_email_address'] ) {
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
     * Copie de Assets::resolve_footer_links() — voir remarque plus haut sur
     * resolve_contact_channels() (même logique, pas factorisée hors d'Assets pour
     * l'instant).
     *
     * @return array<int, array{label: string, href: string}>
     */
    private function resolve_footer_links( array $settings ) : array {
        $links = array();
        foreach ( $settings['footer_links'] as $row ) {
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
     * Client connecté ou non, pour la pastille de compte de l'en-tête du panier.
     *
     * @return array{loggedIn: bool, firstName: string, accountUrl: string}
     */
    private function resolve_account() : array {
        $user = wp_get_current_user();
        $first = '';
        if ( $user->exists() ) {
            $first = ( (( (string) get_user_meta( $user->ID, 'billing_first_name', true ) ?: (string) $user->first_name )) ?: (string) $user->display_name );
        }
        return array(
            'loggedIn'   => $user->exists(),
            'firstName'  => $first,
            'accountUrl' => (string) wc_get_page_permalink( 'myaccount' ),
        );
    }

    /**
     * Icônes des moyens de paiement WooCommerce activés (Réglages > Paiements) — jamais
     * de logo à nous, on relaie tel quel le HTML que chaque passerelle fournit elle-même
     * via get_icon() (identifiants réels : Stripe, PayPal... appliquent leurs propres
     * logos via le filtre `woocommerce_gateway_icon`, comme sur le checkout classique).
     * Une passerelle activée mais sans icône configurée est silencieusement ignorée —
     * rien de pertinent à afficher pour elle ici.
     *
     * Public : aussi utilisé par les blocs de réassurance (panier et checkout, voir
     * resolve_reassurance_items() ici et dans Assets), qui ne listent alors que les
     * passerelles réellement proposées par le checkout ($supported_only, voir
     * Payment_Support) — l'option du panier garde son comportement d'origine.
     */
    public static function resolve_payment_icons( bool $supported_only = false ) : array {
        if ( !function_exists( 'WC' ) || !WC()->payment_gateways ) {
            return array();
        }
        $gateways = array();
        foreach ( WC()->payment_gateways->get_available_payment_gateways() as $gateway ) {
            if ( $supported_only && !Payment_Support::is_supported( $gateway ) ) {
                continue;
            }
            $icon_html = Payment_Support::icon_html( $gateway );
            if ( !$icon_html ) {
                continue;
            }
            $gateways[] = array(
                'id'       => $gateway->id,
                'title'    => Payment_Support::checkout_title( $gateway ),
                'iconHtml' => $icon_html,
            );
        }
        return $gateways;
    }

    /** Copie de Assets::resolve_reassurance_items() — voir remarque ci-dessus. */
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
                'paymentIcons' => ( !empty( $row['show_payment_icons'] ) ? $payment_icons ??= self::resolve_payment_icons( true ) : array() ),
            );
            $items[] = $item;
        }
        return $items;
    }

}
