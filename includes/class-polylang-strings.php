<?php
/**
 * Intégration Polylang : traduction du contenu configuré par l'admin (libellés,
 * textes de la popin de contact, blocs de réassurance...). Indépendant des chaînes
 * fixes de l'UI, gérées via wp-i18n/Loco Translate côté React (voir class-assets.php
 * et src/lib/i18n.ts) — ici il s'agit de contenu saisi dans l'écran de réglages, que
 * seul l'admin du site peut traduire lui-même, langue par langue.
 *
 * No-op complet si Polylang n'est pas actif : chaque appel est gardé par
 * function_exists(), aucune dépendance dure sur le plugin.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Polylang_Strings {

	/** Onglet affiché dans Polylang > Traduction des chaînes. */
	const GROUP = 'Chic-checkout';

	/**
	 * Libellé lisible (colonne "Chaîne" de l'écran Polylang) pour chaque champ de
	 * réglage à texte libre configurable par l'admin. Les champs purement techniques
	 * (URLs, identifiants, couleurs...) n'y figurent pas : rien à traduire pour eux.
	 */
	public static function fields(): array {
		return array(
			'back_label'                   => __( 'Bouton retour panier', 'chic-checkout' ),
			'logo_text'                    => __( 'Texte du logo', 'chic-checkout' ),
			'order_summary_label'          => __( 'Titre du récapitulatif de commande', 'chic-checkout' ),
			'step_name_1'                  => __( "Nom de l'étape 1", 'chic-checkout' ),
			'step_name_2'                  => __( "Nom de l'étape 2", 'chic-checkout' ),
			'step_name_3'                  => __( "Nom de l'étape 3", 'chic-checkout' ),
			'free_shipping_label'          => __( 'Libellé livraison offerte', 'chic-checkout' ),
			'shipping_info_banner_text'    => __( "Texte de la bannière d'information (livraison)", 'chic-checkout' ),
			'cta_login_email_label'        => __( 'Bouton - Connexion (email)', 'chic-checkout' ),
			'cta_login_password_label'     => __( 'Bouton - Connexion (mot de passe)', 'chic-checkout' ),
			'cta_login_guest_label'        => __( 'Bouton - Continuer en invité', 'chic-checkout' ),
			'cta_login_register_label'     => __( 'Bouton - Créer mon compte', 'chic-checkout' ),
			'cta_shipping_address_label'   => __( 'Bouton - Adresse de livraison', 'chic-checkout' ),
			'cta_shipping_method_label'    => __( 'Bouton - Mode de livraison', 'chic-checkout' ),
			'cta_billing_label'            => __( 'Bouton - Facturation', 'chic-checkout' ),
			'cta_payment_label'            => __( 'Bouton - Paiement', 'chic-checkout' ),
			'terms_checkbox_label'         => __( 'Texte de la case à cocher CGV', 'chic-checkout' ),
			'login_intro_title'            => __( "Titre de l'étape connexion", 'chic-checkout' ),
			'login_intro_text'             => __( "Texte de l'étape connexion", 'chic-checkout' ),
			'contact_label'                => __( 'Libellé du bouton contact', 'chic-checkout' ),
			'contact_popup_title'          => __( 'Titre de la popin de contact', 'chic-checkout' ),
			'contact_popup_text'           => __( 'Texte de la popin de contact', 'chic-checkout' ),
			'footer_contrast_toggle_label' => __( 'Libellé du toggle de contraste', 'chic-checkout' ),
			'thankyou_intro_text'          => __( "Texte d'introduction (page de confirmation)", 'chic-checkout' ),
			'thankyou_back_label'          => __( 'Bouton retour (page de confirmation)', 'chic-checkout' ),
			'review_title'                 => __( 'Titre de la popin d’avis (page de confirmation)', 'chic-checkout' ),
			'cart_back_label'                   => __( 'Bouton retour (panier)', 'chic-checkout' ),
			'empty_cart_text'                   => __( 'Texte du panier vide', 'chic-checkout' ),
			'cart_gift_carousel_title'          => __( 'Titre du carrousel de cadeaux (panier)', 'chic-checkout' ),
			'cart_gift_carousel_text'           => __( 'Texte du carrousel de cadeaux (panier)', 'chic-checkout' ),
			'cart_free_shipping_text'           => __( 'Texte livraison gratuite avant déblocage (panier)', 'chic-checkout' ),
			'cart_free_shipping_text_unlocked'  => __( 'Texte livraison gratuite débloquée (panier)', 'chic-checkout' ),
			'cart_promo_banner_text'            => __( 'Texte de la bannière d’opération spéciale (panier)', 'chic-checkout' ),
		);
	}

	public function __construct() {
		add_action( 'init', array( $this, 'register_strings' ) );
	}

	/**
	 * Enregistre chaque chaîne configurée auprès de Polylang. Tourne sur CHAQUE
	 * chargement (init), pas seulement à l'activation : c'est ce qui permet à l'écran
	 * "Traduction des chaînes" de refléter immédiatement un réglage tout juste modifié,
	 * sans étape de synchronisation manuelle.
	 */
	public function register_strings(): void {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		$settings = Settings::get();

		foreach ( self::fields() as $key => $label ) {
			self::register( $label, (string) ( $settings[ $key ] ?? '' ) );
		}

		// Listes à taille variable (jusqu'à 5 liens footer, 4 blocs réassurance) :
		// Polylang associe la traduction au CONTENU de la chaîne, pas à ce nom généré —
		// un réordonnancement des lignes en admin ne fait donc perdre aucune traduction
		// déjà saisie, seul le libellé affiché dans son propre écran change de position.
		foreach ( $settings['footer_links'] as $index => $row ) {
			self::register(
				sprintf( /* translators: %d: row number */ __( 'Footer - Lien %d - Libellé', 'chic-checkout' ), $index + 1 ),
				(string) ( $row['label'] ?? '' )
			);
		}

		foreach ( $settings['reassurance_items'] as $index => $row ) {
			self::register(
				sprintf( /* translators: %d: row number */ __( 'Réassurance - Bloc %d - Titre', 'chic-checkout' ), $index + 1 ),
				(string) ( $row['title'] ?? '' )
			);
			self::register(
				sprintf( /* translators: %d: row number */ __( 'Réassurance - Bloc %d - Texte', 'chic-checkout' ), $index + 1 ),
				(string) ( $row['text'] ?? '' )
			);
		}
	}

	private static function register( string $name, string $value ): void {
		if ( '' === $value ) {
			return;
		}

		pll_register_string( $name, $value, self::GROUP );
	}

	/**
	 * Traduit une valeur déjà enregistrée dans la langue actuellement affichée —
	 * renvoyée telle quelle si Polylang est inactif ou si la chaîne est vide (rien
	 * d'enregistré pour une valeur vide, voir register() ci-dessus).
	 */
	public static function translate( string $value ): string {
		if ( '' === $value || ! function_exists( 'pll__' ) ) {
			return $value;
		}

		return pll__( $value );
	}
}
