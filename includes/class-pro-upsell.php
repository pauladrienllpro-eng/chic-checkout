<?php
/**
 * Encarts « Pro » de la version gratuite, en fin de section des réglages.
 *
 * Uniquement du texte et des liens : le nom de la fonction, un badge « Pro » et un
 * « À quoi ça sert ? » qui déplie deux lignes d'explication, « Passer en Pro » (page de
 * paiement Freemius dans l'administration) et « Découvrir la version Pro » (site de
 * l'éditeur). Aucun interrupteur, aucune logique de la fonction : son code n'existe pas
 * dans la version gratuite. Affiché sur l'écran de réglages de l'extension seulement,
 * jamais ailleurs dans l'administration ni sur la boutique.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pro_Upsell {

	/**
	 * Fonctions Pro présentées dans chaque panneau des réglages.
	 *
	 * @return array<string, array<int, array{key: string, title: string, text: string}>>
	 */
	public static function features(): array {
		return array(
			'cart-general'      => array(
				array(
					'key'   => 'paiement-express',
					'title' => __( 'Paiement express depuis le panier', 'chic-checkout' ),
					'text'  => __( 'Apple Pay, Google Pay et PayPal directement sous « Aller au paiement » : le client paie en deux gestes, adresse et livraison comprises.', 'chic-checkout' ),
				),
				array(
					'key'   => 'options-cadeau',
					'title' => __( 'Options cadeau', 'chic-checkout' ),
					'text'  => __( 'Emballages cadeau (selon la catégorie, l’étiquette ou la classe de livraison), message personnalisé et produits offerts au-delà d’un montant.', 'chic-checkout' ),
				),
				array(
					'key'   => 'banniere-operation',
					'title' => __( 'Bannière d’opération spéciale', 'chic-checkout' ),
					'text'  => __( 'Un message en haut du panier pour vos soldes ou ventes privées, avec compte à rebours et lien.', 'chic-checkout' ),
				),
				array(
					'key'   => 'ordre-des-blocs',
					'title' => __( 'Ordre des blocs et taille des tuiles', 'chic-checkout' ),
					'text'  => __( 'Choisissez l’ordre des blocs du panier sur ordinateur et sur mobile, et la taille des photos produits (grande dans la version gratuite).', 'chic-checkout' ),
				),
			),
			'cart-upsell'       => array(
				array(
					'key'   => 'criteres-upsell',
					'title' => __( 'Critères de recommandation', 'chic-checkout' ),
					'text'  => __( 'Choisissez les produits proposés : moins chers que le panier, liés, mis en avant par étiquette, avec des exclusions par catégorie ou étiquette. La version gratuite propose vos ventes croisées puis les produits liés.', 'chic-checkout' ),
				),
				array(
					'key'   => 'livraison-offerte',
					'title' => __( 'Barre de livraison offerte', 'chic-checkout' ),
					'text'  => __( 'Indique au client combien il lui reste à ajouter pour obtenir la livraison gratuite : un levier simple pour augmenter le panier moyen.', 'chic-checkout' ),
				),
				array(
					'key'   => 'points-fidelite',
					'title' => __( 'Points de fidélité', 'chic-checkout' ),
					'text'  => __( 'Affiche dans le panier et le checkout les points myCred que la commande rapportera.', 'chic-checkout' ),
				),
			),
			'checkout-login'    => array(
				array(
					'key'   => 'connexion-google-apple',
					'title' => __( 'Connexion avec Google et Apple', 'chic-checkout' ),
					'text'  => __( 'Le client s’identifie en un geste avec son compte Google ou Apple, sans mot de passe à retenir.', 'chic-checkout' ),
				),
				array(
					'key'   => 'reassurance-compte',
					'title' => __( 'Réassurance création de compte', 'chic-checkout' ),
					'text'  => __( 'Sous « Créer un compte », montrez ce que le compte apporte (livraison offerte, points de fidélité, suivi de commande) en bannière, en liste ou en tuiles visuelles.', 'chic-checkout' ),
				),
			),
			'checkout-shipping' => array(
				array(
					'key'   => 'banniere-livraison',
					'title' => __( 'Bannière d’informations de livraison', 'chic-checkout' ),
					'text'  => __( 'Un message à l’étape Livraison : délais pendant les fêtes, fermeture estivale, conditions particulières.', 'chic-checkout' ),
				),
				array(
					'key'   => 'validation-champs',
					'title' => __( 'Validation des champs', 'chic-checkout' ),
					'text'  => __( 'Imposez un format aux champs du formulaire (téléphone, code postal…) avec votre propre message d’erreur.', 'chic-checkout' ),
				),
			),
			'checkout-payment'  => array(
				array(
					'key'   => 'logos-paiement',
					'title' => __( 'Logos de paiement personnalisés', 'chic-checkout' ),
					'text'  => __( 'Remplacez le logo d’un moyen de paiement par le vôtre, et affichez-le à côté de son nom ou à sa place.', 'chic-checkout' ),
				),
			),
			'thankyou-general'  => array(
				array(
					'key'   => 'avis-clients',
					'title' => __( 'Avis clients', 'chic-checkout' ),
					'text'  => __( 'Après la commande, une note sur 5 et un commentaire facultatif, enregistrés sur votre site : suivez la satisfaction de vos acheteurs.', 'chic-checkout' ),
				),
			),
			'appearance'        => array(
				array(
					'key'   => 'design-maison',
					'title' => __( 'Design Chic-checkout et couleurs', 'chic-checkout' ),
					'text'  => __( 'Au lieu de reprendre votre thème, utilisez le design Chic-checkout et choisissez la couleur du fond, de l’accent et des boutons.', 'chic-checkout' ),
				),
				array(
					'key'   => 'textes-personnalises',
					'title' => __( 'Textes personnalisés', 'chic-checkout' ),
					'text'  => __( 'Remplacez n’importe quel texte affiché à vos clients (panier, checkout, confirmation), sans traduction ni code.', 'chic-checkout' ),
				),
				array(
					'key'   => 'marque-blanche',
					'title' => __( 'Marque blanche', 'chic-checkout' ),
					'text'  => __( 'Pour les agences : aucune mention de Chic-checkout, ni sur la boutique ni dans l’administration de vos clients.', 'chic-checkout' ),
				),
			),
			'reassurance'       => array(
				array(
					'key'   => 'images-reassurance',
					'title' => __( 'Images personnalisées', 'chic-checkout' ),
					'text'  => __( 'Utilisez vos propres images pour les blocs de réassurance, en plus des icônes fournies.', 'chic-checkout' ),
				),
			),
			'contact'           => array(
				array(
					'key'   => 'icone-contact',
					'title' => __( 'Icône personnalisée', 'chic-checkout' ),
					'text'  => __( 'Remplacez l’icône du bouton Contact par la vôtre.', 'chic-checkout' ),
				),
			),
			'stats'             => array(
				array(
					'key'   => 'performances',
					'title' => __( 'Écran Performances complet', 'chic-checkout' ),
					'text'  => __( 'Abandons à chaque étape, panier moyen avec ou sans upsell et options cadeau, échecs de paiement, et comparaison avec les boutiques comparables.', 'chic-checkout' ),
				),
			),
			'support'           => array(
				array(
					'key'   => 'support-prioritaire',
					'title' => __( 'Support prioritaire', 'chic-checkout' ),
					'text'  => __( 'Une réponse par e-mail, signalement de bug avec captures depuis l’administration et accès support temporaire et sécurisé.', 'chic-checkout' ),
				),
			),
		);
	}

	/**
	 * Encarts d'un panneau des réglages (rien si le panneau n'en a pas).
	 */
	public static function render( string $panel ): void {
		$features = self::features()[ $panel ] ?? array();
		if ( ! $features ) {
			return;
		}

		?>
		<div class="chic-pro-upsell">
			<h3 class="chic-pro-upsell__title"><?php esc_html_e( 'Avec la version Pro', 'chic-checkout' ); ?></h3>
			<ul class="chic-pro-upsell__list">
				<?php foreach ( $features as $feature ) : ?>
					<li class="chic-pro-upsell__item">
						<details>
							<summary>
								<span class="chic-pro-upsell__name"><?php echo esc_html( $feature['title'] ); ?></span>
								<span class="chic-pro-upsell__badge"><?php esc_html_e( 'Pro', 'chic-checkout' ); ?></span>
								<span class="chic-pro-upsell__why"><?php esc_html_e( 'À quoi ça sert ?', 'chic-checkout' ); ?></span>
							</summary>
							<p><?php echo esc_html( $feature['text'] ); ?></p>
							<p class="chic-pro-upsell__links">
								<a href="<?php echo esc_url( Edition::upgrade_url() ); ?>"><?php esc_html_e( 'Passer en Pro', 'chic-checkout' ); ?></a>
								<a href="<?php echo esc_url( Edition::discover_url( $feature['key'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Découvrir la version Pro', 'chic-checkout' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(nouvel onglet)', 'chic-checkout' ); ?></span></a>
							</p>
						</details>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
