<?php
/**
 * Template minimal qui remplace le panier WooCommerce classique.
 * Document HTML autonome (ne dépend pas du header/footer du thème actif), même
 * traitement "distraction-free" que le checkout — voir templates/checkout-app.php.
 * Toute la logique vit dans le bundle React monté sur #chic-checkout-root.
 *
 * @package ChicCheckout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( 'theme' !== \ChicCheckout\Settings::get()['style_mode'] ) : ?>
	<?php require CHIC_CHECKOUT_DIR . 'templates/partials/font-faces.php'; ?>
	<?php endif; ?>
	<style id="chic-boot-css">
		.chic-boot{min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.25rem;padding:2rem;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#57534e;text-align:center}
		.chic-boot__spinner{width:28px;height:28px;border:2px solid #e7e5e4;border-top-color:#1c1917;border-radius:50%;animation:chic-boot-spin .8s linear infinite}
		.chic-boot__fallback{margin:0;font-size:.9rem;opacity:0;animation:chic-boot-show .3s ease 8s forwards}
		.chic-boot__fallback a,.chic-boot__noscript a{display:inline-block;margin-top:.5rem;color:#1c1917;text-decoration:underline}
		.chic-boot__noscript{text-align:center;padding:2rem}
		@keyframes chic-boot-spin{to{transform:rotate(360deg)}}
		@keyframes chic-boot-show{to{opacity:1}}
	</style>
	<?php \ChicCheckout\Branding::generator_meta(); ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="chic-checkout-root"<?php \ChicCheckout\Branding::print_root_attribute(); ?>>
	<?php // Remplacé par React dès qu'il monte. S'il ne monte jamais (JS bloqué, retardé ou en erreur), le visiteur n'est pas bloqué sur une page blanche. ?>
	<div class="chic-boot" role="status">
		<span class="chic-boot__spinner" aria-hidden="true"></span>
		<p class="chic-boot__fallback">
			<?php esc_html_e( 'La page met du temps à s\'afficher.', 'chic-checkout' ); ?>
			<a href="<?php echo esc_url( \ChicCheckout\Compat::classic_url() ); ?>"><?php esc_html_e( 'Continuer avec la version standard', 'chic-checkout' ); ?></a>
		</p>
	</div>
</div>
<noscript>
	<p class="chic-boot__noscript"><a href="<?php echo esc_url( \ChicCheckout\Compat::classic_url() ); ?>"><?php esc_html_e( 'Continuer avec la version standard', 'chic-checkout' ); ?></a></p>
</noscript>

<?php wp_footer(); ?>
</body>
</html>
