<?php
/**
 * Texte suggéré pour la politique de confidentialité du site (Réglages >
 * Confidentialité) : données transmises à Freemius pour la licence, et, si le marchand
 * les a activées, statistiques agrégées et pseudonymes (deux versions du texte).
 *
 * Liste alignée sur ce qu'envoie le SDK Freemius 2.13 (Freemius::get_opt_in_params(),
 * FS_Permission_Manager) : les permissions « essentials » et « events » sont exigées
 * par l'activation d'une licence ; « diagnostic » et « extensions » sont facultatives
 * et décochées par défaut (voir chic-checkout.php).
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Privacy {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_policy_content' ) );
	}

	public function add_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		// Statistiques locales seules : rien n'est transmis, le texte de base reste exact.
		$stats = class_exists( Stats_Settings::class ) && Stats_Settings::is_sharing();

		$content = '<p class="privacy-policy-tutorial">' . ( $stats
			? esc_html__( "Ce texte concerne l'administration du site. Les statistiques Chic-checkout sont activées : seuls des compteurs agrégés par journée sont transmis, aucune donnée personnelle de vos clients.", 'chic-checkout' )
			: esc_html__( "Ce texte concerne l'administration du site, pas les données de vos clients : Chic-checkout n'envoie aucune donnée de commande ni de client à un service tiers.", 'chic-checkout' ) ) . '</p>'
			. '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Texte suggéré :', 'chic-checkout' ) . ' </strong>'
			. ( Edition::has_pro_code()
				? esc_html__( "Ce site utilise l'extension Chic-checkout, dont la licence est gérée par Freemius, Inc. (États-Unis). Lors de l'activation de la licence, puis lors des vérifications périodiques de licence et de mises à jour, sont transmis à Freemius : l'adresse email, le prénom et le nom de l'administrateur qui active la licence, l'adresse (URL) du site et un identifiant technique du site, la clé de licence, les versions de l'extension et de son kit Freemius, l'état de l'extension (active, désactivée, désinstallée) et l'adresse IP du serveur. Les versions de WordPress et de PHP, le titre et la langue du site ne sont transmis que si l'administrateur coche l'option facultative « Informations de diagnostic » lors de l'activation. Ces données servent uniquement à gérer la licence, les mises à jour et le support.", 'chic-checkout' )
				: esc_html__( "Ce site utilise l'extension Chic-checkout. Seulement si l'administrateur l'a accepté (proposition affichée après l'installation, que l'on peut ignorer), Freemius, Inc. (États-Unis) reçoit : l'adresse email, le prénom et le nom de cet administrateur, l'adresse (URL) du site et un identifiant technique du site, les versions de l'extension et de son kit Freemius, l'état de l'extension (active, désactivée, désinstallée) et l'adresse IP du serveur. Les versions de WordPress et de PHP, le titre et la langue du site ne sont transmis que si l'administrateur coche l'option facultative « Informations de diagnostic ». Ces données servent à prévenir des mises à jour importantes et à améliorer l'extension. Sans cet accord, rien n'est transmis à Freemius.", 'chic-checkout' ) )
			. ' ' . ( $stats
				? ( Edition::has_pro_code()
					? esc_html__( "Ce site partage également avec l'éditeur de Chic-checkout des statistiques agrégées sur le tunnel de commande : uniquement des compteurs et des tranches par journée (par exemple le nombre de paniers et de checkouts affichés, de commandes payées, leur répartition par moyen de paiement, mode de livraison, pays, heure et type d'appareil, l'usage des fonctionnalités du checkout). Aucune donnée personnelle des clients n'est transmise : ni nom, adresse email, adresse postale ou adresse IP, ni contenu saisi, ni identifiant de visiteur ; aucun cookie n'est déposé pour ces mesures. Les mesures effectuées dans le navigateur ne le sont qu'avec le consentement du visiteur. Ces statistiques sont pseudonymes au niveau du site (identifiant aléatoire du site, rattaché à son installation de licence) et hébergées dans l'Union européenne (Supabase) ; les journaux techniques de l'hébergeur, qui contiennent l'adresse IP du serveur de ce site, sont conservés quelques jours.", 'chic-checkout' )
					: esc_html__( "Ce site partage également avec l'éditeur de Chic-checkout des statistiques agrégées sur le tunnel de commande : uniquement des compteurs et des tranches par journée (par exemple le nombre de paniers et de checkouts affichés, de commandes payées, leur répartition par moyen de paiement, mode de livraison, pays, heure et type d'appareil, l'usage des fonctionnalités du checkout). Aucune donnée personnelle des clients n'est transmise : ni nom, adresse email, adresse postale ou adresse IP, ni contenu saisi, ni identifiant de visiteur ; aucun cookie n'est déposé pour ces mesures. Les mesures effectuées dans le navigateur ne le sont qu'avec le consentement du visiteur. Ces statistiques sont pseudonymes au niveau du site (identifiant aléatoire du site, sans son adresse) et hébergées dans l'Union européenne (Supabase) ; les journaux techniques de l'hébergeur, qui contiennent l'adresse IP du serveur de ce site, sont conservés quelques jours.", 'chic-checkout' ) )
				: esc_html__( 'Aucune donnée de vos clients ni de vos commandes n’est transmise.', 'chic-checkout' ) )
			. '</p>'
			. '<p>' . sprintf(
				/* translators: %s: lien vers la politique de confidentialité de Freemius */
				esc_html__( 'Politique de confidentialité de Freemius : %s', 'chic-checkout' ),
				'<a href="https://freemius.com/privacy/">https://freemius.com/privacy/</a>'
			) . '</p>';

		wp_add_privacy_policy_content( 'Chic-checkout', wp_kses_post( $content ) );
	}
}
