jQuery(function ($) {
	$('.chic-color-picker').each(function () {
		var palette = $(this).data('palette');
		$(this).wpColorPicker(palette ? { palettes: palette } : {});
	});

	$('.chic-media-select').on('click', function (event) {
		event.preventDefault();

		var $field = $(this).closest('.chic-media-field');
		var title = $(this).data('title') || (window.chicCheckoutAdmin && window.chicCheckoutAdmin.chooseMedia) || '';

		var frame = wp.media({
			title: title,
			multiple: false,
			library: { type: 'image' },
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$field.find('.chic-media-id').val(attachment.id);
			$field.find('.chic-media-preview').attr('src', attachment.url).show();
			$field.find('.chic-media-remove').show();
		});

		frame.open();
	});

	$('.chic-media-remove').on('click', function (event) {
		event.preventDefault();

		var $field = $(this).closest('.chic-media-field');
		$field.find('.chic-media-id').val('');
		$field.find('.chic-media-preview').hide();
		$(this).hide();
	});

	function toggleFooterLinkRow($row) {
		var type = $row.find('.chic-footer-link-type').val();
		$row.find('.chic-footer-link-page').toggle(type === 'page');
		$row.find('.chic-footer-link-custom').toggle(type === 'custom');
	}

	$('.chic-footer-link-row').each(function () {
		toggleFooterLinkRow($(this));
	});

	$('.chic-footer-link-type').on('change', function () {
		toggleFooterLinkRow($(this).closest('.chic-footer-link-row'));
	});

	function toggleReassuranceIconType($row) {
		var type = $row.find('.chic-reassurance-icon-type').val();
		$row.find('.chic-reassurance-icon-preset').toggle(type === 'preset');
		$row.find('.chic-reassurance-icon-custom').toggle(type === 'custom');
	}

	$('.chic-reassurance-item-row').each(function () {
		toggleReassuranceIconType($(this));
	});

	$('.chic-reassurance-icon-type').on('change', function () {
		toggleReassuranceIconType($(this).closest('.chic-reassurance-item-row'));
	});

	// Navigation : menu latéral (pages) et sous-onglets (Panier, Checkout). Les liens sont
	// de vrais liens (?chic-tab=…&chic-sub=…, ouverture dans un nouvel onglet possible) ;
	// un clic simple bascule sans rechargement, met l'adresse à jour et le retour après
	// « Enregistrer » (_wp_http_referer), pour revenir sur le même panneau.
	function showTab(tab, sub) {
		var $page = $('.chic-tab-page[data-page="' + tab + '"]');
		if (!$page.length) {
			return;
		}
		var $subtabs = $page.find('.chic-subtab');
		if ($subtabs.length && !$subtabs.filter('[data-sub="' + sub + '"]').length) {
			sub = $subtabs.first().data('sub');
		}
		if (!$subtabs.length) {
			sub = '';
		}

		$('.chic-tab-page').prop('hidden', true);
		$page.prop('hidden', false);
		$('.chic-nav-item').removeClass('is-active').removeAttr('aria-current');
		$('.chic-nav-item[data-tab="' + tab + '"]').addClass('is-active').attr('aria-current', 'page');

		if ($subtabs.length) {
			$subtabs.removeClass('nav-tab-active');
			$subtabs.filter('[data-sub="' + sub + '"]').addClass('nav-tab-active');
			$page.find('.chic-tab-panel').prop('hidden', true);
			$page.find('.chic-tab-panel[data-tab-panel="' + tab + '-' + sub + '"]').prop('hidden', false);
		}

		var url = new URL(window.location.href);
		url.searchParams.set('chic-tab', tab);
		if (sub) {
			url.searchParams.set('chic-sub', sub);
		} else {
			url.searchParams.delete('chic-sub');
		}
		url.searchParams.delete('chic-idea');
		url.searchParams.delete('chic-bug');
		url.searchParams.delete('settings-updated');
		window.history.replaceState(null, '', url.toString());
		$('input[name="_wp_http_referer"]').val(url.pathname + url.search);

		// Onglet Nouveautés ouvert : dernière version marquée comme vue, badge retiré.
		var seenUrl = window.chicCheckoutAdmin && window.chicCheckoutAdmin.releasesSeenUrl;
		if (tab === 'news' && seenUrl) {
			window.chicCheckoutAdmin.releasesSeenUrl = '';
			$('.chic-nav-item[data-tab="news"] .chic-news-badge').remove();
			$.post(seenUrl);
		}
	}

	$('.chic-nav-item, .chic-subtab').on('click', function (event) {
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1) {
			return;
		}
		event.preventDefault();
		showTab($(this).data('tab'), $(this).data('sub') || '');
	});

	// Page ouverte directement sur Nouveautés (?chic-tab=news) : badge traité de même.
	if (new URLSearchParams(window.location.search).get('chic-tab') === 'news') {
		showTab('news', '');
	}

	
	// Ordre des blocs de réassurance : la ligne entière (tous ses champs) est déplacée,
	// puis les noms reassurance_items[N] et les libellés « Bloc N » sont renumérotés
	// d'après la nouvelle position — c'est cet index qui fixe l'ordre enregistré.
	$('.chic-reassurance-items').on('click', '.chic-reassurance-item-up, .chic-reassurance-item-down', function () {
		var $list = $(this).closest('.chic-reassurance-items');
		var $row = $(this).closest('.chic-reassurance-item-row');
		var up = $(this).hasClass('chic-reassurance-item-up');
		var $sibling = up ? $row.prev('.chic-reassurance-item-row') : $row.next('.chic-reassurance-item-row');
		if (!$sibling.length) {
			return;
		}
		if (up) {
			$row.insertBefore($sibling);
		} else {
			$row.insertAfter($sibling);
		}
		$list.children('.chic-reassurance-item-row').each(function (index) {
			$(this).find('[name*="[reassurance_items]["]').each(function () {
				this.name = this.name.replace(/\[reassurance_items\]\[\d+\]/, '[reassurance_items][' + index + ']');
			});
			var $number = $(this).find('.chic-reassurance-item-number');
			$number.text($number.text().replace(/\d+/, index + 1));
		});
		$row.find(up ? '.chic-reassurance-item-up' : '.chic-reassurance-item-down').trigger('focus');
	});

	// Lien "Gérer dans l'onglet Intégrations" affiché à côté d'une intégration.
	$('.chic-goto-tab').on('click', function (event) {
		event.preventDefault();
		showTab($(this).data('tab'), $(this).data('sub') || '');
		window.scrollTo(0, 0);
	});
});
