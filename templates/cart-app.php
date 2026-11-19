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
