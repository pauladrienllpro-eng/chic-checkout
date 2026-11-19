<?php

/**
 * Onglet « Statistiques » des réglages et état de la collecte.
 *
 * Deux interrupteurs, désactivés par défaut :
 *  - « Mes statistiques » (enabled) : compteurs calculés et gardés sur le site, pour
 *    l'écran Performances. Rien n'est envoyé.
 *  - « Partage » (share, exige enabled) : envoi quotidien à Chic Insights, consentement
 *    distinct et facultatif, qui débloque la comparaison avec les autres boutiques.
 * Un site activé en 1.1.0 (un seul interrupteur, qui valait partage) garde le partage.
 *
 * Niveaux de mesure (partage uniquement pour le niveau 2) :
 *  - niveau 1 (serveur) : actif dès que le marchand a donné son accord ;
 *  - niveau 2 (navigateur) : seulement si la WP Consent API donne le consentement
 *    « statistics-anonymous » (ou « statistics ») du visiteur, ou si le marchand coche
 *    explicitement « sans bandeau de consentement ».
 *
 * Désactiver = arrêt immédiat de la collecte, purge locale et demande de suppression
 * signée à l'API (DELETE). Toujours possible, même licence expirée (lien dédié).
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Stats_Settings {
    const OPTION_KEY = 'chic_checkout_stats';

    const PENDING_DELETE_OPTION = 'chic_checkout_stats_pending_delete';

    const TAB = 'stats';

    const DISABLE_ACTION = 'chic_stats_disable';

    const SEND_NOW_ACTION = 'chic_stats_send_now';

    const TERMS_URL = 'https://pauladrien.fr/chic-checkout/cgv/';

    public function __construct() {
        add_action( 'admin_init', array($this, 'register') );
        add_action(
            'update_option_' . self::OPTION_KEY,
            array($this, 'on_update'),
            10,
            2
        );
        add_action(
            'add_option_' . self::OPTION_KEY,
            array($this, 'on_add'),
            10,
            2
        );
        add_action( 'admin_post_' . self::DISABLE_ACTION, array($this, 'handle_disable_link') );
        add_action( 'admin_post_' . self::SEND_NOW_ACTION, array($this, 'handle_send_now') );
    }

    /** @return array{enabled:bool,share:bool,browser_without_banner:bool,category:string} */
    public static function get() : array {
        $stored = get_option( self::OPTION_KEY, array() );
        $stored = ( is_array( $stored ) ? $stored : array() );
        $enabled = !empty( $stored['enabled'] );
        return array(
            'enabled'                => $enabled,
            'share'                  => $enabled && (( array_key_exists( 'share', $stored ) ? !empty( $stored['share'] ) : true )),
            'browser_without_banner' => !empty( $stored['browser_without_banner'] ),
            'category'               => ( in_array( $stored['category'] ?? '', Stats_Contract::CATEGORIES, true ) ? $stored['category'] : '' ),
        );
    }

    /** Collecte locale active (écran Performances). */
    public static function is_enabled() : bool {
        return self::get()['enabled'];
    }

    /** Partage avec Chic Insights consenti (envoi quotidien, comparaison). */
    public static function is_sharing() : bool {
        return self::get()['share'];
    }

    /**
     * Le niveau 2 peut-il être actif sur ce site ? (le consentement de chaque visiteur
     * reste vérifié dans le navigateur, voir src/lib/stats.ts)
     */
    public static function browser_possible() : bool {
        $settings = self::get();
        // Les mesures navigateur ne servent qu'au partage (aucun indicateur local ne les utilise).
        return $settings['share'] && ($settings['browser_without_banner'] || function_exists( 'wp_has_consent' ));
    }

    /** Configuration transmise au front (window.chicCheckout.stats). */
    public static function front_config() : array {
        $settings = self::get();
        return array(
            'enabled'              => $settings['enabled'],
            'browser'              => self::browser_possible(),
            'browserWithoutBanner' => $settings['share'] && $settings['browser_without_banner'],
            'url'                  => esc_url_raw( rest_url( Stats_Rest::NAMESPACE . Stats_Rest::ROUTE ) ),
        );
    }

    public function register() : void {
        register_setting( Settings::PAGE_SLUG, self::OPTION_KEY, array($this, 'sanitize') );
        $page = Settings::PAGE_SLUG . '_' . self::TAB;
        add_settings_section(
            'chic_tab_' . self::TAB,
            '',
            '__return_false',
            $page
        );
        add_settings_field(
            'stats_enabled',
            __( 'Mes statistiques', 'chic-checkout' ),
            array($this, 'render_enabled_field'),
            $page,
            'chic_tab_' . self::TAB
        );
        add_settings_field(
            'stats_share',
            __( 'Partage', 'chic-checkout' ),
            array($this, 'render_share_field'),
            $page,
            'chic_tab_' . self::TAB
        );
        add_settings_field(
            'stats_status',
            __( 'État des envois', 'chic-checkout' ),
            array($this, 'render_status_field'),
            $page,
            'chic_tab_' . self::TAB
        );
        add_settings_field(
            'stats_browser',
            __( 'Mesures navigateur', 'chic-checkout' ),
            array($this, 'render_browser_field'),
            $page,
            'chic_tab_' . self::TAB
        );
        add_settings_field(
            'stats_category',
            __( 'Catégorie de la boutique', 'chic-checkout' ),
            array($this, 'render_category_field'),
            $page,
            'chic_tab_' . self::TAB
        );
    }

    /** @param mixed $value */
    public function sanitize( $value ) : array {
        $value = ( is_array( $value ) ? $value : array() );
        $enabled = !empty( $value['enabled'] );
        return array(
            'enabled'                => $enabled,
            'share'                  => $enabled && !empty( $value['share'] ),
            'browser_without_banner' => !empty( $value['browser_without_banner'] ),
            'category'               => ( in_array( $value['category'] ?? '', Stats_Contract::CATEGORIES, true ) ? $value['category'] : '' ),
        );
    }

    /**
     * Valeur effective (même règle que get()) d'une valeur brute d'option.
     *
     * @param mixed $value
     * @return array{enabled:bool,share:bool}
     */
    private static function flags( $value ) : array {
        $value = ( is_array( $value ) ? $value : array() );
        $enabled = !empty( $value['enabled'] );
        return array(
            'enabled' => $enabled,
            'share'   => $enabled && (( array_key_exists( 'share', $value ) ? !empty( $value['share'] ) : true )),
        );
    }

    /** @param mixed $old @param mixed $new */
    public function on_update( $old, $new ) : void {
        $was = self::flags( $old );
        $now = self::flags( $new );
        if ( $now['enabled'] && !$was['enabled'] ) {
            self::activate();
        }
        if ( $now['share'] && !$was['share'] ) {
            self::start_sharing();
        }
        if ( $was['share'] && !$now['share'] ) {
            self::stop_sharing();
        }
        if ( $was['enabled'] && !$now['enabled'] ) {
            self::disable();
        }
        if ( $now['enabled'] ) {
            Stats_Onboarding::mark_done();
        }
    }

    /** @param string $option @param mixed $value */
    public function on_add( $option, $value ) : void {
        $this->on_update( array(), $value );
    }

    /** Mes statistiques : tables locales et tâche quotidienne (purge, et envoi si partage). */
    public static function activate() : void {
        Stats_Store::maybe_install();
        Stats_Sender::schedule();
    }

    public static function start_sharing() : void {
        Stats_Client::identity();
        Stats_Sender::schedule();
    }

    /**
     * Retrait du consentement au partage : demande de suppression signée de tout ce qui a
     * été envoyé (rejouée chaque jour en cas d'échec réseau). Les statistiques locales
     * restent disponibles.
     */
    public static function stop_sharing() : void {
        delete_option( Stats_Sender::STATE_OPTION );
        Stats_Performance::clear_benchmark();
        if ( !Stats_Client::has_identity() ) {
            return;
        }
        update_option( self::PENDING_DELETE_OPTION, 1, false );
        self::send_pending_delete();
    }

    /** Statistiques désactivées : collecte coupée et compteurs locaux supprimés. */
    public static function disable() : void {
        Stats_Store::delete_all();
    }

    /** Ancien nom (1.1.0) : désactivation complète. */
    public static function withdraw() : void {
        self::stop_sharing();
        self::disable();
    }

    /** Rejoue une suppression en attente ; true si elle est confirmée par l'API. */
    public static function send_pending_delete() : bool {
        if ( !get_option( self::PENDING_DELETE_OPTION ) || !Stats_Client::has_identity() ) {
            delete_option( self::PENDING_DELETE_OPTION );
            return true;
        }
        $response = Stats_Client::request( 'DELETE', Stats_Client::endpoints()['site'] );
        // 401 site inconnu : rien n'a jamais été enregistré côté serveur.
        if ( in_array( $response['status'], array(200, 401, 410), true ) ) {
            Stats_Client::forget_identity();
            delete_option( self::PENDING_DELETE_OPTION );
            return true;
        }
        return false;
    }

    /** Lien « Désactiver les statistiques » (utilisable même quand le formulaire est verrouillé). */
    public function handle_disable_link() : void {
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Action non autorisée.', 'chic-checkout' ) );
        }
        check_admin_referer( self::DISABLE_ACTION );
        $settings = self::get();
        $settings['enabled'] = false;
        update_option( self::OPTION_KEY, $settings );
        wp_safe_redirect( admin_url( 'admin.php?page=' . Settings::PAGE_SLUG ) );
        exit;
    }

    /** Bouton « Envoyer maintenant » : même envoi que le cron de la nuit (journées terminées uniquement). */
    public function handle_send_now() : void {
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Action non autorisée.', 'chic-checkout' ) );
        }
        check_admin_referer( self::SEND_NOW_ACTION );
        Stats_Sender::run();
        wp_safe_redirect( admin_url( 'admin.php?page=' . Settings::PAGE_SLUG . '&chic-tab=' . self::TAB ) );
        exit;
    }

    /** Explication d'un code d'erreur d'envoi (Stats_Sender::send_day). */
    private static function error_message( string $code ) : string {
        if ( 'invalid_site' === $code ) {
            return __( 'Pays ou devise de la boutique non pris en charge : vérifiez WooCommerce → Réglages → Général.', 'chic-checkout' );
        }
        if ( 'http_0' === $code ) {
            return __( 'Serveur de statistiques injoignable depuis ce site (pare-feu, réseau ou hébergeur). Nouvel essai au prochain envoi.', 'chic-checkout' );
        }
        if ( 'timestamp_out_of_window' === $code ) {
            return __( 'L’horloge du serveur est décalée : demandez à votre hébergeur de la synchroniser. Nouvel essai au prochain envoi.', 'chic-checkout' );
        }
        if ( in_array( $code, array('rate_limited', 'registration_limit'), true ) ) {
            return __( 'Trop d’envois sur 24 heures : nouvel essai demain.', 'chic-checkout' );
        }
        if ( in_array( $code, array(
            'invalid_json',
            'invalid_payload',
            'date_out_of_window',
            'invalid_days',
            'payload_too_large'
        ), true ) ) {
            return __( 'Journée refusée par le serveur de statistiques. Contactez le support en indiquant ce code.', 'chic-checkout' );
        }
        if ( str_starts_with( $code, 'http_5' ) ) {
            return __( 'Serveur de statistiques momentanément indisponible. Nouvel essai au prochain envoi.', 'chic-checkout' );
        }
        return __( 'Envoi refusé. Nouvel essai au prochain envoi.', 'chic-checkout' );
    }

    public function render_status_field() : void {
        $settings = self::get();
        if ( !$settings['share'] ) {
            echo '<p class="description" style="margin-top:0;">' . esc_html__( 'Partage désactivé : rien n’est envoyé, vos statistiques restent sur ce site.', 'chic-checkout' ) . '</p>';
            return;
        }
        $status = Stats_Sender::status();
        $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $day = static fn( string $ymd ) => wp_date( (string) get_option( 'date_format' ), (int) strtotime( $ymd . ' 12:00:00' ) );
        $rows = array();
        if ( $status['last_sent_day'] ) {
            /* translators: 1: day of the statistics, 2: date and time of the send */
            $rows[] = array('ok', sprintf( __( 'Dernier envoi : journée du %1$s, envoyée le %2$s.', 'chic-checkout' ), $day( $status['last_sent_day'] ), wp_date( $format, $status['last_sent_at'] ) ));
        } elseif ( !$status['has_data'] ) {
            $rows[] = array('wait', __( 'Aucun envoi pour l’instant : aucune activité enregistrée depuis l’activation (panier ou checkout pas encore affiché à un visiteur).', 'chic-checkout' ));
        } else {
            $rows[] = array('wait', __( 'Aucun envoi pour l’instant. Seules les journées terminées sont envoyées : la première part la nuit suivant l’activation.', 'chic-checkout' ));
        }
        if ( $status['pending'] > 0 ) {
            /* translators: %d: number of days waiting to be sent */
            $rows[] = array('wait', sprintf( _n(
                '%d journée terminée en attente d’envoi.',
                '%d journées terminées en attente d’envoi.',
                $status['pending'],
                'chic-checkout'
            ), $status['pending'] ));
        }
        if ( $status['last_error'] ) {
            $rows[] = array('error', self::error_message( $status['last_error'] ) . ' (' . $status['last_error'] . ')');
        }
        $verification = array(
            'verified'   => array('ok', __( 'Licence vérifiée : votre boutique compte dans les moyennes de comparaison.', 'chic-checkout' )),
            'no_install' => array('error', __( 'Licence non activée sur ce site : vos statistiques sont reçues, mais ne comptent pas dans les moyennes de comparaison.', 'chic-checkout' )),
            'rejected'   => array('error', __( 'Licence non reconnue (inactive, annulée ou expirée) : vos statistiques sont reçues, mais ne comptent pas dans les moyennes de comparaison.', 'chic-checkout' )),
        );
        if ( isset( $verification[$status['verification']] ) ) {
            $rows[] = $verification[$status['verification']];
        }
        if ( $status['next_run'] ) {
            /* translators: %s: date and time of the next scheduled send */
            $rows[] = array('info', sprintf( __( 'Prochain envoi prévu : %s.', 'chic-checkout' ), wp_date( $format, $status['next_run'] ) ));
        }
        if ( $status['cron_disabled'] ) {
            $rows[] = array('info', __( 'Le cron de WordPress est désactivé sur ce site (DISABLE_WP_CRON) : l’envoi a lieu à l’ouverture de l’administration, ou via la tâche planifiée de votre hébergeur.', 'chic-checkout' ));
        }
        $colors = array(
            'ok'    => '#008a20',
            'wait'  => '#787c82',
            'error' => '#b32d2e',
            'info'  => '#50575e',
        );
        $icons = array(
            'ok'    => '●',
            'wait'  => '○',
            'error' => '●',
            'info'  => '·',
        );
        echo '<ul style="margin:0;max-width:760px;">';
        foreach ( $rows as $row ) {
            printf(
                '<li><span style="color:%1$s;" aria-hidden="true">%2$s</span> %3$s</li>',
                esc_attr( $colors[$row[0]] ),
                esc_html( $icons[$row[0]] ),
                esc_html( $row[1] )
            );
        }
        echo '</ul>';
        ?>
		<p>
			<a class="button" href="<?php 
        echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::SEND_NOW_ACTION ), self::SEND_NOW_ACTION ) );
        ?>"><?php 
        esc_html_e( 'Envoyer maintenant', 'chic-checkout' );
        ?></a>
			<span class="description"><?php 
        esc_html_e( 'Envoie les journées terminées en attente, sans attendre la nuit.', 'chic-checkout' );
        ?></span>
		</p>
		<?php 
    }

    public static function brand() : string {
        return ( self::is_whitelabeled() ? __( "l'éditeur de cette extension de checkout", 'chic-checkout' ) : 'Chic-checkout' );
    }

    /** Marque blanche (plan Agence de la version Pro) : aucune mention de Chic-checkout. */
    private static function is_whitelabeled() : bool {
        return false;
    }

    public function render_enabled_field() : void {
        $settings = self::get();
        ?>
		<label>
			<input type="checkbox" name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[enabled]" value="1" <?php 
        checked( $settings['enabled'] );
        ?> />
			<?php 
        esc_html_e( 'Activer mes statistiques', 'chic-checkout' );
        ?>
		</label>
		<p class="description" style="max-width:760px;">
			<?php 
        echo esc_html( ( Edition::has_pro_code() ? __( 'Calculées et gardées sur ce site, rien n’est envoyé : conversion panier → commande, finalisation du checkout, abandon à chaque étape, panier moyen, effet de l’upsell et de l’option cadeau, dans l’écran « Performances ». Uniquement des compteurs par journée, aucune donnée personnelle de vos clients.', 'chic-checkout' ) : __( 'Calculées et gardées sur ce site, rien n’est envoyé : taux de finalisation du panier et du checkout, dans l’écran « Performances ». Uniquement des compteurs par journée, aucune donnée personnelle de vos clients.', 'chic-checkout' ) ) );
        ?>
		</p>
		<?php 
    }

    public function render_share_field() : void {
        $settings = self::get();
        ?>
		<label>
			<input type="checkbox" name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[share]" value="1" <?php 
        checked( $settings['share'] );
        ?> />
			<?php 
        echo esc_html( self::share_label() );
        ?>
		</label>
		<div class="description" style="max-width:760px;margin-top:8px;">
			<p style="margin-top:0;">
				<?php 
        echo esc_html( ( Edition::has_pro_code() ? __( 'Facultatif, exige « Mes statistiques ». Débloque la comparaison avec la moyenne et la médiane des boutiques comparables (plans Pro et Agence).', 'chic-checkout' ) : __( 'Facultatif, exige « Mes statistiques ». Aide à améliorer Chic-checkout : vos chiffres ne sont jamais comparés ni publiés individuellement.', 'chic-checkout' ) ) );
        ?>
			</p>
			<p><?php 
        esc_html_e( 'Ce qui est partagé : uniquement des compteurs et des tranches par journée (paniers et checkouts affichés, étapes atteintes, commandes payées et leur répartition par moyen de paiement, mode de livraison, pays, heure, appareil ; usage des fonctionnalités du checkout ; erreurs d’affichage), envoyés une fois par jour.', 'chic-checkout' );
        ?></p>
			<p><?php 
        esc_html_e( 'Ce qui n’est jamais partagé : aucune donnée personnelle de vos clients (ni e-mail, nom, adresse, adresse IP, ni texte saisi comme le message cadeau, ni produit, ni identifiant de visiteur ou de session, ni cookie).', 'chic-checkout' );
        ?></p>
			<p><?php 
        echo esc_html( ( Edition::has_pro_code() ? __( 'Les données sont pseudonymes au niveau de votre site : un identifiant aléatoire, rattaché à votre installation de licence pour vérifier qu’elle est active.', 'chic-checkout' ) : __( 'Les données sont pseudonymes au niveau de votre site : un identifiant aléatoire, sans l’adresse de votre boutique ni aucun lien avec votre compte.', 'chic-checkout' ) ) );
        ?> <?php 
        esc_html_e( 'Hébergement : Supabase, dans l’Union européenne. Les journaux techniques de l’hébergeur (dont l’adresse IP de votre serveur) sont conservés quelques jours. Les études publiées ne contiennent que des résultats agrégés sur au moins 5 boutiques.', 'chic-checkout' );
        ?></p>
			<p>
				<?php 
        esc_html_e( 'Décocher arrête l’envoi et demande la suppression de toutes les données déjà partagées ; vos statistiques locales restent disponibles.', 'chic-checkout' );
        ?>
				<?php 
        if ( !self::is_whitelabeled() ) {
            ?>
					<a href="<?php 
            echo esc_url( self::TERMS_URL );
            ?>" target="_blank" rel="noopener noreferrer"><?php 
            esc_html_e( 'Conditions générales', 'chic-checkout' );
            ?></a>
				<?php 
        }
        ?>
			</p>
		</div>
		<?php 
    }

    /** Texte de la case de partage (onglet et fenêtre d'activation). */
    public static function share_label() : string {
        return sprintf( 
            /* translators: %s: Chic-checkout, ou une formule neutre en marque blanche */
            __( 'Je partage aussi des statistiques agrégées avec %s, sans aucune donnée de mes clients, pour me comparer aux boutiques similaires, améliorer le plugin et contribuer à des études sur l’e-commerce.', 'chic-checkout' ),
            self::brand()
         );
    }

    public function render_browser_field() : void {
        $settings = self::get();
        ?>
		<p class="description" style="margin-top:0;max-width:760px;">
			<?php 
        esc_html_e( 'Avec le partage uniquement.', 'chic-checkout' );
        ?>
			<?php 
        esc_html_e( 'Mesures complémentaires calculées dans le navigateur, en mémoire uniquement (affichages de l’upsell, barre de livraison offerte, carte des points relais, durées des étapes…). Elles ne sont actives que pour les visiteurs ayant accepté les statistiques via votre bandeau de consentement (WP Consent API, catégorie « statistics-anonymous » ou « statistics »).', 'chic-checkout' );
        ?>
		</p>
		<p>
			<?php 
        if ( function_exists( 'wp_has_consent' ) ) {
            ?>
				<span style="color:#008a20;">● <?php 
            esc_html_e( 'WP Consent API détectée : le choix de chaque visiteur est respecté.', 'chic-checkout' );
            ?></span>
			<?php 
        } else {
            ?>
				<span style="color:#787c82;">○ <?php 
            esc_html_e( 'WP Consent API non détectée : sans l’option ci-dessous, aucune mesure navigateur.', 'chic-checkout' );
            ?></span>
			<?php 
        }
        ?>
		</p>
		<label>
			<input type="checkbox" name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[browser_without_banner]" value="1" <?php 
        checked( $settings['browser_without_banner'] );
        ?> />
			<?php 
        esc_html_e( 'Activer les mesures navigateur sans bandeau de consentement', 'chic-checkout' );
        ?>
		</label>
		<p class="description" style="max-width:760px;">
			<strong><?php 
        esc_html_e( 'Attention :', 'chic-checkout' );
        ?></strong>
			<?php 
        esc_html_e( 'vérifiez que c’est conforme à votre situation (règles applicables aux mesures d’audience exemptées de consentement, information de vos clients dans votre politique de confidentialité). En cas de doute, laissez décoché et utilisez un bandeau compatible avec la WP Consent API.', 'chic-checkout' );
        ?>
		</p>
		<?php 
    }

    public function render_category_field() : void {
        $settings = self::get();
        $labels = array(
            'mode_accessoires'      => __( 'Mode et accessoires', 'chic-checkout' ),
            'maroquinerie_luxe'     => __( 'Maroquinerie et luxe', 'chic-checkout' ),
            'bijoux_horlogerie'     => __( 'Bijoux et horlogerie', 'chic-checkout' ),
            'beaute_cosmetique'     => __( 'Beauté et cosmétique', 'chic-checkout' ),
            'maison_deco'           => __( 'Maison et déco', 'chic-checkout' ),
            'alimentation_boissons' => __( 'Alimentation et boissons', 'chic-checkout' ),
            'bien_etre'             => __( 'Bien-être', 'chic-checkout' ),
            'sport_loisirs'         => __( 'Sport et loisirs', 'chic-checkout' ),
            'enfants'               => __( 'Enfants', 'chic-checkout' ),
            'culture_creation'      => __( 'Culture et création', 'chic-checkout' ),
            'services_reservations' => __( 'Services et réservations', 'chic-checkout' ),
            'produits_digitaux'     => __( 'Produits digitaux', 'chic-checkout' ),
            'autre'                 => __( 'Autre', 'chic-checkout' ),
        );
        ?>
		<select name="<?php 
        echo esc_attr( self::OPTION_KEY );
        ?>[category]">
			<option value=""><?php 
        esc_html_e( 'Non renseignée', 'chic-checkout' );
        ?></option>
			<?php 
        foreach ( $labels as $key => $label ) {
            ?>
				<option value="<?php 
            echo esc_attr( $key );
            ?>" <?php 
            selected( $settings['category'], $key );
            ?>><?php 
            echo esc_html( $label );
            ?></option>
			<?php 
        }
        ?>
		</select>
		<p class="description"><?php 
        esc_html_e( 'Facultatif, avec le partage : permet de vous comparer aux boutiques de la même catégorie.', 'chic-checkout' );
        ?></p>
		<?php 
    }

}
