<?php
/**
 * Fenêtre d'activation des statistiques, proposée à l'administrateur.
 *
 * « Vos statistiques de checkout » + case de partage DÉCOCHÉE (consentement distinct et
 * facultatif) + [Activer mes statistiques] / [Plus tard]. Jamais de case pré-cochée.
 *
 * Affichée sur les écrans Chic-checkout seulement (jamais sur le reste de l'administration), tant que les
 * statistiques ne sont pas activées. « Plus tard » : nouvelle proposition 30 jours plus
 * tard, 3 fois au maximum, puis plus jamais (l'onglet Statistiques reste disponible).
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stats_Onboarding {

	const OPTION_KEY = 'chic_checkout_stats_prompt';
	const ACTION     = 'chic_stats_onboarding';
	const MAX_PROMPTS = 3;
	const SCREENS    = array( 'toplevel_page_chic-checkout-settings', 'chic-checkout_page_chic-checkout-performance' );

	public function __construct() {
		add_action( 'admin_footer', array( $this, 'maybe_render' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/** @return array{count:int,next_at:int,done:bool} */
	private static function state(): array {
		$state = get_option( self::OPTION_KEY );
		$state = is_array( $state ) ? $state : array();
		return array(
			'count'   => (int) ( $state['count'] ?? 0 ),
			'next_at' => (int) ( $state['next_at'] ?? 0 ),
			'done'    => ! empty( $state['done'] ),
		);
	}

	/** Statistiques activées (ici ou dans l'onglet) : ne plus jamais proposer. */
	public static function mark_done(): void {
		$state         = self::state();
		$state['done'] = true;
		update_option( self::OPTION_KEY, $state, false );
	}

	public static function should_show(): bool {
		if ( ! current_user_can( 'manage_woocommerce' ) || Stats_Settings::is_enabled() ) {
			return false;
		}
		$state = self::state();
		if ( $state['done'] || $state['count'] >= self::MAX_PROMPTS || time() < $state['next_at'] ) {
			return false;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && in_array( $screen->id, self::SCREENS, true );
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'chic-checkout' ) );
		}
		check_admin_referer( self::ACTION );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- vérifié ci-dessus.
		$choice = isset( $_POST['choice'] ) ? sanitize_key( wp_unslash( $_POST['choice'] ) ) : '';
		if ( 'activate' === $choice ) {
			$settings            = Stats_Settings::get();
			$settings['enabled'] = true;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- vérifié ci-dessus.
			$settings['share'] = ! empty( $_POST['share'] );
			update_option( Stats_Settings::OPTION_KEY, $settings );
			self::mark_done();
		} else {
			$state            = self::state();
			$state['count']  += 1;
			$state['next_at'] = time() + 30 * DAY_IN_SECONDS;
			$state['done']    = $state['count'] >= self::MAX_PROMPTS;
			update_option( self::OPTION_KEY, $state, false );
		}

		$back = wp_get_referer();
		wp_safe_redirect( 'activate' === $choice ? admin_url( 'admin.php?page=' . Stats_Performance::PAGE_SLUG ) : ( $back ? $back : admin_url() ) );
		exit;
	}

	public function maybe_render(): void {
		if ( ! self::should_show() ) {
			return;
		}
		$learn_more = admin_url( 'admin.php?page=' . Settings::PAGE_SLUG . '&chic-tab=' . Stats_Settings::TAB );
		?>
		<div class="chic-onboarding" id="chic-onboarding">
			<div class="chic-onboarding__dialog" role="dialog" aria-modal="true" aria-labelledby="chic-onboarding-title">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
					<?php wp_nonce_field( self::ACTION ); ?>
					<h2 id="chic-onboarding-title"><?php esc_html_e( 'Vos statistiques de checkout', 'chic-checkout' ); ?></h2>
					<p><?php esc_html_e( 'Suivez votre conversion panier → commande, l’abandon à chaque étape, votre panier moyen et l’effet de l’upsell et de l’option cadeau.', 'chic-checkout' ); ?></p>
					<label class="chic-onboarding__share">
						<input type="checkbox" name="share" value="1" />
						<span>
							<?php echo esc_html( Stats_Settings::share_label() ); ?>
							<em><?php esc_html_e( 'Désactivable à tout moment.', 'chic-checkout' ); ?></em>
							<a href="<?php echo esc_url( $learn_more ); ?>"><?php esc_html_e( 'En savoir plus', 'chic-checkout' ); ?></a>
						</span>
					</label>
					<div class="chic-onboarding__actions">
						<button type="submit" name="choice" value="activate" class="button button-primary"><?php esc_html_e( 'Activer mes statistiques', 'chic-checkout' ); ?></button>
						<button type="submit" name="choice" value="later" class="button-link"><?php esc_html_e( 'Plus tard', 'chic-checkout' ); ?></button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
