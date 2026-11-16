# Chic-checkout (free version)

Source code of the free version of [Chic-checkout](https://wordpress.org/plugins/chic-checkout/), published on WordPress.org: a cart, a 3-step checkout and an order confirmation page for WooCommerce, built on the WooCommerce Store API.

This repository mirrors the plugin as distributed on WordPress.org, plus the source of its React bundle.

## Contents

- `chic-checkout.php`, `includes/`, `templates/`, `assets/`, `languages/`: the plugin, as installed.
- `build/`: the compiled React bundle loaded by the plugin.
- `src/`: the source of that bundle (readable ES modules and CSS), from which `build/` is generated.
- `vendor/freemius/`: the [Freemius WordPress SDK](https://github.com/Freemius/wordpress-sdk), used for the opt-in and the optional Pro upgrade.

## Building the bundle

Requires Node.js 20 or later.

```
npm install
npm run build
```

This rebuilds `build/` from `src/`. React is not bundled: the plugin uses the React library shipped with WordPress (6.6 or later).

## Support and license

- Support: [plugin support forum on WordPress.org](https://wordpress.org/support/plugin/chic-checkout/).
- License: GPLv2 or later.
