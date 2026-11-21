/*
 * Statistiques de la mention Chic-checkout sur la page de confirmation (badge_shown,
 * badge_clicked) : envoyées à la route REST du site, jamais à un tiers, et seulement
 * avec le consentement du visiteur (WP Consent API).
 */
window.addEventListener('load', function () {
	var c = window.chicBrandingStats || {};
	var el = document.getElementById('chic-branding-confirmation');
	function allowed() {
		return typeof window.wp_has_consent === 'function' && (window.wp_has_consent('statistics-anonymous') || window.wp_has_consent('statistics'));
	}
	function send(metric) {
		if (!c.url || !el || !allowed() || !navigator.sendBeacon) { return; }
		navigator.sendBeacon(c.url, new Blob([JSON.stringify({ app: 'thankyou', browser: true, counters: [[metric, 'place', 'confirmation', 1]], histograms: [] })], { type: 'application/json' }));
	}
	send('badge_shown');
	if (el) { el.querySelector('a').addEventListener('click', function () { send('badge_clicked'); }); }
});
