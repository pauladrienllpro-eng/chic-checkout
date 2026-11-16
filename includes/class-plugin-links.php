<?php
/**
 * Plugins tiers compatibles (paiement, livraison, fidélité…) : état sur le site et
 * lien direct pour les installer ou les activer, affichés dans les onglets Paiement et
 * Intégrations. Trouver le bon plugin parmi les homonymes (Stripe officiel ou Payment
 * Plugins, PayPal officiel ou Payment Plugins…) était la principale difficulté à
 * l'installation.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin_Links {

	/** Slug WordPress.org => fichier principal. */
	const PLUGINS = array(
		'woo-stripe-payment'                   => 'woo-stripe-payment/stripe-payments.php',
		'pymntpl-paypal-woocommerce'           => 'pymntpl-paypal-woocommerce/pymntpl-paypal-woocommerce.php',
		'boxtal-connect'                       => 'boxtal-connect/boxtal-connect.php',
		'la-poste-pro-expeditions-woocommerce' => 'la-poste-pro-expeditions-woocommerce/la-poste-pro-expeditions-woocommerce.php',
		'mycred'                               => 'mycred/mycred.php',
		'booking-activities'                   => 'booking-activities/booking-activities.php',
	);

	/**
	 * Nom affiché sur WordPress.org (non traduit : c'est ce que le marchand cherche).
	 */
	public static function name( string $slug ): string {
		$names = array(
			'woo-stripe-payment'                   => 'Payment Plugins for Stripe WooCommerce',
			'pymntpl-paypal-woocommerce'           => 'Payment Plugins for PayPal WooCommerce',
			'boxtal-connect'                       => 'Boxtal Connect',
			'la-poste-pro-expeditions-woocommerce' => 'La Poste Pro Expéditions WooCommerce',
			'mycred'                               => 'myCred',
			'booking-activities'                   => 'Booking Activities',
		);
		return $names[ $slug ] ?? $slug;
	}

	/**
	 * @return string 'active' | 'inactive' (installé, non activé) | 'missing'
	 */
	public static function status( string $slug ): string {
		$file = self::installed_file( $slug );
		if ( '' === $file ) {
			return 'missing';
		}
		return is_plugin_active( $file ) ? 'active' : 'inactive';
	}

	/**
	 * Fichier principal du plugin s'il est installé, '' sinon. Le dossier n'est pas
	 * toujours le slug : un plugin installé depuis un zip téléchargé arrive souvent dans
	 * « woo-stripe-payment-4.0.8/ » par exemple — constaté sur un site de test, où Stripe
	 * actif s'affichait « Non installé ». Repli : recherche par nom de plugin.
	 */
	private static function installed_file( string $slug ): string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$file    = self::PLUGINS[ $slug ] ?? '';
		$plugins = get_plugins();
		if ( '' !== $file && isset( $plugins[ $file ] ) ) {
			return $file;
		}
		$found = '';
		foreach ( $plugins as $candidate => $data ) {
			if ( self::name( $slug ) === ( $data['Name'] ?? '' ) ) {
				// Plusieurs copies : la copie active l'emporte.
				if ( is_plugin_active( $candidate ) ) {
					return $candidate;
				}
				$found = $found ? $found : $candidate;
			}
		}
		return $found;
	}

	/**
	 * État + action en HTML (déjà échappé) : « Actif », bouton « Activer » ou « Installer »
	 * (installation en un clic depuis WordPress.org), et lien vers la fiche du plugin.
	 */
	public static function render( string $slug ): string {
		$status = self::status( $slug );
		$file   = self::installed_file( $slug );
		$info   = 'https://wordpress.org/plugins/' . rawurlencode( $slug ) . '/';
		$html   = '';

		if ( 'active' === $status ) {
			$html .= '<span style="color:#008a20;">● ' . esc_html__( 'Installé et activé', 'chic-checkout' ) . '</span>';
		} elseif ( 'inactive' === $status ) {
			$html .= '<span style="color:#996800;">○ ' . esc_html__( 'Installé, non activé', 'chic-checkout' ) . '</span>';
			if ( current_user_can( 'activate_plugins' ) ) {
				$url   = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ), 'activate-plugin_' . $file );
				$html .= ' <a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html__( 'Activer', 'chic-checkout' ) . '</a>';
			}
		} else {
			$html .= '<span style="color:#787c82;">○ ' . esc_html__( 'Non installé', 'chic-checkout' ) . '</span>';
			if ( current_user_can( 'install_plugins' ) ) {
				$url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $slug ) ), 'install-plugin_' . $slug );
				$html .= ' <a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html__( 'Installer', 'chic-checkout' ) . '</a>';
			}
		}

		$html .= ' <a href="' . esc_url( $info ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Fiche du plugin', 'chic-checkout' ) . '</a>';

		return $html;
	}
}
