<?php
/**
 * DM Sans auto-hébergée (assets/fonts/), partagée par les templates panier, checkout
 * et confirmation. Jamais chargée depuis Google Fonts : ça transmettait l'IP du
 * visiteur à Google (problème RGPD). Déclarée ici plutôt que dans le CSS du bundle :
 * Vite y réécrivait l'url() en /assets/... à la racine du domaine, ce qui donnait un 404.
 *
 * Fichiers variables 400-600 : couvrent toutes les graisses utilisées par le plugin.
 *
 * @package ChicCheckout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$chic_font_latin     = CHIC_CHECKOUT_URL . 'assets/fonts/dm-sans-latin.woff2';
$chic_font_latin_ext = CHIC_CHECKOUT_URL . 'assets/fonts/dm-sans-latin-ext.woff2';
?>
	<link rel="preload" href="<?php echo esc_url( $chic_font_latin ); ?>" as="font" type="font/woff2" crossorigin>
	<style id="chic-font-faces">
		@font-face{font-family:'DM Sans';font-style:normal;font-weight:400 600;font-display:swap;src:url('<?php echo esc_url( $chic_font_latin ); ?>') format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
		@font-face{font-family:'DM Sans';font-style:normal;font-weight:400 600;font-display:swap;src:url('<?php echo esc_url( $chic_font_latin_ext ); ?>') format('woff2');unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF}
	</style>
