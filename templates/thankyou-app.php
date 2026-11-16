<?php

/**
 * Page de confirmation de commande — remplace le rendu WooCommerce classique de la
 * page order-received. Contrairement à templates/checkout-app.php (SPA React,
 * document autonome sans header/footer du thème), cette page est du PHP pur : reprend
 * le header du checkout (logo + flèche retour) mais le VRAI footer du thème
 * (get_footer() en bas de ce fichier), aucune interactivité ne justifie une app React.
 *
 * @package ChicCheckout
 */
use ChicCheckout\Settings;
use ChicCheckout\Polylang_Strings;
use ChicCheckout\Thankyou_Override;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$chic_override = Thankyou_Override::instance();
$chic_order = ( $chic_override ? $chic_override->get_order() : null );
$chic_needs_login = $chic_override && $chic_override->needs_login();
$chic_settings = Settings::get();
$chic_accent = ( 'theme' === $chic_settings['style_mode'] ? 'var(--wp--preset--color--primary, ' . $chic_settings['accent_color'] . ')' : $chic_settings['accent_color'] );
$chic_bg = ( 'theme' === $chic_settings['style_mode'] ? 'var(--wp--preset--color--base, ' . $chic_settings['bg_color'] . ')' : $chic_settings['bg_color'] );
$chic_cta = $chic_settings['cta_color'];
list( $chic_logo_url, $chic_logo_text ) = \ChicCheckout\Assets::resolve_logo( $chic_settings );
$chic_back_label = Polylang_Strings::translate( $chic_settings['thankyou_back_label'] );
$chic_back_url = ( 'custom' === $chic_settings['thankyou_back_target'] && $chic_settings['thankyou_back_custom_url'] ? $chic_settings['thankyou_back_custom_url'] : wc_get_page_permalink( 'shop' ) );
?><!DOCTYPE html>
<html <?php 
language_attributes();
?>>
<head>
	<meta charset="<?php 
bloginfo( 'charset' );
?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php 
if ( 'theme' !== $chic_settings['style_mode'] ) {
    ?>
	<?php 
    require CHIC_CHECKOUT_DIR . 'templates/partials/font-faces.php';
    ?>
	<?php 
}
?>
	<?php 
\ChicCheckout\Branding::generator_meta();
?>
	<?php 
wp_head();
?>
</head>
<body <?php 
body_class();
?>>
<?php 
wp_body_open();
?>

<div id="chic-checkout-root"<?php 
\ChicCheckout\Branding::print_root_attribute();
?>>
	<div
		class="chic-checkout chic-checkout--<?php 
echo esc_attr( $chic_settings['style_mode'] );
?>"
		style="--chic-accent:<?php 
echo esc_attr( $chic_accent );
?>;--chic-bg:<?php 
echo esc_attr( $chic_bg );
?>;--chic-cta:<?php 
echo esc_attr( $chic_cta );
?>;"
	>
		<header class="chic-thankyou-header">
			<div class="chic-thankyou-header__inner">
				<a class="chic-thankyou-header__back" href="<?php 
echo esc_url( $chic_back_url );
?>">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
						<path d="m15 6-6 6 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
					<?php 
echo esc_html( $chic_back_label );
?>
				</a>

				<?php 
if ( $chic_logo_url ) {
    ?>
					<a class="chic-logo" href="<?php 
    echo esc_url( home_url( '/' ) );
    ?>"><img src="<?php 
    echo esc_url( $chic_logo_url );
    ?>" alt=""></a>
				<?php 
} else {
    ?>
					<a class="chic-logo chic-logo--text" href="<?php 
    echo esc_url( home_url( '/' ) );
    ?>"><span><?php 
    echo esc_html( $chic_logo_text );
    ?></span></a>
				<?php 
}
?>

				<span aria-hidden="true"></span>
			</div>
		</header>

		<main>
			<?php 
