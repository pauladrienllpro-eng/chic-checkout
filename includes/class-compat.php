<?php
/**
 * Robustesse à l'installation : décide si l'app React doit remplacer la page
 * (panier / checkout) et la protège des plugins d'optimisation JS.
 *
 * Deux principes :
 *  1. "Fail open" : si le build est absent/incomplet, ou si le visiteur passe
 *     ?chic-classic=1, on laisse WooCommerce afficher sa page standard plutôt
 *     qu'un template vide (page blanche = vente perdue).
 *  2. Nos pages ne doivent jamais être retardées, combinées, déplacées ou
 *     réécrites par un optimiseur (WP Rocket, LiteSpeed, SG Optimizer,
 *     Autoptimize, Jetpack Boost, Cloudflare Rocket Loader...) : le bundle lit
 *     window.chicCheckout dès son exécution, si l'ordre change il plante avant
 *     même que React ne monte — d'où la page blanche.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Compat {

	const CLASSIC_PARAM = 'chic-classic';

	const ENTRIES = array(
		'checkout' => 'src/main.tsx',
		'cart'     => 'src/main-cart.tsx',
	);

	/** @var array<string,mixed>|null */
	private static ?array $manifest = null;

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'disable_optimizers' ), 0 );
		add_filter( 'script_loader_tag', array( $this, 'protect_script_tags' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'maybe_notice_missing_build' ) );

		// SiteGround Optimizer : exclusions par handle (combinaison, minification, async).
		foreach ( array( 'sgo_javascript_combine_exclude', 'sgo_js_minify_exclude', 'sgo_js_async_exclude' ) as $filter ) {
			add_filter( $filter, array( $this, 'add_our_handles' ) );
		}

		// WP Rocket : en plus de DONOTROCKETOPTIMIZE, exclusions explicites du "Delay JS" / defer.
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'add_our_patterns' ) );
		add_filter( 'rocket_exclude_defer_js', array( $this, 'add_our_patterns' ) );
	}

	/**
	 * Quelle app React concerne la requête courante ('checkout', 'cart' ou null).
	 */
	public static function current_app(): ?string {
		if ( ! function_exists( 'is_checkout' ) ) {
			return null;
		}

		if ( is_checkout() && ! is_wc_endpoint_url() ) {
			return 'checkout';
		}

		if ( is_cart() ) {
			return 'cart';
		}

		return null;
	}

	public static function is_dev(): bool {
		return defined( 'CHIC_CHECKOUT_VITE_DEV' ) && CHIC_CHECKOUT_VITE_DEV;
	}

	/**
	 * Entrée du manifest Vite pour une app, uniquement si le fichier JS existe
	 * réellement sur le disque. null = build absent ou incomplet.
	 */
	public static function build_entry( string $app ): ?array {
		if ( null === self::$manifest ) {
			$path           = CHIC_CHECKOUT_DIR . Assets::build_dir() . '.vite/manifest.json';
			$decoded        = file_exists( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null;
			self::$manifest = is_array( $decoded ) ? $decoded : array();
		}

		$entry = self::$manifest[ self::ENTRIES[ $app ] ?? '' ] ?? null;

		if ( ! is_array( $entry ) || empty( $entry['file'] ) || ! file_exists( CHIC_CHECKOUT_DIR . Assets::build_dir() . $entry['file'] ) ) {
			return null;
		}

		return $entry;
	}

	/**
	 * Doit-on remplacer la page WooCommerce par l'app React ?
	 * Filtrable : add_filter( 'chic_checkout_render_app', '__return_false' ) coupe tout.
	 */
	public static function should_render( string $app ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- simple bascule d'affichage.
		if ( isset( $_GET[ self::CLASSIC_PARAM ] ) ) {
			return false;
		}

		$ready = self::is_dev() || null !== self::build_entry( $app );

		return (bool) apply_filters( 'chic_checkout_render_app', $ready, $app );
	}

	/**
	 * Raccourci : la requête courante affiche-t-elle une de nos apps React ?
	 */
	public static function rendering_app(): bool {
		$app = self::current_app();

		return null !== $app && self::should_render( $app );
	}

	public static function classic_url(): string {
		return add_query_arg( self::CLASSIC_PARAM, '1' );
	}

	/**
	 * Désactive les optimisations JS/HTML sur nos pages, via les conventions que
	 * chaque plugin respecte. Sans effet si le plugin concerné n'est pas installé.
	 */
	public function disable_optimizers(): void {
		if ( ! self::rendering_app() ) {
			return;
		}

		foreach ( array( 'DONOTCACHEPAGE', 'DONOTROCKETOPTIMIZE', 'DONOTMINIFY', 'DONOTCDN', 'LITESPEED_NO_OPTM' ) as $constant ) {
			if ( ! defined( $constant ) ) {
				define( $constant, true );
			}
		}

		// Autoptimize.
		add_filter( 'autoptimize_filter_noptimize', '__return_true' );
		// Jetpack Boost (defer des JS "non essentiels").
		add_filter( 'jetpack_boost_should_defer_js', '__return_false' );
		// LiteSpeed Cache.
		do_action( 'litespeed_control_set_nocache', 'chic-checkout' );
		do_action( 'litespeed_disable_all', 'chic-checkout' );
	}

	/**
	 * Cloudflare Rocket Loader réécrit le type des <script> et change leur ordre
	 * d'exécution : data-cfasync="false" l'en empêche. Appliqué à notre bundle,
	 * à son script inline de config (même $tag) et à ses dépendances wp-i18n.
	 */
	public function protect_script_tags( string $tag, string $handle ): string {
		if ( ! in_array( $handle, self::script_handles(), true ) || ! self::rendering_app() ) {
			return $tag;
		}

		return (string) preg_replace( '/<script(?![^>]*data-cfasync)/i', '<script data-cfasync="false"', $tag );
	}

	/**
	 * @param mixed $handles
	 * @return mixed
	 */
	public function add_our_handles( $handles ) {
		if ( ! is_array( $handles ) ) {
			return $handles;
		}

		return array_values( array_unique( array_merge( $handles, self::script_handles() ) ) );
	}

	/**
	 * @param mixed $patterns
	 * @return mixed
	 */
	public function add_our_patterns( $patterns ) {
		if ( ! is_array( $patterns ) ) {
			return $patterns;
		}

		return array_values( array_unique( array_merge( $patterns, array( '/chic-checkout/', 'chicCheckout', 'wp-i18n', 'wp-hooks', 'wp-polyfill', '/vendor/react' ) ) ) );
	}

	public function maybe_notice_missing_build(): void {
		if ( self::is_dev() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$missing = array_filter( array_keys( self::ENTRIES ), static fn( $app ) => null === self::build_entry( $app ) );

		if ( ! $missing ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		esc_html_e( 'Chic-checkout : les fichiers compilés (dossier build/) sont absents ou incomplets. Le panier et le checkout WooCommerce standard sont affichés à la place. Réinstallez le zip complet du plugin.', 'chic-checkout' );
		echo '</p></div>';
	}

	/**
	 * @return string[]
	 */
	private static function script_handles(): array {
		return array( Assets::HANDLE, Cart_Assets::HANDLE, 'wp-i18n', 'wp-hooks', 'wp-polyfill', 'react', 'react-dom', 'react-jsx-runtime' );
	}
}
