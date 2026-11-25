=== Chic-checkout ===
Contributors: pauladrien
Tags: woocommerce, checkout, cart, one page checkout, conversion
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.14.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A fast, clean cart, a 3-step checkout and an order confirmation page for WooCommerce, designed for mobile.

== Description ==

Chic-checkout replaces the WooCommerce cart, checkout and order confirmation pages with a 3-step flow (Sign in, Shipping, Payment) built on the WooCommerce Store API. It follows your theme's colors and fonts and works with the payment and shipping methods you already set up in WooCommerce.

= Cart =

* Full-page, distraction-free cart with coupon field, a back link (shop, last viewed product or any address) and a custom empty cart text.
* Large product photos, editable quantities, an always visible summary.
* Recommended products: your WooCommerce cross-sells, then related products, added in one tap.
* Payment method logos under the checkout button.
* Contact button: contact page, email, WhatsApp and phone with opening hours.

= 3-step checkout =

* Sign in by email: log in, create an account or check out as a guest (following your WooCommerce settings).
* Shipping: progressive address form, Google Places address autocomplete (with your own API key), delivery times per shipping method, Boxtal and La Poste pickup points chosen on their map.
* Payment: the payment methods of your WooCommerce gateways, including Payment Plugins for Stripe (card, Apple Pay, Google Pay, Klarna, Wero, Alma), PayPal, myCred, bank transfer, check and cash on delivery.
* Numbered or plain steps, editable step and button labels, terms and conditions checkbox.

= Order confirmation =

* Clear order summary, shipping address or pickup point, estimated delivery time.

= Also included =

* Reassurance blocks (secure payment, shipping, returns…) with built-in icons, footer links and a high contrast mode.
* SEO: the checkout page is excluded from search engines, with a custom title and description.
* Google Analytics 4 checkout events, off by default and compatible with the WP Consent API.
* Statistics computed on your own site (cart and checkout completion rates), off by default.
* Polylang translation of the texts entered in the settings.
* Compatible with WooCommerce High-Performance Order Storage (HPOS). Requires WooCommerce 6.4 or later.

The plugin interface is in French.

= Source code =

