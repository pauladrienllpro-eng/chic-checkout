<?php

/**
 * Envoi quotidien des statistiques à Chic Insights (POST ingest, voir INGEST.md).
 *
 * - Cron WordPress quotidien, à une heure de la nuit tirée au hasard pour chaque site
 *   (étale la charge) ; repli au premier chargement de l'admin si le cron n'a pas tourné.
 * - Jamais pendant l'affichage d'une page : le cron s'exécute dans sa propre requête.
 * - Une journée par envoi, au plus les 7 dernières : un échec est rattrapé le lendemain.
 * - En plus, la journée en cours est envoyée vers 8 h, 13 h et 19 h (heure du site, décalée
 *   de quelques minutes par site) pour des chiffres frais dans Insights, qui remplace la
 *   journée à chaque envoi. Elle n'est pas marquée envoyée : l'envoi de nuit l'envoie
 *   complète le lendemain.
 * - Codes de retour respectés sans boucler : 429 = arrêt jusqu'au lendemain, 410 = site
 *   supprimé côté serveur (identité effacée, collecte coupée), 400 = journée abandonnée.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Stats_Sender {
    const HOOK = 'chic_checkout_stats_send';

    const TODAY_HOOK = 'chic_checkout_stats_send_today';

    /** Heures (site) des envois de la journée en cours. */
    const TODAY_HOURS = array(8, 13, 19);

    const STATE_OPTION = 'chic_checkout_stats_state';

    const SLOT_OPTION = 'chic_checkout_stats_slot';

    const MAX_DAYS = 7;

    /** Journées gardées localement : 30 jours pour l'écran Performances, + marge. */
    const KEEP_DAYS = 31;

    public function __construct() {
        add_action( self::HOOK, array(self::class, 'run') );
        add_action( self::TODAY_HOOK, array(self::class, 'run_today') );
        add_action( 'admin_init', array($this, 'maybe_run_from_admin') );
    }

    /** Seconde de la nuit (entre 1 h et 5 h) propre au site, tirée au hasard une fois. */
    private static function slot() : int {
        $slot = get_option( self::SLOT_OPTION );
        if ( !is_numeric( $slot ) ) {
            $slot = wp_rand( HOUR_IN_SECONDS, 5 * HOUR_IN_SECONDS );
            update_option( self::SLOT_OPTION, $slot, false );
        }
        return (int) $slot;
    }

    /**
     * Envoi de nuit : demain (ou cette nuit) à l'heure propre au site, entre 1 h et 5 h.
     * Envois de la journée : prochain créneau de TODAY_HOURS.
     */
    public static function schedule() : void {
        $midnight = ( new \DateTimeImmutable('today', wp_timezone()) )->getTimestamp();
        if ( !wp_next_scheduled( self::HOOK ) ) {
            $next = $midnight + self::slot();
            if ( $next <= time() ) {
                $next += DAY_IN_SECONDS;
            }
            wp_schedule_event( $next, 'daily', self::HOOK );
        }
        if ( !wp_next_scheduled( self::TODAY_HOOK ) ) {
            wp_schedule_single_event( self::next_today_run( $midnight ), self::TODAY_HOOK );
        }
    }

    /** Prochain créneau de TODAY_HOURS, décalé de 0 à 30 min selon le site (étale la charge). */
    private static function next_today_run( int $midnight ) : int {
        $offset = self::slot() % (30 * MINUTE_IN_SECONDS);
        foreach ( array(0, DAY_IN_SECONDS) as $day ) {
            foreach ( self::TODAY_HOURS as $hour ) {
                $at = $midnight + $day + $hour * HOUR_IN_SECONDS + $offset;
                if ( $at > time() ) {
                    return $at;
                }
            }
        }
        return $midnight + DAY_IN_SECONDS + self::TODAY_HOURS[0] * HOUR_IN_SECONDS + $offset;
    }

    public static function unschedule() : void {
        wp_clear_scheduled_hook( self::HOOK );
        wp_clear_scheduled_hook( self::TODAY_HOOK );
    }

    /** Envoi de la journée en cours (TODAY_HOOK), puis programmation du créneau suivant. */
    public static function run_today() : void {
        self::schedule();
        if ( !Stats_Settings::is_enabled() || !Stats_Settings::is_sharing() || time() < (int) self::state()['blocked_until'] ) {
            return;
        }
        Stats_Store::maybe_install();
        $today = Stats_Store::today();
        if ( in_array( $today, Stats_Store::days(), true ) ) {
            self::send_day( $today, true, true );
        }
    }

    private static function state() : array {
        $state = get_option( self::STATE_OPTION );
        $defaults = array(
            'sent'          => array(),
            'abandoned'     => array(),
            'last_run'      => 0,
            'blocked_until' => 0,
            'last_error'    => '',
            'verification'  => '',
        );
        return ( is_array( $state ) ? $state + $defaults : $defaults );
    }

    private static function save_state( array $state ) : void {
        update_option( self::STATE_OPTION, $state, false );
    }

    /**
     * Journées à renvoyer (données locales corrigées après coup) : retirées des listes
     * « envoyée » et « abandonnée ». Seules celles encore dans la fenêtre MAX_DAYS repartent.
     *
     * @param string[] $days
     */
    public static function forget_sent_days( array $days ) : void {
        $state = self::state();
        foreach ( $days as $day ) {
            unset($state['sent'][$day], $state['abandoned'][$day]);
        }
        self::save_state( $state );
    }

    public static function last_error() : string {
        return (string) self::state()['last_error'];
    }

    /**
     * État des envois, affiché dans l'onglet Statistiques pour qu'un marchand (ou le
     * support) comprenne pourquoi rien n'arrive, sans accès au serveur.
     *
     * @return array{last_sent_day:string,last_sent_at:int,pending:int,has_data:bool,next_run:int,last_run:int,last_error:string,blocked_until:int,verification:string,cron_disabled:bool,registered:bool}
     */
    public static function status() : array {
        $state = self::state();
        $sent = (array) $state['sent'];
        ksort( $sent );
        $last_day = (string) array_key_last( $sent );
        $today = Stats_Store::today();
        $oldest = wp_date( 'Y-m-d', strtotime( $today . ' -' . self::MAX_DAYS . ' days' ) );
        $days = Stats_Store::days();
        $pending = array_filter( $days, static fn( $day ) => $day < $today && $day >= $oldest && !isset( $state['sent'][$day] ) && !isset( $state['abandoned'][$day] ) );
        // Lecture seule : Stats_Client::identity() créerait une identité.
        $identity = get_option( Stats_Client::IDENTITY_OPTION );
        return array(
            'last_sent_day' => $last_day,
            'last_sent_at'  => ( $last_day ? (int) $sent[$last_day] : 0 ),
            'pending'       => count( $pending ),
            'has_data'      => !empty( $days ),
            'next_run'      => (int) wp_next_scheduled( self::HOOK ),
            'last_run'      => (int) $state['last_run'],
            'last_error'    => (string) $state['last_error'],
            'blocked_until' => (int) $state['blocked_until'],
            'verification'  => (string) $state['verification'],
            'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
            'registered'    => is_array( $identity ) && !empty( $identity['registered'] ),
        );
    }

    /** Repli : cron désactivé ou en retard de plus de 26 h → envoi au chargement de l'admin (3 journées au plus). */
    public function maybe_run_from_admin() : void {
        if ( wp_doing_ajax() || !current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        if ( get_option( Stats_Settings::PENDING_DELETE_OPTION ) ) {
            Stats_Settings::send_pending_delete();
        }
        if ( !Stats_Settings::is_enabled() ) {
            return;
        }
        self::schedule();
        $state = self::state();
        if ( time() - (int) $state['last_run'] > 26 * HOUR_IN_SECONDS && time() >= (int) $state['blocked_until'] ) {
            self::run( 3 );
        }
    }

    public static function run( int $max_days = self::MAX_DAYS ) : void {
        if ( get_option( Stats_Settings::PENDING_DELETE_OPTION ) ) {
            Stats_Settings::send_pending_delete();
        }
        if ( !Stats_Settings::is_enabled() ) {
            return;
        }
        // Sites mis à jour sans passage dans l'admin : programme aussi les envois de la journée.
        self::schedule();
        Stats_Store::maybe_install();
        $state = self::state();
        $state['last_run'] = time();
        self::save_state( $state );
        $today = Stats_Store::today();
        $oldest = wp_date( 'Y-m-d', strtotime( $today . ' -' . self::MAX_DAYS . ' days' ) );
        // Sans partage : seulement la purge locale ci-dessous, rien n'est envoyé.
        if ( Stats_Settings::is_sharing() && time() >= (int) $state['blocked_until'] ) {
            $pending = array_filter( Stats_Store::days(), static fn( $day ) => $day < $today && $day >= $oldest && !isset( $state['sent'][$day] ) && !isset( $state['abandoned'][$day] ) );
            foreach ( array_slice( array_values( $pending ), 0, $max_days ) as $day ) {
                if ( !self::send_day( $day ) ) {
                    break;
                }
            }
        }
        // Purge : rien au-delà de KEEP_DAYS, et plus de suivi pour les journées purgées.
        $keep_from = wp_date( 'Y-m-d', strtotime( $today . ' -' . self::KEEP_DAYS . ' days' ) );
        Stats_Store::delete_before( $keep_from );
        $state = self::state();
        foreach ( array('sent', 'abandoned') as $list ) {
            $state[$list] = array_filter( $state[$list], static fn( $value, $day ) => $day >= $keep_from, ARRAY_FILTER_USE_BOTH );
        }
        self::save_state( $state );
    }

    /**
     * true = continuer avec la journée suivante ; false = arrêter pour aujourd'hui.
     *
     * @param bool $partial Journée en cours (run_today) : jamais marquée envoyée ni
     *                      abandonnée, l'envoi de nuit l'enverra complète.
     */
    public static function send_day( string $day, bool $retry = true, bool $partial = false ) : bool {
        $payload = self::build_payload( $day );
        $state = self::state();
        if ( null === $payload ) {
            if ( !$partial ) {
                $state['abandoned'][$day] = 'invalid_site';
            }
            $state['last_error'] = 'invalid_site';
            self::save_state( $state );
            return true;
        }
        $response = Stats_Client::request( 'POST', Stats_Client::endpoints()['ingest'], (string) wp_json_encode( $payload ) );
        $status = $response['status'];
        $error = (string) ($response['body']['error'] ?? '');
        if ( 200 === $status ) {
            if ( $partial ) {
                $state['today_sent_at'] = time();
            } else {
                $state['sent'][$day] = time();
            }
            $state['last_error'] = '';
            $state['verification'] = sanitize_key( (string) ($response['body']['verification'] ?? $state['verification']) );
            self::save_state( $state );
            if ( !empty( $response['body']['registered'] ) ) {
                Stats_Client::mark_registered();
            }
            return true;
        }
        // Déjà reçue (rejeu, envoi plus récent déjà stocké) : rien à refaire.
        if ( 409 === $status && in_array( $error, array('older_than_stored', 'replayed_request'), true ) ) {
            if ( !$partial ) {
                $state['sent'][$day] = time();
                self::save_state( $state );
            }
            return true;
        }
        // Le serveur ne connaît pas (ou plus) ce site : nouvel essai immédiat avec l'enregistrement.
        if ( 401 === $status && 'unknown_site' === $error && $retry ) {
            $identity = Stats_Client::identity();
            $identity['registered'] = false;
            update_option( Stats_Client::IDENTITY_OPTION, $identity, false );
            return self::send_day( $day, false, $partial );
        }
        if ( 410 === $status ) {
            // Site supprimé côté serveur : identité effacée et partage coupé (statistiques locales gardées).
            $settings = Stats_Settings::get();
            $settings['share'] = false;
            update_option( Stats_Settings::OPTION_KEY, $settings );
            Stats_Client::forget_identity();
            return false;
        }
        $state['last_error'] = ( $error ? $error : 'http_' . $status );
        if ( 400 === $status || 413 === $status ) {
            // Rejet du contenu : renvoyer la même journée échouerait encore (journée en
            // cours : son contenu change d'ici l'envoi de nuit, qui tranchera).
            if ( !$partial ) {
                $state['abandoned'][$day] = $state['last_error'];
            }
            self::save_state( $state );
            return true;
        }
        if ( 429 === $status ) {
            $state['blocked_until'] = time() + DAY_IN_SECONDS;
        }
        // 401 (horloge, signature), 429, 5xx, réseau : on réessaiera au prochain passage.
        self::save_state( $state );
        return false;
    }

    /**
     * Payload d'une journée, conforme à INGEST.md ; null si le contexte du site ne peut
     * pas l'être (pays de la boutique non renseigné…).
     */
    public static function build_payload( string $day ) : ?array {
        $site = self::site_block( $day );
        if ( null === $site ) {
            return null;
        }
        $counters = array();
        foreach ( Stats_Store::day_counters( $day ) as $row ) {
            if ( !$site['browser_measures'] && Stats_Contract::is_browser_counter( $row['metric'] ) ) {
                continue;
            }
            if ( !Stats_Contract::is_valid_counter(
                $row['metric'],
                $row['device'],
                $row['dim_key'],
                $row['dim_value'],
                $row['value']
            ) || 0 === $row['value'] ) {
                continue;
            }
            $dims = array(
                'device' => $row['device'],
            );
            if ( '' !== $row['dim_key'] ) {
                $dims[$row['dim_key']] = $row['dim_value'];
            }
            $counters[] = array(
                'metric' => $row['metric'],
                'dims'   => $dims,
                'value'  => $row['value'],
            );
        }
        // Réglages obligatoires vides : constat du jour d'envoi, rattaché à la veille (mesure sans appareil : unknown).
        if ( wp_date( 'Y-m-d', strtotime( Stats_Store::today() . ' -1 day' ) ) === $day ) {
            foreach ( self::missing_config_keys() as $key ) {
                $counters[] = array(
                    'metric' => 'config_missing',
                    'dims'   => array(
                        'device' => 'unknown',
                        'key'    => $key,
                    ),
                    'value'  => 1,
                );
            }
        }
        $histograms = array();
        foreach ( Stats_Store::day_histograms( $day ) as $row ) {
            if ( !$site['browser_measures'] && in_array( $row['metric'], Stats_Contract::BROWSER_HISTOGRAMS, true ) ) {
                continue;
            }
            $edges = Stats_Contract::histograms()[$row['metric']] ?? null;
            if ( null === $edges || $row['bucket'] >= count( $edges ) ) {
                continue;
            }
            $key = $row['metric'] . '|' . $row['device'];
            if ( !isset( $histograms[$key] ) ) {
                $histograms[$key] = array(
                    'metric' => $row['metric'],
                    'dims'   => array(
                        'device' => $row['device'],
                    ),
                    'counts' => array_fill( 0, count( $edges ), 0 ),
                );
            }
            $histograms[$key]['counts'][$row['bucket']] = min( $row['count'], Stats_Contract::MAX_COUNTER_VALUE );
        }
        $payload = array(
            'schema_version' => Stats_Contract::SCHEMA_VERSION,
            'date'           => $day,
            'site'           => $site,
            'counters'       => array_slice( $counters, 0, Stats_Contract::MAX_COUNTERS ),
            'histograms'     => array_values( $histograms ),
        );
        // Version Pro : preuve Freemius au premier envoi, puis tant qu'Insights n'a pas
        // vérifié la licence (un site enregistré avant l'activation de sa licence restait
        // sinon « sans licence » pour toujours). Version gratuite : jamais de preuve, le
        // site reste pseudonyme (edition: free, aucun lien avec une installation Freemius).
        $identity = Stats_Client::identity();
        $proof = null;
        if ( empty( $identity['registered'] ) || null !== $proof && 'verified' !== self::state()['verification'] ) {
            $payload['registration'] = array(
                'install_key' => $identity['install_key'],
                'freemius'    => $proof,
            );
        }
        return $payload;
    }

    private static function site_block( string $day ) : ?array {
        $country = ( function_exists( 'WC' ) && WC()->countries ? (string) WC()->countries->get_base_country() : '' );
        $currency = strtoupper( (string) get_woocommerce_currency() );
        if ( !in_array( $country, Stats_Contract::countries(), true ) || !preg_match( '/^[A-Z]{3}$/', $currency ) ) {
            return null;
        }
        $has_browser_data = false;
        foreach ( Stats_Store::day_counters( $day ) as $row ) {
            if ( Stats_Contract::is_browser_counter( $row['metric'] ) ) {
                $has_browser_data = true;
                break;
            }
        }
        $plan = Edition::plan();
        $template = (string) get_template();
        $settings = Stats_Settings::get();
        return array(
            'plan'             => ( in_array( $plan, Stats_Contract::PLANS, true ) ? $plan : null ),
            'edition'          => Edition::name(),
            'country'          => $country,
            'category'         => ( '' !== $settings['category'] ? $settings['category'] : null ),
            'currency'         => $currency,
            'versions'         => array(
                'plugin' => Stats_Contract::clean_version( CHIC_CHECKOUT_VERSION ),
                'wp'     => Stats_Contract::clean_version( (string) get_bloginfo( 'version' ) ),
                'wc'     => Stats_Contract::clean_version( ( defined( 'WC_VERSION' ) ? WC_VERSION : '0' ) ),
                'php'    => Stats_Contract::clean_version( PHP_VERSION ),
            ),
            'theme'            => ( in_array( $template, Stats_Contract::THEMES, true ) ? $template : 'other' ),
            'integrations'     => array_keys( array_filter( Integrations::enabled_map() ) ),
            'detected'         => self::detected(),
            'cache_plugins'    => self::cache_plugins(),
            'browser_measures' => $has_browser_data || Stats_Settings::browser_possible(),
        );
    }

    /** Extensions détectées ailleurs dans le plugin (voir Payment_Support, Polylang_Strings, consent). */
    private static function detected() : array {
        $detected = array();
        $enabled = array();
        if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
            foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
                if ( $gateway instanceof \WC_Payment_Gateway && 'yes' === $gateway->enabled ) {
                    $enabled[] = $gateway->id;
                }
            }
        }
        if ( array_filter( $enabled, static fn( $id ) => 0 === strpos( $id, 'stripe_' ) ) ) {
            $detected[] = 'stripe_payment_plugins';
        }
        if ( array_filter( $enabled, array(Payment_Support::class, 'is_paypal') ) ) {
            $detected[] = 'paypal_payment_plugins';
        }
        if ( function_exists( 'pll_current_language' ) ) {
            $detected[] = 'polylang';
        }
        if ( function_exists( 'wp_has_consent' ) ) {
            $detected[] = 'wp_consent_api';
        }
        return $detected;
    }

    private static function cache_plugins() : array {
        $checks = array(
            'wp_rocket'        => defined( 'WP_ROCKET_VERSION' ),
            'litespeed_cache'  => defined( 'LSCWP_V' ),
            'autoptimize'      => defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ),
            'jetpack_boost'    => defined( 'JETPACK_BOOST_VERSION' ),
            'w3_total_cache'   => defined( 'W3TC' ),
            'wp_super_cache'   => defined( 'WPCACHEHOME' ),
            'wp_fastest_cache' => defined( 'WPFC_WP_CONTENT_BASENAME' ),
            'sg_optimizer'     => defined( '\\SiteGround_Optimizer\\VERSION' ),
            'breeze'           => defined( 'BREEZE_VERSION' ),
            'wp_optimize'      => defined( 'WPO_VERSION' ),
            'nitropack'        => defined( 'NITROPACK_VERSION' ),
            'perfmatters'      => defined( 'PERFMATTERS_VERSION' ),
            'flying_press'     => defined( 'FLYING_PRESS_VERSION' ),
            'cloudflare'       => defined( 'CLOUDFLARE_PLUGIN_DIR' ),
        );
        return array_keys( array_filter( $checks ) );
    }

    /** Intégration active dont le réglage obligatoire est vide. */
    private static function missing_config_keys() : array {
        $settings = Settings::get();
        $missing = array();
        if ( Integrations::is_enabled( 'google_places' ) && '' === trim( (string) $settings['google_places_api_key'] ) ) {
            $missing[] = 'google_places_api_key';
        }
        if ( Integrations::is_enabled( 'google_login' ) && '' === trim( (string) $settings['social_google_client_id'] ) ) {
            $missing[] = 'social_google_client_id';
        }
        if ( Integrations::is_enabled( 'apple_login' ) && '' === trim( (string) $settings['social_apple_services_id'] ) ) {
            $missing[] = 'social_apple_services_id';
        }
        return $missing;
    }

}
