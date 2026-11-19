<?php

/**
 * Édition du plugin : gratuite (WordPress.org) ou Pro (Freemius).
 *
 * Seul point d'entrée du code commun pour savoir si les fonctions Pro sont disponibles.
 * Dans la version gratuite générée par Freemius, les blocs réservés à la version Pro
 * sont retirés : chaque méthode renvoie alors la valeur de l'édition gratuite, sans aucune
 * notion de licence.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Edition {
    /** Page de présentation des plans (lien « Découvrir la version Pro »). */
    const PRICING_URL = 'https://pauladrien.fr/chic-checkout/#tarifs';

    /** Forum de support WordPress.org de l'extension (version gratuite). */
    const FORUM_URL = 'https://wordpress.org/support/plugin/chic-checkout/';

    /** Support prioritaire par e-mail (version Pro). */
    const SUPPORT_EMAIL = 'hello@pauladrien.fr';

    /**
     * Version Pro installée (licence valide ou non) ? Ne bride aucune fonction : sert à
     * présenter la version Pro dans la version gratuite et à adapter les textes.
     */
    public static function has_pro_code() : bool {
        return false;
    }

    /** 'pro' ou 'free' (statistiques : champ edition). */
    public static function name() : string {
        return 'free';
    }

    /**
     * Page de paiement Freemius dans l'administration (« Passer en Pro »).
     */
    public static function upgrade_url() : string {
        return chic_checkout_fs()->get_upgrade_url();
    }

    /**
     * Page de présentation des plans, avec le suivi de campagne de l'option concernée.
     */
    public static function discover_url( string $campaign ) : string {
        return add_query_arg( array(
            'utm_source'   => 'plugin-free',
            'utm_medium'   => 'admin',
            'utm_campaign' => sanitize_title( $campaign ),
        ), self::PRICING_URL );
    }

}
