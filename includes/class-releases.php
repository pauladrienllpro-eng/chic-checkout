<?php

/**
 * Onglet « Nouveautés » : notes de version de Chic-checkout (numéro, date, améliorations).
 *
 * Version gratuite : changelog de readme.txt embarqué (versions jusqu'à celle installée,
 * sans date), aucun appel externe.
 *
 * Version Pro : notes lues sur Chic Insights (fonction publique `releases`, les mêmes pour
 * toutes les boutiques) et gardées 12 h en cache, changelog embarqué en repli. La requête
 * n'envoie rien sur la boutique : GET sans paramètre, agent utilisateur
 * « Chic-checkout/x.y.z » au lieu de celui de WordPress (qui contient l'adresse du site).
 *
 * Badge « Nouveau » sur l'onglet tant que l'administrateur n'a pas ouvert l'onglet depuis
 * la dernière version publiée (méta utilisateur, par administrateur).
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Releases {
    const TRANSIENT = 'chic_checkout_releases';

    const SEEN_META = 'chic_checkout_releases_seen';

    const SEEN_ACTION = 'chic_checkout_releases_seen';

    const CACHE_TTL = 12 * HOUR_IN_SECONDS;

    /** Échec de lecture : nouvel essai dans 1 h, changelog embarqué en attendant. */
    const RETRY_TTL = HOUR_IN_SECONDS;

    public function __construct() {
        add_action( 'admin_post_' . self::SEEN_ACTION, array($this, 'handle_seen') );
    }

    /**
     * Versions, la plus récente d'abord.
     *
     * @return array<int, array{version: string, date: string, notes: string[]}>
     */
    public static function all() : array {
        return self::bundled();
    }

    /**
     * Changelog de readme.txt (« = 1.2.2 = » puis « * … ») : jusqu'à la version installée.
     *
     * @return array<int, array{version: string, date: string, notes: string[]}>
     */
    public static function bundled() : array {
        $readme = CHIC_CHECKOUT_DIR . 'readme.txt';
        $text = ( is_readable( $readme ) ? (string) file_get_contents( $readme ) : '' );
        $parts = preg_split( '/^== Changelog ==\\s*$/m', $text );
        $lines = explode( "\n", (string) ($parts[1] ?? '') );
        $releases = array();
        foreach ( $lines as $line ) {
            if ( preg_match( '/^= (\\d+\\.\\d+\\.\\d+) =\\s*$/', $line, $match ) ) {
                $releases[] = array(
                    'version' => $match[1],
                    'date'    => '',
                    'notes'   => array(),
                );
            } elseif ( str_starts_with( $line, '* ' ) && $releases ) {
                $releases[count( $releases ) - 1]['notes'][] = sanitize_text_field( substr( $line, 2 ) );
            } elseif ( str_starts_with( $line, '== ' ) ) {
                break;
            }
        }
        return array_values( array_filter( $releases, static fn( $release ) => (bool) $release['notes'] ) );
    }

    public static function latest_version() : string {
        $releases = self::all();
        return ( $releases ? $releases[0]['version'] : '' );
    }

    /** Une version publiée que cet administrateur n'a pas encore vue dans l'onglet. */
    public static function has_unseen() : bool {
        $latest = self::latest_version();
        if ( '' === $latest ) {
            return false;
        }
        $seen = (string) get_user_meta( get_current_user_id(), self::SEEN_META, true );
        return '' === $seen || version_compare( $latest, $seen, '>' );
    }

    /** Ouverture de l'onglet (admin-settings.js) : dernière version marquée comme vue. */
    public function handle_seen() : void {
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Action non autorisée.', 'chic-checkout' ), '', array(
                'response' => 403,
            ) );
        }
        check_admin_referer( self::SEEN_ACTION );
        $latest = self::latest_version();
        if ( '' !== $latest ) {
            update_user_meta( get_current_user_id(), self::SEEN_META, $latest );
        }
        wp_send_json_success();
    }

    public static function seen_url() : string {
        return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::SEEN_ACTION ), self::SEEN_ACTION );
    }

}
