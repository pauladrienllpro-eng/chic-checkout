<?php

/**
 * Niveau 1 : compteurs mesurés côté serveur (rendu des pages, Store API, commandes).
 *
 * Rien n'est mesuré si les statistiques sont désactivées. Aucune donnée personnelle
 * n'est lue au-delà de ce qu'il faut pour en déduire une catégorie (appareil depuis le
 * User-Agent, nouveau client ou non), et rien de tel n'est stocké : seuls des compteurs.
 *
 * Commandes « Chic-checkout » : marquées à leur création quand elles viennent de l'app
 * (en-tête X-Chic-Checkout sur la Store API, ou session ouverte par l'app pour le bouton
 * PayPal qui passe par le checkout classique). Méta _chic_stats : catégorie d'appareil,
 * mode d'identification et parcours (checkout ou paiement express du panier).
 *
 * Paiement express du panier (Apple Pay, Google Pay : en-tête X-Chic-Express sur la Store API ;
 * PayPal, version Pro) : ni étapes du checkout, ni tentatives
 * de paiement du checkout — compteurs express_* à part, et dimension `flow` sur les
 * commandes payées, pour que les taux du checkout restent comparables.
 *
 * Parcours digital (depuis la 1.12.0) : panier sans livraison (produits virtuels ou
 * téléchargeables), donc checkout sans étape Livraison. Sa part de chaque compteur du
 * tunnel est comptée en plus dans digital_funnel (stage : cart, checkout, identification,
 * payment, paid) ; Chic Insights en déduit le parcours physique (total − digital).
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Stats_Server {
    const ORDER_META = '_chic_stats';

    const ORDER_COUNTED_META = '_chic_stats_counted';

    const ITEM_UPSELL_META = '_chic_upsell';

    const SESSION_KEY = 'chic_stats';

    const STORE_API_ROUTE = '#^/wc/store(?:/v\\d+)?/(cart/update-customer|cart/select-shipping-rate|checkout)$#';

    public function __construct() {
        add_action( 'template_redirect', array($this, 'count_page'), 99 );
        add_filter(
            'rest_request_after_callbacks',
            array($this, 'after_rest'),
            10,
            3
        );
        add_action( 'rest_api_init', array($this, 'register_routes') );
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'mark_upsell_cart_item') );
        add_action(
            'woocommerce_checkout_create_order_line_item',
            array($this, 'mark_upsell_order_item'),
            10,
            3
        );
        add_action(
            'woocommerce_store_api_checkout_update_order_from_request',
            array($this, 'mark_store_api_order'),
            10,
            2
        );
        add_action(
            'woocommerce_blocks_checkout_update_order_from_request',
            array($this, 'mark_store_api_order'),
            10,
            2
        );
        add_action( 'woocommerce_checkout_create_order', array($this, 'mark_classic_order') );
        add_action( 'woocommerce_payment_complete', array($this, 'count_paid_order') );
        add_action(
            'woocommerce_order_status_changed',
            array($this, 'on_status_changed'),
            10,
            3
        );
    }

    /** mobile / tablet / desktop d'après le User-Agent, qui n'est ni stocké ni transmis. */
    public static function device() : string {
        $ua = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '' );
        if ( preg_match( '/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/', $ua ) ) {
            return 'tablet';
        }
        if ( preg_match( '/mobi|iphone|ipod|android|windows phone|blackberry/', $ua ) ) {
            return 'mobile';
        }
        return 'desktop';
    }

    public static function is_bot() : bool {
        $ua = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '' );
        return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|preview|lighthouse|headless|curl|wget|python|monitor|uptime|facebookexternalhit/', $ua );
    }

    private static function is_prefetch() : bool {
        $purpose = ( isset( $_SERVER['HTTP_SEC_PURPOSE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_PURPOSE'] ) ) : (( isset( $_SERVER['HTTP_PURPOSE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_PURPOSE'] ) ) : '' )) );
        return false !== stripos( $purpose, 'prefetch' ) || isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'];
    }

    /** Session WooCommerce existante (aucun cookie ajouté) : sert seulement à ne pas compter deux fois une étape. */
    private static function session() : ?\WC_Session {
        return ( function_exists( 'WC' ) && WC()->session instanceof \WC_Session ? WC()->session : null );
    }

    private static function session_data() : array {
        $session = self::session();
        $data = ( $session ? $session->get( self::SESSION_KEY ) : null );
        return ( is_array( $data ) && ($data['day'] ?? '') === Stats_Store::today() ? $data : array(
            'day'   => Stats_Store::today(),
            'steps' => array(),
        ) );
    }

    private static function save_session_data( array $data ) : void {
        $session = self::session();
        if ( $session ) {
            $session->set( self::SESSION_KEY, $data );
        }
    }

    /**
     * Une étape n'est comptée qu'une fois par jour et par panier. Panier digital (drapeau de
     * session posé à l'affichage du checkout, mis à jour par la Store API) : aussi dans
     * digital_funnel.
     */
    private static function count_step_once( string $step ) : void {
        $data = self::session_data();
        if ( !empty( $data['steps'][$step] ) ) {
            return;
        }
        $data['steps'][$step] = 1;
        self::save_session_data( $data );
        $device = self::device();
        Stats_Store::add(
            'step_reached',
            $device,
            1,
            'step',
            $step
        );
        if ( !empty( $data['digital'] ) && in_array( $step, array('identification', 'payment'), true ) ) {
            Stats_Store::add(
                'digital_funnel',
                $device,
                1,
                'stage',
                $step
            );
        }
    }

    /**
     * Panier sans livraison (parcours digital : pas d'étape Livraison dans le checkout). Même
     * critère que le front (Cart.needs_shipping de la Store API). Panier vide : non.
     */
    private static function is_digital_cart() : bool {
        $cart = ( function_exists( 'WC' ) ? WC()->cart : null );
        return $cart instanceof \WC_Cart && !$cart->is_empty() && !$cart->needs_shipping();
    }

    /** Commande sans article à livrer : même règle que WC_Cart::needs_shipping(), appliquée aux articles. */
    private static function is_digital_order( \WC_Order $order ) : bool {
        if ( !wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
            return true;
        }
        foreach ( $order->get_items() as $item ) {
            $product = ( $item instanceof \WC_Order_Item_Product ? $item->get_product() : null );
            if ( $product && $product->needs_shipping() ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Étape Livraison affichée (depuis la 1.8.3). Auparavant, elle n'était comptée qu'à
     * l'envoi de l'adresse ou au choix du mode : un client parti sans valider comptait comme
     * un abandon à l'identification, et l'abandon à la livraison restait proche de 0 %.
     * L'envoi de l'adresse la compte toujours (secours si cet appel n'aboutit pas) ; la
     * session évite tout double comptage.
     */
    public function register_routes() : void {
        register_rest_route( 'chic-checkout/v1', '/step-reached', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'step_reached'),
            'permission_callback' => array($this, 'check_rest_nonce'),
        ) );
    }

    public function check_rest_nonce( \WP_REST_Request $request ) : bool {
        return (bool) wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
    }

    public function step_reached( \WP_REST_Request $request ) : \WP_REST_Response {
        // 204 dans tous les cas : le navigateur n'attend rien et n'apprend rien.
        $done = new \WP_REST_Response(null, 204);
        if ( !Stats_Settings::is_enabled() || self::is_bot() || 'shipping' !== $request->get_param( 'step' ) ) {
            return $done;
        }
        if ( function_exists( 'wc_load_cart' ) ) {
            wc_load_cart();
        }
        // Sans session WooCommerce (aucun panier), rien ne dédoublonnerait les appels. Pas de
        // test needs_shipping() : dans une route REST custom, le contenu du panier n'est pas
        // encore relu depuis la session (il le serait au hook wp_loaded, déjà passé) et la
        // réponse serait toujours non ; le front n'appelle cette route que si l'étape existe.
        $session = self::session();
        if ( !$session || !$session->has_session() ) {
            return $done;
        }
        if ( is_wp_error( Rate_Limiter::hit( 'stats' ) ) ) {
            return $done;
        }
        self::count_step_once( 'shipping' );
        return $done;
    }

    public function count_page() : void {
        if ( !Stats_Settings::is_enabled() || self::is_bot() || self::is_prefetch() ) {
            return;
        }
        $app = Compat::current_app();
        if ( null === $app ) {
            return;
        }
        $device = self::device();
        if ( Compat::should_render( $app ) ) {
            $digital = self::is_digital_cart();
            if ( 'cart' === $app ) {
                Stats_Store::add( 'cart_viewed', $device );
                if ( $digital ) {
                    Stats_Store::add(
                        'digital_funnel',
                        $device,
                        1,
                        'stage',
                        'cart'
                    );
                }
                return;
            }
            Stats_Store::add( 'checkout_started', $device );
            if ( $digital ) {
                Stats_Store::add(
                    'digital_funnel',
                    $device,
                    1,
                    'stage',
                    'checkout'
                );
            }
            $data = self::session_data();
            // Marque la session : une commande PayPal (checkout classique) sera reconnue comme venant de l'app.
            $data['checkout'] = 1;
            $data['digital'] = ( $digital ? 1 : 0 );
            self::save_session_data( $data );
            self::count_step_once( 'identification' );
            return;
        }
        Stats_Store::add(
            'fallback_standard',
            $device,
            1,
            'reason',
            self::fallback_reason( $app )
        );
    }

    private static function fallback_reason( string $app ) : string {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- simple lecture du paramètre d'affichage.
        if ( isset( $_GET[Compat::CLASSIC_PARAM] ) ) {
            return 'classic_param';
        }
        if ( !Compat::is_dev() && null === Compat::build_entry( $app ) ) {
            return 'missing_build';
        }
        return 'filter';
    }

    /**
     * Store API (étapes, tentatives de paiement) et routes d'identification du plugin.
     *
     * @param mixed            $response
     * @param array            $handler
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function after_rest( $response, $handler, $request ) {
        if ( !$request instanceof \WP_REST_Request || !Stats_Settings::is_enabled() ) {
            return $response;
        }
        $route = $request->get_route();
        $ok = !is_wp_error( $response ) && !($response instanceof \WP_REST_Response && $response->get_status() >= 400);
        if ( preg_match( '#^/chic-checkout/v1/(login|register|social-login)$#', $route, $match ) ) {
            if ( $ok ) {
                $via = ( 'social-login' === $match[1] ? (string) $request->get_param( 'provider' ) : 'password' );
                $data = self::session_data();
                $data['via'] = ( in_array( $via, array('google', 'apple', 'password'), true ) ? $via : 'password' );
                self::save_session_data( $data );
            }
            return $response;
        }
        // Moyens de paiement : demandés uniquement à l'affichage de l'étape Paiement
        // (PaymentStep), le seul signal fiable que le client l'a atteinte.
        if ( '/chic-checkout/v1/payment-methods' === $route ) {
            if ( $ok ) {
                self::count_step_once( 'payment' );
            }
            return $response;
        }
        if ( 'POST' !== $request->get_method() || '1' !== $request->get_header( 'x_chic_checkout' ) || !preg_match( self::STORE_API_ROUTE, $route, $match ) ) {
            return $response;
        }
        $express = (string) $request->get_header( 'x_chic_express' );
        if ( '' !== $express ) {
            if ( 'checkout' === $match[1] && in_array( $express, array('apple_pay', 'google_pay'), true ) ) {
                $this->count_express_attempt( $response, $express );
            }
            return $response;
        }
        if ( 'checkout' === $match[1] ) {
            $this->count_payment_attempt( $response, (string) $request->get_param( 'payment_method' ) );
            return $response;
        }
        if ( $ok ) {
            $needs_shipping = WC()->cart && WC()->cart->needs_shipping();
            // Panier relu par la Store API : parcours digital à jour si le panier a changé.
            $data = self::session_data();
            $data['digital'] = ( $needs_shipping ? 0 : 1 );
            self::save_session_data( $data );
            // Adresse de livraison envoyée ou mode de livraison choisi : étape Livraison. Le
            // choix du mode compte aussi, car un client connecté qui garde son adresse
            // enregistrée n'envoie pas d'adresse. Sans livraison (produits virtuels),
            // l'adresse envoyée est celle de facturation, à l'étape Paiement.
            self::count_step_once( ( $needs_shipping ? 'shipping' : 'payment' ) );
        }
        return $response;
    }

    /** @param mixed $response */
    private function count_express_attempt( $response, string $method ) : void {
        $device = self::device();
        Stats_Store::add(
            'express_attempted',
            $device,
            1,
            'method',
            $method
        );
        $failed = is_wp_error( $response ) || $response instanceof \WP_REST_Response && ($response->get_status() >= 400 || in_array( (string) (( (array) $response->get_data() )['payment_result']['payment_status'] ?? ''), array('failure', 'error'), true ));
        if ( $failed ) {
            Stats_Store::add(
                'express_failed',
                $device,
                1,
                'method',
                $method
            );
        }
    }

    /** @param mixed $response */
    private function count_payment_attempt( $response, string $gateway_id ) : void {
        $device = self::device();
        $method = self::payment_method( $gateway_id );
        Stats_Store::add(
            'payment_attempted',
            $device,
            1,
            'method',
            $method
        );
        $code = '';
        $message = '';
        $failed = false;
        if ( is_wp_error( $response ) ) {
            $failed = true;
            $code = (string) $response->get_error_code();
            $message = (string) $response->get_error_message();
        } elseif ( $response instanceof \WP_REST_Response ) {
            $data = (array) $response->get_data();
            $status = (string) ($data['payment_result']['payment_status'] ?? '');
            if ( $response->get_status() >= 400 || in_array( $status, array('failure', 'error'), true ) ) {
                $failed = true;
                $code = (string) ($data['code'] ?? '');
                $message = (string) ($data['message'] ?? '');
            }
        }
        if ( $failed ) {
            Stats_Store::add(
                'payment_failed',
                $device,
                1,
                'method',
                $method
            );
            // Le message de la passerelle n'est lu que pour choisir une catégorie, jamais conservé.
            Stats_Store::add(
                'payment_failed_by',
                $device,
                1,
                'failure',
                self::failure_category( $code . ' ' . $message )
            );
        }
    }

    public static function failure_category( string $text ) : string {
        $text = strtolower( $text );
        if ( preg_match( '/3d|secure|authenticat|authentif|requires_action/', $text ) ) {
            return 'authentication';
        }
        if ( preg_match( '/declin|refus|insufficient|insuffisant|do_not_honor|expired|expir|incorrect|invalid_cvc|lost|stolen|card_/', $text ) ) {
            return 'declined';
        }
        return 'technical';
    }

    /** Identifiant de passerelle => valeur du contrat (Payment_Support::SUPPORTED_IDS + passerelles sans champs). */
    public static function payment_method( string $gateway_id ) : string {
        $map = array(
            'stripe_cc'        => 'card',
            'stripe_applepay'  => 'apple_pay',
            'stripe_googlepay' => 'google_pay',
            'stripe_klarna'    => 'klarna',
            'stripe_wero'      => 'wero',
            'stripe_upm'       => 'alma',
            'mycred'           => 'mycred',
            'bacs'             => 'bacs',
            'cheque'           => 'cheque',
            'cod'              => 'cod',
        );
        if ( Payment_Support::is_paypal( $gateway_id ) ) {
            return 'paypal';
        }
        return $map[$gateway_id] ?? 'other';
    }

    /** Article ajouté depuis le carrousel d'upsell (en-tête posé par UpsellCarousel). */
    public function mark_upsell_cart_item( $data ) {
        if ( is_array( $data ) && isset( $_SERVER['HTTP_X_CHIC_UPSELL'] ) && '1' === $_SERVER['HTTP_X_CHIC_UPSELL'] ) {
            $data['chic_upsell'] = 1;
        }
        return $data;
    }

    /** @param \WC_Order_Item_Product $item @param string $key @param array $values */
    public function mark_upsell_order_item( $item, $key, $values ) : void {
        if ( !empty( $values['chic_upsell'] ) && $item instanceof \WC_Order_Item_Product ) {
            $item->add_meta_data( self::ITEM_UPSELL_META, 1, true );
        }
    }

    /** @param \WC_Order $order @param \WP_REST_Request $request */
    public function mark_store_api_order( $order, $request ) : void {
        if ( $request instanceof \WP_REST_Request && '1' === $request->get_header( 'x_chic_checkout' ) ) {
            $express = (string) $request->get_header( 'x_chic_express' );
            $this->mark_order( $order, ( in_array( $express, array('apple_pay', 'google_pay'), true ) ? 'express_' . $express : 'checkout' ) );
        }
    }

    /** Checkout classique (bouton PayPal) : seulement si la session a été ouverte par l'app. */
    public function mark_classic_order( $order ) : void {
        $paypal_express = false;
        if ( $paypal_express ) {
            $this->mark_order( $order, 'express_paypal' );
            if ( $order instanceof \WC_Order && Stats_Settings::is_enabled() ) {
                Stats_Store::add(
                    'express_attempted',
                    self::device(),
                    1,
                    'method',
                    'paypal'
                );
            }
            return;
        }
        if ( !empty( self::session_data()['checkout'] ) ) {
            $this->mark_order( $order );
            if ( $order instanceof \WC_Order ) {
                Stats_Store::add(
                    'payment_attempted',
                    self::device(),
                    1,
                    'method',
                    self::payment_method( (string) $order->get_payment_method() )
                );
            }
        }
    }

    private function mark_order( $order, string $flow = 'checkout' ) : void {
        if ( !$order instanceof \WC_Order || !Stats_Settings::is_enabled() || $order->get_meta( self::ORDER_META ) ) {
            return;
        }
        $device = self::device();
        $via = ( is_user_logged_in() ? self::session_data()['via'] ?? 'password' : 'guest' );
        $order->update_meta_data( self::ORDER_META, wp_json_encode( array(
            'device' => $device,
            'via'    => $via,
            'flow'   => $flow,
        ) ) );
        Stats_Store::add( 'order_placed', $device );
        Stats_Store::add(
            'login',
            $device,
            1,
            'via',
            $via
        );
    }

    /** @param int $order_id @param string $from @param string $to */
    public function on_status_changed( $order_id, $from, $to ) : void {
        if ( in_array( $to, wc_get_is_paid_statuses(), true ) ) {
            $this->count_paid_order( $order_id );
        }
    }

    /** Compte une commande payée, une seule fois. */
    public function count_paid_order( $order_id ) : void {
        $order = wc_get_order( $order_id );
        if ( !$order instanceof \WC_Order || !Stats_Settings::is_enabled() || $order->get_meta( self::ORDER_COUNTED_META ) ) {
            return;
        }
        $meta = json_decode( (string) $order->get_meta( self::ORDER_META ), true );
        if ( !is_array( $meta ) ) {
            return;
        }
        $order->update_meta_data( self::ORDER_COUNTED_META, 1 );
        $order->save_meta_data();
        $device = ( in_array( $meta['device'] ?? '', Stats_Contract::DEVICES, true ) ? $meta['device'] : 'unknown' );
        $settings = Settings::get();
        $cents = (int) round( (float) $order->get_total() * 100 );
        $paid_at = ( $order->get_date_paid() ? $order->get_date_paid()->getTimestamp() : time() );
        $upsell_cents = 0;
        $has_upsell = false;
        $has_free = false;
        $has_wrapping = false;
        $items_count = 0;
        foreach ( $order->get_items() as $item ) {
            if ( !$item instanceof \WC_Order_Item_Product ) {
                continue;
            }
            $items_count += (int) $item->get_quantity();
            $product_id = (int) $item->get_product_id();
            if ( $item->get_meta( self::ITEM_UPSELL_META ) ) {
                $has_upsell = true;
                $upsell_cents += (int) round( ((float) $item->get_total() + (float) $item->get_total_tax()) * 100 );
            }
        }
        $gift = false;
        $message = false;
        $method = self::payment_method( (string) $order->get_payment_method() );
        Stats_Store::add( 'order_paid', $device );
        Stats_Store::add( 'revenue_cents', $device, $cents );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'method',
            $method
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'shipping',
            self::shipping_mode( $order )
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'customer',
            ( self::is_returning( $order ) ? 'returning' : 'new' )
        );
        $country = (string) (( $order->get_shipping_country() ?: $order->get_billing_country() ));
        if ( in_array( $country, Stats_Contract::countries(), true ) ) {
            Stats_Store::add(
                'order_paid_by',
                $device,
                1,
                'ship_country',
                $country
            );
        }
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'hour',
            wp_date( 'G', $paid_at )
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'weekday',
            wp_date( 'N', $paid_at )
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'upsell',
            ( $has_upsell ? 'with' : 'without' )
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'gift',
            ( $gift ? 'with' : 'without' )
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'coupon',
            ( $order->get_coupon_codes() ? 'with' : 'without' )
        );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'free_product',
            ( $has_free ? 'with' : 'without' )
        );
        Stats_Store::add(
            'revenue_cents_by',
            $device,
            $cents,
            'upsell',
            ( $has_upsell ? 'with' : 'without' )
        );
        Stats_Store::add(
            'revenue_cents_by',
            $device,
            $cents,
            'gift',
            ( $gift ? 'with' : 'without' )
        );
        // Commandes marquées avant la 1.5.1 : pas de parcours enregistré, donc checkout.
        $flow = ( in_array( $meta['flow'] ?? '', Stats_Contract::dimensions()['flow'], true ) ? $meta['flow'] : 'checkout' );
        Stats_Store::add(
            'order_paid_by',
            $device,
            1,
            'flow',
            $flow
        );
        if ( 'checkout' === $flow && self::is_digital_order( $order ) ) {
            Stats_Store::add(
                'digital_funnel',
                $device,
                1,
                'stage',
                'paid'
            );
        }
        Stats_Store::add(
            'revenue_cents_by',
            $device,
            $cents,
            'flow',
            $flow
        );
        Stats_Store::add( 'upsell_revenue_cents', $device, $upsell_cents );
        if ( $gift ) {
            Stats_Store::add(
                'gift_option_orders',
                $device,
                1,
                'option',
                'gift'
            );
        }
        if ( $has_wrapping ) {
            Stats_Store::add(
                'gift_option_orders',
                $device,
                1,
                'option',
                'wrapping'
            );
        }
        if ( $message ) {
            Stats_Store::add(
                'gift_option_orders',
                $device,
                1,
                'option',
                'message'
            );
        }
        if ( 'mycred' === $method ) {
            Stats_Store::add( 'loyalty_orders', $device );
            Stats_Store::add( 'loyalty_points_used', $device, self::loyalty_points( $order ) );
        }
        if ( 'EUR' === $order->get_currency() ) {
            Stats_Store::add_histogram( 'cart_value_eur', $device, (float) $order->get_total() );
        }
        Stats_Store::add_histogram( 'items_count', $device, (float) $items_count );
    }

    /** home : modes génériques ; carrier : mode d'un plugin transporteur ; relay : point relais ; pickup : retrait. */
    private static function shipping_mode( \WC_Order $order ) : string {
        $methods = $order->get_shipping_methods();
        $method = ( $methods ? reset( $methods ) : null );
        if ( !$method ) {
            return 'home';
        }
        $id = (string) $method->get_method_id();
        if ( in_array( $id, array('local_pickup', 'pickup_location'), true ) ) {
            return 'pickup';
        }
        if ( in_array( $id, array('flat_rate', 'free_shipping'), true ) ) {
            return 'home';
        }
        foreach ( array_merge( $order->get_meta_data(), $method->get_meta_data() ) as $meta ) {
            if ( preg_match( '/parcel|relay|relais|pickup_point/i', (string) $meta->key ) && '' !== (string) (( is_scalar( $meta->value ) ? $meta->value : 'x' )) ) {
                return 'relay';
            }
        }
        return 'carrier';
    }

    /** Le client a-t-il déjà une commande payée ? Calculé ici, seul le résultat oui/non est compté. */
    private static function is_returning( \WC_Order $order ) : bool {
        $args = array(
            'status'  => wc_get_is_paid_statuses(),
            'exclude' => array($order->get_id()),
            'limit'   => 1,
            'return'  => 'ids',
            'type'    => 'shop_order',
        );
        if ( $order->get_customer_id() ) {
            $args['customer_id'] = $order->get_customer_id();
        } elseif ( $order->get_billing_email() ) {
            $args['billing_email'] = $order->get_billing_email();
        } else {
            return false;
        }
        return (bool) wc_get_orders( $args );
    }

    /** Même règle que Mycred_Helper::get_cost_and_balance(), appliquée au total de la commande. */
    private static function loyalty_points( \WC_Order $order ) : int {
        $gateways = ( WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array() );
        $gateway = $gateways['mycred'] ?? null;
        $currency = $order->get_currency();
        if ( Mycred_Helper::point_type_exists( $currency ) || 'MYC' === $currency || !$gateway instanceof \WC_Payment_Gateway ) {
            return (int) round( (float) $order->get_total() );
        }
        $rate = (float) $gateway->get_option( 'exchange_rate', 1 );
        return (int) round( (float) $order->get_total() / (( $rate > 0 ? $rate : 1 )) );
    }

}
