(function ($) {
	'use strict';

	var $linkedBox = $('#mmi_lda_linked_products');
	var $search = $('#mmi-lda-product-search');
	var $list = $('#mmi-lda-selected-list');

	if (!$linkedBox.length || !$search.length || !$list.length || typeof $.fn.select2 === 'undefined') {
		return;
	}

	var $ampBox = $('#side-sortables .postbox').filter(function () {
		return /show amp/i.test($(this).find('.hndle, h2').text());
	}).first();
	if ($ampBox.length) {
		$linkedBox.insertAfter($ampBox);
	}

	$linkedBox.on('click', '.mmi-lda-tab', function () {
		var tab = $(this).data('tab');
		$linkedBox.find('.mmi-lda-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$linkedBox.find('.mmi-lda-panel').each(function () {
			var $panel = $(this);
			var active = $panel.data('panel') === tab;
			$panel.toggleClass('is-active', active);
			$panel.prop('hidden', !active);
		});
	});

	function selectedIds() {
		var ids = [];
		$list.find('input[name="mmi_lda_product_ids[]"]').each(function () {
			ids.push(String($(this).val()));
		});
		return ids;
	}

	function addToList(item) {
		var id = String(item.id);
		if (selectedIds().indexOf(id) !== -1) {
			return false;
		}

		var $li = $('<li></li>').attr('data-id', id);
		$li.append('<span class="mmi-lda-grip" aria-hidden="true">⋮⋮</span>');
		$li.append($('<span class="mmi-lda-selected-label"></span>').text(item.label));
		$li.append(
			$('<button type="button" class="button-link mmi-lda-remove" aria-label="Remove">&times;</button>')
		);
		$li.append($('<input type="hidden" name="mmi_lda_product_ids[]">').val(id));
		$list.append($li);
		return true;
	}

	function canSearch(term) {
		term = $.trim(term || '');
		return term.length >= 2 || /^\d+$/.test(term);
	}

	var lastRequest = null;

	$search.select2({
		width: '100%',
		placeholder: MmiLdaAdmin.i18n.placeholder,
		allowClear: true,
		minimumInputLength: 0,
		dropdownParent: $linkedBox,
		language: {
			inputTooShort: function () {
				return MmiLdaAdmin.i18n.inputTooShort;
			},
			noResults: function () {
				return MmiLdaAdmin.i18n.noResults;
			},
			searching: function () {
				return MmiLdaAdmin.i18n.searching;
			}
		},
		ajax: {
			url: MmiLdaAdmin.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			delay: 250,
			cache: false,
			data: function (params) {
				return {
					action: 'mmi_lda_search_products',
					nonce: MmiLdaAdmin.nonce,
					term: params.term || ''
				};
			},
			transport: function (params, success, failure) {
				var term = $.trim(params.data.term || '');
				if (!canSearch(term)) {
					success({ success: true, data: { items: [] } });
					return null;
				}
				if (lastRequest) {
					lastRequest.abort();
				}
				lastRequest = $.ajax(params);
				lastRequest.then(success);
				lastRequest.fail(failure);
				return lastRequest;
			},
			processResults: function (response) {
				if (!response || !response.success || !response.data || !response.data.items) {
					return { results: [] };
				}
				var selected = selectedIds();
				return {
					results: response.data.items
						.filter(function (item) {
							return selected.indexOf(String(item.id)) === -1;
						})
						.map(function (item) {
							return { id: String(item.id), text: item.label };
						})
				};
			}
		}
	});

	$search.on('select2:select', function (e) {
		var data = e.params.data;
		addToList({ id: data.id, label: data.text });
		$search.val(null).trigger('change');
	});

	$list.sortable({
		handle: '.mmi-lda-grip',
		axis: 'y',
		tolerance: 'pointer'
	});

	$list.on('click', '.mmi-lda-remove', function () {
		$(this).closest('li').remove();
	});

	var $brandRow = $('#mmi-lda-brand-row');
	if ($brandRow.length && MmiLdaAdmin.brands && MmiLdaAdmin.brands.length) {
		MmiLdaAdmin.brands.forEach(function (brand) {
			var $btn = $('<button type="button" class="button mmi-lda-brand-btn"></button>')
				.text(brand.name)
				.attr('title', brand.count + ' products');
			$btn.on('click', function () {
				$search.select2('open');
				var $field = $('.select2-container--open .select2-search__field');
				$field.val(brand.name).trigger('input');
			});
			$brandRow.append($btn);
		});
	}

	$('#mmi-lda-bulk-add').on('click', function () {
		var raw = $('#mmi-lda-bulk-ids').val();
		if (!raw) {
			return;
		}
		$.post(MmiLdaAdmin.ajaxUrl, {
			action: 'mmi_lda_resolve_products',
			nonce: MmiLdaAdmin.nonce,
			ids: raw
		}).done(function (response) {
			if (!response || !response.success) {
				return;
			}
			(response.data.items || []).forEach(function (item) {
				addToList(item);
			});
			$('#mmi-lda-bulk-ids').val('');
			$linkedBox.find('.mmi-lda-tab[data-tab="search"]').trigger('click');
		});
	});

	$('#mmi-lda-clear-all').on('click', function () {
		$list.empty();
	});
})(jQuery);
