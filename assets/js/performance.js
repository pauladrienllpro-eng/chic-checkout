/**
 * Écran Performances : bulles « i », onglets et curseur des courbes, animations à
 * l'apparition. Sans ce script, la page reste entièrement lisible (bulles au survol et
 * au focus en CSS, première courbe affichée, aucune animation).
 */
(function () {
	'use strict';

	var root = document.querySelector('.chic-perf');
	if (!root) {
		return;
	}
	root.classList.add('chic-perf--js');

	/* Animations : déclenchées quand l'élément entre à l'écran. */
	var animated = root.querySelectorAll('.chic-card, .chic-panel');
	if ('IntersectionObserver' in window) {
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.15 });
		animated.forEach(function (el) { observer.observe(el); });
	} else {
		animated.forEach(function (el) { el.classList.add('is-visible'); });
	}

	/* Bulles « i » : ouverture au toucher ou au clic, fermeture par Échap ou clic ailleurs. */
	function place(info) {
		var tip = info.querySelector('.chic-info__tip');
		info.style.removeProperty('--shift');
		info.classList.remove('is-below');
		var rect = tip.getBoundingClientRect();
		var margin = 12;
		var shift = 0;
		if (rect.left < margin) {
			shift = margin - rect.left;
		} else if (rect.right > window.innerWidth - margin) {
			shift = window.innerWidth - margin - rect.right;
		}
		tip.style.setProperty('--shift', shift + 'px');
		info.style.setProperty('--shift', shift + 'px');
		if (rect.top < 40) {
			info.classList.add('is-below');
		}
	}

	function closeAll(except) {
		root.querySelectorAll('.chic-info.is-open').forEach(function (info) {
			if (info !== except) {
				info.classList.remove('is-open');
				info.querySelector('.chic-info__btn').setAttribute('aria-expanded', 'false');
			}
		});
	}

	root.querySelectorAll('.chic-info').forEach(function (info) {
		var btn = info.querySelector('.chic-info__btn');
		info.addEventListener('mouseenter', function () {
			info.classList.remove('is-dismissed');
			place(info);
		});
		btn.addEventListener('focus', function () {
			info.classList.remove('is-dismissed');
			place(info);
		});
		btn.addEventListener('click', function (event) {
			event.stopPropagation();
			var open = !info.classList.contains('is-open');
			closeAll(info);
			info.classList.toggle('is-open', open);
			info.classList.toggle('is-dismissed', !open);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				place(info);
			}
		});
		info.addEventListener('mouseleave', function () {
			info.classList.remove('is-dismissed');
		});
	});

	document.addEventListener('click', function () { closeAll(null); });
	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}
		root.querySelectorAll('.chic-info').forEach(function (info) {
			if (info.classList.contains('is-open') || info.matches(':hover') || info.contains(document.activeElement)) {
				info.classList.add('is-dismissed');
			}
		});
		closeAll(null);
	});

	/* Onglets des courbes (flèches gauche / droite, comme un tablist). */
	root.querySelectorAll('[data-chic-chart]').forEach(function (chart) {
		var tabs = Array.prototype.slice.call(chart.querySelectorAll('[role="tab"]'));
		function select(tab) {
			tabs.forEach(function (other) {
				var selected = other === tab;
				other.setAttribute('aria-selected', selected ? 'true' : 'false');
				other.tabIndex = selected ? 0 : -1;
				document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
			});
		}
		tabs.forEach(function (tab, index) {
			tab.addEventListener('click', function () { select(tab); });
			tab.addEventListener('keydown', function (event) {
				var next = null;
				if (event.key === 'ArrowRight') {
					next = tabs[(index + 1) % tabs.length];
				} else if (event.key === 'ArrowLeft') {
					next = tabs[(index - 1 + tabs.length) % tabs.length];
				}
				if (next) {
					event.preventDefault();
					select(next);
					next.focus();
				}
			});
		});
	});

	/* Curseur des courbes : point le plus proche, date et valeur. */
	root.querySelectorAll('.chic-curve__plot').forEach(function (plot) {
		var points;
		try {
			points = JSON.parse(plot.getAttribute('data-points') || '[]');
		} catch (e) {
			points = [];
		}
		if (!points.length) {
			return;
		}
		var cursor = plot.querySelector('.chic-curve__cursor');
		var dot = plot.querySelector('.chic-curve__dot');
		var tip = plot.querySelector('.chic-curve__tip');

		function show(clientX) {
			var rect = plot.getBoundingClientRect();
			var ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width)) * 100;
			var best = points[0];
			points.forEach(function (point) {
				if (Math.abs(point.x - ratio) < Math.abs(best.x - ratio)) {
					best = point;
				}
			});
			cursor.hidden = false;
			tip.hidden = false;
			cursor.style.left = best.x + '%';
			dot.style.top = best.y + '%';
			tip.textContent = '';
			var value = document.createElement('strong');
			value.textContent = best.v;
			tip.appendChild(value);
			tip.appendChild(document.createTextNode(best.d));
			var left = (best.x / 100) * rect.width;
			var width = tip.offsetWidth;
			left = Math.min(Math.max(left - width / 2, 0), rect.width - width);
			tip.style.left = left + 'px';
			tip.style.top = Math.max(0, (best.y / 100) * rect.height - tip.offsetHeight - 14) + 'px';
		}
		function hide() {
			cursor.hidden = true;
			tip.hidden = true;
		}
		plot.addEventListener('pointermove', function (event) { show(event.clientX); });
		plot.addEventListener('pointerdown', function (event) { show(event.clientX); });
		plot.addEventListener('pointerleave', hide);
	});
})();