if ( $chic_needs_login ) {
    ?>

				<div class="chic-thankyou-shell">
					<div class="chic-thankyou-fallback">
						<h1><?php 
    esc_html_e( 'Connectez-vous pour voir cette commande', 'chic-checkout' );
    ?></h1>
						<p><?php 
    esc_html_e( 'Cette commande est associée à un compte. Connectez-vous avec ce compte pour en voir le détail.', 'chic-checkout' );
    ?></p>
						<a class="chic-thankyou-hero__cta" href="<?php 
    echo esc_url( wp_login_url( $chic_override->get_login_redirect_url() ) );
    ?>">
							<?php 
    esc_html_e( 'Se connecter', 'chic-checkout' );
    ?>
						</a>
					</div>
				</div>

			<?php 
} elseif ( $chic_override && $chic_override->needs_email_verification() ) {
    ?>

				<div class="chic-thankyou-shell">
					<div class="chic-thankyou-fallback">
						<h1><?php 
    esc_html_e( 'Confirmez votre adresse e-mail', 'chic-checkout' );
    ?></h1>
						<p><?php 
    esc_html_e( 'Pour protéger vos informations, saisissez l’adresse e-mail utilisée pour cette commande.', 'chic-checkout' );
    ?></p>
						<form method="post" class="chic-thankyou-verify">
							<?php 
    wp_nonce_field( \ChicCheckout\Thankyou_Override::VERIFY_ACTION, '_chic_nonce' );
    ?>
							<label for="chic-order-email"><?php 
    esc_html_e( 'Adresse e-mail', 'chic-checkout' );
    ?></label>
							<input id="chic-order-email" type="email" name="chic_order_email" autocomplete="email" required
								<?php 
    echo ( $chic_override->email_verification_failed() ? 'aria-invalid="true" aria-describedby="chic-order-email-error"' : '' );
    ?> />
							<?php 
    if ( $chic_override->email_verification_failed() ) {
        ?>
								<p id="chic-order-email-error" class="chic-thankyou-verify__error" role="alert"><?php 
        esc_html_e( 'Cette adresse ne correspond pas à la commande.', 'chic-checkout' );
        ?></p>
							<?php 
    }
    ?>
							<button type="submit" class="chic-thankyou-hero__cta"><?php 
    esc_html_e( 'Voir ma commande', 'chic-checkout' );
    ?></button>
						</form>
					</div>
				</div>

			<?php 
} elseif ( !$chic_order ) {
    ?>

				<div class="chic-thankyou-shell">
					<div class="chic-thankyou-fallback">
						<h1><?php 
    esc_html_e( 'Merci !', 'chic-checkout' );
    ?></h1>
						<p><?php 
    esc_html_e( 'Votre commande est en cours de traitement.', 'chic-checkout' );
    ?></p>
					</div>
				</div>

			<?php 
} else {
    $chic_first_name = $chic_order->get_billing_first_name();
    $chic_last_name = $chic_order->get_billing_last_name();
    $chic_intro = str_replace( array('{prenom}', '{nom}'), array($chic_first_name, $chic_last_name), Polylang_Strings::translate( $chic_settings['thankyou_intro_text'] ) );
    $chic_parcelpoint = $chic_override->get_parcelpoint( $chic_order );
    $chic_delivery_estimate = ( $chic_settings['thankyou_show_delivery_estimate'] ? $chic_override->get_delivery_estimate( $chic_order ) : '' );
    $chic_packaging_item = $chic_override->get_packaging_item( $chic_order );
    $chic_gift_item = $chic_override->get_gift_item( $chic_order );
    $chic_gift_message = $chic_override->get_gift_message( $chic_order );
    $chic_can_view_order = is_user_logged_in() && $chic_order->get_customer_id() && get_current_user_id() === $chic_order->get_customer_id();
    ?>

				<section class="chic-thankyou-hero">
					<div class="chic-thankyou-shell">
						<div class="chic-thankyou-hero__badge">
							<svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
						</div>
						<h1><?php 
    echo esc_html( $chic_intro );
    ?></h1>
						<p class="chic-thankyou-hero__order">
							<?php 
    printf( 
        /* translators: %s: numéro de commande */
        esc_html__( 'Commande %s', 'chic-checkout' ),
        '<strong>' . esc_html( $chic_order->get_order_number() ) . '</strong>'
     );
    ?>
						</p>
						<p class="chic-thankyou-hero__email">
							<?php 
    printf( 
        /* translators: %s: adresse email */
        esc_html__( 'Un e-mail de confirmation a été envoyé à %s.', 'chic-checkout' ),
        '<strong>' . esc_html( $chic_order->get_billing_email() ) . '</strong>'
     );
    ?>
						</p>
						<?php 
    if ( $chic_can_view_order ) {
        ?>
							<a class="chic-thankyou-hero__cta" href="<?php 
        echo esc_url( $chic_order->get_view_order_url() );
        ?>">
								<?php 
        esc_html_e( 'Voir ma commande', 'chic-checkout' );
        ?>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
							</a>
						<?php 
    }
    ?>
					</div>
				</section>

				<div class="chic-thankyou-shell">
					<div class="chic-thankyou-layout">
						<div class="chic-thankyou-layout__main">
							<div class="chic-thankyou-card chic-thankyou-card--img-<?php 
    echo esc_attr( $chic_settings['cart_image_size'] );
    ?>">
								<h2><?php 
    echo esc_html( Polylang_Strings::translate( $chic_settings['order_summary_label'] ) );
    ?></h2>

								<?php 
    foreach ( $chic_order->get_items() as $chic_item ) {
        // L'emballage et le cadeau offert ont leur propre section plus bas
        // dans cette même carte (voir chic-thankyou-gift-details) — pas
        // question de les montrer une seconde fois ici, noyés parmi les
        // vrais produits achetés.
        if ( $chic_packaging_item && $chic_item->get_id() === $chic_packaging_item->get_id() || $chic_gift_item && $chic_item->get_id() === $chic_gift_item->get_id() ) {
            continue;
        }
        $chic_product = $chic_item->get_product();
        $chic_meta = $chic_item->get_formatted_meta_data( '_', true );
        ?>
									<div class="chic-thankyou-summary-item">
										<?php 
        if ( $chic_product ) {
            ?>
											<img src="<?php 
            echo esc_url( ( wp_get_attachment_image_url( $chic_product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src( 'thumbnail' ) ) );
            ?>" alt="" />
										<?php 
        }
        ?>
										<div class="chic-thankyou-summary-item__meta">
											<p class="chic-thankyou-summary-item__name"><?php 
        echo esc_html( $chic_item->get_name() );
        ?></p>
											<?php 
        if ( $chic_meta ) {
            ?>
												<p class="chic-thankyou-summary-item__variant">
													<?php 
            echo esc_html( implode( ', ', array_map( function ( $chic_meta_entry ) {
                return wp_strip_all_tags( $chic_meta_entry->display_key ) . ': ' . wp_strip_all_tags( $chic_meta_entry->display_value );
            }, $chic_meta ) ) );
            ?>
												</p>
											<?php 
        }
        ?>
											<p class="chic-thankyou-summary-item__qty">
												<?php 
        printf( 
            /* translators: %d: quantité */
            esc_html__( 'Qté : %d', 'chic-checkout' ),
            (int) $chic_item->get_quantity()
         );
        ?>
											</p>
										</div>
										<div class="chic-thankyou-summary-item__price">
											<?php 
        echo wp_kses_post( $chic_order->get_formatted_line_subtotal( $chic_item ) );
        ?>
										</div>
									</div>
								<?php 
    }
    ?>

								<?php 
    if ( $chic_packaging_item || $chic_gift_item || null !== $chic_gift_message ) {
        ?>
									<div class="chic-thankyou-gift-details">
										<?php 
        if ( $chic_packaging_item ) {
            ?>
											<div class="chic-thankyou-gift-section">
												<p class="chic-thankyou-gift-section__title"><?php 
            esc_html_e( 'Emballage', 'chic-checkout' );
            ?></p>
												<div class="chic-thankyou-gift-section__row">
													<?php 
            $chic_packaging_product = $chic_packaging_item->get_product();
            ?>
													<?php 
            if ( $chic_packaging_product ) {
                ?>
														<img src="<?php 
                echo esc_url( ( wp_get_attachment_image_url( $chic_packaging_product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src( 'thumbnail' ) ) );
                ?>" alt="" />
													<?php 
            }
            ?>
													<span class="chic-thankyou-gift-section__name"><?php 
            echo esc_html( $chic_packaging_item->get_name() );
            ?></span>
													<span class="chic-thankyou-gift-section__price">
														<?php 
            echo ( 0.0 === (float) $chic_packaging_item->get_subtotal() ? esc_html__( 'Offert', 'chic-checkout' ) : wp_kses_post( $chic_order->get_formatted_line_subtotal( $chic_packaging_item ) ) );
            ?>
													</span>
												</div>
											</div>
										<?php 
        }
        ?>

										<?php 
        if ( $chic_gift_item ) {
            ?>
											<div class="chic-thankyou-gift-section">
												<p class="chic-thankyou-gift-section__title"><?php 
            esc_html_e( 'Cadeau offert', 'chic-checkout' );
            ?></p>
												<div class="chic-thankyou-gift-section__row">
													<?php 
            $chic_gift_product = $chic_gift_item->get_product();
            ?>
													<?php 
            if ( $chic_gift_product ) {
                ?>
														<img src="<?php 
                echo esc_url( ( wp_get_attachment_image_url( $chic_gift_product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src( 'thumbnail' ) ) );
                ?>" alt="" />
													<?php 
            }
            ?>
													<span class="chic-thankyou-gift-section__name"><?php 
            echo esc_html( $chic_gift_item->get_name() );
            ?></span>
													<span class="chic-thankyou-gift-section__price">
														<?php 
            $chic_gift_total = (float) $chic_gift_item->get_total();
            echo ( $chic_gift_total > 0 ? wp_kses_post( $chic_order->get_formatted_line_subtotal( $chic_gift_item ) ) : esc_html__( 'Offert', 'chic-checkout' ) );
            ?>
													</span>
												</div>
											</div>
										<?php 
        }
        ?>

										<?php 
        if ( null !== $chic_gift_message ) {
            ?>
											<div class="chic-thankyou-gift-section">
												<p class="chic-thankyou-gift-section__title"><?php 
            esc_html_e( 'Message cadeau', 'chic-checkout' );
            ?></p>
												<p class="chic-thankyou-gift-message">
													<?php 
            echo ( $chic_gift_message ? esc_html( '« ' . $chic_gift_message . ' »' ) : esc_html__( 'Carte vierge sélectionnée', 'chic-checkout' ) );
            ?>
												</p>
											</div>
										<?php 
        }
        ?>
									</div>
								<?php 
    }
    ?>

								<div class="chic-thankyou-totals">
									<?php 
    foreach ( $chic_order->get_items( 'coupon' ) as $chic_coupon_item ) {
        ?>
										<div class="chic-thankyou-totals__row is-discount">
											<span class="chic-thankyou-totals__code"><?php 
        echo esc_html( $chic_coupon_item->get_code() );
        ?></span>
											<span>-<?php 
        echo wp_kses_post( wc_price( $chic_coupon_item->get_discount() ) );
        ?></span>
										</div>
									<?php 
    }
    ?>

									<div class="chic-thankyou-totals__row">
										<span><?php 
    esc_html_e( 'Livraison', 'chic-checkout' );
    ?></span>
										<span>
											<?php 
    $chic_shipping_total = (float) $chic_order->get_shipping_total();
    echo ( $chic_shipping_total > 0 ? wp_kses_post( wc_price( $chic_shipping_total ) ) : esc_html( Polylang_Strings::translate( $chic_settings['free_shipping_label'] ) ) );
    ?>
										</span>
									</div>

									<div class="chic-thankyou-totals__row is-total">
										<span><?php 
    esc_html_e( 'Total', 'chic-checkout' );
    ?></span>
										<span><?php 
    echo wp_kses_post( $chic_order->get_formatted_order_total() );
    ?></span>
									</div>
								</div>
							</div>
						</div>

						<div class="chic-thankyou-layout__side">

							<div class="chic-thankyou-card">
								<h2>
									<?php 
    esc_html_e( 'Livraison', 'chic-checkout' );
    ?>
									<?php 
    if ( $chic_delivery_estimate ) {
        ?>
										<span class="chic-thankyou-card-eta">
											(<?php 
        esc_html_e( 'estimée à :', 'chic-checkout' );
        ?> <?php 
        echo esc_html( $chic_delivery_estimate );
        ?>)
										</span>
									<?php 
    }
    ?>
								</h2>

								<?php 
    if ( $chic_parcelpoint ) {
        ?>
									<div class="chic-thankyou-pickup">
										<div class="chic-thankyou-pickup__icon">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1-5h16l1 5"/><path d="M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M9 21v-6h6v6"/></svg>
										</div>
										<div class="chic-thankyou-pickup__body">
											<p class="chic-thankyou-pickup__name"><?php 
        echo esc_html( $chic_parcelpoint['name'] );
        ?></p>
											<p class="chic-thankyou-pickup__address">
												<?php 
        echo esc_html( trim( $chic_parcelpoint['address'] . ', ' . $chic_parcelpoint['postcode'] . ' ' . $chic_parcelpoint['city'], ', ' ) );
        ?>
											</p>
											<?php 
        if ( $chic_parcelpoint['hours'] ) {
            ?>
												<ul class="chic-thankyou-pickup__hours">
													<?php 
            foreach ( $chic_parcelpoint['hours'] as $chic_day ) {
                ?>
														<?php 
                // Une plage horaire ne se coupe jamais ; la suivante passe dessous si la place manque (séparateur lu par les lecteurs d'écran seulement).
                ?>
														<li><span><?php 
                echo esc_html( $chic_day['label'] );
                ?></span><span class="chic-thankyou-pickup__periods"><?php 
                echo wp_kses_post( implode( '<span class="screen-reader-text">, </span>', array_map( static fn( $period ) => '<span class="chic-thankyou-pickup__period">' . esc_html( $period ) . '</span>', explode( ' · ', $chic_day['value'] ) ) ) );
                ?></span></li>
													<?php 
            }
            ?>
												</ul>
											<?php 
        }
        ?>
										</div>
									</div>
								<?php 
    } else {
        ?>
									<div class="chic-thankyou-address">
										<strong><?php 
        echo esc_html( $chic_order->get_shipping_first_name() . ' ' . $chic_order->get_shipping_last_name() );
        ?></strong>
										<span>
											<?php 
        echo esc_html( implode( ', ', array_filter( array($chic_order->get_shipping_address_1(), $chic_order->get_shipping_address_2()) ) ) );
        ?>
										</span>
										<span>
											<?php 
        echo esc_html( $chic_order->get_shipping_postcode() . ', ' . $chic_order->get_shipping_city() . ', ' . $chic_order->get_shipping_country() );
        ?>
										</span>
										<?php 
        if ( $chic_order->get_billing_phone() ) {
            ?>
											<span><?php 
            echo esc_html( $chic_order->get_billing_phone() );
            ?></span>
										<?php 
        }
        ?>
									</div>
								<?php 
    }
    ?>
							</div>

							<div class="chic-thankyou-card">
								<h2><?php 
    esc_html_e( 'Facturation', 'chic-checkout' );
    ?></h2>
								<div class="chic-thankyou-address">
									<strong><?php 
    echo esc_html( $chic_order->get_billing_first_name() . ' ' . $chic_order->get_billing_last_name() );
    ?></strong>
									<span>
										<?php 
    echo esc_html( implode( ', ', array_filter( array($chic_order->get_billing_address_1(), $chic_order->get_billing_address_2()) ) ) );
    ?>
									</span>
									<span>
										<?php 
    echo esc_html( $chic_order->get_billing_postcode() . ', ' . $chic_order->get_billing_city() . ', ' . $chic_order->get_billing_country() );
    ?>
									</span>
									<?php 
    if ( $chic_order->get_billing_phone() ) {
        ?>
										<span><?php 
        echo esc_html( $chic_order->get_billing_phone() );
        ?></span>
									<?php 
    }
    ?>
								</div>
							</div>

							<div class="chic-thankyou-card">
								<h2><?php 
    esc_html_e( 'Moyen de paiement', 'chic-checkout' );
    ?></h2>
								<?php 
    $chic_payment_method = $chic_order->get_payment_method();
    $chic_payment_title = wp_strip_all_tags( (string) $chic_order->get_payment_method_title() );
    ?>
								<div class="chic-thankyou-payment-row">
									<?php 
    if ( 'stripe_applepay' === $chic_payment_method ) {
        ?>
										<div class="chic-thankyou-payment-row__icon is-applepay">
											<svg viewBox="0 0 24 24" fill="#fff"><path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.09997 22C7.78997 22.05 6.79997 20.68 5.95997 19.47C4.24997 17 2.93997 12.45 4.69997 9.39C5.56997 7.87 7.12997 6.91 8.81997 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.09 16.67C20.06 16.74 19.67 18.11 18.71 19.5ZM13 3.5C13.73 2.67 14.94 2.04 15.94 2C16.07 3.17 15.6 4.35 14.9 5.19C14.21 6.04 13.07 6.7 11.95 6.61C11.8 5.46 12.36 4.26 13 3.5Z"/></svg>
											<span class="chic-thankyou-payment-row__icon-word">Pay</span>
										</div>
									<?php 
    } elseif ( 'ppcp' === $chic_payment_method ) {
        ?>
										<div class="chic-thankyou-payment-row__icon is-paypal">
											<svg viewBox="-3.5 0 48 48" fill="#022B87"><path d="M838.91167,663.619443 C836.67088,661.085983 832.621734,660 827.440097,660 L812.404732,660 C811.344818,660 810.443663,660.764988 810.277343,661.801472 L804.016136,701.193856 C803.892151,701.970844 804.498465,702.674333 805.292267,702.674333 L814.574458,702.674333 L816.905967,688.004562 L816.833391,688.463555 C816.999712,687.427071 817.894818,686.662083 818.95322,686.662083 L823.363735,686.662083 C832.030541,686.662083 838.814901,683.170138 840.797138,673.069296 C840.856106,672.7693 840.951363,672.194809 840.951363,672.194809 C841.513828,668.456868 840.946827,665.920407 838.91167,663.619443 Z M843.301017,674.10803 C841.144899,684.052874 834.27133,689.316292 823.363735,689.316292 L819.408334,689.316292 L816.458414,708 L822.873846,708 C823.800704,708 824.588458,707.33101 824.733611,706.423525 L824.809211,706.027531 L826.284927,696.754676 L826.380183,696.243184 C826.523823,695.335698 827.313089,694.666708 828.238435,694.666708 L829.410238,694.666708 C836.989913,694.666708 842.92604,691.611256 844.660308,682.776394 C845.35583,679.23045 845.021677,676.257496 843.301017,674.10803 Z" transform="translate(-804, -660)"/></svg>
										</div>
									<?php 
    } else {
        ?>
										<div class="chic-thankyou-payment-row__icon">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
										</div>
									<?php 
    }
    ?>
									<div class="chic-thankyou-payment-row__text">
										<p class="chic-thankyou-payment-row__label"><?php 
    echo esc_html( $chic_payment_title );
    ?></p>
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>

				<?php 
    ?>

			<?php 
}
?>
			<?php 
\ChicCheckout\Branding::render_confirmation();
?>
		</main>
	</div>
</div>

<?php 
get_footer();