The React bundle in build/ is compiled. Its readable source, and the tools to rebuild it, are in the public repository [github.com/pauladrienllpro-eng/chic-checkout](https://github.com/pauladrienllpro-eng/chic-checkout).

= Pro version =

The Pro version, sold separately, adds: Apple Pay, Google Pay and PayPal express checkout from the cart, gift options (wrapping, gift message, free gifts), a free shipping progress bar, loyalty points, Google and Apple sign-in, customer reviews, the Chic-checkout design with custom colors, the full Performance screen with store benchmarks, and priority support. [Discover the Pro version](https://pauladrien.fr/chic-checkout/#tarifs)

The free version has no order limit and no time limit.

== Installation ==

1. Install and activate WooCommerce.
2. In Plugins → Add New, search for "Chic-checkout", then install and activate it.
3. Open the Chic-checkout menu: the cart, checkout and confirmation pages are replaced right away. Turn on the integrations you need (Boxtal, La Poste, Google Places…) in the Integrations tab.

To see the standard WooCommerce checkout at any time, add ?chic-classic=1 to the cart or checkout address.

== Frequently Asked Questions ==

= Which payment methods are supported? =

Gateways that do not need their own JavaScript fields (bank transfer, check, cash on delivery, gateways that redirect to the bank), and the gateways integrated by the plugin: Payment Plugins for Stripe WooCommerce (card, Apple Pay, Google Pay, Klarna, Wero, Alma), PayPal by Payment Plugins and myCred. The Payment tab shows the status of each one.

= Does my theme keep its look? =

Yes: the cart, checkout and confirmation pages use your theme's colors and fonts (theme.json). The Chic-checkout design and custom colors are part of the Pro version.

= What if something goes wrong on the checkout? =

If the plugin's files are missing or incomplete, WooCommerce shows its standard checkout instead of a blank page: a sale is never blocked.

= What data is sent? =

None by default. Statistics are computed and kept on your site; sharing them with the developer is optional and off by default. See "External services" below.

= How do I upgrade to Pro? =

Chic-checkout menu → Upgrade. After purchase, download the Pro zip (confirmation email or Freemius customer area) and upload it in Plugins → Add New → Upload Plugin: the free version is deactivated automatically and your settings are kept.

= Where can I get help? =

On the [plugin support forum](https://wordpress.org/support/plugin/chic-checkout/).

== Screenshots ==

1. The cart on desktop, with the summary and recommended products.
2. The cart on mobile.
3. The Shipping step: address, shipping method and pickup point.
4. The Payment step with the store's payment methods.
5. The order confirmation page.
6. The plugin settings screen.

== External services ==

The plugin connects to an external service only when the matching feature is set up by the site administrator, or after their explicit consent.

* **Freemius** (plugin account and Pro version management). Only if the administrator accepts the opt-in shown after installation: administrator email, first and last name, site address, plugin version and status (active, deactivated, uninstalled); WordPress and PHP versions only if "Diagnostic info" is checked. [Terms](https://freemius.com/terms/) · [Privacy](https://freemius.com/privacy/)
* **Chic Insights** (statistics API run by the plugin developer (pauladrien.fr), at qttpbbtohzftyitewfnp.supabase.co, hosted by Supabase in the European Union). Used only if the administrator turns on "My statistics" and then "Share" (Settings → Statistics, both off by default), to compare the store's checkout with other stores and improve the plugin.
  * What is sent: once a day, the previous day's aggregated counters (carts and checkouts shown, steps reached, paid orders and their split by payment method, shipping method, country, hour and device type, use of checkout features, display errors; measures taken in the browser only for visitors who accepted statistics through the WP Consent API), with a random site identifier and a technical profile of the store: plugin, WordPress, WooCommerce and PHP versions, theme (from a fixed list, otherwise "other"), store country, currency and optional category, enabled integrations, active payment gateways and cache plugins detected. No personal data of customers (no name, email, address, IP address, typed text, product or visitor identifier) and no store address.
  * When: the daily send (WP-Cron), the benchmark shown on the Performance screen (read only), and a deletion request when sharing is turned off, which deletes the data already sent.
  * [Terms and data protection (section 13)](https://pauladrien.fr/chic-checkout/cgv/) · Hosting: [Supabase terms](https://supabase.com/terms) · [Supabase privacy](https://supabase.com/privacy)
* **Stripe** (Stripe.js, js.stripe.com), on the cart and checkout, when Payment Plugins for Stripe WooCommerce is active: card and wallet payment forms. [Terms](https://stripe.com/legal) · [Privacy](https://stripe.com/privacy)
* **PayPal** (JavaScript SDK, paypal.com), on the checkout, when the PayPal gateway by Payment Plugins is active. [Terms](https://www.paypal.com/legalhub/home) · [Privacy](https://www.paypal.com/legalhub/privacy-full)
* **Google Maps Platform** (Places API, maps.googleapis.com), on the Shipping step, only when an API key is entered and the Google Places integration is on: address autocomplete. [Terms](https://cloud.google.com/maps-platform/terms) · [Privacy](https://policies.google.com/privacy)
* **Google Analytics 4**, only when the GA4 integration is on (off by default): checkout funnel events (steps, shipping and payment chosen, purchase with order total and products) are pushed to the Google Tag Manager / gtag.js already installed on the site, only for visitors who accepted statistics through the WP Consent API. Without the WP Consent API, no event is sent. The plugin itself does not load any Google script for this. [Terms](https://marketingplatform.google.com/about/analytics/terms/us/) · [Privacy](https://policies.google.com/privacy)
* **Boxtal** and **La Poste**, on the Shipping step, when their plugins and the matching integration are active: pickup point map, loaded by the carrier's plugin.

== Changelog ==

= 1.14.1 =
* Recommended products: the number of products shown (3 to 5) can now be set in the free version.
* Compatibility: optimization plugin exclusions (WP Rocket, SiteGround Optimizer) now only apply to the cart and checkout pages; the rest of the site stays optimized.
* Security: login, registration, logout and payment methods REST routes check the REST nonce in their permission callback; the order received page no longer changes the cart itself (WooCommerce does it).

= 1.14.0 =
* Pro: account creation reassurance. Under "Create an account", show what an account brings (free shipping, loyalty points, order tracking) as a banner, a list of benefits or image tiles.
* Banners: text can now use italics and several links ([text](address)), in addition to bold.

= 1.13.1 =
* WordPress.org review: the free version no longer contains any white-label code.
* Privacy: Google Analytics 4 events are only sent to visitors who accepted statistics through the WP Consent API; nothing is sent without it. The "browser measures without consent banner" statistics option is removed.
* Security: the sign-in step of the free version no longer checks whether an email has an account; customers choose between signing in, guest checkout and account creation.
* Security: the order received page only clears the cart once the order key is verified.

= 1.13.0 =
* New Appearance setting "Field style": boxed fields (default) or underlined fields, a single line under each field.
* Sign-in step: fields use the full width; buttons are centered.
* Fix: the "Create an account" and "Continue as guest" buttons had lost their border.
* Pro: Google and Apple buttons now have the same size and come first in the sign-in step.
* Pro: Google and Apple sign-in on the WooCommerce My Account page.
* Pro: Apple sign-in from the cart uses the return URL registered with Apple (the checkout URL).

= 1.12.1 =
* WordPress.org review: the free version no longer contains any code of the Pro features; styles and scripts are loaded the WordPress way; external services documented in this readme.
* Performance screen (free version): the blurred preview of the Pro screen is removed.

= 1.12.0 =
* New Performance screen: period picker (7 days, 30 days, 90 days, 12 months or custom dates), comparison with the previous period, and an explanation bubble for every figure.
* Pro: revenue and order charts, step-by-step checkout funnel with drop-off between steps, payment methods, upsell and gift option impact on the average order value.
* Pro: the funnel can be split between physical products and digital products (no shipping step), for accurate drop-off rates.
* Local statistics are kept for 13 months (instead of 31 days) to allow the 12-month view.

= 1.11.2 =
* Plugin name: Chic-checkout.
* The Freemius SDK is now loaded from the vendor folder.

= 1.11.1 =
* Release notes in the News tab are shown in French again (the plugin interface is in French).
* Readme: link to the public source code of the React bundle.

= 1.11.0 =
* New free version on WordPress.org: cart, 3-step checkout and order confirmation page, with no order or time limit. The Pro version remains available with all its features.
* Missing or expired Pro license: the store keeps the Chic-checkout checkout with the free features (instead of the standard WooCommerce checkout); Pro settings are kept.
* The "Checkout by Chic-checkout" credit is off by default in the free version. Referral link removed.
* The plugin now uses the React library shipped with WordPress (WordPress 6.6 or later required).
* Statistics: the activation prompt only appears on Chic-checkout screens.

= 1.10.0 =
* Customer review popup on the confirmation page (Pro).
* Performance screen: customer satisfaction section (Pro).

= 1.9.0 =
* Support tab: report a bug and follow it from the admin, temporary support access (Pro).
