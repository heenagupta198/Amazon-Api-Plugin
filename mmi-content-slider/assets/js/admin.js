(function ($) {
	'use strict';

	var $linkedBox = $('#mmi_csl_linked_products');
	var $search = $('#mmi-csl-product-search');
	var $list = $('#mmi-csl-selected-list');

	if (!$linkedBox.length || !$search.length || !$list.length || typeof $.fn.select2 === 'undefined') {
		return;
	}

	var $ampBox = $('#side-sortables .postbox').filter(function () {
		return /show amp/i.test($(this).find('.hndle, h2').text());
	}).first();
	if ($ampBox.length) {
		$linkedBox.insertAfter($ampBox);
	}

	$linkedBox.on('click', '.mmi-csl-tab', function () {
		var tab = $(this).data('tab');
		$linkedBox.find('.mmi-csl-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$linkedBox.find('.mmi-csl-panel').each(function () {
			var $panel = $(this);
			var active = $panel.data('panel') === tab;
			$panel.toggleClass('is-active', active);
			$panel.prop('hidden', !active);
		});
	});

	function selectedIds() {
		var ids = [];
		$list.find('input[name="mmi_csl_product_ids[]"]').each(function () {
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
		$li.append('<span class="mmi-csl-grip" aria-hidden="true">⋮⋮</span>');
		$li.append($('<span class="mmi-csl-selected-label"></span>').text(item.label));
		$li.append(
			$('<button type="button" class="button-link mmi-csl-remove" aria-label="Remove">&times;</button>')
		);
		$li.append($('<input type="hidden" name="mmi_csl_product_ids[]">').val(id));
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
		placeholder: MmiCslAdmin.i18n.placeholder,
		allowClear: true,
		minimumInputLength: 0,
		dropdownParent: $linkedBox,
		language: {
			inputTooShort: function () {
				return MmiCslAdmin.i18n.inputTooShort;
			},
			noResults: function () {
				return MmiCslAdmin.i18n.noResults;
			},
			searching: function () {
				return MmiCslAdmin.i18n.searching;
			}
		},
		ajax: {
			url: MmiCslAdmin.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			delay: 250,
			cache: false,
			data: function (params) {
				return {
					action: 'mmi_csl_search_products',
					nonce: MmiCslAdmin.nonce,
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
		handle: '.mmi-csl-grip',
		axis: 'y',
		tolerance: 'pointer'
	});

	$list.on('click', '.mmi-csl-remove', function () {
		$(this).closest('li').remove();
	});

	var $brandRow = $('#mmi-csl-brand-row');
	if ($brandRow.length && MmiCslAdmin.brands && MmiCslAdmin.brands.length) {
		MmiCslAdmin.brands.forEach(function (brand) {
			var $btn = $('<button type="button" class="button mmi-csl-brand-btn"></button>')
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

	$('#mmi-csl-bulk-add').on('click', function () {
		var raw = $('#mmi-csl-bulk-ids').val();
		if (!raw) {
			return;
		}
		$.post(MmiCslAdmin.ajaxUrl, {
			action: 'mmi_csl_resolve_products',
			nonce: MmiCslAdmin.nonce,
			ids: raw
		}).done(function (response) {
			if (!response || !response.success) {
				return;
			}
			(response.data.items || []).forEach(function (item) {
				addToList(item);
			});
			$('#mmi-csl-bulk-ids').val('');
			$linkedBox.find('.mmi-csl-tab[data-tab="search"]').trigger('click');
		});
	});

	$('#mmi-csl-clear-all').on('click', function () {
		$list.empty();
	});
})(jQuery);
