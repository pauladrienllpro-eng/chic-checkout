<?php

/**
 * Désactive le rendu WooCommerce classique de la page "commande reçue" (order-received)
 * et sert notre propre template à la place.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Thankyou_Override {
    /**
     * Instance accessible depuis templates/thankyou-app.php (get_order()/needs_login()),
     * qui n'a sinon aucun moyen d'atteindre l'instance créée dans Plugin::__construct().
     */
    private static ?Thankyou_Override $instance = null;

    /** @var \WC_Order|null|false false = pas encore résolu, null = résolu mais invalide/refusé. */
    private $order = false;

    /** URL de connexion à proposer si la commande existe mais appartient à un compte
     * auquel le visiteur actuel n'est pas connecté — voir get_order()/needs_login(). */
    private string $login_redirect_url = '';

    /** Commande invité à protéger par l'e-mail de facturation (voir guest_must_verify()). */
    private bool $needs_email_verification = false;

    /** E-mail saisi qui ne correspond pas à la commande. */
    private bool $email_verification_failed = false;

    const VERIFY_ACTION = 'chic_verify_order_email';

    const VERIFIED_SESSION = 'chic_verified_orders';

    public function __construct() {
        self::$instance = $this;
        add_filter( 'template_include', array($this, 'maybe_override_thankyou_template'), 99 );
        add_action( 'template_redirect', array($this, 'prevent_caching'), 1 );
    }

    public static function instance() : ?Thankyou_Override {
        return self::$instance;
    }

    /**
     * Même interrupteur que le panier et le checkout (Compat::should_render()) :
     * add_filter( 'chic_checkout_render_app', '__return_false' ) rend aussi la page de
     * remerciement standard de WooCommerce. Pas de dépendance au build ici : la page
     * est en PHP pur.
     */
    public static function should_render() : bool {
        return (bool) apply_filters( 'chic_checkout_render_app', true, 'thankyou' );
    }

    /**
     * Cette page affiche des données personnelles (adresse, email, moyen de paiement)
     * propres à CHAQUE commande — jamais de cache plein-page, même raisonnement que
     * Checkout_Override::prevent_caching().
     */
    public function prevent_caching() : void {
        if ( !function_exists( 'is_order_received_page' ) || !is_order_received_page() ) {
            return;
        }
        if ( !defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        if ( !headers_sent() ) {
            nocache_headers();
        }
    }

    public function maybe_override_thankyou_template( string $template ) : string {
        if ( !function_exists( 'is_order_received_page' ) || !is_order_received_page() ) {
            return $template;
        }
        if ( !self::should_render() ) {
            return $template;
        }
        $custom_template = CHIC_CHECKOUT_DIR . 'templates/thankyou-app.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
        return $template;
    }

    /**
     * Résout la commande depuis order-received/key, en répliquant la validation faite
     * par WC_Shortcode_Checkout::order_received() (bypassée puisqu'on ne passe pas par
     * le rendu shortcode habituel de WooCommerce) :
     * - order_id + order_key doivent correspondre à une vraie commande (hash_equals,
     *   comparaison à temps constant) — sinon on ne révèle jamais si l'ID existe ou non.
     * - une commande liée à un compte n'est montrée qu'au client connecté sous CE
     *   compte — sinon on propose de se connecter plutôt que de tout masquer sans
     *   explication (voir needs_login()).
     *
     * Simplification assumée (voir plan) : la vérification email pour les commandes
     * invité (fonctionnalité WooCommerce à part, formulaire dédié) n'est pas répliquée
     * ici — la protection order_id+order_key reste la protection principale.
     */
    public function get_order() : ?\WC_Order {
        if ( false !== $this->order ) {
            return $this->order;
        }
        $this->order = null;
        global $wp;
        $order_id = ( isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0 );
        // La clé de commande est le jeton d'accès de cette URL (lien envoyé par WooCommerce
        // et par la passerelle de paiement) : comparée à temps constant ci-dessous, elle ne
        // déclenche aucune écriture. Lecture seule, comme WC_Shortcode_Checkout.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_key = ( isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '' );
        if ( $order_id > 0 ) {
            $candidate = wc_get_order( $order_id );
            if ( $candidate instanceof \WC_Order && hash_equals( $candidate->get_order_key(), $order_key ) ) {
                $this->order = $candidate;
            }
        }
        if ( !$this->order ) {
            return null;
        }
        // Aucune écriture ici : le panier est vidé par WooCommerce lui-même
        // (wc_clear_cart_after_payment, sur template_redirect), qui vérifie en plus que la
        // commande correspond au panier de la session.
        $customer_id = $this->order->get_customer_id();
        if ( $customer_id && get_current_user_id() !== $customer_id ) {
            $this->login_redirect_url = $this->order->get_checkout_order_received_url();
            $this->order = null;
        } elseif ( !$customer_id && $this->guest_must_verify( $this->order ) ) {
            $this->needs_email_verification = true;
            $this->order = null;
        }
        return $this->order;
    }

    /**
     * Commande invité : la clé de l'URL suffit juste après la commande, puis l'e-mail de
     * facturation est demandé (même règle que WooCommerce, même filtre de délai). Un lien
     * qui fuit (partage, historique, outil d'analyse) ne montre plus nom, adresse et
     * téléphone. Une fois l'e-mail confirmé, la commande reste lisible pour la session.
     */
    private function guest_must_verify( \WC_Order $order ) : bool {
        $grace = (int) apply_filters(
            'woocommerce_order_email_verification_grace_period',
            10 * MINUTE_IN_SECONDS,
            $order,
            'order-received'
        );
        $created = $order->get_date_created();
        if ( $created && time() - $created->getTimestamp() < $grace ) {
            return false;
        }
        $billing_email = strtolower( (string) $order->get_billing_email() );
        if ( is_user_logged_in() && strtolower( wp_get_current_user()->user_email ) === $billing_email ) {
            return false;
        }
        $session = WC()->session;
        $verified = ( $session ? (array) $session->get( self::VERIFIED_SESSION, array() ) : array() );
        if ( in_array( $order->get_id(), array_map( 'intval', $verified ), true ) ) {
            return false;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- vérifié juste après.
        if ( isset( $_POST['chic_order_email'] ) ) {
            $nonce = ( isset( $_POST['_chic_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_chic_nonce'] ) ) : '' );
            $email = strtolower( sanitize_email( wp_unslash( $_POST['chic_order_email'] ) ) );
            if ( wp_verify_nonce( $nonce, self::VERIFY_ACTION ) && '' !== $email && hash_equals( $billing_email, $email ) ) {
                if ( $session ) {
                    $verified[] = $order->get_id();
                    $session->set( self::VERIFIED_SESSION, array_slice( array_unique( array_map( 'intval', $verified ) ), -20 ) );
                    // Visiteur sans panier : WooCommerce ne pose pas de cookie de session, la
                    // confirmation serait perdue au rechargement.
                    if ( method_exists( $session, 'set_customer_session_cookie' ) ) {
                        $session->set_customer_session_cookie( true );
                    }
                }
                return false;
            }
            $this->email_verification_failed = true;
        }
        return true;
    }

    public function needs_email_verification() : bool {
        $this->get_order();
        return $this->needs_email_verification;
    }

    public function email_verification_failed() : bool {
        return $this->email_verification_failed;
    }

    /**
     * True si une commande valide existe mais appartient à un compte auquel le
     * visiteur actuel n'est pas connecté (à distinguer d'un order_id/key invalide,
     * où on ne veut au contraire rien laisser transparaître).
     */
    public function needs_login() : bool {
        $this->get_order();
        return '' !== $this->login_redirect_url;
    }

    public function get_login_redirect_url() : string {
        return $this->login_redirect_url;
    }

    /** Mots laissés en minuscules dans une adresse, sauf en tête : « Place de la Mairie ». */
    const LOWERCASE_WORDS = array(
        'de',
        'du',
        'des',
        'la',
        'le',
        'les',
        'et',
        'sur',
        'sous',
        'aux',
        'au',
        'en'
    );

    /**
     * « 80 RUE D'AGUESSEAU » → « 80 Rue d'Aguesseau », « 3 PLACE DE LA MAIRIE » →
     * « 3 Place de la Mairie ». mb_convert_case (pas ucwords) : multibyte-safe, donc
     * correct sur les accents français (é, è, à...) qu'une adresse contient presque
     * toujours.
     */
    public static function to_title_case( string $text ) : string {
        if ( '' === $text ) {
            return $text;
        }
        $words = explode( ' ', mb_convert_case( $text, MB_CASE_TITLE, 'UTF-8' ) );
        foreach ( $words as $index => $word ) {
            $lower = mb_strtolower( $word, 'UTF-8' );
            if ( $index > 0 && in_array( $lower, self::LOWERCASE_WORDS, true ) ) {
                $words[$index] = $lower;
            } elseif ( $index > 0 && preg_match( "/^([dl])['’](.+)\$/iu", $word, $match ) ) {
                // Élision : « D'aguesseau » → « d'Aguesseau ».
                $words[$index] = mb_strtolower( $match[1], 'UTF-8' ) . "'" . mb_convert_case( $match[2], MB_CASE_TITLE, 'UTF-8' );
            }
        }
        return implode( ' ', $words );
    }

    /**
     * Boutique + horaires d'un retrait en point relais Boxtal Connect ou La Poste Pro
     * Expéditions — confirmé sur une vraie commande (staging #8050) que ces plugins
     * sauvegardent déjà cette donnée automatiquement, via leur propre hook sur
     * woocommerce_store_api_checkout_order_processed (celui que notre checkout
     * déclenche), sans rien coder de notre côté pour la capturer. Détection par
     * présence de meta technique, jamais par le libellé du mode d'expédition.
     *
     * @return array{name:string,address:string,postcode:string,city:string,hours:array<array{label:string,value:string}>}|null
     */
    public function get_parcelpoint( \WC_Order $order ) : ?array {
        $point = $order->get_meta( 'bw_parcel_point' );
        if ( !is_object( $point ) || !isset( $point->name ) ) {
            $point = $order->get_meta( 'laposteproexp_parcel_point' );
        }
        if ( !is_object( $point ) || !isset( $point->name ) ) {
            return null;
        }
        $weekday_labels = array(
            'MONDAY'    => 'Lundi',
            'TUESDAY'   => 'Mardi',
            'WEDNESDAY' => 'Mercredi',
            'THURSDAY'  => 'Jeudi',
            'FRIDAY'    => 'Vendredi',
            'SATURDAY'  => 'Samedi',
            'SUNDAY'    => 'Dimanche',
        );
        $hours_by_weekday = array();
        if ( isset( $point->opening_hours ) && is_array( $point->opening_hours ) ) {
            foreach ( $point->opening_hours as $opening_hour ) {
                if ( !isset( $opening_hour->weekday ) ) {
                    continue;
                }
                $periods = array();
                foreach ( (array) ($opening_hour->opening_periods ?? array()) as $period ) {
                    if ( !empty( $period->open ) && !empty( $period->close ) ) {
                        $periods[] = $period->open . ' – ' . $period->close;
                    }
                }
                $hours_by_weekday[$opening_hour->weekday] = ( $periods ? implode( ' · ', $periods ) : 'Fermé' );
            }
        }
        $hours = array();
        foreach ( $weekday_labels as $weekday => $label ) {
            if ( isset( $hours_by_weekday[$weekday] ) ) {
                $hours[] = array(
                    'label' => $label,
                    'value' => $hours_by_weekday[$weekday],
                );
            }
        }
        return array(
            'name'     => (string) $point->name,
            'address'  => self::to_title_case( (string) ($point->address ?? '') ),
            'postcode' => (string) ($point->zipcode ?? ''),
            'city'     => self::to_title_case( (string) ($point->city ?? '') ),
            'hours'    => $hours,
        );
    }

    /**
     * Délai de livraison estimé configuré par le marchand pour la méthode
     * d'expédition réellement utilisée sur cette commande — un réglage par méthode
     * (Settings::get()['shipping_lead_times'][$instance_id]), pas une donnée à lire
     * depuis la commande elle-même. Voir class-shipping-lead-time.php pour l'autre
     * moitié de ce mécanisme (côté étape Livraison du checkout).
     */
    public function get_delivery_estimate( \WC_Order $order ) : string {
        $lead_times = Settings::get()['shipping_lead_times'];
        foreach ( $order->get_shipping_methods() as $shipping_item ) {
            $instance_id = $shipping_item->get_instance_id();
            if ( $instance_id && !empty( $lead_times[$instance_id] ) ) {
                return $lead_times[$instance_id];
            }
        }
        return '';
    }

}
