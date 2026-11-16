<?php
/**
 * SEO de la page checkout : noindex/nofollow, titre et meta description propres à
 * cette page — prioritaires sur ce que Yoast SEO / Rank Math auraient généré pour
 * elle (ou, à défaut d'extension SEO, sur le titre par défaut du thème). Une page de
 * paiement est un cas particulier : son contenu est propre à chaque client (panier,
 * adresse...) et n'a normalement rien à faire dans des résultats de recherche — ce
 * réglage permet de la retirer proprement du référencement sans toucher à la
 * configuration SEO globale du site.
 *
 * Ne touche à rien tant que rien n'est configuré au-delà de la valeur par défaut
 * (noindex/nofollow cochés) — voir Settings::get(). Titre et meta description restent
 * vides par défaut : rien n'est écrasé tant que l'admin n'a rien saisi.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Seo {

	public function __construct() {
		// Mécanisme natif WordPress (5.7+) pour la balise <meta name="robots"> — la
		// priorité élevée assure qu'on passe après Yoast/Rank Math s'ils y contribuent
		// aussi, pour avoir le dernier mot sur cette page précise.
		add_filter( 'wp_robots', array( $this, 'filter_wp_robots' ), 1000 );

		// Repli WordPress natif pour la balise <title> quand aucune extension SEO n'est
		// active (thème standard, `_wp_render_title_tag` sur `wp_head`).
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ), 1000 );

		// Yoast SEO : filtres dédiés à l'override par page, documentés pour cet usage —
		// ne passent pas par le calcul interne habituel de Yoast pour cette page.
		add_filter( 'wpseo_title', array( $this, 'filter_title' ), 1000 );
		add_filter( 'wpseo_metadesc', array( $this, 'filter_meta_description' ), 1000 );
		add_filter( 'wpseo_robots', array( $this, 'filter_yoast_robots' ), 1000 );

		// Rank Math : équivalents de ces 3 filtres.
		add_filter( 'rank_math/frontend/title', array( $this, 'filter_title' ), 1000 );
		add_filter( 'rank_math/frontend/description', array( $this, 'filter_meta_description' ), 1000 );
		add_filter( 'rank_math/frontend/robots', array( $this, 'filter_wp_robots' ), 1000 );

		// Repli si ni Yoast ni Rank Math ne sont actifs : aucun des deux n'imprimera sa
		// propre balise meta description, donc rien à écraser — on l'imprime nous-mêmes.
		add_action( 'wp_head', array( $this, 'maybe_print_meta_description' ), 1 );
	}

	/**
	 * Seule la page checkout elle-même est concernée — pas les endpoints qui y sont
	 * rattachés (order-received, order-pay...), déjà exclus partout ailleurs dans le
	 * plugin (voir Assets::dequeue_default_assets()) car ce ne sont pas des pages que
	 * ce plugin remplace.
	 */
	private function is_target_page(): bool {
		return function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url();
	}

	/**
	 * Pas de type déclaré sur $robots : ce filtre est aussi rebranché sur
	 * `rank_math/frontend/robots` (voir constructeur), dont la forme exacte n'est pas
	 * vérifiée ici — un type strict ferait planter TOUTE page du site (pas seulement le
	 * checkout, ce filtre étant invoqué globalement par ces extensions) si la valeur
	 * reçue s'avère différente de ce qui est supposé. Coûte de vérifier au runtime avec
	 * is_array() plutôt que de faire confiance à un contrat non garanti d'une extension
	 * tierce — voir le même raisonnement sur filter_yoast_robots() ci-dessous, où ce
	 * choix a évité un vrai incident.
	 *
	 * @param mixed $robots
	 * @return mixed
	 */
	public function filter_wp_robots( $robots ) {
		if ( ! is_array( $robots ) || ! $this->is_target_page() ) {
			return $robots;
		}

		$settings = Settings::get();

		if ( $settings['seo_noindex'] ) {
			unset( $robots['index'] );
			$robots['noindex'] = true;
		}

		if ( $settings['seo_nofollow'] ) {
			unset( $robots['follow'] );
			$robots['nofollow'] = true;
		}

		return $robots;
	}

	/**
	 * Yoast encode ses directives robots sous forme de CHAÎNE ("index, follow"), pas
	 * d'un tableau — un type déclaré `array $robots` ici a provoqué un TypeError fatal
	 * sur TOUTES les pages du site (Yoast appelle ce filtre globalement, pas seulement
	 * sur le checkout) dès l'activation, avant que ce correctif ne soit passé en prod.
	 * Gère les deux formes (chaîne observée en réel, tableau au cas où une version de
	 * Yoast ou une autre extension utilise ce même nom de filtre différemment) sans
	 * jamais lever d'erreur sur une forme imprévue.
	 *
	 * @param mixed $robots
	 * @return mixed
	 */
	public function filter_yoast_robots( $robots ) {
		if ( ! $this->is_target_page() ) {
			return $robots;
		}

		$settings = Settings::get();

		if ( is_string( $robots ) ) {
			$parts = array_filter( array_map( 'trim', explode( ',', $robots ) ) );

			if ( $settings['seo_noindex'] ) {
				$parts   = array_diff( $parts, array( 'index' ) );
				$parts[] = 'noindex';
			}

			if ( $settings['seo_nofollow'] ) {
				$parts   = array_diff( $parts, array( 'follow' ) );
				$parts[] = 'nofollow';
			}

			return implode( ', ', array_unique( $parts ) );
		}

		if ( is_array( $robots ) ) {
			if ( $settings['seo_noindex'] ) {
				$robots   = array_diff( $robots, array( 'index' ) );
				$robots[] = 'noindex';
			}

			if ( $settings['seo_nofollow'] ) {
				$robots   = array_diff( $robots, array( 'follow' ) );
				$robots[] = 'nofollow';
			}

			return array_values( array_unique( $robots ) );
		}

		// Forme ni chaîne ni tableau : on ne maîtrise pas ce contrat, on renonce à
		// surcharger ce champ plutôt que de risquer une nouvelle erreur fatale.
		return $robots;
	}

	/**
	 * @param string $title
	 * @return string
	 */
	public function filter_title( $title ) {
		if ( ! $this->is_target_page() ) {
			return $title;
		}

		$settings = Settings::get();

		return $settings['seo_meta_title'] ? $settings['seo_meta_title'] : $title;
	}

	/**
	 * @param string $description
	 * @return string
	 */
	public function filter_meta_description( $description ) {
		if ( ! $this->is_target_page() ) {
			return $description;
		}

		$settings = Settings::get();

		return $settings['seo_meta_description'] ? $settings['seo_meta_description'] : $description;
	}

	public function maybe_print_meta_description(): void {
		if ( ! $this->is_target_page() ) {
			return;
		}

		$settings = Settings::get();

		if ( ! $settings['seo_meta_description'] ) {
			return;
		}

		// Yoast/Rank Math impriment déjà leur propre balise (écrasée à la bonne valeur
		// via les filtres ci-dessus) : ne pas en ajouter une seconde si l'un des deux
		// est actif.
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) ) {
			return;
		}

		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $settings['seo_meta_description'] ) );
	}
}
